<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsOutbox;
use App\Models\ApiUser;
use App\Models\BulkMessage;
use App\Models\ApiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SmsApiController extends Controller
{
    /**
     * Validate API credentials
     */
    private function validateApiCredentials($apiKey, $apiSecret)
    {
        $apiUser = ApiUser::where([
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'is_active' => true,
        ])->first();

        if (!$apiUser) {
            return false;
        }

        return $apiUser;
    }

    /**
     * Log API request
     */
    private function logApiRequest($apiUser, $endpoint, $request, $response, $statusCode)
    {
        ApiLog::create([
            'api_user_id' => $apiUser->id,
            'endpoint' => $endpoint,
            'method' => $request->method(),
            'request_data' => json_encode($request->all()),
            'response_data' => json_encode($response),
            'status_code' => $statusCode
        ]);
    }

    /**
     * Send single SMS
     */
    public function sendSingle(Request $request)
    {
        try {
            // Check API credentials
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

            $apiUser = $this->validateApiCredentials($apiKey, $apiSecret);

            if (!$apiUser) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'AUTH_FAILED',
                        'message' => 'Invalid API credentials'
                    ]
                ], 401);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'sender_id' => 'required|string|max:11',
                'recipient' => 'required|string|regex:/^[0-9]+$/',
                'message' => 'required|string|max:918',
                'schedule_at' => 'nullable|date|after:now'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Invalid request parameters',
                        'details' => $validator->errors()
                    ]
                ], 422);
            }

            // Create message ID
            $messageId = 'msg_' . Str::random(20);

            // Create SMS record
            $sms = SmsOutbox::create([
                'message_id' => $messageId,
                'source_addr' => $request->sender_id,
                'destination_addr' => $request->recipient,
                'message' => $request->message,
                'status' => 'pending',
                'user_id' => $apiUser->id,
                'scheduled_at' => $request->schedule_at,
                'message_type' => 'single',
                'recipient_count' => 1,
                'submitted_date' => now(),
            ]);

            $response = [
                'success' => true,
                'message' => 'SMS queued successfully',
                'data' => [
                    'message_id' => $messageId,
                    'status' => 'pending',
                    'scheduled_at' => $request->schedule_at
                ]
            ];

            // Log API request
            $this->logApiRequest($apiUser, '/messages/single', $request, $response, 200);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('SMS Send Error: ' . $e->getMessage(), [
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'An error occurred while processing your request',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }

    /**
     * Send bulk SMS
     */
    public function sendBulk(Request $request)
    {
        try {
            // Check API credentials
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

            $apiUser = $this->validateApiCredentials($apiKey, $apiSecret);

            if (!$apiUser) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'AUTH_FAILED',
                        'message' => 'Invalid API credentials'
                    ]
                ], 401);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'sender_id' => 'required|string|max:11',
                'recipients' => 'required|array|min:1',
                'recipients.*' => 'required|string|regex:/^[0-9]+$/',
                'message' => 'required|string|max:918',
                'schedule_at' => 'nullable|date|after:now'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Invalid request parameters',
                        'details' => $validator->errors()
                    ]
                ], 422);
            }

            // Create bulk message ID
            $bulkId = 'bulk_' . Str::random(20);
            $totalRecipients = count($request->recipients);

            // Create bulk message record
            $bulk = BulkMessage::create([
                'user_id' => $apiUser->id,
                'message' => $request->message,
                'recipients' => json_encode($request->recipients),
                'status' => 'pending',
                'scheduled_at' => $request->schedule_at,
                'total_recipients' => $totalRecipients,
                'message_id' => $bulkId
            ]);

            // Create individual SMS records
            foreach ($request->recipients as $recipient) {
                SmsOutbox::create([
                    'message_id' => 'msg_' . Str::random(20),
                    'source_addr' => $request->sender_id,
                    'destination_addr' => $recipient,
                    'message' => $request->message,
                    'status' => 'pending',
                    'user_id' => $apiUser->id,
                    'scheduled_at' => $request->schedule_at,
                    'message_type' => 'bulk',
                    'bulk_id' => $bulkId,
                    'submitted_date' => now(),
                ]);
            }

            $response = [
                'success' => true,
                'message' => 'Bulk SMS queued successfully',
                'data' => [
                    'bulk_id' => $bulkId,
                    'total_recipients' => $totalRecipients,
                    'status' => 'pending',
                    'scheduled_at' => $request->schedule_at
                ]
            ];

            // Log API request
            $this->logApiRequest($apiUser, '/messages/bulk', $request, $response, 200);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Bulk SMS Send Error: ' . $e->getMessage(), [
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'An error occurred while processing your request',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }

    /**
     * Check message status
     */
    public function checkStatus(Request $request, $messageId)
    {
        try {
            // Check API credentials
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

            $apiUser = $this->validateApiCredentials($apiKey, $apiSecret);

            if (!$apiUser) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'AUTH_FAILED',
                        'message' => 'Invalid API credentials'
                    ]
                ], 401);
            }

            $message = SmsOutbox::where('message_id', $messageId)
                ->where('user_id', $apiUser->id)
                ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'MESSAGE_NOT_FOUND',
                        'message' => 'Message not found or access denied'
                    ]
                ], 404);
            }

            $response = [
                'success' => true,
                'data' => [
                    'message_id' => $message->message_id,
                    'status' => $message->status,
                    'sent_at' => $message->submitted_date,
                    'delivered_at' => $message->done_date
                ]
            ];

            // Log API request
            $this->logApiRequest($apiUser, '/messages/status', $request, $response, 200);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Message Status Check Error: ' . $e->getMessage(), [
                'message_id' => $messageId
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'An error occurred while checking message status',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }
}