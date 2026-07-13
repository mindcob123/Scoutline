from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import requests
import os
from dotenv import load_dotenv
from pydantic import BaseModel
from enrichment import router as enrichment_router

load_dotenv()                                                             # Load environment variables from .env file

app = FastAPI()                                                           # Initialize FastAPI application

app.include_router(enrichment_router)                                     #tells fastapi to hook up yhe routes from erichment.py

GOOGLE_API_KEY = os.getenv("GOOGLE_API_KEY")                              # Retrieve Google API Key from environment variables

class ScanRequest(BaseModel):                                             # Define the request model for scanning places
    category: str       
    location: str
    api_key: str 

@app.get("/")                                                              # Define a simple root endpoint to check the status of the service
def check_status():
    return {"status": "online", "service": "Scoutline Local Web Engine (Geoapify)"}

@app.post("/api/scan")                                                      # Define the endpoint for scanning places using Google Places API
async def scan_places(request: ScanRequest):
    api_key=request.api_key 

    if not request.api_key:                                                   # If the API key is not provided in the request, use the one from environment variables
        raise HTTPException(status_code=400, detail="Google API Key configuration is missing.")
    
    url = "https://places.googleapis.com/v1/places:searchText"                            # Define the Google Places API endpoint for text search     
    headers = {
        "Content-Type": "application/json",
        "X-Goog-Api-Key": api_key,
        "X-Goog-FieldMask": "places.displayName,places.formattedAddress,places.location,places.websiteUri"   # Specify the fields to be returned in the response
    }

    query_text = f"{request.category} in {request.location}"                               # Construct the query text for the Google Places API request based on the category and location provided in the request
    payload = {
        "textQuery": query_text
    }

    try:                                                                                    # Make a POST request to the Google Places API with the constructed payload and headers
        response = requests.post(url, json=payload, headers=headers)                      
        
        if response.status_code != 200:                                                      # If the response status code is not 200 (OK), raise an HTTPException 
            raise HTTPException(status_code=response.status_code, detail=f"Google API request failed with status code {response.status_code}: {response.text}")
            
        return response.json()                                                               # Return the JSON response from the Google Places API if the request is successful
        
    except requests.exceptions.RequestException as e:                                        # If there is a connection failure or any other request exception, raise an HTTPException with a 500 status code and the error details
        raise HTTPException(status_code=500, detail=f"Connection failure to Google API gateway: {str(e)}")
