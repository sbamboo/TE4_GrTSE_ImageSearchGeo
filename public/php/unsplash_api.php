<?php
// This file contians classes to wrap the Unsplash API
// Uses Extension: GD
// Requires: blurhash.php

class UnsplashAPI {
    private string $accessKey;
    private bool $autoGetDetails;
    private string $apiUrl = 'https://api.unsplash.com/';

    public function __construct(string $accessKey, $autoGetDetails = false) {
        $this->accessKey = $accessKey;
        $this->autoGetDetails = $autoGetDetails;
    }

    // lower level make GET request function without curl extension
    private function makeGetRequest(string $endpoint, array $params = []) {
        $url = $this->apiUrl . $endpoint . '?' . http_build_query(array_merge($params, ['client_id' => $this->accessKey]));
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n"
            ]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        if ($response === FALSE) {
            throw new Exception("Error making GET request to Unsplash API");
        }
        return json_decode($response, true);
    }

    public function GetRandomImage() {
        $response = $this->makeGetRequest('photos/random', []);
        return new UnsplashAPIImage($this, $response);
    }

    public function SearchPhotos(string $query, int $perPage = 10, int $page = 1, bool $filterNonGeo = false, string $orderBy = 'relevant'): array {
        $params = [
            'query' => $query,
            'per_page' => $perPage,
            'page' => $page,
            'order_by' => $orderBy
        ];
        $response = $this->makeGetRequest('search/photos', $params);
        $images = [];
        // foreach ($response['results'] as $imageData) {
        //     $images[] = new UnsplashAPIImage($imageData);
        // }
        foreach ($response['results'] as $imageData) {
            $image = new UnsplashAPIImage($this, $imageData);

            // If we automatically fetch details we can filter out non-geo images here
            if ($this->autoGetDetails && $filterNonGeo && !$image->HasGeoData()) {
                continue;
            }

            $images[] = $image;
        }
        return $images;
    }

    public function getPhotoDetailsAsArray(string $photoId): array {
        return $this->makeGetRequest('photos/' . $photoId);
    }

    public function GetPhotoDetails(string $photoId) {
        $response = $this->getPhotoDetailsAsArray($photoId);
        return new UnsplashAPIImage($this, $response);
    }

    // Checks if an imageData is considered to have Geodata
    // Either city or country or lat/lon set in location or exif
    public function DataHasGeoData(array $imageData): bool {
        // Check if imageData.location.city or imageData.location.country or imageData.location.latitude/longitude or imageData.exif.location.latitude/longitude is set
        // We must consider that data might be missing location or exif or exif.location and also the actuall city,country,latitude,longitude fields
        if (isset($imageData['location'])) {
            if (!empty($imageData['location']['city']) || !empty($imageData['location']['country'])) {
                return true;
            }
            if (isset($imageData['location']['position'])) {
                if (!empty($imageData['location']['position']['latitude']) && !empty($imageData['location']['position']['longitude'])) {
                    return true;
                }
            }
        }
        if (isset($imageData['exif']) && isset($imageData['exif']['location'])) {
            if (!empty($imageData['exif']['location']['latitude']) && !empty($imageData['exif']['location']['longitude'])) {
                return true;
            }
        }
        return false;
    }
    // public function DataHasGeoData(array $imageData): bool {
    //     // Helper to check if lat/lon are set and non-empty
    //     $hasLatLon = function ($data): bool {
    //         return isset($data['latitude'], $data['longitude']) 
    //             && $data['latitude'] !== null && $data['longitude'] !== null 
    //             && $data['latitude'] !== '' && $data['longitude'] !== '';
    //     };
    
    //     // Check in location
    //     if (isset($imageData['location'])) {
    //         $loc = $imageData['location'];
    //         if (
    //             (!empty($loc['city'])) ||
    //             (!empty($loc['country'])) ||
    //             $hasLatLon($loc)
    //         ) {
    //             return true;
    //         }
    //     }
    
    //     // Check in exif.location
    //     if (isset($imageData['exif']['location'])) {
    //         $exifLoc = $imageData['exif']['location'];
    //         if ($hasLatLon($exifLoc)) {
    //             return true;
    //         }
    //     }
    
    //     return false;
    // }
    

    // Getters
    public function IsAutoFetchingDetails(): bool {
        return $this->autoGetDetails;
    }
}

// Represents the EXIF data from Unsplash API
class UnsplashAPIExif {
    // Static factory creating known KeyedArray
    static public function Create(array $exifData): array {
        return [
            'make' => $exifData['make'] ?? '',
            'model' => $exifData['model'] ?? '',
            'name' => $exifData['name'] ?? '',
            'exposure_time' => $exifData['exposure_time'] ?? '',
            'aperture' => $exifData['aperture'] ?? '',
            'focal_length' => $exifData['focal_length'] ?? '',
            'iso' => $exifData['iso'] ?? 0,
            'location' => [
                'latitude' => $exifData['location']['latitude'] ?? null,
                'longitude' => $exifData['location']['longitude'] ?? null,
                'altitude' => $exifData['location']['altitude'] ?? null
            ]
        ];
    }
}

// Represents a location from Unsplash API
class UnsplashAPILocation {
    // Static factory creating known KeyedArray
    static public function Create(array $locationData): array {
        return [
            'name' => $locationData['name'] ?? '',
            'city' => $locationData['city'] ?? '',
            'country' => $locationData['country'] ?? '',
            'latitude' => $locationData['position']['latitude'] ?? null,
            'longitude' => $locationData['position']['longitude'] ?? null
        ];
    }
}

// Represents an image from the Unsplash API
class UnsplashAPIImage {
    private UnsplashAPI $parent;
    private bool $detailsAreFetched = false;

    private string $id;
    private string $slug;
    private array $alternative_slugs; // alternative_slugs keyedarray "langcode"=>"altslug"
    private DateTime $created_at;
    private DateTime $updated_at;
    private DateTime $promoted_at;
    private int $width;
    private int $height;
    private string $color;
    private string $blur_hash;
    private string $description;
    private string $alt_description;
    //private array $breakcrumbs; 
    private array $urls; // urls keyedarray "raw"=>"url","full"=>"url","regular"=>"url","small"=>"url","thumb"=>"url","small_s3"=>"url"
    private array $links; // links keyedarray "self"=>"url","html"=>"url","download"=>"url","download_location"=>"url"
    //private int $likes;
    //private bool $liked_by_user;
    //private array $current_user_collections;
    //private array $sponsorship;
    //private array $topic_submissions; 
    private string $asset_type; // "photo",...
    private string $user_username; // <APIResponse>.User.username
    private string $user_unsplash_profile; // <APIResponse>.User.links.html
    private array $exif;
    private array $location;
    private array $meta; // meta keyedarray "index"=>"value"
    private array $tags; // tags array of keyedarray "type"=>"value","title"=>"value","source"=>keyedarray
    //private int $views;
    //private int $downloads;
    private array $topics; // topics is array of "id","title","slug","visibility" => string:s

    public function __construct(UnsplashAPI $parent, array $imageData) {
        $this->parent = $parent;

        // If parent has IsAutoFetchingDetails() enabled we fetch details now
        if ($parent->IsAutoFetchingDetails() === true && $this->detailsAreFetched === false) {
            $imageData = $parent->getPhotoDetailsAsArray($imageData['id'] ?? '');
            $this->detailsAreFetched = true;
        }

        $this->id = $imageData['id'] ?? '';
        $this->slug = $imageData['slug'] ?? '';
        $this->alternative_slugs = $imageData['alternative_slugs'] ?? [];
        $this->created_at = new DateTime($imageData['created_at'] ?? 'now');
        $this->updated_at = new DateTime($imageData['updated_at'] ?? 'now');
        $this->promoted_at = new DateTime($imageData['promoted_at'] ?? 'now');
        $this->width = $imageData['width'] ?? 0;
        $this->height = $imageData['height'] ?? 0;
        $this->color = $imageData['color'] ?? '';
        $this->blur_hash = $imageData['blur_hash'] ?? '';
        $this->description = $imageData['description'] ?? '';
        $this->alt_description = $imageData['alt_description'] ?? '';
        // $this->breakcrumbs = $imageData['breakcrumbs'] ?? [];
        $this->urls = $imageData['urls'] ?? [];
        $this->links = $imageData['links'] ?? [];
        // $this->likes = $imageData['likes'] ?? 0;
        // $this->liked_by_user = $imageData['liked_by_user'] ?? false;
        // $this->current_user_collections = $imageData['current_user_collections'] ?? [];
        // $this->sponsorship = $imageData['sponsorship'] ?? [];
        // $this->topic_submissions = $imageData['topic_submissions'] ?? [];
        $this->asset_type = $imageData['asset_type'] ?? '';
        $this->user_username = $imageData['user']['username'] ?? '';
        $this->user_unsplash_profile = $imageData['user']['links']['html'] ?? '';
        $this->exif = UnsplashAPIExif::Create($imageData['exif'] ?? []);
        $this->location = UnsplashAPILocation::Create($imageData['location'] ?? []);
        $this->meta = $imageData['meta'] ?? [];
        $this->tags = $imageData['tags'] ?? [];
        // $this->views = $imageData['views'] ?? 0;
        // $this->downloads = $imageData['downloads'] ?? 0;
        $this->topics = $imageData['topics'] ?? [];
    }

    // Function to return the entire image as keyedarray
    public function ToArray(): array {
        // Return only cared abt data that we use
        return [
            "id" => $this->id,
            "slug" => $this->slug,
            "created_at" => $this->created_at->format(DateTime::ATOM), // ATOM is Y-m-d\TH:i:sP ex. 2005-08-15T15:52:01+00:00
            "updated_at" => $this->updated_at->format(DateTime::ATOM),
            "color" => $this->color,
            "dimentions" => $this->GetDimentions(),
            "description" => $this->GetDescription(),
            "download" => $this->GetDownloadUrl(),
            "user" => [
                "username" => $this->user_username,
                "profile" => $this->user_unsplash_profile
            ],
            "exif" => $this->exif,
            "location" => $this->location,
            "meta" => $this->meta,
            "tags" => $this->tags,
            "topics" => $this->topics,
        ];
    }

    // Function to fetch the details for this image from the API
    public function FetchDetails() {
        // If details are already fetched do nothing
        if ($this->detailsAreFetched) {
            return;
        }

        $imageData = $this->parent->getPhotoDetailsAsArray($this->id);
        // Update all properties from the fetched data
        $this->__construct($this->parent, $imageData);
    }

    // Use blurHashToDataUrl to get image of blur hash
    // Takes $width and $height of the blurImage to generate, height is default -1 which means base it of aspect ratio
    //   GetBlurAsImage(32,32) => 32x32 1:1 ratio
    //   GetBlurAsImage(32,-1) => 32x(height based on aspect ratio) x:? ratio
    //   GetBlurAsImage(-1,32) => (width based on aspect ratio)x32 ?:x ratio
    //   GetBlurAsImage(-1,-1) => (width,height) of original image ?:? ratio (NOT RECOMMENDED SINCE IT MIGHT BE LARGE)
    public function GetBlurAsImage(int $width = -1, int $height = -1): string {
        $orgAspectRatio = $this->width / $this->height;
        if ($width < 0 && $height < 0) {
            $width = $this->width;
            $height = $this->height;
        } else if ($width < 0) {
            // Calculate width based on $height and aspect ratio of the image
            $width = (int) round($height * $orgAspectRatio);
        } else if ($height < 0) {
            // Calculate height based on $width and aspect ratio of the image
            $height = (int) round($width / $orgAspectRatio);
        }

        // If this image does not have a blurhash return empty string
        if (empty($this->blur_hash)) {
            return '';
        }

        // Decode the blurhash into RGB pixels
        $pixels = Blurhash::decode($this->blur_hash, $width, $height);
        $image  = imagecreatetruecolor($width, $height);
        if (!$image) {
            throw new Exception("Failed to create image resource");
        }
        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                [$r, $g, $b] = $pixels[$y][$x];
                imagesetpixel($image, $x, $y, imagecolorallocate($image, $r, $g, $b));
            }
        }

        // Capture image output as data URI
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();

        // Free memory
        imagedestroy($image);

        // Return data URI
        return 'data:image/png;base64,' . base64_encode($imageData);
    }

    // Function to check if this image is considered to have Geodata
    public function HasGeoData(): bool {
        // Check if imageData.location.city or imageData.location.country or imageData.location.latitude/longitude or imageData.exif.location.latitude/longitude is set
        // We must consider that data might be missing location or exif or exif.location and also the actuall city,country,latitude,longitude fields
        if (isset($this->location)) {
            if (!empty($this->location['city']) || !empty($this->location['country'])) {
                return true;
            }
            if (!empty($this->location['latitude']) && !empty($this->location['longitude'])) {
                    return true;
            }
        }
        if (isset($this->exif) && isset($this->exif['location'])) {
            if (!empty($this->exif['location']['latitude']) && !empty($this->exif['location']['longitude'])) {
                return true;
            }
        }
        return false;
    }

    // Function to get the prefered thumbnail (not blurhash)
    public function GetImageThumbnailUrl(): string {
        // Does thumb exists else use small else use small_s3
        return $this->urls['thumb'] ?? $this->urls['small'] ?? $this->urls['small_s3'] ?? '';
    }

    // Function to get the prefered url for displaying the image on the website
    public function GetImageDisplayUrl(): string {
        return $this->urls['regular'] ?? '';
    }

    // Function to get the prefered download url for the image
    public function GetDownloadUrl(): string {
        return $this->links['download'] ?? '';
    }

    // Function to get the prefereed description
    public function GetDescription(): string {
        return !empty($this->description) ? $this->description : $this->alt_description;
    }

    // Function to get the prefered lon/lat coordinates
    public function GetCoordinates(): array {
        if (!empty($this->location['latitude']) && !empty($this->location['longitude'])) {
            return ['latitude' => $this->location['latitude'], 'longitude' => $this->location['longitude']];
        }
        if (!empty($this->exif['location']['latitude']) && !empty($this->exif['location']['longitude'])) {
            return ['latitude' => $this->exif['location']['latitude'], 'longitude' => $this->exif['location']['longitude']];
        }
        return ['latitude' => null, 'longitude' => null];
    }

    // Function to get the prefered geolocation names
    public function GetGeoNames(): array {
        // Clears city if city is strContained inside name, clears country if country is strContained inside name
        $toRet = [
            'name' => $this->location['name'] ?? '',
            'city' => $this->location['city'] ?? '',
            'country' => $this->location['country'] ?? ''
        ];

        if (!empty($toRet['name'])) {
            if (!empty($toRet['city']) && stripos($toRet['name'], $toRet['city']) !== false) {
                $toRet['city'] = '';
            }
            if (!empty($toRet['country']) && stripos($toRet['name'], $toRet['country']) !== false) {
                $toRet['country'] = '';
            }
        }

        return $toRet;
    }

    public function GetMostPreciseGMapsUrl(): ?string {
        $coords = $this->GetCoordinates();
        $location = $this->GetLocation();
        // 1 if non of coords empty
        // 2 if place is not empty
        // 3 if city and country is not empty
        // 4 if only country
        if (!empty($coords["latitude"]) && !empty($coords["longitude"])) {
            return '<a href="https://maps.google.com/?q=' . $coords["latitude"]. ',' . $coords["longitude"]. '"> Maps</a>';
        }
        elseif (!empty($location["name"])) {
            return '<a href="https://www.google.com/maps/place/'.urlencode($location["name"]).'"> Maps</a>';
        }
        elseif (!empty($location["city"]) && !empty($location["country"])) {
            return '<a href="https://www.google.com/maps/place/'.urlencode($location["country"]. ',' . $location["city"]). '"> Maps</a>';
        }
        elseif (!empty($location["country"])) {
            return '<a href="https://www.google.com/maps/place/'.urlencode($location["country"]). '"> Maps</a>';
        }
        return null;

    }

    // Getters
    public function GetLocation(): array {
        return $this->location;
    }
    public function GetExif(): array {
        return $this->exif;
    }
    public function GetDimentions(): array {
        return ['width' => $this->width, 'height' => $this->height];
    }
    public function GetIdentifiers() : array {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'alternative_slugs' => $this->alternative_slugs
        ];
    }
    public function GetUserInfo(): array {
        return [
            'username' => $this->user_username,
            'profile' => $this->user_unsplash_profile
        ];
    }
}
?>