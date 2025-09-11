<?php
// This file contians classes to wrap the Unsplash API
// Uses Extension: GD
// Requires: blurhash.php

class UnsplashAPI {
    private $access_key;
    private $api_url = 'https://api.unsplash.com/';

    public function __construct($access_key) {
        $this->access_key = $access_key;
    }

    // lower level make GET request function without curl extension
    private function makeGetRequest($endpoint, $params = []) {
        $url = $this->api_url . $endpoint . '?' . http_build_query(array_merge($params, ['client_id' => $this->access_key]));
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
        return new UnsplashAPIImage($response);
    }

    public function SearchPhotos($query, $per_page = 10, $page = 1) {
        $params = [
            'query' => $query,
            'per_page' => $per_page,
            'page' => $page
        ];
        $response = $this->makeGetRequest('search/photos', $params);
        $images = [];
        // foreach ($response['results'] as $imageData) {
        //     $images[] = new UnsplashAPIImage($imageData);
        // }
        foreach ($response['results'] as $imageData) {
            $fullData = $this->makeGetRequest('photos/' . $imageData['id']);
            $images[] = new UnsplashAPIImage($fullData);
        }
        return $images;
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
            'latitude' => $locationData['position']['latitude'] ?? 0.0,
            'longitude' => $locationData['position']['longitude'] ?? 0.0
        ];
    }
}

// Represents an image from the Unsplash API
class UnsplashAPIImage {
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
    private string $user_unsplash_profile; // <APIResponse>.User.links.self
    private array $exif;
    private array $location;
    private array $meta; // meta keyedarray "index"=>"value"
    private array $tags; // tags array of keyedarray "type"=>"value","title"=>"value","source"=>keyedarray
    //private int $views;
    //private int $downloads;
    private array $topics; // topics is array of "id","title","slug","visibility" => string:s

    public function __construct(array $imageData) {
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
        $this->user_unsplash_profile = $imageData['user']['links']['self'] ?? '';
        $this->exif = UnsplashAPIExif::Create($imageData['exif'] ?? []);
        $this->location = UnsplashAPILocation::Create($imageData['location'] ?? []);
        $this->meta = $imageData['meta'] ?? [];
        $this->tags = $imageData['tags'] ?? [];
        // $this->views = $imageData['views'] ?? 0;
        // $this->downloads = $imageData['downloads'] ?? 0;
        $this->topics = $imageData['topics'] ?? [];
    }

    // Use blurHashToDataUrl to get image of blur hash
    public function GetBlurAsImage(int $width = 32, int $height = 32): string {
        // If this image does not have a blurhash return empty string
        if (empty($this->blur_hash)) {
            return '';
        }

        // Decode the blurhash into RGB pixels
        $pixels = Blurhash::decode($this->blur_hash, $width, $height);
        $image  = imagecreatetruecolor($width, $height);
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

    // Function to get the prefered url for displaying the image on the website
    public function GetImageDisplayUrl(): string {
        return $this->urls['regular'] ?? '';
    }

    // Function to get the prefered download url for the image
    public function GetDownloadUrl(): string {
        return $this->links['download'] ?? '';
    }

    // Getters
    public function GetLocation(): array {
        return $this->location;
    }
    public function GetExif(): array {
        return $this->exif;
    }
}
?>