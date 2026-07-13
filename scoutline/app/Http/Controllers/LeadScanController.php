<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LeadScanController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'location' => 'required|string',
        ]);

        $pythonUrl = env('FASTAPI_URL', 'http://127.0.0.1:8001');                      //gets the URL of the FastAPI service and the Google API key from the environment variables
        $apiKey = env('GOOGLE_API_KEY');

        if (empty($apiKey)) {                                                         //checks if the Google API key is not configured on the server and returns an error message to the user
            return back()->with('error', 'Google API key is not configured on the server.');
        }

        $response = Http::post("{$pythonUrl}/api/scan", [                            //sends a POST request to the FastAPI service with the category, location, and Google API key as parameters
            'category' => $request->category,
            'location' => $request->location,

            'api_key' => $apiKey,
        ]);

        if ($response->successful()) {                                              //checks if the HTTP request to the FastAPI service was successful and returns the response data to the user
            $responseData = $response->json();

            return back()->with([
                'success' => $responseData['message'] ?? 'Scan complete!',
                'results' => $responseData['places'] ?? [],
                'last_category' => $request->category,
                'last_location' => $request->location,
                
            ]);
        }

        return back()->with('error', 'Could not connect to Python service or the Google API failed.');   //returns an error message to the user if failed
    }
}