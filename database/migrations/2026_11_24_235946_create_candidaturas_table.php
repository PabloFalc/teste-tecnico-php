<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('candidaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidato_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vaga_id')->constrained('vagas')->cascadeOnDelete();
            $table->string('status')->default('pendente');
            $table->timestamps();
            $table->unique(['candidato_id', 'vaga_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidaturas');
    }
};
