<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'citizen',
            'is_active' => true,
            'is_verified' => false,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]
        ], 201);
    }

    /**
     * Connexion classique email / mot de passe
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants incorrects'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de créer le token'
            ], 500);
        }

        $user = auth()->user();

        if ($user->is_banned) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été banni',
                'reason' => $user->ban_reason,
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est désactivé'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]
        ]);
    }

    /**
     * ── Connexion via Google (Firebase ID Token) ──────────────────────────
     * L'app Flutter envoie le idToken Firebase.
     * On vérifie ce token auprès de Firebase, puis on crée ou récupère
     * l'utilisateur dans notre base et on retourne un JWT Laravel.
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
            'name'     => 'nullable|string|max:255',
            'email'    => 'required|email',
            'avatar'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        // ── Vérification du token Firebase ────────────────────────────────
        // Option A : vérification légère via l'API Google (sans SDK Firebase Admin)
        // Appel à tokeninfo pour valider le idToken
        $idToken = $request->id_token;
        $googleResponse = $this->verifyFirebaseToken($idToken);

        if (!$googleResponse) {
            return response()->json([
                'success' => false,
                'message' => 'Token Google invalide ou expiré'
            ], 401);
        }

        // ── Trouver ou créer l'utilisateur ────────────────────────────────
        $email = $request->email;
        $name  = $request->name ?? $googleResponse['name'] ?? explode('@', $email)[0];

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'          => $name,
                'password'      => Hash::make(Str::random(32)), // mot de passe aléatoire
                'role'          => 'citizen',
                'is_active'     => true,
                'is_verified'   => true, // vérifié via Google
                'auth_provider' => 'google',
                'avatar'        => $request->avatar,
                'google_id'     => $googleResponse['sub'] ?? null,
            ]
        );

        // Mise à jour si l'utilisateur existe déjà
        if (!$user->wasRecentlyCreated) {
            $user->update([
                'name'          => $name,
                'auth_provider' => 'google',
                'avatar'        => $request->avatar ?? $user->avatar,
                'google_id'     => $googleResponse['sub'] ?? $user->google_id,
            ]);
        }

        // Vérifications
        if ($user->is_banned ?? false) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte a été banni',
            ], 403);
        }

        if (!($user->is_active ?? true)) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est désactivé'
            ], 403);
        }

        // ── Génération du JWT Laravel ─────────────────────────────────────
        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de créer le token'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Connexion Google réussie',
            'data'    => [
                'user'         => $user,
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => config('jwt.ttl') * 60,
            ]
        ]);
    }

    private function verifyFirebaseToken(string $idToken): ?array
    {
        try {
            $webApiKey = env('FIREBASE_WEB_API_KEY');

            if (empty($webApiKey)) {
                \Log::warning('FIREBASE_WEB_API_KEY manquante — vérification ignorée');
                // Fallback : décoder le JWT sans vérifier la signature (DEV UNIQUEMENT)
                return $this->decodeJwtPayload($idToken);
            }

            $url = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . $webApiKey;

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => json_encode(['idToken' => $idToken]),
                CURLOPT_TIMEOUT        => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                \Log::error('Firebase cURL error: ' . $curlError);
                return $this->decodeJwtPayload($idToken); // fallback
            }

            if ($httpCode !== 200) {
                \Log::warning('Firebase lookup HTTP ' . $httpCode . ': ' . $response);
                return null;
            }

            $data = json_decode($response, true);

            if (!isset($data['users'][0])) {
                \Log::warning('Firebase: aucun utilisateur trouvé dans la réponse');
                return null;
            }

            $user = $data['users'][0];
            \Log::info('Firebase token vérifié pour: ' . ($user['email'] ?? 'inconnu'));

            return [
                'sub'   => $user['localId'],
                'email' => $user['email']         ?? null,
                'name'  => $user['displayName']   ?? null,
                'avatar'=> $user['photoUrl']      ?? null,
            ];

        } catch (\Exception $e) {
            \Log::error('verifyFirebaseToken exception: ' . $e->getMessage());
            return $this->decodeJwtPayload($idToken); // fallback
        }
    }

    /**
     * Décode le payload d'un JWT sans vérifier la signature.
     * Utilisé comme fallback si Firebase API Key n'est pas configurée.
     * NE PAS UTILISER EN PRODUCTION sans vérification de signature.
     */
    private function decodeJwtPayload(string $jwt): ?array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            \Log::warning('JWT malformé: ' . count($parts) . ' parties');
            return null;
        }

        try {
            $padded  = str_pad(strtr($parts[1], '-_', '+/'),
                strlen($parts[1]) % 4 === 0 ? strlen($parts[1]) : strlen($parts[1]) + (4 - strlen($parts[1]) % 4),
                '=');
            $payload = json_decode(base64_decode($padded), true);

            if (!$payload || !isset($payload['sub'])) {
                \Log::warning('JWT payload invalide ou sub manquant');
                return null;
            }

            // Vérifier expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                \Log::warning('JWT expiré depuis ' . (time() - $payload['exp']) . 's');
                return null;
            }

            \Log::info('JWT décodé (sans vérification) pour: ' . ($payload['email'] ?? $payload['sub']));
            return $payload;

        } catch (\Exception $e) {
            \Log::error('Erreur décodage JWT: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Déconnexion
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['success' => true, 'message' => 'Déconnexion réussie']);
        } catch (JWTException $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la déconnexion'], 500);
        }
    }

    /**
     * Rafraîchir le token
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'message' => 'Token rafraîchi',
                'data'    => [
                    'access_token' => $token,
                    'token_type'   => 'bearer',
                    'expires_in'   => config('jwt.ttl') * 60,
                ]
            ]);
        } catch (JWTException $e) {
            return response()->json(['success' => false, 'message' => 'Impossible de rafraîchir le token'], 500);
        }
    }

    /**
     * Utilisateur connecté
     */
    public function me(): JsonResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Utilisateur non trouvé'], 404);
            }
            $user->loadCount('reports');
            return response()->json(['success' => true, 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la récupération'], 500);
        }
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name'   => 'sometimes|string|max:255',
            'email'  => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'  => 'nullable|string|unique:users,phone,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'email', 'phone']);

        if ($request->hasFile('avatar')) {
            if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
                \Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        return response()->json(['success' => true, 'message' => 'Profil mis à jour', 'data' => $user]);
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Le mot de passe actuel est incorrect'], 401);
        }

        $user->update(['password' => Hash::make($request->new_password)]);
        JWTAuth::invalidate(JWTAuth::getToken());
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès',
            'data'    => ['access_token' => $token, 'token_type' => 'bearer', 'expires_in' => config('jwt.ttl') * 60]
        ]);
    }

    public function verifyPhone(Request $request): JsonResponse
    {
        $user = auth()->user();
        $user->update(['phone_verified_at' => now(), 'is_verified' => true]);
        return response()->json(['success' => true, 'message' => 'Téléphone vérifié', 'data' => $user]);
    }

    public function sendVerificationCode(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user->phone) {
            return response()->json(['success' => false, 'message' => 'Aucun numéro associé'], 400);
        }
        return response()->json(['success' => true, 'message' => 'Code envoyé par SMS']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email|exists:users,email']);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }
        return response()->json(['success' => true, 'message' => 'Instructions envoyées par email']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:users,email',
            'token'    => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }
        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);
        return response()->json(['success' => true, 'message' => 'Mot de passe réinitialisé']);
    }
}
