from fastapi import FastAPI
from pydantic import BaseModel
import requests

app = FastAPI()

class ScanRequest(BaseModel):
    category: str
    location: str
    api_key: str = ""

# Geoapify ke fixed category codes hain (free-text nahi chalta).
# Yahan common user-facing categories ko Geoapify codes se map kar rahe hain.
# Full list: https://apidocs.geoapify.com/docs/places/#categories
CATEGORY_MAP = {
    "restaurant": "catering.restaurant",
    "restaurants": "catering.restaurant",
    "cafe": "catering.cafe",
    "cafes": "catering.cafe",
    "coffee shop": "catering.cafe.coffee_shop",
    "bakery": "commercial.food_and_drink.bakery",
    "bakeries": "commercial.food_and_drink.bakery",
    "gym": "sport.fitness",
    "gyms": "sport.fitness",
    "fitness": "sport.fitness",
    "salon": "service.beauty.hairdresser",
    "salons": "service.beauty.hairdresser",
    "barber": "service.beauty.hairdresser",
    "hairdresser": "service.beauty.hairdresser",
    "hospital": "healthcare.hospital",
    "hospitals": "healthcare.hospital",
    "clinic": "healthcare.clinic_or_praxis",
    "pharmacy": "healthcare.pharmacy",
    "school": "education.school",
    "schools": "education.school",
    "hotel": "accommodation.hotel",
    "hotels": "accommodation.hotel",
    "bank": "service.financial.bank",
    "banks": "service.financial.bank",
    "logistics": "office.logistics",
    "logistics company": "office.logistics",
    "logistics companies": "office.logistics",
    "courier": "office.logistics",
    "shipping": "office.logistics",
    "warehouse": "office.logistics",
    "electronics": "commercial.elektronics",  # Note: Geoapify's actual category code has this spelling
    "supermarket": "commercial.supermarket",
    "grocery": "commercial.supermarket",
    "clothing": "commercial.clothing",
    "clothing store": "commercial.clothing",
    "real estate": "office.estate_agent",
}

DEFAULT_CATEGORY = "commercial"  # fallback jab koi mapping na mile


def resolve_category(user_category: str) -> str:
    key = user_category.strip().lower()
    return CATEGORY_MAP.get(key, DEFAULT_CATEGORY)


def geocode_location(location: str, api_key: str):
    """Location string ko lat/lon mein convert karta hai Geoapify Geocoding API se."""
    url = "https://api.geoapify.com/v1/geocode/search"
    params = {"text": location, "limit": 1, "apiKey": api_key}
    resp = requests.get(url, params=params, timeout=10)
    resp.raise_for_status()
    data = resp.json()

    features = data.get("features", [])
    if not features:
        return None

    coords = features[0]["geometry"]["coordinates"]  # [lon, lat]
    return coords[1], coords[0]  # (lat, lon)


def fetch_places(lat: float, lon: float, category_code: str, api_key: str, radius_m: int = 8000, limit: int = 25):
    """Geoapify Places API se category + radius ke hisaab se businesses laata hai."""
    url = "https://api.geoapify.com/v2/places"
    params = {
        "categories": category_code,
        "filter": f"circle:{lon},{lat},{radius_m}",
        "bias": f"proximity:{lon},{lat}",
        "limit": limit,
        "apiKey": api_key,
    }
    resp = requests.get(url, params=params, timeout=15)
    resp.raise_for_status()
    return resp.json().get("features", [])


@app.get("/")
def check_status():
    return {"status": "online", "service": "Scoutline Local Web Engine (Geoapify)"}


@app.post("/api/scan")
def process_lead_scan(request: ScanRequest):
    print(f"Geoapify scan triggered: '{request.category}' in '{request.location}'")

    if not request.api_key:
        return {"status": "error", "message": "Geoapify API key missing in request.", "results": []}

    try:
        coords = geocode_location(request.location, request.api_key)
        if coords is None:
            return {"status": "error", "message": f"Could not resolve location '{request.location}'.", "results": []}

        lat, lon = coords
        category_code = resolve_category(request.category)
        features = fetch_places(lat, lon, category_code, request.api_key)

        businesses = []
        for feature in features:
            props = feature.get("properties", {})

            name = props.get("name") or "Unnamed Business"
            address = props.get("formatted") or props.get("address_line2") or f"Located in {request.location}"
            phone = props.get("contact", {}).get("phone") or props.get("datasource", {}).get("raw", {}).get("phone") or "No Phone Listed"
            website = props.get("website") or props.get("contact", {}).get("website") or "No Website Listed"

            businesses.append({
                "name": name,
                "address": address,
                "category": request.category.title(),
                "phone": phone,
                "website": website,
            })

        print(f"Geoapify returned {len(businesses)} leads.")
        return {
            "status": "success",
            "message": f"Successfully found {len(businesses)} leads matching '{request.category}' in '{request.location}'.",
            "results": businesses,
        }

    except requests.exceptions.HTTPError as e:
        status = e.response.status_code if e.response is not None else None
        body = e.response.text[:300] if e.response is not None else ""
        print(f"Geoapify HTTP error ({status}): {body}")

        if status == 400:
            message = "Invalid request — likely an unsupported category code sent to Geoapify."
        elif status == 401 or status == 403:
            message = "Geoapify API key is invalid or unauthorized."
        elif status == 429:
            message = "Geoapify daily quota exceeded (free plan: 3000 credits/day)."
        else:
            message = f"Geoapify request failed (status {status})."

        return {"status": "error", "message": message, "results": []}
    except Exception as e:
        print(f"Critical execution error: {str(e)}")
        return {"status": "error", "message": f"Failed to execute lead scan: {str(e)}", "results": []}