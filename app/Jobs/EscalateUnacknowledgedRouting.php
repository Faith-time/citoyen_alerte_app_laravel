<?php

namespace App\Jobs;

use App\Models\ReportRouting;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Vérifie, pour UN routage donné, si l'autorité a accusé réception.
 * Si non, relance une notification "urgente" et se reprogramme lui-même
 * jusqu'à MAX_ESCALATION_LEVEL relances.
 *
 * Dispatché ainsi (voir ReportController::store) :
 *   EscalateUnacknowledgedRouting::dispatch($routing->id)->delay(now()->addMinutes(5));
 *
 * Ne nécessite pas de scheduler/Kernel : un simple
 *   php artisan queue:work
 * doit tourner en continu pour que les jobs différés s'exécutent.
 */
class EscalateUnacknowledgedRouting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_ESCALATION_LEVEL = 3;
    private const RETRY_DELAY_MINUTES  = 5;

    public function __construct(private readonly int $routingId)
    {
    }

    public function handle(FcmService $fcm): void
    {
        $routing = ReportRouting::with(['report', 'authority'])->find($this->routingId);

        // Déjà pris en charge, résolu, ou supprimé depuis → rien à faire.
        if (!$routing || $routing->acknowledged_at !== null || $routing->status === 'resolved') {
            return;
        }

        if ($routing->escalation_level >= self::MAX_ESCALATION_LEVEL) {
            return;
        }

        $routing->increment('escalation_level');
        $routing->update(['escalated_at' => now()]);

        $fcm->notifyAuthorityNewReport(
            $routing->authority_id,
            '⚠️ RELANCE — ' . ($routing->report->title ?? 'Signalement urgent') . ' (sans réponse)'
        );

        if ($routing->escalation_level < self::MAX_ESCALATION_LEVEL) {
            self::dispatch($this->routingId)->delay(now()->addMinutes(self::RETRY_DELAY_MINUTES));
        }
    }
}
