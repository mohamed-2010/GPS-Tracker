<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockInvalidRequests
{
    /**
     * Block HTTP CONNECT proxy requests and invalid hosts
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Block HTTP CONNECT method (proxy attempts)
        if ($request->getMethod() === 'CONNECT') {
            logger()->warning('Blocked CONNECT proxy attempt', [
                'ip' => $request->ip(),
                'uri' => $request->getRequestUri(),
            ]);
            
            return response('Method Not Allowed', 405);
        }

        // Block requests with suspicious hosts containing URLs
        $host = $request->header('Host', '');
        
        if (str_contains($host, 'http://') || str_contains($host, 'https://')) {
            logger()->warning('Blocked request with invalid host', [
                'ip' => $request->ip(),
                'host' => $host,
            ]);
            
            return response('Bad Request', 400);
        }

        return $next($request);
    }
}
