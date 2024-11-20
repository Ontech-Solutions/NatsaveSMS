<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiKeyController extends Controller
{
    /**
     * Generate new API credentials
     */
    public function generateCredentials(Request $request)
    {
        $user = auth()->user();
        
        // Generate new API credentials
        $apiKey = 'key_' . Str::random(32);
        $apiSecret = 'secret_' . Str::random(32);

        // Update user with new credentials
        $user->update([
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API credentials generated successfully',
            'data' => [
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'note' => 'Please save these credentials securely. The secret key will not be shown again.'
            ]
        ]);
    }

    /**
     * Revoke existing API credentials
     */
    public function revokeCredentials(Request $request)
    {
        $user = auth()->user();
        
        $user->update([
            'api_key' => null,
            'api_secret' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API credentials revoked successfully'
        ]);
    }

    /**
     * Get current API key (but not secret)
     */
    public function getCurrentKey(Request $request)
    {
        $user = auth()->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'api_key' => $user->api_key,
                'created_at' => $user->updated_at
            ]
        ]);
    }
}