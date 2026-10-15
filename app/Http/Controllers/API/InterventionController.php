<?php
// app/Http/Controllers/API/InterventionController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Intervention;
use Illuminate\Http\Request;

class InterventionController extends Controller
{
    // GET /interventions/mine
    public function mine(Request $request)
    {
        $interventions = Intervention::where('agent_id', $request->user()->id)
            ->with(['report', 'authority'])
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $interventions]);
    }

    // GET /interventions
    public function index(Request $request)
    {
        $query = Intervention::with(['report', 'authority', 'agent']);

        if ($request->has('agent_id'))  $query->where('agent_id',  $request->agent_id);
        if ($request->has('report_id')) $query->where('report_id', $request->report_id);
        if ($request->has('status'))    $query->where('status',    $request->status);

        return response()->json(['success' => true, 'data' => $query->latest()->get()]);
    }

    // GET /interventions/{id}
    public function show($id)
    {
        $intervention = Intervention::with(['report', 'authority', 'agent'])->find($id);
        if (!$intervention) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $intervention]);
    }

    // POST /interventions
    public function store(Request $request)
    {
        $intervention = Intervention::create([
            'agent_id'     => $request->user()->id,
            'report_id'    => $request->report_id,
            'authority_id' => $request->authority_id,
            'notes'        => $request->notes,
            'status'       => 'pending',
        ]);
        return response()->json(['success' => true, 'data' => $intervention], 201);
    }

    // PUT /interventions/{id}
    public function update(Request $request, $id)
    {
        $intervention = Intervention::find($id);
        if (!$intervention) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        $intervention->update($request->only(['status', 'notes', 'started_at', 'completed_at']));
        return response()->json(['success' => true, 'data' => $intervention]);
    }

    // PUT /interventions/{id}/status
    public function updateStatus(Request $request, $id)
    {
        $intervention = Intervention::find($id);
        if (!$intervention) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        $intervention->update([
            'status' => $request->status,
            'notes'  => $request->notes ?? $intervention->notes,
        ]);
        return response()->json(['success' => true, 'data' => $intervention]);
    }

    // POST /interventions/{id}/complete
    public function complete(Request $request, $id)
    {
        $intervention = Intervention::find($id);
        if (!$intervention) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        $intervention->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'notes'        => $request->notes ?? $intervention->notes,
        ]);
        return response()->json(['success' => true, 'data' => $intervention]);
    }

    // DELETE /interventions/{id}
    public function destroy($id)
    {
        $intervention = Intervention::find($id);
        if (!$intervention) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        $intervention->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    }
}
