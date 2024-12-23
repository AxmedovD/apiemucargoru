<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    /**
     * Get all clients with pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20); // Default 20 items per page
            $perPage = min(max($perPage, 1), 100); // Ensure per_page is between 1 and 100
            
            $clients = Client::paginate($perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => $clients->items(),
                'pagination' => [
                    'current_page' => $clients->currentPage(),
                    'per_page' => $clients->perPage(),
                    'total' => $clients->total(),
                    'last_page' => $clients->lastPage(),
                    'from' => $clients->firstItem(),
                    'to' => $clients->lastItem(),
                    'next_page_url' => $clients->nextPageUrl(),
                    'prev_page_url' => $clients->previousPageUrl(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch clients', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch clients: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific client by ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $client = Client::where('client_id', $id)->first();
            
            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $client
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'client_id' => $id
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search clients by ID or name
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('q');
            $perPage = $request->input('per_page', 20);
            $perPage = min(max($perPage, 1), 100);

            if (empty($query)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Search query is required'
                ], 400);
            }

            $clients = Client::where(function($q) use ($query) {
                // If query is numeric, search by client_id
                if (is_numeric($query)) {
                    $q->where('client_id', $query);
                }
                
                // Also search by name (case-insensitive)
                $q->orWhere('name', 'LIKE', '%' . $query . '%');
            })
            ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $clients->items(),
                'pagination' => [
                    'current_page' => $clients->currentPage(),
                    'per_page' => $clients->perPage(),
                    'total' => $clients->total(),
                    'last_page' => $clients->lastPage(),
                    'from' => $clients->firstItem(),
                    'to' => $clients->lastItem(),
                    'next_page_url' => $clients->nextPageUrl(),
                    'prev_page_url' => $clients->previousPageUrl(),
                ],
                'query' => $query
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to search clients', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'query' => $query,
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to search clients: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a new client ID
     * Starts from 300 and skips random 1-9 numbers from last ID
     *
     * @return int
     */
    private function generateClientId()
    {
        $lastClient = Client::orderBy('client_id', 'desc')->first();
        $startId = 300;
        
        if (!$lastClient) {
            return $startId;
        }

        $lastId = $lastClient->client_id;
        $skipCount = rand(1, 9); // Random skip between 1-9
        
        return $lastId + $skipCount;
    }

    /**
     * Add a new client
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            
            Log::info('Client creation attempt', ['request' => $request->all()]);

            $validated = $request->validate([
                'name' => 'required|string|max:50',
                'contact' => 'required|string|max:50',
                'country_code' => 'required|string|max:5',
                'address' => 'required|string|max:100',
                'url' => 'required|string|max:50|url',
                'webhook' => 'nullable|string|max:255|url'
            ]);

            // Generate client_id
            $clientId = $this->generateClientId();
            Log::info('Generated client_id', ['client_id' => $clientId]);

            // Generate a unique token
            $token = $this->generateUniqueToken();
            Log::info('Token generated', ['token' => $token]);

            // Create client with explicit values
            $client = new Client();
            $client->client_id = $clientId;
            $client->name = $validated['name'];
            $client->contact = $validated['contact'];
            $client->country_code = $validated['country_code'];
            $client->address = $validated['address'];
            $client->url = $validated['url'];
            $client->webhook = $validated['webhook'] ?? null;
            $client->token = $token;
            $client->timestamps = false;
            $client->save();

            Log::info('Client created successfully', [
                'client_id' => $clientId,
                'token_hash' => hash('sha256', $token) // Log hashed version for security
            ]);
            
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Client created successfully',
                'data' => $client
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing client
     *
     * @param Request $request
     * @param int $client_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $client_id)
    {
        try {
            DB::beginTransaction();
            
            $client = Client::where('client_id', $client_id)->first();
            
            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client not found'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:50',
                'contact' => 'sometimes|required|string|max:50',
                'country_code' => 'sometimes|required|string|max:5',
                'address' => 'sometimes|required|string|max:100',
                'url' => 'sometimes|required|string|max:50|url',
                'webhook' => 'nullable|string|max:255|url'
            ]);

            Log::info('Updating client', [
                'client_id' => $client_id,
                'current_data' => $client->toArray(),
                'new_data' => $validated
            ]);

            // Update only the fields that were provided
            $client->fill($validated);
            $client->timestamps = false;
            $client->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Client updated successfully',
                'data' => $client
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'client_id' => $client_id,
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update client: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Regenerate client token
     *
     * @param int $client_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function regenerateToken($client_id)
    {
        try {
            DB::beginTransaction();
            
            $client = Client::where('client_id', $client_id)->first();
            
            if (!$client) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client not found'
                ], 404);
            }

            // Store old token for logging
            $oldToken = $client->token;

            // Generate new token
            $newToken = $this->generateUniqueToken();
            
            Log::info('Regenerating client token', [
                'client_id' => $client_id,
                'old_token_hash' => hash('sha256', $oldToken) // Log hashed version for security
            ]);

            // Update token
            $client->token = $newToken;
            $client->timestamps = false;
            $client->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Client token regenerated successfully',
                'data' => [
                    'client_id' => $client->client_id,
                    'token' => $newToken
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to regenerate client token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'client_id' => $client_id
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to regenerate client token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a unique token for the client
     *
     * @return string
     */
    private function generateUniqueToken(): string
    {
        $attempts = 0;
        do {
            $token = Str::random(20);
            $exists = Client::where('token', $token)->exists();
            $attempts++;

            if ($attempts > 5) {
                Log::error('Failed to generate unique token after 5 attempts');
                throw new \RuntimeException('Unable to generate unique token');
            }
        } while ($exists);

        return $token;
    }
}
