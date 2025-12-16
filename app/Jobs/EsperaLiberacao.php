<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
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

    // tempo do lock em segundos — ajuste conforme o tempo máximo esperado para processar
    protected int $lockSeconds = 120;
    protected string $lockKey = 'queue_projects_lock';

    public function handle(): void
    {
        while (true) {
            $lock = Cache::lock($this->lockKey, $this->lockSeconds);

            if (!$lock->get()) {
                sleep(1);
                continue;
            }

            try {
                $fila = Cache::get('queue_projects', [
                    'Administrador' => [],
                    'Professor' => [],
                    'Aluno' => [],
                    'running' => []
                ]);

                $item = null;
                $source = null;

                if (!empty($fila['Administrador'])) {
                    $item = array_shift($fila['Administrador']);
                    $source = 'Administrador';
                } elseif (!empty($fila['Professor'])) {
                    $item = array_shift($fila['Professor']);
                    $source = 'Professor';
                } elseif (!empty($fila['Aluno'])) {
                    $item = array_shift($fila['Aluno']);
                    $source = 'Aluno';
                }

                if ($item === null) { // nada pra processar
                    // reindex e salva (opcional)
                    $fila['Administrador'] = array_values($fila['Administrador']);
                    $fila['Professor'] = array_values($fila['Professor']);
                    $fila['Aluno'] = array_values($fila['Aluno']);
                    $fila['running'] = array_values($fila['running']);
                    Cache::put('queue_projects', $fila);

                    $lock->release();
                    break; // sai do while principal
                }

                // move item para running e salva (a operação é atômica enquanto temos o lock)
                $fila['running'][] = $item;
                $fila['Administrador'] = array_values($fila['Administrador']);
                $fila['Professor'] = array_values($fila['Professor']);
                $fila['Aluno'] = array_values($fila['Aluno']);
                $fila['running'] = array_values($fila['running']);
                Cache::put('queue_projects', $fila);

                Log::info("[EsperaLiberacao] pegou item {$this->getExternalId($item)} da fila {$source} e colocou em running.");

            } finally {
                if (isset($lock)) {
                    try {
                        $lock->release();
                    } catch (\Throwable $e) {
                    }
                }
            }

            try {
                $this->runQuantumCircuit($item);

                Log::info("[EsperaLiberacao] processamento finalizado: {$this->getExternalId($item)}");
            } catch (\Throwable $e) {
                Log::error("[EsperaLiberacao] Erro ao processar {$this->getExternalId($item)}: " . $e->getMessage());
            }

            $lock = Cache::lock($this->lockKey, $this->lockSeconds);
            if ($lock->get()) {
                try {
                    $fila = Cache::get('queue_projects', [
                        'Administrador' => [],
                        'Professor' => [],
                        'Aluno' => [],
                        'running' => []
                    ]);

                    $ext = $this->getExternalId($item);
                    foreach ($fila['running'] as $i => $r) {
                        if ($this->getExternalId($r) === $ext) {
                            unset($fila['running'][$i]);
                            break;
                        }
                    }

                    $fila['running'] = array_values($fila['running']);
                    $fila['Administrador'] = array_values($fila['Administrador']);
                    $fila['Professor'] = array_values($fila['Professor']);
                    $fila['Aluno'] = array_values($fila['Aluno']);

                    Cache::put('queue_projects', $fila);

                    Log::info("[EsperaLiberacao] removido de running: {$ext}");
                } finally {
                    try {
                        $lock->release();
                    } catch (\Throwable $e) {
                    }
                }
            } else {
                Log::warning("[EsperaLiberacao] não conseguiu lock para remover de running — item: " . $this->getExternalId($item));
            }

            sleep(1);
        }
    }

    protected function getExternalId($project): ?string
    {
        return $project->external_id ?? null;
    }

    public function runQuantumCircuit($project)
    {
        $python = '/opt/venv/bin/python';
        $script = base_path('app/Http/Controllers/Projects/runner.py');

        $process = new Process([$python, $script], dirname($script));
        $process->setInput($project->qobject);
        $process->setWorkingDirectory(dirname($script));
        $process->run();

        $projectService = new ProjectService();
        $projModel = $projectService->getProject($project->external_id);

        if (!$process->isSuccessful() && $projModel) {
            Log::error('[EsperaLiberacao] Process error: ' . $process->getErrorOutput());
            $projModel->status = 'ERROR';
            $projModel->qobject_result = $process->getErrorOutput();
        } else if ($projModel) {
            Log::info('[EsperaLiberacao] Process output: ' . $process->getOutput());
            $projModel->status = 'FINISHED';
            $projModel->qobject_result = $process->getOutput();
        }

        $projModel->save();
    }
}
