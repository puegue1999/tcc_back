<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendQuantumJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $qobj;

    public function __construct(array $qobj)
    {
        $this->qobj = $qobj;
    }

    public function handle()
    {
        // 1. Autenticação
        $tokenResp = Http::asForm()->post('https://iam.cloud.ibm.com/identity/token', [
            'grant_type' => 'urn:ibm:params:oauth:grant-type:apikey',
            'apikey' => config('services.ibm.api_key'),
        ])->throw();

        $token = $tokenResp->json('access_token');

        // 2. Envio do job
        $jobResp = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Service-CRN' => config('services.ibm.crn'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'IBM-API-Version' => '2025-05-01'
        ])->post('https://quantum.cloud.ibm.com/api/v1/jobs', [
                    'data' => json_encode($this->qobj),
                    'params' => [
                        'deviceRunType' => 'desired_backend',
                        'shots' => $this->qobj['shots'] ?? 1024,
                        'fromCache' => 'false'
                    ]
                ])->throw();

        $jobId = $jobResp->json('id');

        // 3. Polling até DONE
        do {
            sleep(5);
            $status = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Service-CRN' => config('services.ibm.crn'),
                'Accept' => 'application/json',
            ])->get("https://quantum.cloud.ibm.com/api/v1/jobs/{$jobId}")
                ->throw()
                ->json();
        } while (empty($status['status']) || $status['status'] !== 'DONE');

        // 4. Extrair resultado
        $result = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Service-CRN' => config('services.ibm.crn'),
            'Accept' => 'application/json',
        ])->get("https://quantum.cloud.ibm.com/api/v1/jobs/{$jobId}/results")
            ->throw()
            ->json();

        // 5. Faça algo com o resultado:
        \Log::info('Quantum job result', $result);
    }
}
