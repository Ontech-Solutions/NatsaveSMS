<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsOutbox;
use App\Models\BulkMessage;
use App\Models\ApiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmsController extends Controller
{
    /**
     * Send a single SMS
     */
    public function sendSingle(Request $request)
    {
        try {
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

            // Create SMS record
            $sms = SmsOutbox::create([
                'message_id' => uniqid('msg_'),
                'source_addr' => $request->sender_id,
                'destination_addr' => $request->recipient,
                'message' => $request->message,
                'status' => 'pending',
                'user_id' => auth()->id(),
                'scheduled_at' => $request->schedule_at,
                'message_type' => 'single',
                'recipient_count' => 1
            ]);

            // Log API request
            ApiLog::create([
                'api_user_id' => auth()->id(),
                'endpoint' => '/messages/single',
                'request_data' => json_encode($request->all()),
                'response_data' => json_encode(['message_id' => $sms->message_id]),
                'status_code' => 200
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SMS queued successfully',
                'data' => [
                    'message_id' => $sms->message_id,
                    'status' => 'queued',
                    'scheduled_at' => $request->schedule_at
                ]
            ]);

        } catch (\Exception $e) {
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

            // Create bulk message record
            $bulkId = uniqid('bulk_');
            $totalRecipients = count($request->recipients);

            $bulk = BulkMessage::create([
                'user_id' => auth()->id(),
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
                    'message_id' => uniqid('msg_'),
                    'source_addr' => $request->sender_id,
                    'destination_addr' => $recipient,
                    'message' => $request->message,
                    'status' => 'pending',
                    'user_id' => auth()->id(),
                    'scheduled_at' => $request->schedule_at,
                    'message_type' => 'bulk',
                    'bulk_id' => $bulkId
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk SMS queued successfully',
                'data' => [
                    'bulk_id' => $bulkId,
                    'total_recipients' => $totalRecipients,
                    'status' => 'queued',
                    'scheduled_at' => $request->schedule_at
                ]
            ]);

        } catch (\Exception $e) {
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
    public function checkStatus($messageId)
    {
        try {
            $message = SmsOutbox::where('message_id', $messageId)->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'message_id' => $message->message_id,
                    'status' => $message->status,
                    'sent_at' => $message->submitted_date,
                    'delivered_at' => $message->done_date
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MESSAGE_NOT_FOUND',
                    'message' => 'Message not found'
                ]
            ], 404);
        }
    }
}