<?php

namespace App\Http\Controllers\Projects;

use App\Models\Project;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Cache;

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

        $queue_projects = Cache::get('queue_projects', []);
        $queue_projects[] = $project;
        Cache::put('queue_projects', $queue_projects);

        return response()->json([
            'message' => 'QObject enfileirado com sucesso',
            'id' => $project->external_id,
            'fila' => array_column($queue_projects, 'external_id'),
        ], 200);
    }
}
