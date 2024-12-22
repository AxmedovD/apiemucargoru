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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20); // Default 20 items per page
            $perPage = min(max($perPage, 1), 100); // Ensure per_page is between 1 and 100
            
            $parcels = Parcel::with(['items', 'client', 'receiver'])
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $parcels->items(),
                'pagination' => [
                    'current_page' => $parcels->currentPage(),
                    'per_page' => $parcels->perPage(),
                    'total' => $parcels->total(),
                    'last_page' => $parcels->lastPage(),
                    'from' => $parcels->firstItem(),
                    'to' => $parcels->lastItem(),
                    'next_page_url' => $parcels->nextPageUrl(),
                    'prev_page_url' => $parcels->previousPageUrl(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch parcels', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
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
