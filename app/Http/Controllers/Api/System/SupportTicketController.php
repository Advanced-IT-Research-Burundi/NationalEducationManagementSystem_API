<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use App\Models\TicketSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Support Ticket Controller
 */
class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TicketSupport::query()->with(['demandeur', 'assignee']);

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('priorite')) {
            $query->where('priorite', $request->priorite);
        }

        if ($request->has('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        $perPage = $request->get('per_page', 20);
        $tickets = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'sujet' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priorite' => ['required', 'in:BASSE,NORMALE,HAUTE,URGENTE'],
            'categorie' => ['required', 'in:TECHNIQUE,FONCTIONNELLE,DEMANDE,AUTRE'],
        ]);

        $data = $request->all();
        $data['demandeur_id'] = auth()->id();

        $ticket = TicketSupport::create($data);
        $ticket->load('demandeur');

        return response()->json([
            'message' => 'Ticket créé avec succès',
            'data' => $ticket,
        ], 201);
    }

    public function show(TicketSupport $ticket): JsonResponse
    {
        $ticket->load(['demandeur', 'assignee']);

        return response()->json(['data' => $ticket]);
    }

    public function update(Request $request, TicketSupport $ticket): JsonResponse
    {
        $request->validate([
            'sujet' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'priorite' => ['sometimes', 'in:BASSE,NORMALE,HAUTE,URGENTE'],
        ]);

        $ticket->update($request->all());

        return response()->json([
            'message' => 'Ticket mis à jour',
            'data' => $ticket,
        ]);
    }

    public function destroy(TicketSupport $ticket): JsonResponse
    {
        $ticket->delete();

        return response()->json(['message' => 'Ticket supprimé']);
    }

    public function respond(Request $request, TicketSupport $ticket): JsonResponse
    {
        $request->validate([
            'reponse' => ['required', 'string'],
        ]);

        $ticket->update([
            'reponse' => $request->reponse,
            'statut' => 'RESOLU',
            'date_resolution' => now(),
        ]);

        return response()->json([
            'message' => 'Réponse ajoutée',
            'data' => $ticket,
        ]);
    }

    public function assign(Request $request, TicketSupport $ticket): JsonResponse
    {
        $request->validate([
            'assignee_id' => ['required', 'exists:users,id'],
        ]);

        $ticket->update([
            'assignee_id' => $request->assignee_id,
            'statut' => 'EN_COURS',
        ]);

        return response()->json([
            'message' => 'Ticket assigné',
            'data' => $ticket->load('assignee'),
        ]);
    }

    public function close(TicketSupport $ticket): JsonResponse
    {
        $ticket->update([
            'statut' => 'FERME',
            'date_resolution' => now(),
        ]);

        return response()->json(['message' => 'Ticket fermé']);
    }

    public function reopen(TicketSupport $ticket): JsonResponse
    {
        $ticket->update(['statut' => 'OUVERT']);

        return response()->json(['message' => 'Ticket réouvert']);
    }

    public function open(): JsonResponse
    {
        $tickets = TicketSupport::query()
            ->where('statut', 'OUVERT')
            ->with('demandeur')
            ->orderBy('priorite')
            ->paginate(20);

        return response()->json($tickets);
    }

    public function byStatus(string $status): JsonResponse
    {
        $tickets = TicketSupport::query()
            ->where('statut', strtoupper($status))
            ->with(['demandeur', 'assignee'])
            ->paginate(20);

        return response()->json($tickets);
    }
}
