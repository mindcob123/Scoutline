from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
import httpx
import asyncio
import os
from dotenv import load_dotenv

load_dotenv()  # Load environment variables from .env file (e.g. GOOGLE_API_KEY, FASTAPI_URL)

app = FastAPI()  # Initialize the FastAPI application



# REQUEST MODELS
class ScanRequest(BaseModel): #request body that is sent by the laravel lead scan controller with category and location
    category: str = Field(..., min_length=1)
    location: str = Field(..., min_length=1)
    api_key: str
    # How many businesses the user asked for. Google's Text Search (New) API caps each
    # page at 20 results and returns at most ~60 total per query, so this is clamped
    # to that ceiling below.
    limit: int = Field(20, ge=1, le=60)


# ROUTES
@app.get("/")
def check_status():
    return {"status": "online", "service": "Scoutline Local Web Engine "} #checks if the service is online and returns a status message


@app.post("/api/scan")
#searches google places for businesses matching the given category and location, called by leadscancontroller whenever there's no valid cached recentsearch for the requested category/location
async def scan_places(request: ScanRequest): 
    api_key = request.api_key

    if not request.api_key:
        raise HTTPException(status_code=400, detail="Google API Key configuration is missing.")

    url = "https://places.googleapis.com/v1/places:searchText"
    headers = {
        "Content-Type": "application/json",
        "X-Goog-Api-Key": api_key,
        # Restrict the response to only the fields we actually use, to keep the Google API response minimal.
        # nextPageToken must be explicitly listed (it's a top-level field, not under "places.") or
        # Google won't include it and we'd have no way to fetch further pages.
        "X-Goog-FieldMask": "places.displayName,places.formattedAddress,places.location,"
                             "places.websiteUri,places.nationalPhoneNumber,places.primaryTypeDisplayName,"
                             "nextPageToken"
    }

    query_text = f"{request.category} in {request.location}"
    results = []
    page_token = None

    async with httpx.AsyncClient() as client:
        try:
            while len(results) < request.limit:
                payload = {
                    "textQuery": query_text,
                    "pageSize": min(20, request.limit - len(results)),
                }
                if page_token:
                    payload["pageToken"] = page_token

                response = await client.post(url, json=payload, headers=headers)
                response.raise_for_status()

                data = response.json()
                places = data.get("places", [])

                for place in places:
                    results.append({
                        "name": place.get("displayName", {}).get("text", "Unknown"),
                        "address": place.get("formattedAddress", "N/A"),
                        "phone": place.get("nationalPhoneNumber", "N/A"),
                        "website": place.get("websiteUri", "No Website Listed"),
                        "category": place.get("primaryTypeDisplayName", {}).get("text", request.category),
                    })
                    if len(results) >= request.limit:
                        break

                page_token = data.get("nextPageToken")

                # No more pages, or Google returned an empty page — stop rather than looping forever.
                if not page_token or not places:
                    break

                # Google needs a brief delay before a freshly issued pageToken becomes usable.
                await asyncio.sleep(2)

            return {"results": results}

        except httpx.HTTPStatusError as e:
            # Google responded, but with an error status (bad key, quota, etc.)
            try:
                error_detail = e.response.json()
            except Exception:
                error_detail = e.response.text

            print(f"--- GOOGLE API ERROR DETECTED ---")
            print(f"Status Code: {e.response.status_code}")
            print(f"Error Body: {error_detail}")
            print(f"---------------------------------")

            # This raises the specific error message to your logs/console
            raise HTTPException(
                status_code=e.response.status_code,
                detail=f"Google API Error: {error_detail}"
            )
        except httpx.RequestError as e:
            # Network-level failure — couldn't even reach Google (DNS, timeout, etc.)
            raise HTTPException(status_code=500, detail=f"Connection failure to Google API gateway: {str(e)}")