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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique()->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            $table->boolean('validated');

            $table->enum('status', ['ACTIVE', 'INACTIVE']);

            $table->dateTime('last_activity')->nullable();
            $table->dateTime('terms_agreement_date')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
