<?php

namespace App\Jobs;

use App\Domain\Rh\Models\Periode;
use App\Domain\Rh\Services\PaieService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenererBulletinsPeriode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes max
    public int $tries = 1;      // pas de retry auto : la génération est idempotente mais mieux vaut éviter

    public function __construct(
        public int $periodeId,
        public int $userId,
    ) {}

    public function handle(PaieService $service): void
    {
        $periode = Periode::find($this->periodeId);
        if (! $periode) {
            Log::warning('GenererBulletinsPeriode : période introuvable', [
                'periode_id' => $this->periodeId,
            ]);
            return;
        }

        Log::info('GenererBulletinsPeriode : début', [
            'periode_id' => $this->periodeId,
            'periode'    => $periode->libelle,
            'user_id'    => $this->userId,
        ]);

        try {
            $recap = $service->genererBulletinsPeriode($this->periodeId);

            // Stocke le résultat en cache pour que l'UI puisse le récupérer
            Cache::put("paie.recap.{$this->periodeId}", $recap, now()->addHours(24));
            Cache::put("paie.statut.{$this->periodeId}", 'termine', now()->addHours(24));

            Log::info('GenererBulletinsPeriode : terminé', [
                'periode_id' => $this->periodeId,
                'crees'      => $recap['crees'],
                'ignores'    => $recap['ignores'],
            ]);
        } catch (\Throwable $e) {
            Cache::put("paie.statut.{$this->periodeId}", 'erreur', now()->addHours(24));
            Cache::put("paie.erreur.{$this->periodeId}", $e->getMessage(), now()->addHours(24));

            Log::error('GenererBulletinsPeriode : échec', [
                'periode_id' => $this->periodeId,
                'exception'  => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}