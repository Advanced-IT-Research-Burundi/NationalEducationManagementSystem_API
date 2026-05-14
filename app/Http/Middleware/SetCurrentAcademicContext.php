<?php

namespace App\Http\Middleware;

use App\Services\CurrentAcademicContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentAcademicContext
{
    public function __construct(
        private readonly CurrentAcademicContextService $contextService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->contextService->syncCurrentTrimestre();

        return $next($request);
    }
}
