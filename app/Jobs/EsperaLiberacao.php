<?php

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EsperaLiberacao implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function handle(): void
    {
        $fila = Cache::get('fila_ordens', []);

        $ids = array_column($fila, 'id');
        Log::info("⏱️ [Job {$this->id}] Iniciando; fila atual: [" . implode(',', $ids) . "]");

        $idLiberado = Cache::pull('id_liberado');

        if ($idLiberado) {
            $fila = array_values(array_filter($fila, fn($item) => $item['id'] !== $idLiberado));
            Cache::put('fila_ordens', $fila);
            $ids = array_column($fila, 'id');
            Log::info("✅ [Job {$this->id}] ID liberado pela API: {$idLiberado}; nova fila: [" . implode(',', $ids) . "]");
        } else {
            Log::info("🔄 [Job {$this->id}] aguardando. Fila: [" . implode(',', $ids) . "]");
            $this->release(5);
            return;
        }

        Log::info("▶️ [Job {$this->id}] executando payload...");
        $item = current($fila);
        $payload = $item['payload'];

        array_shift($fila);
        Cache::put('fila_ordens', $fila);
        $remaining = array_column($fila, 'id');
        Log::info("🏁 [Job {$this->id}] concluído. Fila restante: [" . implode(',', $remaining) . "]");
    }
}

