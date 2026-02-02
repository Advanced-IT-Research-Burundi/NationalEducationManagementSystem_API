<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Certificate Controller
 */
class CertificateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Certificates - pending implementation', 'data' => []], 501);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Create certificate - pending implementation'], 501);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['message' => 'Show certificate - pending implementation'], 501);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Update certificate - pending implementation'], 501);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Delete certificate - pending implementation'], 501);
    }

    public function issue(string $id): JsonResponse
    {
        return response()->json(['message' => 'Issue certificate - pending implementation'], 501);
    }

    public function verify(string $id): JsonResponse
    {
        return response()->json(['message' => 'Verify certificate - pending implementation'], 501);
    }

    public function download(string $id): JsonResponse
    {
        return response()->json(['message' => 'Download certificate - pending implementation'], 501);
    }
}
