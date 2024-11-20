<?php

namespace App\Http\Controllers;

use App\Models\Sent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SmscDeliveryReportController extends Controller
{
    protected const STATUS_MAPPINGS = [
        'DELIVRD' => 'delivered',
        'REJECTD' => 'failed',
        'EXPIRED' => 'failed',
        'DELETED' => 'failed',
        'UNDELIV' => 'failed',
        'ACCEPTD' => 'submitted',
        'UNKNOWN' => 'failed',
    ];

    public function handle(Request $request)
    {
        Log::info('DLR Received', $request->all());

        try {
            // Validate the request
            $validated = $request->validate([
                'message_id' => 'required|string',
                'status' => 'required|string',
                'error_code' => 'nullable|string',
                'error_description' => 'nullable|string',
                'received_at' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Find the message
            $message = Sent::where('message_id', $validated['message_id'])
                         ->orWhere('smsc_message_id', $validated['message_id'])
                         ->lockForUpdate()
                         ->first();

            if (!$message) {
                Log::warning("DLR received for unknown message ID: {$validated['message_id']}");
                DB::commit();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found',
                ], 404);
            }

            // Map the status
            $mappedStatus = self::STATUS_MAPPINGS[strtoupper($validated['status'])] ?? 'failed';

            // Update the message
            $message->status = $mappedStatus;
            $message->smsc_message_id = $validated['message_id'];
            $message->error_code = $validated['error_code'];
            $message->error_message = $validated['error_description'];
            $message->done_date = $validated['received_at'] ? 
                Carbon::parse($validated['received_at']) : now();

            // Handle failed messages
            if ($mappedStatus === 'failed') {
                $this->handleFailedMessage($message);
            }

            $message->save();
            DB::commit();

            // Log successful processing
            Log::info('DLR Processed Successfully', [
                'message_id' => $validated['message_id'],
                'status' => $mappedStatus,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery report processed successfully',
                'data' => [
                    'message_id' => $message->message_id,
                    'status' => $message->status,
                    'processed_at' => now()->toDateTimeString(),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('DLR Processing Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process delivery report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    protected function handleFailedMessage(Sent $message)
    {
        // Increment retry count if less than max attempts
        if ($message->retry_count < 3) {
            $message->retry_count += 1;
            $message->last_retry_at = now();
        }
    }
}