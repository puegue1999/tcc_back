<?php

namespace App\Http\Controllers\Jobs;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Jobs\EsperaLiberacao;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class JobsController extends Controller
{

    public function iniciar(Request $request)
    {
        $payload = $request->json()->all();
        $id = Str::uuid()->toString();
        $item = [
            'id' => $id,
            'payload' => $payload,
        ];

        $fila = Cache::get('fila_ordens', []);

        $fila[] = $item;
        Cache::put('fila_ordens', $fila);

        // EsperaLiberacao::dispatch($id);

        // \Log::info("➕ QObject enfileirado (ID: $id). Fila total: " . json_encode(array_column($fila, 'id')));

        return response()->json([
            'message' => 'QObject enfileirado com sucesso',
            'id' => $id,
            'fila' => array_column($fila, 'id'),
        ], 200);
    }


    public function liberar()
    {
        $fila = Cache::get('fila_ordens', []);
        if (empty($fila)) {
            return response()->json(['error' => 'Fila vazia'], 400);
        }

        $liberado = $fila->shift();
        Cache::put('id_liberado', $liberado);

        return response()->json([
            'liberado' => $liberado,
            'fila' => array_column($fila, 'id'),
        ], 200);
    }


    public function parar(Request $r)
    {
        $idLiberado = $r->input('id');
        Cache::put('id_liberado', $idLiberado);
        return response()->json(['liberado' => $idLiberado], 200);
    }
}
