<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ParcelController extends Controller
{
    /**
     * Get all parcels with their items
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $parcels = Parcel::with(['items', 'client', 'receiver'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $parcels
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch parcels', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch parcels: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific parcel with its items
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $parcel = Parcel::with(['items', 'client', 'receiver'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $parcel
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch parcel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'parcel_id' => $id
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch parcel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get parcels for a specific client
     *
     * @param int $clientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByClient($clientId)
    {
        try {
            $parcels = Parcel::with(['items', 'client', 'receiver'])
                ->where('client_id', $clientId)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $parcels
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch parcels for client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'client_id' => $clientId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch parcels: ' . $e->getMessage()
            ], 500);
        }
    }
}
