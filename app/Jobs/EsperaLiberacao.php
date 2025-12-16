<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Support\QueueStructure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use App\Models\Project;
use App\Services\ProjectService;
use App\Http\Controllers\Projects\ProjectsController;

class EsperaLiberacao implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected string $lockKey = 'queue_projects_lock';
    protected int $lockSeconds = 30;

    /**
     * Ordem de prioridade das filas
     */
    protected array $priority = [
        'Administrador',
        'Professor',
        'Aluno',
        'Usuário',
    ];

    public function handle(): void
    {
        while (true) {

            $lock = Cache::lock($this->lockKey, $this->lockSeconds);

            if (!$lock->get()) {
                sleep(1);
                continue;
            }

            try {
                $fila = Cache::get('queue_projects', QueueStructure::empty());

                $next = $this->pullNext($fila);

                if ($next === null) {
                    Cache::put('queue_projects', $fila);
                    break;
                }

                $item = $next['item'];
                $source = $next['source'];

                // move para running
                $fila['running'][] = $item;

                Cache::put('queue_projects', $fila);

                Log::info("[EsperaLiberacao] {$item['external_id']} saiu da fila {$source} → running");

            } finally {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                }
            }

            /**
             * Processamento fora do lock
             */
            try {
                Project::where('external_id', $item['external_id'])
                    ->update(['status' => 'QUEUE']);

                $this->runQuantumCircuit($item);

                Project::where('external_id', $item['external_id'])
                    ->update(['status' => 'FINISHED']);

                Log::info("[EsperaLiberacao] processamento finalizado: {$item['external_id']}");

            } catch (\Throwable $e) {

                Project::where('external_id', $item['external_id'])
                    ->update(['status' => 'ERROR']);

                Log::error(
                    "[EsperaLiberacao] erro ao processar {$item['external_id']}: {$e->getMessage()}"
                );
            }

            /**
             * Remove de running
             */
            $lock = Cache::lock($this->lockKey, $this->lockSeconds);

            if ($lock->get()) {
                try {
                    $fila = Cache::get('queue_projects', QueueStructure::empty());

                    $fila['running'] = array_values(array_filter(
                        $fila['running'],
                        fn($r) => $r['external_id'] !== $item['external_id']
                    ));

                    Cache::put('queue_projects', $fila);

                    Log::info("[EsperaLiberacao] removido de running: {$item['external_id']}");

                } finally {
                    try {
                        $lock->release();
                    } catch (\Throwable $e) {
                    }
                }
            }

            sleep(1);
        }
    }

    /**
     * Pega o próximo item respeitando prioridade
     */
    private function pullNext(array &$fila): ?array
    {
        foreach ($this->priority as $role) {
            if (!empty($fila[$role])) {
                return [
                    'item' => array_shift($fila[$role]),
                    'source' => $role,
                ];
            }
        }

        return null;
    }

    public function runQuantumCircuit($project)
    {
        $python = '/opt/venv/bin/python';
        $script = base_path('app/Http/Controllers/Projects/runner.py');

        $projectService = new ProjectService();
        $request = $projectService->getProject($project['external_id']);

        $process = new Process([$python, $script], dirname($script));
        $process->setInput($request->qobject);
        $process->setWorkingDirectory(dirname($script));
        $process->run();

        if (!$process->isSuccessful() && $request) {
            Log::error('[EsperaLiberacao] Process error: ' . $process->getErrorOutput());
            $request->status = 'ERROR';
            $request->qobject_result = $process->getErrorOutput();
        } else if ($request) {
            Log::info('[EsperaLiberacao] Process output: ' . $process->getOutput());
            $request->status = 'FINISHED';
            $request->qobject_result = $process->getOutput();
        }

        $request->save();
    }
}
