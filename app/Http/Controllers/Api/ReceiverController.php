<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receiver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReceiverController extends Controller
{
    /**
     * Get all receivers with their parcels
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $receivers = Receiver::with('parcels')->get();

            return response()->json([
                'status' => 'success',
                'data' => $receivers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch receivers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch receivers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific receiver with their parcels
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $receiver = Receiver::with('parcels')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $receiver
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch receiver', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'receiver_id' => $id
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch receiver: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search receivers by name, phone, email, or passport
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $query = Receiver::query();

            if ($request->has('search')) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('phone_nums', 'like', "%{$searchTerm}%")
                      ->orWhere('email', 'like', "%{$searchTerm}%")
                      ->orWhere('passport_id', 'like', "%{$searchTerm}%");
                });
            }

            if ($request->has('inn')) {
                $query->where('inn', $request->inn);
            }

            $receivers = $query->with('parcels')->get();

            return response()->json([
                'status' => 'success',
                'data' => $receivers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to search receivers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'search_params' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to search receivers: ' . $e->getMessage()
            ], 500);
        }
    }
}
