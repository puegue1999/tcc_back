<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //

        DB::table('roles')->insert([
            ['name' => 'Administrador', 'role_type' => 'admin'],
            ['name' => 'Professor', 'role_type' => 'user'],
            ['name' => 'Aluno', 'role_type' => 'user'],
            ['name' => 'Usuário', 'role_type' => 'user'],
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
