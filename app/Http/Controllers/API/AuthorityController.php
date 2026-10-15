<?php
// app/Http/Controllers/API/AuthorityController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Authority;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthorityController extends Controller
{
    /**
     * Display a listing of authorities
     */
    public function index(Request $request)
    {
        $query = Authority::query()->where('is_active', true);

        // ✅ Filtrer par type si demandé
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('name')->get(),
        ]);
    }
    /**
     * Store a newly created authority
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'acronym' => 'nullable|string|max:20',
            'type' => 'required|in:government,company,municipal,security,health,environment',
            'description' => 'nullable|string',
            'logo_path' => 'nullable|string',
            'website' => 'nullable|url',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'emergency_phone' => 'nullable|string|max:20',
            'api_endpoint' => 'nullable|url',
            'api_key' => 'nullable|string',
            'api_active' => 'boolean',
            'scope' => 'required|in:national,regional,municipal',
            'regions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $authority = Authority::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Authority created successfully',
            'data' => $authority
        ], 201);
    }

    /**
     * Display the specified authority
     */
    public function show(string $id)
    {
        $authority = Authority::with(['categories', 'externalAgents', 'reports'])->find($id);

        if (!$authority) {
            return response()->json([
                'success' => false,
                'message' => 'Authority not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $authority
        ]);
    }

    /**
     * Update the specified authority
     */
    public function update(Request $request, string $id)
    {
        $authority = Authority::find($id);

        if (!$authority) {
            return response()->json([
                'success' => false,
                'message' => 'Authority not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:150',
            'acronym' => 'nullable|string|max:20',
            'type' => 'sometimes|in:government,company,municipal,security,health,environment',
            'description' => 'nullable|string',
            'logo_path' => 'nullable|string',
            'website' => 'nullable|url',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'emergency_phone' => 'nullable|string|max:20',
            'api_endpoint' => 'nullable|url',
            'api_key' => 'nullable|string',
            'api_active' => 'boolean',
            'scope' => 'sometimes|in:national,regional,municipal',
            'regions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $authority->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Authority updated successfully',
            'data' => $authority->fresh()
        ]);
    }

    /**
     * Remove the specified authority
     */
    public function destroy(string $id)
    {
        $authority = Authority::find($id);

        if (!$authority) {
            return response()->json([
                'success' => false,
                'message' => 'Authority not found'
            ], 404);
        }

        $authority->delete();

        return response()->json([
            'success' => true,
            'message' => 'Authority deleted successfully'
        ]);
    }
}
