<?php

// Contains classes for caching

// Generic cache class
class SingleDataCacheHandler {
    private string $cacheFile; // Filepath or a filename, if filename will be ensured in ../cache/<cacheFile> where . is PHP root (Relative paths are supported ex ...)
    private int $cacheTTL = 86400; // TTL = Time To Live in seconds, 0<= TTL means no expiration, defaulted to 1day (24h = 1440min = 86400s)

    public function __construct(string $cacheFile, int $cacheTTL = 86400) {
        $this->cacheFile = $cacheFile;
        $this->cacheTTL = $cacheTTL;

        // Filename -> Filepath
        //MARK: Is there a better PHP func to determine if path or filename?
        if (strpos($this->cacheFile, '/') === false && strpos($this->cacheFile, '\\') === false) {
            $this->cacheFile = __DIR__ . '/../../cache/' . $this->cacheFile;
        } else {
            $this->cacheFile = realpath($this->cacheFile);
        }

        // Ensure directory exists
        //   0755 = rwxr-xr-x = Owner can read/write/execute, group and others can read/execute
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Ensure file exists
        if (!file_exists($this->cacheFile)) {
            file_put_contents($this->cacheFile, json_encode(new stdClass()));
        }
    }

    private function readCache(): array {
        if (!file_exists($this->cacheFile)) {
            return [];
        }
    
        $fp = fopen($this->cacheFile, 'r');
        if (!$fp) {
            return [];
        }
    
        flock($fp, LOCK_SH); // Shared lock
        $contents = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    
        $data = json_decode($contents, true);
        return is_array($data) ? $data : [];
    }
    
    private function writeCache(array $data): bool {
        $fp = fopen($this->cacheFile, 'c+'); // c+ allows read/write
        if (!$fp) {
            return false;
        }
    
        flock($fp, LOCK_EX); // Exclusive lock
        ftruncate($fp, 0); // Clear file
        fwrite($fp, json_encode($data));
        fflush($fp); // Ensure written
        flock($fp, LOCK_UN);
        fclose($fp);
    
        return true;
    }    

    public function GetCacheKeys() {
        $data = $this->readCache();
        return array_keys($data);
    }

    public function GetAsAssocArray(): array {
        // All values are [<timestamp>, <value>] we only want <value>
        $data = $this->readCache();
        return array_map(fn($entry) => $entry[1], $data);
    }
    
    public function SetFromAssocArray(array $data): bool {
        // All values are [<timestamp>, <value>]
        $timestamp = time();
        $cacheData = array_map(fn($value) => [$timestamp, $value], $data);
        return $this->writeCache($cacheData);
    }

    public function Clear(): bool {
        return $this->writeCache([]);
    }

    public function KeyExists(string $key): bool {
        $data = $this->readCache();
        return array_key_exists($key, $data);
    }

    public function KeyIsExpired(string $key): bool {
        $data = $this->readCache();
        if (!array_key_exists($key, $data)) {
            return true; // Non-existing key is considered expired
        }

        if ($this->cacheTTL === 0) {
            return false; // No expiration
        }

        $timestamp = $data[$key][0];
        return (time() - $timestamp) > $this->cacheTTL;
    }

    public function GetValueAsAssocArray(string $key): ?array {
        $data = $this->readCache();
        if (!array_key_exists($key, $data)) {
            return null;
        }

        return $data[$key][1];
    }

    public function SetValueFromAssocArray(string $key, array $value): bool {
        $data = $this->readCache();
        $data[$key] = [time(), $value];
        return $this->writeCache($data);
    }

    public function GetExpiryForKey(string $key): ?int {
        $data = $this->readCache();
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $timestamp = $data[$key][0];
        if ($this->cacheTTL === 0) {
            return null; // No expiration
        }

        $expiryTime = $timestamp + $this->cacheTTL;
        return $expiryTime > time() ? $expiryTime : null;
    }

    public function SetExpiryForKey(string $key, int $newTTL): bool {
        $data = $this->readCache();
        if (!array_key_exists($key, $data)) {
            return false;
        }

        $timestamp = $data[$key][0];
        $data[$key][0] = time() - ($this->cacheTTL - $newTTL);
        return $this->writeCache($data);
    }

    public function UnsetKey(string $key): bool {
        $data = $this->readCache();
        if (!array_key_exists($key, $data)) {
            return false;
        }

        unset($data[$key]);
        return $this->writeCache($data);
    }
}

// Handler for image detail cache
class ImgDetailsCache extends SingleDataCacheHandler {
    // Stores into ../cache/img_details.json by default with 1 days TTL
    public function __construct(string $cacheFile = "img_details.json", int $cacheTTL = 86400) {
        parent::__construct($cacheFile, $cacheTTL);
    }

    public function ImageIdHasNonExpiredDetails(string $imageId): bool {
        return $this->KeyExists($imageId) && !$this->KeyIsExpired($imageId);
    }

    public function GetImageDetails(string $imageId): ?array {
        return $this->GetValueAsAssocArray($imageId);
    }

    public function StoreImageDetails(string $imageId, array $details): bool {
        return $this->SetValueFromAssocArray($imageId, $details);
    }
}

?>