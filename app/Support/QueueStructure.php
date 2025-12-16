<?php

namespace App\Support;

class QueueStructure
{
    public static function empty(): array
    {
        return [
            'Administrador' => [],
            'Professor' => [],
            'Aluno' => [],
            'Usuário' => [],
            'running' => [],
        ];
    }
}
