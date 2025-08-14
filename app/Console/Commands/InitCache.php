<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class InitCache extends Command
{
    protected $signature = 'cache:init';
    protected $description = 'Inicializa variáveis no cache';

    public function handle()
    {
        $prioridade = [
            'admin' => [],
            'professor' => [],
            'aluno' => [],
            'running' => [],
        ];
        Cache::put('queue_projects', $prioridade);
    }
}