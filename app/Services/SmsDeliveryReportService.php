<?php

namespace App\Services;

use App\Models\Sent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SmscDeliveryReportService
{
    public function processDlr(
        string $messageId,
        string $status,
        ?string $errorCode = null,
        ?string $errorDescription = null,
        ?string $receivedAt = null
    ) {
        DB::beginTransaction();

        try {
            $message = Sent::where('message_id', $messageId)
                         ->orWhere('smsc_message_id', $messageId)
                         ->lockForUpdate()
                         ->first();

            if (!$message) {
                Log::warning("DLR received for unknown message ID: $messageId");
                DB::commit();
                return null;
            }

            $message->status = $this->mapStatus($status);
            $message->smsc_message_id = $messageId;
            $message->error_code = $errorCode;
            $message->error_message = $errorDescription;
            $message->done_date = $receivedAt ? Carbon::parse($receivedAt) : now();
            $message->save();

            DB::commit();
            return $message;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function mapStatus(string $status): string
    {
        return match(strtoupper($status)) {
            'DELIVRD' => 'delivered',
            'ACCEPTD' => 'submitted',
            'REJECTD', 'EXPIRED', 'DELETED', 'UNDELIV', 'UNKNOWN' => 'failed',
            default => 'failed'
        };
    }
}