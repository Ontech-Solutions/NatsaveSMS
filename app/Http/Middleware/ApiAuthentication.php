<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log for debugging
        Log::info('ApiAuthentication middleware executed', [
            'api_key_present' => $request->hasHeader('X-API-Key'),
            'api_secret_present' => $request->hasHeader('X-API-Secret')
        ]);

        $apiKey = $request->header('X-API-Key');
        $apiSecret = $request->header('X-API-Secret');

        if (!$apiKey || !$apiSecret) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_FAILED',
                    'message' => 'API credentials are missing'
                ]
            ], 401);
        }

        return $next($request);
    }
}