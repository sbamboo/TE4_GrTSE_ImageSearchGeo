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

    public function GetCacheKeys(): array {
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
        // throw json_encode of $value
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

    // Function to get all key-value pairs where value contains a field, also value should be just the field
    // Returns KEY => [FIELD => FIELD_VALUE] for any entries that has the field in value
    public function GetAllFieldOfEntries(string $fieldName): array {
        $data = $this->readCache();
        $result = [];
        foreach ($data as $key => $entry) {
            if (is_array($entry[1]) && array_key_exists($fieldName, $entry[1])) {
                $result[$key] = [$fieldName => $entry[1][$fieldName]];
            }
        }
        return $result;
    }
}

/*
CREATE TABLE IF NOT EXISTS cache (
    cache_key VARCHAR(255) PRIMARY KEY,
    cache_value JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL
);
*/
class SingleSQLCacheHandler {
    private mysqli $db;
    private string $tableName; // Configurable table name
    private int $cacheTTL = 86400; // TTL = Time To Live in seconds, 0 means no expiration

    public function __construct(
        mysqli $db,
        string $sqlTableName = "cache",
        int $cacheTTL = 86400
    ) {
        $this->db = $db;
        $this->tableName = $sqlTableName;
        $this->cacheTTL = $cacheTTL;

        $this->ensureTableExists();
    }

    public static function ConstructWithNewSQL(
        string $sqlUser,
        string $sqlPassword,
        string $sqlDbName,
        string $sqlHost = 'localhost:3306',
        string $sqlTableName = 'cache',
        int $cacheTTL = 86400
    ): SingleSqlCacheHandler {
        // Split host and port
        $parts = explode(':', $sqlHost);
        $host = $parts[0];
        $port = isset($parts[1]) ? (int)$parts[1] : 3306;

        $db = new mysqli($host, $sqlUser, $sqlPassword, $sqlDbName, $port);

        if ($db->connect_error) {
            throw new Exception("Failed to connect to MySQL: " . $db->connect_error);
        }
        return new self($db, $sqlTableName, $cacheTTL);
    }

    private function ensureTableExists(): void {
        $query = "
            CREATE TABLE IF NOT EXISTS {$this->tableName} (
                cache_key VARCHAR(255) PRIMARY KEY,
                cache_value JSON NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL
            );
        ";
        if (!$this->db->query($query)) {
            //error_log("Failed to create cache table '{$this->tableName}': " . $this->db->error);
            throw new Exception("Failed to create cache table '{$this->tableName}': " . $this->db->error);
        }
    }

    private function readCacheEntry(string $key): ?array {
        $stmt = $this->db->prepare("
            SELECT cache_value, UNIX_TIMESTAMP(created_at) AS created_timestamp, UNIX_TIMESTAMP(expires_at) AS expires_timestamp
            FROM {$this->tableName}
            WHERE cache_key = ?;
        ");
        if (!$stmt) {
            //error_log("Failed to prepare readCacheEntry statement: " . $this->db->error);
            //return null;
            throw new Exception("Failed to prepare readCacheEntry statement: " . $this->db->error);
        }

        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return null; // Key not found
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        $cachedValue = json_decode($row['cache_value'], true);
        if (isset($row['created_timestamp'])) {
            $createdTimestamp = (int) $row['created_timestamp'];
        } else {
            $createdTimestamp = time(); // Fallback to current time if not set
        }

        if (isset($row['expires_timestamp']) || isset($row['expires_at'])) {
            $expiresTimestamp = (isset($row['expires_timestamp']) && $row['expires_timestamp'] !== null) ? (int) $row['expires_timestamp'] : null;
        } else {
            $expiresTimestamp = $this->cacheTTL === 0 ? null : $createdTimestamp + $this->cacheTTL; // Fallback to calculated expiration if one is not set
        }


        // Check expiration
        if ($expiresTimestamp !== null && $expiresTimestamp <= time()) {
            return null; // Key found but expired
        }

        return [$createdTimestamp, $cachedValue];
    }

    private function readAllCacheEntries(): array {
        $data = [];
        $query = "
            SELECT cache_key, cache_value, UNIX_TIMESTAMP(created_at) AS created_timestamp, UNIX_TIMESTAMP(expires_at) AS expires_timestamp
            FROM {$this->tableName}
            WHERE expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP;
        ";
        $result = $this->db->query($query);

        if (!$result) {
            //error_log("Failed to read all cache entries: " . $this->db->error);
            //return [];
            throw new Exception("Failed to read all cache entries: " . $this->db->error);
        }

        while ($row = $result->fetch_assoc()) {
            $cachedValue = json_decode($row['cache_value'], true);
            $createdTimestamp = (int) $row['created_timestamp'];
            $data[$row['cache_key']] = [$createdTimestamp, $cachedValue];
        }
        $result->free();
        return $data;
    }

    private function writeCacheEntry(string $key, array $value, ?int $specificTTL = null): bool {
        $jsonValue = json_encode($value);
        if ($jsonValue === false) {
            //error_log("Failed to JSON encode value for cache key: " . $key);
            //return false;
            throw new Exception("Failed to JSON encode value for cache key: " . $key);
        }

        $currentTTL = $specificTTL ?? $this->cacheTTL;
        $expiresAt = ($currentTTL === 0) ? null : date('Y-m-d H:i:s', time() + $currentTTL);
        $createdAt = date('Y-m-d H:i:s', time());

        $stmt = $this->db->prepare("
            INSERT INTO {$this->tableName} (cache_key, cache_value, created_at, expires_at)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                cache_value = VALUES(cache_value),
                created_at = VALUES(created_at),
                expires_at = VALUES(expires_at);
        ");
        if (!$stmt) {
            //error_log("Failed to prepare writeCacheEntry statement: " . $this->db->error);
            //return false;
            throw new Exception("Failed to prepare writeCacheEntry statement: " . $this->db->error);
        }

        // Determine parameter type for expiresAt (string or null)
        $paramTypes = "sss";
        $params = [$key, $jsonValue, $createdAt];
        if ($expiresAt === null) {
            $paramTypes .= "s"; // Treat null as string for bind_param
            $params[] = null;
        } else {
            $paramTypes .= "s";
            $params[] = $expiresAt;
        }

        $stmt->bind_param($paramTypes, ...$params);
        if ($stmt->error) {
            //error_log("Failed to bind parameters for cache write '{$key}': " . $stmt->error);
            $stmt->close();
            throw new Exception("Failed to bind parameters for cache write '{$key}': " . $stmt->error);
        }


        $success = $stmt->execute();
        if (!$success) {
            //error_log("Failed to write cache entry for key '{$key}': " . $stmt->error);
            $stmt->close();
            throw new Exception("Failed to write cache entry for key '{$key}': " . $stmt->error);
        }
        $stmt->close();

        return $success;
    }

    // Helper to pass parameters by reference for bind_param
    private function refValues(array $arr): array {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }

    public function GetCacheKeys(): array {
        $keys = [];
        $query = "
            SELECT cache_key
            FROM {$this->tableName}
            WHERE expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP;
        ";
        $result = $this->db->query($query);
        if (!$result) {
            //error_log("Failed to get cache keys: " . $this->db->error);
            //return [];
            throw new Exception("Failed to get cache keys: " . $this->db->error);
        }

        while ($row = $result->fetch_assoc()) {
            $keys[] = $row['cache_key'];
        }
        $result->free();
        return $keys;
    }

    public function GetAsAssocArray(): array {
        $data = $this->readAllCacheEntries();
        return array_map(fn ($entry) => $entry[1], $data);
    }

    public function SetFromAssocArray(array $data): bool {
        $success = true;
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $value = (array) $value;
            }
            if (!$this->writeCacheEntry($key, $value)) {
                $success = false;
            }
        }
        return $success;
    }

    public function Clear(): bool {
        $query = "DELETE FROM {$this->tableName};";
        if (!$this->db->query($query)) {
            //error_log("Failed to clear cache: " . $this->db->error);
            //return false;
            throw new Exception("Failed to clear cache: " . $this->db->error);
        }
        return true;
    }

    public function KeyExists(string $key): bool {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM {$this->tableName}
            WHERE cache_key = ? AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
            LIMIT 1;
        ");
        if (!$stmt) {
            //error_log("Failed to prepare KeyExists statement: " . $this->db->error);
            //return false;
            throw new Exception("Failed to prepare KeyExists statement: " . $this->db->error);
        }
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function KeyIsExpired(string $key): bool {
        $stmt = $this->db->prepare("
            SELECT expires_at
            FROM {$this->tableName}
            WHERE cache_key = ?;
        ");
        if (!$stmt) {
            //error_log("Failed to prepare KeyIsExpired statement: " . $this->db->error);
            //return true;
            throw new Exception("Failed to prepare KeyIsExpired statement: " . $this->db->error);
        }
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return true; // Non-existing key is considered expired
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        $expiresAt = $row['expires_at'];

        if ($expiresAt === null) {
            return false; // No expiration
        }

        return strtotime($expiresAt) <= time();
    }

    public function GetValueAsAssocArray(string $key): ?array {
        $entry = $this->readCacheEntry($key);
        return $entry[1] ?? null; // Returns the actual value, or null if not found/expired
    }

    public function SetValueFromAssocArray(string $key, array $value): bool {
        return $this->writeCacheEntry($key, $value);
    }

    public function GetExpiryForKey(string $key): ?int {
        $stmt = $this->db->prepare("
            SELECT expires_at
            FROM {$this->tableName}
            WHERE cache_key = ?;
        ");
        if (!$stmt) {
            //error_log("Failed to prepare GetExpiryForKey statement: " . $this->db->error);
            //return null;
            throw new Exception("Failed to prepare GetExpiryForKey statement: " . $this->db->error);
        }
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return null; // Key not found
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        $expiresAt = $row['expires_at'];

        if ($expiresAt === null) {
            return null; // No expiration
        }

        $expiryTime = strtotime($expiresAt);
        return $expiryTime > time() ? $expiryTime : null;
    }

    public function SetExpiryForKey(string $key, int $newTTL): bool {
        $expiresAt = ($newTTL === 0) ? null : date('Y-m-d H:i:s', time() + $newTTL);

        $stmt = $this->db->prepare("
            UPDATE {$this->tableName}
            SET expires_at = ?, created_at = CURRENT_TIMESTAMP()
            WHERE cache_key = ?;
        ");
        if (!$stmt) {
            //error_log("Failed to prepare SetExpiryForKey statement: " . $this->db->error);
            //return false;
            throw new Exception("Failed to prepare SetExpiryForKey statement: " . $this->db->error);
        }

        // Handle null for expiresAt in bind_param
        $paramTypes = "ss";
        $bindValue = $expiresAt; // Reference for bind_param
        $stmt->bind_param($paramTypes, $bindValue, $key);

        $success = $stmt->execute();
        if (!$success) {
            //error_log("Failed to set expiry for key '{$key}': " . $stmt->error);
            $stmt->close();
            throw new Exception("Failed to set expiry for key '{$key}': " . $stmt->error);
        }
        $stmt->close();
        return $success && $this->db->affected_rows > 0;
    }

    public function UnsetKey(string $key): bool {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->tableName}
            WHERE cache_key = ?;
        ");
        if (!$stmt) {
            //error_log("Failed to prepare UnsetKey statement: " . $this->db->error);
            throw new Exception("Failed to prepare UnsetKey statement: " . $this->db->error);
            return false;
        }
        $stmt->bind_param("s", $key);
        $success = $stmt->execute();
        if (!$success) {
            //error_log("Failed to unset key '{$key}': " . $stmt->error);
            $stmt->close();
            throw new Exception("Failed to unset key '{$key}': " . $stmt->error);
        }
        $stmt->close();
        return $success && $this->db->affected_rows > 0;
    }

    public function clearExpired(): bool {
        $query = "
            DELETE FROM {$this->tableName}
            WHERE expires_at IS NOT NULL AND expires_at <= CURRENT_TIMESTAMP;
        ";
        if (!$this->db->query($query)) {
            //error_log("Failed to clear expired cache entries: " . $this->db->error);
            //return false;
            throw new Exception("Failed to clear expired cache entries: " . $this->db->error);
        }
        return true;
    }

    // Function to get all key-value pairs where value contains a field, also value should be just the field
    // Returns KEY => [FIELD => FIELD_VALUE] for any entries that has the field in value
    public function GetAllFieldOfEntries(string $fieldName): array {
        //     $data = $this->readCache();
        //     $result = [];
        //     foreach ($data as $key => $entry) {
        //         if (is_array($entry[1]) && array_key_exists($fieldName, $entry[1])) {
        //             $result[$key] = [$fieldName => $entry[1][$fieldName]];
        //         }
        //     }
        //     return $result;

        $result = [];
        $query = "
            SELECT cache_key, cache_value
            FROM {$this->tableName}
            WHERE (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
              AND JSON_EXTRACT(cache_value, ?) IS NOT NULL;
        ";
        $fieldPath = '$.' . $fieldName;
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            //error_log("Failed to prepare GetAllFieldOfEntries statement: " . $this->db->error);
            //return [];
            throw new Exception("Failed to prepare GetAllFieldOfEntries statement: " . $this->db->error);
        }
        $stmt->bind_param("s", $fieldPath);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $cachedValue = json_decode($row['cache_value'], true);
            if (is_array($cachedValue) && array_key_exists($fieldName, $cachedValue)) {
                $result[$row['cache_key']] = [$fieldName => $cachedValue[$fieldName]];
            }
        }
        $stmt->close();
        return $result;
    }
}

// Interface for image detail cache handlers
interface ImgDetailsCacheInterface {
    public function ImageIdHasNonExpiredDetails(string $imageId): bool;
    public function GetImageDetails(string $imageId): ?array;
    public function StoreImageDetails(string $imageId, array $details): bool;
    public function GetAllKnownTags(): array;
    // Add any other common methods you might need from the base cache handlers
    public function GetCacheKeys(): array;
    public function GetAsAssocArray(): array;
    public function SetFromAssocArray(array $data): bool;
    public function Clear(): bool;
    public function KeyExists(string $key): bool;
    public function KeyIsExpired(string $key): bool;
    public function GetValueAsAssocArray(string $key): ?array;
    public function SetValueFromAssocArray(string $key, array $value): bool;
    public function GetExpiryForKey(string $key): ?int;
    public function SetExpiryForKey(string $key, int $newTTL): bool;
    public function UnsetKey(string $key): bool;
    public function GetAllFieldOfEntries(string $fieldName): array;
}

// Handler for image detail cache
// Extends SingleDataCacheHandler and implements ImgDetailsCacheInterface
class ImgDetailsCache extends SingleDataCacheHandler implements ImgDetailsCacheInterface {
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
        echo "FILE: Storing details for imageId: $imageId<br>";
        return $this->SetValueFromAssocArray($imageId, $details);
    }

    public function GetAllKnownTags(): array {
        $allEntriesWithTags = $this->GetAllFieldOfEntries('tags'); // KEY => ['tags' => []VALUE]
        $allTags = [];
        // Iterate $allEntriesWithTags and iterate their "tags" field, for each get the "title" field and add to $allTags if not already present
        foreach ($allEntriesWithTags as $entry) {
            if (isset($entry['tags']) && is_array($entry['tags'])) {
                foreach ($entry['tags'] as $tag) {
                    if (isset($tag['title']) && is_string($tag['title'])) {
                        // If $tag['title'] not already in $allTags array add it
                        if (!in_array($tag['title'], $allTags)) {
                            $allTags[] = $tag['title'];
                        }
                    }
                }
            }
        }

        return $allTags;
    }

    public function GetAllFieldOfEntries(string $fieldName): array {
        return parent::GetAllFieldOfEntries($fieldName);
    }
}

class ImgDetailsCacheSQL extends SingleSQLCacheHandler implements ImgDetailsCacheInterface {
    // Stores into SQL table 'img_details' by default with 1 days TTL
    public function __construct(
        mysqli $db,
        string $sqlTableName = "img_details",
        int $cacheTTL = 86400
    ) {
        parent::__construct($db, $sqlTableName, $cacheTTL);
    }

    public static function ConstructWithNewSQL(
        string $sqlUser,
        string $sqlPassword,
        string $sqlDbName,
        string $sqlHost = 'localhost:3306',
        string $sqlTableName = 'img_details',
        int $cacheTTL = 86400
    ): ImgDetailsCacheSQL {
        // Split host and port
        $parts = explode(':', $sqlHost);
        $host = $parts[0];
        $port = isset($parts[1]) ? (int)$parts[1] : 3306;

        $db = new mysqli($host, $sqlUser, $sqlPassword, $sqlDbName, $port);

        if ($db->connect_error) {
            throw new Exception("Failed to connect to MySQL: " . $db->connect_error);
        }
        return new self($db, $sqlTableName, $cacheTTL);
    }

    public function ImageIdHasNonExpiredDetails(string $imageId): bool {
        return $this->KeyExists($imageId) && !$this->KeyIsExpired($imageId);
    }

    public function GetImageDetails(string $imageId): ?array {
        return $this->GetValueAsAssocArray($imageId);
    }

    public function StoreImageDetails(string $imageId, array $details): bool {
        echo "SQL: Storing details for imageId: $imageId<br>";
        return $this->SetValueFromAssocArray($imageId, $details);
    }

    public function GetAllKnownTags(): array {
        $allEntriesWithTags = $this->GetAllFieldOfEntries('tags'); // KEY => ['tags' => []VALUE]
        $allTags = [];
        // Iterate $allEntriesWithTags and iterate their "tags" field, for each get the "title" field and add to $allTags if not already present
        foreach ($allEntriesWithTags as $entry) {
            if (isset($entry['tags']) && is_array($entry['tags'])) {
                foreach ($entry['tags'] as $tag) {
                    if (isset($tag['title']) && is_string($tag['title'])) {
                        // If $tag['title'] not already in $allTags array add it
                        if (!in_array($tag['title'], $allTags)) {
                            $allTags[] = $tag['title'];
                        }
                    }
                }
            }
        }

        return $allTags;
    }

    public function GetAllFieldOfEntries(string $fieldName): array {
        return parent::GetAllFieldOfEntries($fieldName);
    }
}

// // Handler for image detail cache
// // Extends either ImgDetailsCacheGeneric or ImgDetailsCacheSQL
// class ImgDetailsCache extends SingleDataCacheHandler {
//     // Stores into ../cache/img_details.json by default with 1 days TTL
//     public function __construct(string $cacheFile = "img_details.json", int $cacheTTL = 86400) {
//         parent::__construct($cacheFile, $cacheTTL);
//     }

//     public function ImageIdHasNonExpiredDetails(string $imageId): bool {
//         return $this->KeyExists($imageId) && !$this->KeyIsExpired($imageId);
//     }

//     public function GetImageDetails(string $imageId): ?array {
//         return $this->GetValueAsAssocArray($imageId);
//     }

//     public function StoreImageDetails(string $imageId, array $details): bool {
//         return $this->SetValueFromAssocArray($imageId, $details);
//     }

//     public function GetAllKnownTags(): array {
//         $allEntriesWithTags = $this->GetAllFieldOfEntries('tags'); // KEY => ['tags' => []VALUE]
//         $allTags = [];
//         // Iterate $allEntriesWithTags and iterate their "tags" field, for each get the "title" field and add to $allTags if not already present
//         foreach ($allEntriesWithTags as $entry) {
//             if (isset($entry['tags']) && is_array($entry['tags'])) {
//                 foreach ($entry['tags'] as $tag) {
//                     if (isset($tag['title']) && is_string($tag['title'])) {
//                         // If $tag['title'] not already in $allTags array add it
//                         if (!in_array($tag['title'], $allTags)) {
//                             $allTags[] = $tag['title'];
//                         }
//                     }
//                 }
//             }
//         }

//         return $allTags;
//     }
// }

// class ImgDetailsCacheSQL extends SingleSQLCacheHandler {
//     // Stores into SQL table 'img_details' by default with 1 days TTL
//     public function __construct(
//         mysqli $db,
//         string $sqlTableName = "img_details",
//         int $cacheTTL = 86400
//     ) {
//         parent::__construct($db, $sqlTableName, $cacheTTL);
//     }

//     public static function ConstructWithNewSQL(
//         string $sqlUser,
//         string $sqlPassword,
//         string $sqlDbName,
//         string $sqlHost = 'localhost:3306',
//         string $sqlTableName = 'img_details',
//         int $cacheTTL = 86400
//     ): ImgDetailsCacheSQL {
//         $db = new mysqli($sqlHost, $sqlUser, $sqlPassword, $sqlDbName);

//         if ($db->connect_error) {
//             throw new Exception("Failed to connect to MySQL: " . $db->connect_error);
//         }
//         return new self($db, $sqlTableName, $cacheTTL);
//     }

//     public function ImageIdHasNonExpiredDetails(string $imageId): bool {
//         return $this->KeyExists($imageId) && !$this->KeyIsExpired($imageId);
//     }

//     public function GetImageDetails(string $imageId): ?array {
//         return $this->GetValueAsAssocArray($imageId);
//     }

//     public function StoreImageDetails(string $imageId, array $details): bool {
//         return $this->SetValueFromAssocArray($imageId, $details);
//     }

//     public function GetAllKnownTags(): array {
//         $allEntriesWithTags = $this->GetAllFieldOfEntries('tags'); // KEY => ['tags' => []VALUE]
//         $allTags = [];
//         // Iterate $allEntriesWithTags and iterate their "tags" field, for each get the "title" field and add to $allTags if not already present
//         foreach ($allEntriesWithTags as $entry) {
//             if (isset($entry['tags']) && is_array($entry['tags'])) {
//                 foreach ($entry['tags'] as $tag) {
//                     if (isset($tag['title']) && is_string($tag['title'])) {
//                         // If $tag['title'] not already in $allTags array add it
//                         if (!in_array($tag['title'], $allTags)) {
//                             $allTags[] = $tag['title'];
//                         }
//                     }
//                 }
//             }
//         }

//         return $allTags;
//     }
// }

?>