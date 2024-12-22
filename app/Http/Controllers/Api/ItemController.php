<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    /**
     * Get all items
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $items = Item::with('parcel')->get();

            return response()->json([
                'status' => 'success',
                'data' => $items
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch items', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific item
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $item = Item::with('parcel')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $item
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch item', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'item_id' => $id
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get items for a specific parcel
     *
     * @param int $parcelId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByParcel($parcelId)
    {
        try {
            $items = Item::with('parcel')
                ->where('parcel_id', $parcelId)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $items
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch items for parcel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'parcel_id' => $parcelId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch items: ' . $e->getMessage()
            ], 500);
        }
    }
}
