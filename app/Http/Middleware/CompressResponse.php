<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldCompress($request, $response)) {
            $content = $response->getContent();
            $compressed = gzencode($content, 5);

            if ($compressed !== false) {
                $response->setContent($compressed);
                $response->headers->set('Content-Encoding', 'gzip');
                $response->headers->set('Content-Length', (string) strlen($compressed));
                $response->headers->remove('Transfer-Encoding');
            }
        }

        return $response;
    }

    private function shouldCompress(Request $request, Response $response): bool
    {
        if (! str_contains($request->header('Accept-Encoding', ''), 'gzip')) {
            return false;
        }

        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');
        $compressible = ['application/json', 'text/html', 'text/plain', 'text/css', 'application/javascript'];

        foreach ($compressible as $type) {
            if (str_contains($contentType, $type)) {
                return strlen($response->getContent()) > 1024;
            }
        }

        return false;
    }
}
