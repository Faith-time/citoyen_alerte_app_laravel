<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Afficher la liste des notifications
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::where('user_id', auth()->id());

        // Notifications non lues uniquement
        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        // Notifications lues uniquement
        if ($request->boolean('read_only')) {
            $query->read();
        }

        // Notifications récentes
        if ($request->has('days')) {
            $query->recent($request->days);
        }

        // Filtre par type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->latest('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json($notifications);
    }

    /**
     * Créer une nouvelle notification
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array',
            'action_url' => 'nullable|url',
        ]);

        $notification = Notification::create($validated);

        // Envoyer une notification push si configuré
        // event(new NotificationCreated($notification));

        return response()->json([
            'message' => 'Notification créée avec succès',
            'data' => $notification
        ], 201);
    }

    /**
     * Afficher une notification spécifique
     */
    public function show(Notification $notification): JsonResponse
    {
        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Non autorisé'
            ], 403);
        }

        // Marquer comme lue automatiquement
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return response()->json($notification);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Non autorisé'
            ], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marquée comme lue',
            'data' => $notification
        ]);
    }

    /**
     * Marquer une notification comme non lue
     */
    public function markAsUnread(Notification $notification): JsonResponse
    {
        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Non autorisé'
            ], 403);
        }

        $notification->markAsUnread();

        return response()->json([
            'message' => 'Notification marquée comme non lue',
            'data' => $notification
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(): JsonResponse
    {
        Notification::where('user_id', auth()->id())
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'Toutes les notifications ont été marquées comme lues'
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Notification $notification): JsonResponse
    {
        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Non autorisé'
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification supprimée avec succès'
        ]);
    }

    /**
     * Supprimer toutes les notifications lues
     */
    public function deleteAllRead(): JsonResponse
    {
        $count = Notification::where('user_id', auth()->id())
            ->read()
            ->delete();

        return response()->json([
            'message' => 'Notifications supprimées avec succès',
            'count' => $count
        ]);
    }

    /**
     * Obtenir le nombre de notifications non lues
     */
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', auth()->id())
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Obtenir les statistiques des notifications
     */
    public function statistics(): JsonResponse
    {
        $userId = auth()->id();

        $stats = [
            'total' => Notification::where('user_id', $userId)->count(),
            'unread' => Notification::where('user_id', $userId)->unread()->count(),
            'read' => Notification::where('user_id', $userId)->read()->count(),
            'today' => Notification::where('user_id', $userId)
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
            'this_week' => Notification::where('user_id', $userId)
                ->recent(7)
                ->count(),
            'by_type' => Notification::where('user_id', $userId)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
        ];

        return response()->json($stats);
    }

    /**
     * Envoyer une notification à plusieurs utilisateurs
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array',
            'action_url' => 'nullable|url',
        ]);

        $notifications = [];

        foreach ($validated['user_ids'] as $userId) {
            $notifications[] = Notification::create([
                'user_id' => $userId,
                'type' => $validated['type'],
                'title' => $validated['title'],
                'message' => $validated['message'],
                'data' => $validated['data'] ?? null,
                'action_url' => $validated['action_url'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Notifications envoyées avec succès',
            'count' => count($notifications)
        ]);
    }
}
