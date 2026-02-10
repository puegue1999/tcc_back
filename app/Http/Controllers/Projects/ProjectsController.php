<?php

namespace App\Http\Controllers\Projects;

use App\Models\Project;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;
use App\Jobs\EsperaLiberacao;
use Illuminate\Support\Facades\Log;

class ProjectsController extends Controller
{
    //
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $payload = $request->json()->all();
        $project = new Project();

        $projectService = new ProjectService();
        $projectService->generateExtId($project);
        $project->status = 'QUEUE';
        $project->qobject = json_encode($payload);

        $project->save();

        $user = auth('api')->user();
        $user->projects()->attach($project->id);

        $activeRole = $user->activeRole();

        $queue = Cache::get('queue_projects', [
            'Administrador' => [],
            'Professor' => [],
            'Aluno' => [],
            'Usuário' => [],
            'running' => []
        ]);

        $queue[$activeRole->name][] = [
            'id' => $project->id,
            'external_id' => $project->external_id,
        ];

        Cache::put('queue_projects', $queue);

        EsperaLiberacao::dispatch();

        return response()->json([
            'message' => 'QObject enfileirado com sucesso',
            'id' => $project->external_id
        ], 200);
    }

    /**
     * Show the form for list all resources.
     */
    public function listAllProjectByUser()
    {
        $user = auth('api')->user();
        $pageProject = $user->projects()
            ->orderBy('created_at', 'desc')
            ->paginate(6);

        return response()->json([
            'data' => $pageProject->items(),
            'meta' => [
                'current_page' => $pageProject->currentPage(),
                'last_page' => $pageProject->lastPage(),
                'per_page' => $pageProject->perPage(),
            ]
        ], 200);
    }

    /**
     * Show the form for list all resources.
     */
    public function getProject(Project $project)
    {
        $projectService = new ProjectService();
        $time = $projectService->getTime($project->external_id);
        return response()->json([
            'projeto' => $project,
            'tempo' => $time
        ], 200);
    }

    public function updateProject(Project $project, Request $request)
    {
        $payload = $request->json()->all();
        $projectService = new ProjectService();
        $projectService->saveOutput($project->external_id, $payload);
        $project->save();

        return response()->json(
            [
                'message' => 'QObject salvo com sucesso',
                'id' => $project->external_id
            ]
        );
    }

    public function runQuantumCircuit(Request $request)
    {
        $queue_projects = Cache::get('queue_projects', []);

        return response()->json($queue_projects);

        $qobj = $request->json()->all();

        $python = '/opt/qvenv/bin/python';
        $script = base_path('app/Http/Controllers/Projects/runner.py');

        $process = new Process([$python, $script], dirname($script));
        $process->setInput(json_encode($qobj));
        $process->setWorkingDirectory(dirname($script));
        $process->run();

        return response()->json(json_decode($process->getOutput()));
    }

    public function acessQiskit(Request $request)
    {
        $qobj = $request->json()->all();
        $response = Http::asForm()->post('https://iam.cloud.ibm.com/identity/token', [
            'grant_type' => 'urn:ibm:params:oauth:grant-type:apikey',
            'apikey' => 'oc4r-v-nkaPw_fZcOIJ0d54FAIozYT22wDia1wvroSKC',
        ]);

        $token = $response->json()['access_token'];

        $crn = 'crn:v1:bluemix:public:quantum-computing:us-east:a/70eaf13e2ab64dcda72db561d81fde72:a49cc80a-0826-41dd-a4df-17bb39e802cc::';

        $backendList = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Service-CRN' => $crn,
            'Accept' => 'application/json',
            'IBM-API-Version' => '2025-05-01',
        ])->get('https://quantum.cloud.ibm.com/api/v1/backends')->json();


        $jobResp = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Service-CRN' => $crn,
            'accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->post('https://quantum.cloud.ibm.com/api/v1/jobs', [
                    'program_id' => 'estimator',
                    'backend' => 'ibm_torino',
                    'params' => [
                        'pubs' => [
                            [
                                json_encode($qobj)
                            ]
                        ],
                        'options' => ['dynamical_decoupling' => ['enable' => True]],
                        'version' => 2,
                        'resilience_level' => 1
                    ]
                ])->throw();

        $jobId = $jobResp->json('id');

        do {
            sleep(5);
            $status = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Service-CRN' => $crn,
                'Accept' => 'application/json',
            ])->get("https://quantum.cloud.ibm.com/api/v1/jobs/{$jobId}")
                ->throw()
                ->json();
            dd($status);
        } while (empty($status['status']));

        $result = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Service-CRN' => $crn,
            'Accept' => 'application/json',
        ])->get("https://quantum.cloud.ibm.com/api/v1/jobs/{$jobId}/results")
            ->throw()
            ->json();

        return response()->json([
            'message' => $result,
        ], 200);
    }
}
