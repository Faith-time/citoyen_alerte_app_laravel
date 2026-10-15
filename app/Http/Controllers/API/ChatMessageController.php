<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ChatMessageController extends Controller
{
    /**
     * Afficher la liste des messages
     */
    public function index(Request $request): JsonResponse
    {
        $query = ChatMessage::with(['sender', 'receiver', 'report']);

        // Filtre par signalement
        if ($request->has('report_id')) {
            $query->forReport($request->report_id);
        }

        // Messages non lus uniquement
        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        $messages = $query->latest('created_at')
            ->paginate($request->get('per_page', 20));

        return response()->json($messages);
    }

    /**
     * Envoyer un message
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_id' => 'required|exists:reports,id',
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required_without:attachment|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        $validated['sender_id'] = auth()->id();

        // Gérer l'upload du fichier
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('chat_attachments', 'public');
            $validated['attachment_path'] = $path;
        }

        $message = ChatMessage::create($validated);
        $message->load(['sender', 'receiver', 'report']);

        // Envoyer une notification au destinataire
        // event(new NewChatMessage($message));

        return response()->json([
            'message' => 'Message envoyé avec succès',
            'data' => $message
        ], 201);
    }

    /**
     * Afficher un message spécifique
     */
    public function show(ChatMessage $chatMessage): JsonResponse
    {
        $chatMessage->load(['sender', 'receiver', 'report']);

        return response()->json($chatMessage);
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead(ChatMessage $chatMessage): JsonResponse
    {
        // Vérifier que l'utilisateur actuel est le destinataire
        if ($chatMessage->receiver_id !== auth()->id()) {
            return response()->json([
                'message' => 'Non autorisé'
            ], 403);
        }

        $chatMessage->markAsRead();

        return response()->json([
            'message' => 'Message marqué comme lu',
            'data' => $chatMessage
        ]);
    }

    /**
     * Obtenir la conversation pour un signalement
     */
    public function conversation(Report $report): JsonResponse
    {
        $messages = ChatMessage::forReport($report->id)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Obtenir les messages entre deux utilisateurs pour un signalement
     */
    public function betweenUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_id' => 'required|exists:reports,id',
            'other_user_id' => 'required|exists:users,id',
        ]);

        $messages = ChatMessage::forReport($validated['report_id'])
            ->betweenUsers(auth()->id(), $validated['other_user_id'])
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Obtenir le nombre de messages non lus
     */
    public function unreadCount(): JsonResponse
    {
        $count = ChatMessage::where('receiver_id', auth()->id())
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Marquer tous les messages comme lus
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_id' => 'nullable|exists:reports,id',
        ]);

        $query = ChatMessage::where('receiver_id', auth()->id())
            ->unread();

        if (isset($validated['report_id'])) {
            $query->forReport($validated['report_id']);
        }

        $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'message' => 'Tous les messages ont été marqués comme lus'
        ]);
    }

    /**
     * Supprimer un message
     */
    public function destroy(ChatMessage $chatMessage): JsonResponse
    {
        // Vérifier que l'utilisateur actuel est l'expéditeur
        if ($chatMessage->sender_id !== auth()->id()) {
            return response()->json([
                'message' => 'Non autorisé'
            ], 403);
        }

        // Supprimer le fichier attaché s'il existe
        if ($chatMessage->hasAttachment()) {
            Storage::disk('public')->delete($chatMessage->attachment_path);
        }

        $chatMessage->delete();

        return response()->json([
            'message' => 'Message supprimé avec succès'
        ]);
    }

    /**
     * Télécharger une pièce jointe
     */
    public function downloadAttachment(ChatMessage $chatMessage): mixed
    {
        if (!$chatMessage->hasAttachment()) {
            return response()->json([
                'message' => 'Aucune pièce jointe disponible'
            ], 404);
        }

        // Vérifier que l'utilisateur est impliqué dans la conversation
        if ($chatMessage->sender_id !== auth()->id() && $chatMessage->receiver_id !== auth()->id()) {
            return response()->json([
                'message' => 'Non autorisé'
            ], 403);
        }

        return Storage::disk('public')->download($chatMessage->attachment_path);
    }
}
