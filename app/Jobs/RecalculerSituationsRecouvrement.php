<?php

namespace App\Jobs;

use App\Domain\Finances\Services\RecouvrementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculerSituationsRecouvrement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?int $anneeId = null) {}

    public function handle(RecouvrementService $service): void
    {
        $service->recalculerTout($this->anneeId);
    }
}