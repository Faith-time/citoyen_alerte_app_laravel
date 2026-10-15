<?php

use App\Http\Controllers\API\AgentZoneController;
use App\Http\Controllers\API\AppVersionController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AuthorityAgentController;
use App\Http\Controllers\API\AuthorityController;
use App\Http\Controllers\API\BadgeController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ChatMessageController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\MunicipalityController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\UserBadgeController;
use App\Http\Controllers\API\AuditLogController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


/*
|--------------------------------------------------------------------------
| API Routes — Citoyen Alerte
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ========================================================================
    //  AUTHENTIFICATION (JWT)
    // ========================================================================
    Route::prefix('auth')->group(function () {

        // Routes publiques
        Route::post('register',           [AuthController::class, 'register']);
        Route::post('login',              [AuthController::class, 'login']);
        Route::post('forgot-password',    [AuthController::class, 'forgotPassword']);
        Route::post('reset-password',     [AuthController::class, 'resetPassword']);
        Route::post('google', [AuthController::class, 'googleLogin']);
        // Routes protégées
        Route::middleware('auth:api')->group(function () {
            Route::post('logout',                 [AuthController::class, 'logout']);
            Route::post('refresh',                [AuthController::class, 'refresh']);
            Route::get('me',                      [AuthController::class, 'me']);
            Route::put('profile',                 [AuthController::class, 'updateProfile']);
            Route::post('change-password',        [AuthController::class, 'changePassword']);
            Route::post('verify-phone',           [AuthController::class, 'verifyPhone']);
            Route::post('send-verification-code', [AuthController::class, 'sendVerificationCode']);
        });
    });

    // ========================================================================
    //  RESSOURCES PUBLIQUES (sans authentification)
    // ========================================================================

    Route::get('categories',      [CategoryController::class, 'index']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);

    Route::get('app-versions/latest/{platform}', [AppVersionController::class, 'latest']);
    Route::post('app-versions/check-update',     [AppVersionController::class, 'checkUpdate']);

    Route::get('settings/public', [SettingController::class, 'publicSettings']);

    // ========================================================================
    //  ROUTES PROTÉGÉES (JWT + vérifications banni/suspendu)
    // ========================================================================
    Route::middleware(['auth:api', 'check.banned', 'check.suspended'])->group(function () {

        // ====================================================================
        //  CATÉGORIES — mutations (admin) + relation autorités
        // ====================================================================
        Route::middleware('admin')->prefix('categories')->group(function () {
            Route::post('/',       [CategoryController::class, 'store']);
            Route::put('/{id}',    [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
        });

        // Autorités compétentes pour une catégorie
        Route::get('categories/{id}/authorities', function ($id) {
            $category = \App\Models\Category::with('authorities')->find($id);
            if (!$category) {
                return response()->json(['success' => false, 'message' => 'Catégorie non trouvée'], 404);
            }
            return response()->json(['success' => true, 'data' => $category->authorities]);
        });

        // ====================================================================
        //  AUTORITÉS
        // ====================================================================
        Route::prefix('authorities')->group(function () {

            // Lecture (tous connectés)
            Route::get('/',     [AuthorityController::class, 'index']);
            Route::get('/{id}', [AuthorityController::class, 'show']);

            // Routing intelligent (tous connectés)
            Route::get('/for-category/{categoryId}', [AuthorityController::class, 'forCategory']);
            Route::post('/suggest',                  [AuthorityController::class, 'suggest']);

            // Mutations (admin)
            Route::middleware('admin')->group(function () {
                Route::post('/',       [AuthorityController::class, 'store']);
                Route::put('/{id}',    [AuthorityController::class, 'update']);
                Route::delete('/{id}', [AuthorityController::class, 'destroy']);

                // Gestion du pivot authority_categories
                Route::get('/{id}/categories',                [AuthorityController::class, 'getCategories']);
                Route::post('/{id}/categories',               [AuthorityController::class, 'attachCategories']);
                Route::put('/{id}/categories',                [AuthorityController::class, 'syncCategories']);
                Route::delete('/{id}/categories',             [AuthorityController::class, 'detachCategories']);
                Route::patch('/{id}/categories/{categoryId}', [AuthorityController::class, 'updateCategoryPrimary']);
            });
        });

        // Dans le groupe middleware auth:api
        Route::prefix('interventions')->group(function () {
            Route::get('/mine', function (Request $request) {
                $interventions = \App\Models\Intervention::where('agent_id', $request->user()->id)
                    ->with([
                        'report:id,title,reference,severity,status,commune_name,region_name,address,latitude,longitude',
                        'authority:id,name,type',
                    ])
                    ->latest()
                    ->get()
                    ->map(fn($i) => array_merge($i->toArray(), [
                        'report_title'      => $i->report?->title,
                        'report_reference'  => $i->report?->reference,
                        'authority_name'    => $i->authority?->name,
                        'report_address'    => $i->report?->address,
                        'report_commune'    => $i->report?->commune_name,
                        'report_region'     => $i->report?->region_name,
                        'report_latitude'   => $i->report?->latitude,
                        'report_longitude'  => $i->report?->longitude,
                    ]));

                return response()->json(['success' => true, 'data' => $interventions]);
            });
            Route::get('/', function (Request $request) {
                $interventions = \App\Models\Intervention::with(['report', 'authority'])
                    ->latest()
                    ->get();
                return response()->json(['success' => true, 'data' => $interventions]);
            });

            Route::post('/', function (Request $request) {
                $validated = $request->validate([
                    'report_id' => 'required|exists:reports,id',
                    'agent_id'  => 'required|exists:users,id',
                ]);

                $agent = \App\Models\User::find($validated['agent_id']);

                if (!$agent || !$agent->authority_id) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cet agent n'est rattaché à aucune autorité",
                    ], 422);
                }

                $intervention = \App\Models\Intervention::create([
                    'report_id'    => $validated['report_id'],
                    'agent_id'     => $validated['agent_id'],
                    'authority_id' => $agent->authority_id,
                    'status'       => 'pending',
                    'started_at'   => now(),
                ]);

                return response()->json(['success' => true, 'data' => $intervention], 201);
            });

            Route::get('/{id}', function (Request $request, $id) {
                $intervention = \App\Models\Intervention::with(['report', 'authority'])->find($id);
                if (!$intervention) {
                    return response()->json(['success' => false, 'message' => 'Not found'], 404);
                }
                return response()->json(['success' => true, 'data' => $intervention]);
            });

            Route::put('/{id}/status', function (Request $request, $id) {
                $intervention = \App\Models\Intervention::find($id);
                if (!$intervention) {
                    return response()->json(['success' => false, 'message' => 'Not found'], 404);
                }

                $updateData = [
                    'status' => $request->status,
                    'notes'  => $request->notes,
                ];

                if ($request->status === 'completed') {
                    $updateData['completed_at'] = now();
                }

                $intervention->update($updateData);

                if ($request->status === 'completed') {
                    $intervention->report()->update([
                        'status'      => 'resolved',
                        'resolved_at' => now(),
                    ]);
                }

                return response()->json(['success' => true, 'data' => $intervention]);
            });
            Route::post('/{id}/complete', function (Request $request, $id) {
                $intervention = \App\Models\Intervention::find($id);
                if (!$intervention) {
                    return response()->json(['success' => false, 'message' => 'Not found'], 404);
                }
                $intervention->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                    'notes'        => $request->notes,
                ]);

                $intervention->report()->update([
                    'status'      => 'resolved',
                    'resolved_at' => now(),
                ]);

                return response()->json(['success' => true, 'data' => $intervention]);
            });
        });

        // ====================================================================
        //  SIGNALEMENTS
        // ====================================================================
        Route::prefix('reports')->group(function () {

            Route::get('/nearby',            [ReportController::class, 'nearby']);
            Route::get('/user/{userId}',     [ReportController::class, 'userReports']);
            Route::get('/dashboard',         [ReportController::class, 'dashboard']);
            Route::get('/statistics',        [ReportController::class, 'statistics']);
            Route::get('/images/statistics', [ReportController::class, 'imageStatistics']);
            Route::get('/routings/statistics',                      [ReportController::class, 'routingStatistics']);
            Route::get('/routings/authority/{authorityId}/pending', [ReportController::class, 'routingsPendingForAuthority']);
            Route::get('/resolved-feed',     [ReportController::class, 'resolvedFeed']); // ⬅️ AJOUT

            // CRUD
            Route::get('/',            [ReportController::class, 'index']);
            Route::post('/',           [ReportController::class, 'store']);
            Route::get('/{report}',    [ReportController::class, 'show']);
            Route::put('/{report}',    [ReportController::class, 'update']);
            Route::delete('/{report}', [ReportController::class, 'destroy']);

            // Actions
            Route::put('/{report}/status',  [ReportController::class, 'updateStatus']);
            Route::put('/{report}/resolve', [ReportController::class, 'markAsResolved']);

            // Images
            Route::get('/{report}/images',                   [ReportController::class, 'images']);
            Route::get('/{report}/images/before-after',      [ReportController::class, 'imagesBeforeAfter']);
            Route::post('/{report}/images',                  [ReportController::class, 'imageStore']);
            Route::post('/{report}/images/multiple',         [ReportController::class, 'imageStoreMultiple']);
            Route::delete('/{report}/images/{image}',        [ReportController::class, 'imageDestroy']);
            Route::put('/{report}/images/{image}/validate',  [ReportController::class, 'imageValidate']);
            Route::get('/{report}/images/{image}/download',  [ReportController::class, 'imageDownload']);
            Route::post('/{report}/images/{image}/analyze',  [ReportController::class, 'imageAnalyzeAI']);

            // Routage
            Route::get('/{report}/routings',                       [ReportController::class, 'routings']);
            Route::put('/{report}/routings/{routing}',             [ReportController::class, 'routingUpdate']);
            Route::put('/{report}/routings/{routing}/in-progress', [ReportController::class, 'routingMarkInProgress']);
            Route::put('/{report}/routings/{routing}/resolve',     [ReportController::class, 'routingMarkResolved']);
            Route::put('/{report}/routings/{routing}/acknowledge', [ReportController::class, 'routingAcknowledge']);
            Route::middleware('admin')->group(function () {
                Route::post('/{report}/routings',                   [ReportController::class, 'routingStore']);
                Route::delete('/{report}/routings/{routing}',       [ReportController::class, 'routingDestroy']);
                Route::put('/{report}/routings/{routing}/reassign', [ReportController::class, 'routingReassign']);
            });

            // Autorité peut marquer ses routings in-progress / resolved
            Route::middleware('authority')->group(function () {
                Route::put('/{report}/routings/{routing}/in-progress', [ReportController::class, 'routingMarkInProgress']);
                Route::put('/{report}/routings/{routing}/resolve',     [ReportController::class, 'routingMarkResolved']);
            });
        });

        // ====================================================================
        //  ZONES D'AGENTS (admin)
        // =======================================================middleware(['auth:api',=============
        Route::prefix('agent-zones')->middleware(['check.role:admin,authority'])->group(function () {
            // Routes nommées avant les paramètres dynamiques
            Route::get('/available',          [AgentZoneController::class, 'getAvailableAgents']);
            Route::get('/statistics',         [AgentZoneController::class, 'statistics']);
            Route::get('/agent/{agentId}',    [AgentZoneController::class, 'getAgentZones']);

            // CRUD
            Route::get('/',               [AgentZoneController::class, 'index']);
            Route::post('/',              [AgentZoneController::class, 'store']);
            Route::get('/{agentZone}',    [AgentZoneController::class, 'show']);
            Route::put('/{agentZone}',    [AgentZoneController::class, 'update']);
            Route::delete('/{agentZone}', [AgentZoneController::class, 'destroy']);

            // Action métier
            Route::put('/{agentZone}/set-primary', [AgentZoneController::class, 'setPrimary']);
        });

        // ====================================================================
        //  BADGES
        // ====================================================================
        Route::apiResource('badges', BadgeController::class);

        Route::prefix('user-badges')->group(function () {
            Route::get('/',     [UserBadgeController::class, 'index']);
            Route::get('/{id}', [UserBadgeController::class, 'show']);

            Route::middleware('admin')->group(function () {
                Route::post('/',                           [UserBadgeController::class, 'store']);
                Route::delete('/{id}',                     [UserBadgeController::class, 'destroy']);
                Route::post('/check-eligibility/{userId}', [UserBadgeController::class, 'checkEligibility']);
            });
        });

        // ====================================================================
        //  NOTIFICATIONS
        // ====================================================================
        Route::prefix('notifications')->group(function () {
            Route::get('/unread/count',        [NotificationController::class, 'unreadCount']);
            Route::post('/mark-all-as-read',   [NotificationController::class, 'markAllAsRead']);
            Route::delete('/read/delete-all',  [NotificationController::class, 'deleteAllRead']);

            Route::get('/',       [NotificationController::class, 'index']);
            Route::post('/',      [NotificationController::class, 'store']);
            Route::get('/{id}',   [NotificationController::class, 'show']);
            Route::put('/{id}',   [NotificationController::class, 'update']);
            Route::delete('/{id}',[NotificationController::class, 'destroy']);
            Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
        });

        // ====================================================================
        //  MESSAGERIE
        // ====================================================================
        Route::prefix('chat-messages')->group(function () {
            Route::get('/unread/count', [ChatMessageController::class, 'unreadCount']);

            Route::get('/',       [ChatMessageController::class, 'index']);
            Route::post('/',      [ChatMessageController::class, 'store']);
            Route::get('/{id}',   [ChatMessageController::class, 'show']);
            Route::put('/{id}',   [ChatMessageController::class, 'update']);
            Route::delete('/{id}',[ChatMessageController::class, 'destroy']);
            Route::post('/{id}/mark-as-read', [ChatMessageController::class, 'markAsRead']);
        });

        // ====================================================================
        //  AUDIT LOGS (admin — lecture uniquement)
        // ====================================================================
        Route::prefix('audit-logs')->middleware('admin')->group(function () {
            Route::get('/statistics',                    [AuditLogController::class, 'statistics']);
            Route::get('/record/{tableName}/{recordId}', [AuditLogController::class, 'forRecord']);
            Route::get('/user/{userId}/activity',        [AuditLogController::class, 'userActivity']);
            Route::get('/',     [AuditLogController::class, 'index']);
            Route::get('/{id}', [AuditLogController::class, 'show']);
        });

        Route::prefix('municipality')->middleware('admin')->group(function () {
            Route::get('/reports',    [MunicipalityController::class, 'reports']);
            Route::get('/statistics', [MunicipalityController::class, 'statistics']);
        });

        // ====================================================================
        //  PARAMÈTRES (admin)
        // ====================================================================
        Route::prefix('settings')->middleware('admin')->group(function () {
            Route::get('/key/{key}',         [SettingController::class, 'getByKey']);
            Route::post('/key/{key}',        [SettingController::class, 'setByKey']);
            Route::get('/group/{groupName}', [SettingController::class, 'byGroup']);
            Route::post('/bulk-update',      [SettingController::class, 'bulkUpdate']);

            Route::get('/',       [SettingController::class, 'index']);
            Route::post('/',      [SettingController::class, 'store']);
            Route::get('/{id}',   [SettingController::class, 'show']);
            Route::put('/{id}',   [SettingController::class, 'update']);
            Route::delete('/{id}',[SettingController::class, 'destroy']);
        });

        // ====================================================================
        //  VERSIONS DE L'APP
        // ====================================================================
        Route::get('app-versions',      [AppVersionController::class, 'index']);
        Route::get('app-versions/{id}', [AppVersionController::class, 'show']);

        Route::middleware('admin')->group(function () {
            Route::post('app-versions',       [AppVersionController::class, 'store']);
            Route::put('app-versions/{id}',   [AppVersionController::class, 'update']);
            Route::delete('app-versions/{id}',[AppVersionController::class, 'destroy']);
        });

        // ====================================================================
        //  TABLEAUX DE BORD
        // ====================================================================
        Route::prefix('dashboard')->group(function () {
            Route::get('/citizen', [DashboardController::class, 'citizenDashboard'])->middleware('citizen');
            Route::get('/agent',   [DashboardController::class, 'agentDashboard'])->middleware('agent');
            Route::get('/admin',   [DashboardController::class, 'adminDashboard'])->middleware('admin');
            Route::get('/authority', [DashboardController::class, 'authorityDashboard'])
                ->middleware('authority');
            Route::get('/mairie',    [DashboardController::class, 'mairieDashboard'])->middleware('admin');


            Route::middleware('admin')->group(function () {
                Route::get('/reports-statistics',      [DashboardController::class, 'reportsStatistics']);
                Route::get('/category-statistics',     [DashboardController::class, 'categoryStatistics']);
                Route::get('/geographical-statistics', [DashboardController::class, 'geographicalStatistics']);
                Route::get('/user-statistics',         [DashboardController::class, 'userStatistics']);
                Route::get('/real-time',               [DashboardController::class, 'realTimeStatistics']);
                Route::post('/export',                 [DashboardController::class, 'exportStatistics']);
            });
        });

        Route::get('/authority/routings', function (Request $request) {
            $user = $request->user();
            $routings = \App\Models\ReportRouting::where('authority_id', $user->authority_id)
                ->with(['report.category', 'report'])
                ->latest()
                ->get();
            return response()->json(['success' => true, 'data' => $routings]);
        })->middleware('authority');

    });

    Route::prefix('authority/agents')->group(function () {
        Route::post('/',           [AuthorityAgentController::class, 'store']);
        Route::put('/{userId}',    [AuthorityAgentController::class, 'update']);
        Route::delete('/{userId}', [AuthorityAgentController::class, 'destroy']);
        Route::patch('/{userId}/duty', [AuthorityAgentController::class, 'toggleDuty']);
    });

}); // fin prefix v1

// Fallback — endpoint non trouvé
Route::fallback(fn() => response()->json([
    'success' => false,
    'message' => 'Endpoint not found',
], 404));
