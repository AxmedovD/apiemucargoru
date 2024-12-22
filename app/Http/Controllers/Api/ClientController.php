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
     * Get all clients
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $clients = Client::all();
            
            return response()->json([
                'status' => 'success',
                'data' => $clients
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch clients', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
                'client_id' => 'required|integer|min:1|max:9999999|unique:client',
                'name' => 'required|string|max:50',
                'contact' => 'required|string|max:50',
                'country_code' => 'required|string|max:5',
                'address' => 'required|string|max:100',
                'url' => 'required|string|max:50|url',
                'webhook' => 'nullable|string|max:255|url'
            ]);

            Log::info('Validation passed', ['validated_data' => $validated]);

            // Generate a unique token
            $token = $this->generateUniqueToken();
            Log::info('Token generated', ['token' => $token]);

            // Create client with explicit values
            $client = new Client();
            $client->client_id = $validated['client_id'];
            $client->name = $validated['name'];
            $client->contact = $validated['contact'];
            $client->country_code = $validated['country_code'];
            $client->address = $validated['address'];
            $client->url = $validated['url'];
            $client->webhook = $validated['webhook'] ?? null;
            $client->token = $token;
            $client->timestamps = false;
            $client->save();

            Log::info('Client created successfully', ['client' => $client->toArray()]);
            
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Client added successfully',
                'data' => $client
            ], 201);

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
                'message' => 'Failed to add client: ' . $e->getMessage(),
                'error' => $e->getMessage()
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
