import requests

ACCESS_KEY = "FKM3gFjymmtGNN9IwGbLJaMnGTngjJwszyxxjQLZwS4"

url = "https://api.unsplash.com/photos/random"
params = {"client_id": ACCESS_KEY}

response = requests.get(url, params=params)

if response.status_code == 200:
    data = response.json()
    print("Photo ID:", data["id"])
    print("Photographer:", data["user"]["name"])
    print("Image URL:", data["urls"]["regular"])
    
    # Check if geodata is available
    location = data.get("location", {})
    if location:
        title = location.get("title")
        city = location.get("city")
        country = location.get("country")
        position = location.get("position", {})
        lat = position.get("latitude")
        lng = position.get("longitude")

        print("\n--- Location Info ---")
        if title: print("Title:", title)
        if city: print("City:", city)
        if country: print("Country:", country)
        if lat and lng:
            print("Coordinates:", lat, lng)
        else:
            print("Coordinates: Not available")
    else:
        print("No geodata available for this photo.")
else:
    print("Error:", response.status_code, response.text)