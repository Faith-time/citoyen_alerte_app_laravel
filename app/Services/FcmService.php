<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Envoie des notifications push via l'API FCM v1 (HTTP v1), en utilisant
 * google/auth pour l'authentification OAuth2 par compte de service.
 *
 * Convention de topics — alignée sur PushNotificationService.subscribeToRoleTopics
 * (lib/core/services/push_notification_service.dart) côté Flutter :
 *   - "user_{userId}"       -> topic personnel, abonné par TOUT utilisateur connecté
 *                              quel que soit son rôle (citoyen, agent, autorité, admin)
 *   - "role_citizen"        -> topic auquel tous les citoyens sont abonnés (broadcast)
 *   - "role_agent"          -> topic auquel tous les agents sont abonnés
 *   - "role_authority"      -> topic auquel toutes les autorités sont abonnées
 *   - "authority_{id}"      -> topic par autorité spécifique (abonnement additionnel
 *                              pour les comptes de rôle 'authority')
 *
 * Si ces noms changent côté Flutter, adapte les méthodes ci-dessous en conséquence.
 */
class FcmService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private ServiceAccountCredentials $credentials;
    private Client $http;
    private string $projectId;

    public function __construct()
    {
        $credentialsPath = base_path(
            env('FIREBASE_CREDENTIALS', 'storage/app/firebase/service-account.json')
        );

        $this->projectId = (string) env('FIREBASE_PROJECT_ID', '');

        if ($this->projectId === '') {
            Log::warning('FCM_PROJECT_ID manquant — ajoute FIREBASE_PROJECT_ID dans .env');
        }

        $this->credentials = new ServiceAccountCredentials(self::SCOPE, $credentialsPath);
        $this->http = new Client();
    }

    // ==========================================================
    //  MÉTHODES PUBLIQUES — appelées depuis les contrôleurs
    // ==========================================================

    /**
     * Nouveau signalement routé vers une autorité.
     */
    public function notifyAuthorityNewReport(int $authorityId, string $reportTitle): void
    {
        // Topic "authority_{id}" : correspond à l'abonnement additionnel que
        // subscribeToRoleTopics() fait pour un compte de rôle 'authority'.
        $this->sendToTopic(
            "authority_{$authorityId}",
            'Nouveau signalement',
            "Un nouveau signalement vous a été routé : {$reportTitle}",
            [
                'type'         => 'authority_new_report',
                'authority_id' => $authorityId,
            ]
        );
    }

    /**
     * Changement de statut d'un signalement, notifié au citoyen concerné.
     * Cible son topic personnel "user_{id}" (abonné par tout utilisateur connecté).
     */
    public function notifyCitizenReportStatus(int $citizenId, int $reportId, string $statusLabel): void
    {
        $this->sendToTopic(
            "user_{$citizenId}",
            'Mise à jour de votre signalement',
            "Votre signalement est maintenant : {$statusLabel}",
            [
                'type'      => 'report_status',
                'report_id' => $reportId,
                'status'    => $statusLabel,
            ]
        );
    }

    /**
     * Diffusion "Actualités" à tous les citoyens (signalement résolu avec photos before/after).
     * Topic "role_citizen" : tous les comptes citoyens y sont abonnés en permanence.
     */
    public function notifyAllCitizensNewActuality(string $reportTitle): void
    {
        $this->sendToTopic(
            'role_citizen',
            'Nouvelle actualité',
            "Un signalement vient d'être résolu : {$reportTitle}",
            [
                'type' => 'new_actuality',
            ]
        );
    }

    /**
     * Intervention assignée à un agent.
     * Cible son topic personnel "user_{id}" (un agent n'a pas de topic de rôle dédié à lui seul).
     */
    public function notifyAgentInterventionAssigned(int $agentId, int $interventionId): void
    {
        $this->sendToTopic(
            "user_{$agentId}",
            'Nouvelle intervention',
            'Une intervention vous a été assignée.',
            [
                'type'            => 'intervention_assigned',
                'intervention_id' => $interventionId,
            ]
        );
    }

    // ==========================================================
    //  MÉCANIQUE INTERNE — FCM v1 + OAuth2
    // ==========================================================

    /**
     * Récupère un access token OAuth2 valide via le compte de service.
     * google/auth gère lui-même la mise en cache/expiration en interne
     * pour la durée de la requête HTTP courante.
     */
    private function accessToken(): string
    {
        $token = $this->credentials->fetchAuthToken();

        return $token['access_token'] ?? throw new \RuntimeException(
            'Impossible de récupérer un access token Firebase — vérifie service-account.json'
        );
    }

    /**
     * Envoie une notification à tous les appareils abonnés à un topic FCM.
     *
     * @param array<string, mixed> $data Données custom (converties en strings, requis par FCM)
     */
    private function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        $this->send([
            'topic'        => $topic,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'data' => $this->stringifyData($data),
        ]);
    }

    /**
     * FCM exige que toutes les valeurs du champ "data" soient des strings.
     */
    private function stringifyData(array $data): array
    {
        return array_map(static fn ($value) => (string) $value, $data);
    }

    /**
     * POST vers l'endpoint FCM v1. Les erreurs sont journalisées mais ne
     * remontent jamais d'exception vers l'appelant — une notif ratée ne doit
     * jamais faire échouer une requête API métier (création de signalement, etc.).
     */
    private function send(array $message): void
    {
        if ($this->projectId === '') {
            return;
        }

        try {
            $this->http->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->accessToken(),
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => ['message' => $message],
                ]
            );
        } catch (\Throwable $e) {
            Log::error('FCM send failed', [
                'message' => $e->getMessage(),
                'topic'   => $message['topic'] ?? null,
            ]);
        }
    }
}
