<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCertificatRequest;
use App\Http\Requests\UpdateCertificatRequest;
use App\Models\Certificat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Certificat Controller
 *
 * Manages certificate issuance and verification.
 */
class CertificatController extends Controller
{
    public function index(): JsonResponse
    {
        $certificats = Certificat::with('inscription.eleve')->paginate(15);

        return response()->json([
            'message' => 'Liste des certificats récupérée avec succès',
            'data' => $certificats
        ]);
    }

    public function store(StoreCertificatRequest $request): JsonResponse
    {
        $certificat = Certificat::create($request->validated());

        return response()->json([
            'message' => 'Certificat créé avec succès',
            'data' => $certificat->load('inscription.eleve')
        ], 201);
    }

    public function show(Certificat $certificat): JsonResponse
    {
        return response()->json([
            'data' => $certificat->load('inscription.eleve')
        ]);
    }

    public function update(UpdateCertificatRequest $request, Certificat $certificat): JsonResponse
    {
        $certificat->update($request->validated());

        return response()->json([
            'message' => 'Certificat mis à jour avec succès',
            'data' => $certificat->load('inscription.eleve')
        ]);
    }

    public function destroy(Certificat $certificat): JsonResponse
    {
        $certificat->delete();

        return response()->json([
            'message' => 'Certificat supprimé avec succès'
        ]);
    }

    public function issue(Certificat $certificat): JsonResponse
    {
        // Example logic for issuing a certificate
        return response()->json([
            'message' => 'Certificat émis avec succès',
            'data' => $certificat
        ]);
    }

    public function verify(string $numero_unique): JsonResponse
    {
        $certificat = Certificat::where('numero_unique', $numero_unique)->firstOrFail();

        return response()->json([
            'message' => 'Certificat vérifié avec succès',
            'data' => $certificat->load('inscription.eleve')
        ]);
    }
}
