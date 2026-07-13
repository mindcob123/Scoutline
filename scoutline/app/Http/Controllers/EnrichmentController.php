<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class EnrichmentController extends Controller
{
    public function enrich(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string',                            //takes the company name as input from the user
            'domain' => 'nullable|string',                                  //takes the domain as input from the user, but it is optional
        ]);

        $pythonUrl = env('FASTAPI_URL', 'http://127.0.0.1:8001');          //gets the URL of the FastAPI service from the env and Apollo API key
        $apolloApiKey = env('APOLLO_API_KEY');                             

        if (empty($apolloApiKey)) {                                         // Fail clearly instead of silently sending an empty/invalid key.
            return response()->json([
                'error' => 'Apollo API key is not configured on the server.'], 500);
        }
        try{                                                                 //try block to catch any exceptions that may occur during the HTTP request to the FastAPI service
            $response = Http::post("{$pythonUrl}/enrich", [
                'company_name' => $request->company_name,
                'domain' => $request->domain,
                'api_key' => $apolloApiKey,
            ]);

            if ($response->failed()) {                                        //checks if the HTTP request to the FastAPI service failed
                Log::error('Apollo API request failed'. $response->body());
                return response()->json([
                    'error' => 'Failed to fetch details from enrichment engine.',], 500);
            }  
           return response()->json($response->json());                        //returns the response from the FastAPI service as a JSON response to the client

        } catch (\Exception $e) {                                             //catches any exceptions that may occur during the HTTP request to the FastAPI service
            Log::error('Failed to connect to Python service for enrichment: ' . $e->getMessage());
            return response()->json([
                'error' => 'Enrichment service connection timeout.'], 500);
        }

    }
}