<?php

use App\Modules\Vagas\Http\Controllers\VagasController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/vagas', [VagasController::class, 'list']);

Route::middleware(['auth:sanctum', 'tipo:recrutador'])->group(function () {
    Route::post('/vagas', [VagasController::class, 'store']);
    Route::put('/vagas/{vaga}', [VagasController::class, 'update']);
    Route::delete('/vagas/{vaga}', [VagasController::class, 'destroy']);
});
