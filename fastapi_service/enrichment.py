import requests
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from typing import Optional

router = APIRouter()                                                #split applications into seperate files

class EnrichRequest(BaseModel):                              
    company_name: str
    domain: Optional[str] = None
    api_key: str

@router.post("/enrich")                                             #define the route
async def enrich_company_staff(request: EnrichRequest):             #matches the structural rules you set up in EnrichRequest model
    if not request.api_key:                                         #checks if the api_key string is empty.
        raise HTTPException(status_code=400, detail="Apollo API Key parameter is missing.")
    url = "https://api.apollo.io/v1/people/search"                  #Apollo's specialized lookup engine used to search their global database for people
    headers = {                                                     #network to fetch fresh data using the key
        "Cache-Control": "no-cache",
        "Content-Type": "application/json",
        "X-Api-Key": request.api_key
    }
    payload = {                                                     #builds the search criteria body for Apollo using smart fallback logic
        "q_organization_domains": request.domain if request.domain else None,
        "organization_names": [request.company_name] if not request.domain else [],
        "person_titles": ["ceo", "owner", "founder", "manager", "director", "vice president"],
        "page": 1,
        "per_page": 10                                               #maximum of 10 people per click to save your Apollo credit quota.
    }
    try:                                                             #launches POST req to apollo with payload and secure headers
        response = requests.post(url, json=payload, headers=headers)
        if response.status_code != 200:                              #Checks if things went wrong on Apollo's end
            raise HTTPException(status_code=response.status_code, detail=response.json())
            
        raw_data = response.json()                                   #converts Apollo's raw text response into an organized Python dictionary
        processed_employees = []
        for person in raw_data.get("people", []):                    #Apollo sends back massive packets of messy data for each individual to inspect each person
            processed_employees.append({                              #data filter
                "name": person.get("name"),
                "title": person.get("title"),
                "email": person.get("email", "N/A"),
                "contact_number": person.get("sanitized_phone", "N/A")
            })
            
        return {"company": request.company_name, "employees": processed_employees}
        
    except requests.exceptions.RequestException as e:               
        raise HTTPException(status_code=500, detail=f"Apollo API network breakdown: {str(e)}")

