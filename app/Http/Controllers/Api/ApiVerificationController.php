<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiUser;
use App\Models\ApiLog;
use Illuminate\Http\Request;

class ApiVerificationController extends Controller
{
    public function verifyCredentials(Request $request)
    {
        try {
            // Get API credentials from headers
            $apiKey = $request->header('X-API-Key');
            $apiSecret = $request->header('X-API-Secret');

            // Check if credentials are provided
            if (!$apiKey || !$apiSecret) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'AUTH_FAILED',
                        'message' => 'API credentials are missing'
                    ]
                ], 401);
            }

            // Find API user
            $apiUser = ApiUser::where([
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ])->first();

            if (!$apiUser) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'AUTH_FAILED',
                        'message' => 'Invalid API credentials'
                    ]
                ], 401);
            }

            // Get usage statistics
            $todayUsage = $apiUser->getTodayUsageAttribute();
            $recentLogs = ApiLog::where('api_user_id', $apiUser->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            $response = [
                'success' => true,
                'data' => [
                    'user' => [
                        'name' => $apiUser->name,
                        'email' => $apiUser->email,
                        'is_active' => $apiUser->is_active,
                        'access_level' => $apiUser->access_level,
                        'created_at' => $apiUser->created_at,
                        'last_used_at' => $apiUser->last_used_at,
                    ],
                    'limits' => [
                        'daily_limit' => $apiUser->daily_limit,
                        'rate_limit' => $apiUser->rate_limit,
                        'today_usage' => $todayUsage,
                        'remaining_daily_limit' => $apiUser->daily_limit - $todayUsage,
                    ],
                    'security' => [
                        'allowed_ips' => $apiUser->allowed_ips,
                        'current_ip' => $request->ip(),
                        'is_ip_allowed' => $apiUser->isIpAllowed($request->ip()),
                    ],
                    'recent_activity' => $recentLogs->map(function($log) {
                        return [
                            'endpoint' => $log->endpoint,
                            'method' => $log->method,
                            'status_code' => $log->status_code,
                            'timestamp' => $log->created_at,
                            'ip_address' => $log->ip_address
                        ];
                    }),
                    'status' => [
                        'is_over_daily_limit' => $apiUser->isOverLimitAttribute,
                        'is_rate_limited' => !$apiUser->checkRateLimit(),
                        'key_age_days' => $apiUser->keyAgeInDays,
                    ]
                ]
            ];

            // Log this verification request
            ApiLog::create([
                'api_user_id' => $apiUser->id,
                'endpoint' => '/verify',
                'method' => $request->method(),
                'request_data' => json_encode(['verification_request' => true]),
                'response_data' => json_encode($response),
                'status_code' => 200,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'An error occurred while verifying credentials',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }
}