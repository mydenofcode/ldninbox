<?php
/**
 * LDN Inbox Implementation with Database Support
 * A PHP implementation of a Linked Data Notifications inbox
 * based on https://www.eventnotifications.net/
 * 
 * Supports PostgreSQL, MySQL, and MongoDB storage backends
 */

// Configuration
$config = [
    'baseUrl' => 'https://example.com',
    'inboxPath' => '/inbox/',
    'maxNotificationSize' => 100 * 1024, // 100KB
    'supportedContentTypes' => [
        'application/ld+json',
        'application/activity+json',
        'application/json'
    ],
    // Database configuration
    'database' => [
        'type' => 'mysql', // Options: 'mysql', 'postgresql', 'mongodb'
        
        // MySQL configuration
        'mysql' => [
            'host' => 'localhost',
            'port' => 3306,
            'dbname' => 'ldn_inbox',
            'username' => 'ldn_user',
            'password' => 'your_password',
            'table' => 'notifications'
        ],
        
        // PostgreSQL configuration
        'postgresql' => [
            'host' => 'localhost',
            'port' => 5432,
            'dbname' => 'ldn_inbox',
            'username' => 'ldn_user',
            'password' => 'your_password',
            'table' => 'notifications'
        ],
        
        // MongoDB configuration
        'mongodb' => [
            'uri' => 'mongodb://localhost:27017',
            'database' => 'ldn_inbox',
            'collection' => 'notifications'
        ]
    ]
];

// Initialize database connection
$db = initializeDatabase($config);

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Process the request based on method
switch ($method) {
    case 'POST':
        handlePost($config, $db);
        break;
    case 'GET':
        handleGet($config, $db);
        break;
    case 'HEAD':
        // Same as GET but without body
        handleGet($config, $db, true);
        break;
    case 'OPTIONS':
        handleOptions($config);
        break;
    default:
        // Method not allowed
        header('HTTP/1.1 405 Method Not Allowed');
        header('Allow: GET, HEAD, POST, OPTIONS');
        exit;
}

/**
 * Initialize database connection based on configuration
 */
function initializeDatabase($config) {
    $dbType = $config['database']['type'];
    
    try {
        switch ($dbType) {
            case 'mysql':
                $db = new PDO(
                    'mysql:host=' . $config['database']['mysql']['host'] . 
                    ';port=' . $config['database']['mysql']['port'] . 
                    ';dbname=' . $config['database']['mysql']['dbname'],
                    $config['database']['mysql']['username'],
                    $config['database']['mysql']['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // Create table if it doesn't exist
                $db->exec("CREATE TABLE IF NOT EXISTS " . $config['database']['mysql']['table'] . " (
                    id VARCHAR(36) PRIMARY KEY,
                    content JSON NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                return $db;
                
            case 'postgresql':
                $db = new PDO(
                    'pgsql:host=' . $config['database']['postgresql']['host'] . 
                    ';port=' . $config['database']['postgresql']['port'] . 
                    ';dbname=' . $config['database']['postgresql']['dbname'],
                    $config['database']['postgresql']['username'],
                    $config['database']['postgresql']['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // Create table if it doesn't exist
                $db->exec("CREATE TABLE IF NOT EXISTS " . $config['database']['postgresql']['table'] . " (
                    id VARCHAR(36) PRIMARY KEY,
                    content JSONB NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                return $db;
                
            case 'mongodb':
                $mongoClient = new MongoDB\Client($config['database']['mongodb']['uri']);
                $db = $mongoClient->selectDatabase($config['database']['mongodb']['database']);
                return $db;
                
            default:
                throw new Exception("Unsupported database type: $dbType");
        }
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        die("Database connection error: " . $e->getMessage());
    }
}

/**
 * Handle POST requests - store incoming notifications
 */
function handlePost($config, $db) {
    // Check Content-Type header
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    $validContentType = false;
    
    foreach ($config['supportedContentTypes'] as $supportedType) {
        if (strpos($contentType, $supportedType) !== false) {
            $validContentType = true;
            break;
        }
    }
    
    if (!$validContentType) {
        header('HTTP/1.1 415 Unsupported Media Type');
        header('Accept: ' . implode(', ', $config['supportedContentTypes']));
        exit;
    }
    
    // Get the request body
    $input = file_get_contents('php://input');
    
    // Check size
    if (strlen($input) > $config['maxNotificationSize']) {
        header('HTTP/1.1 413 Payload Too Large');
        exit;
    }
    
    // Validate JSON
    $data = json_decode($input);
    if (json_last_error() !== JSON_ERROR_NONE) {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }
    
    // Generate a unique ID for the notification
    $id = generateUniqueId();
    $notificationUrl = $config['baseUrl'] . $config['inboxPath'] . $id;
    
    // Store the notification in the database
    try {
        storeNotification($config, $db, $id, $input);
        
        // Respond with 201 Created
        header('HTTP/1.1 201 Created');
        header('Location: ' . $notificationUrl);
        exit;
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        die("Error storing notification: " . $e->getMessage());
    }
}

/**
 * Store a notification in the database
 */
function storeNotification($config, $db, $id, $content) {
    $dbType = $config['database']['type'];
    
    switch ($dbType) {
        case 'mysql':
            $table = $config['database']['mysql']['table'];
            $stmt = $db->prepare("INSERT INTO $table (id, content) VALUES (?, ?)");
            $stmt->execute([$id, $content]);
            break;
            
        case 'postgresql':
            $table = $config['database']['postgresql']['table'];
            $stmt = $db->prepare("INSERT INTO $table (id, content) VALUES (?, ?)");
            $stmt->execute([$id, $content]);
            break;
            
        case 'mongodb':
            $collection = $db->selectCollection($config['database']['mongodb']['collection']);
            $collection->insertOne([
                '_id' => $id,
                'content' => json_decode($content),
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ]);
            break;
            
        default:
            throw new Exception("Unsupported database type: $dbType");
    }
}

/**
 * Handle GET requests - retrieve notifications
 */
function handleGet($config, $db, $headOnly = false) {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $inboxBasePath = rtrim(parse_url($config['inboxPath'], PHP_URL_PATH), '/');
    
    // If requesting the inbox itself, return a list of notifications
    if ($path == $inboxBasePath || $path == $inboxBasePath . '/') {
        // Set headers
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/ld+json');
        
        if (!$headOnly) {
            // Get all notifications
            $notifications = getNotificationsList($config, $db);
            
            // Create the inbox response
            $response = [
                '@context' => 'https://www.w3.org/ns/ldp',
                '@id' => $config['baseUrl'] . $config['inboxPath'],
                '@type' => [
                    'Container',
                    'BasicContainer',
                    'http://www.w3.org/ns/ldp#Inbox'
                ],
                'contains' => $notifications
            ];
            
            echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }
    
    // Otherwise, try to retrieve a specific notification
    $id = basename($path);
    $notification = getNotification($config, $db, $id);
    
    if ($notification) {
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/ld+json');
        
        if (!$headOnly) {
            if (is_string($notification)) {
                echo $notification;
            } else {
                echo json_encode($notification, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
        }
    } else {
        header('HTTP/1.1 404 Not Found');
        if (!$headOnly) {
            echo json_encode(['error' => 'Notification not found']);
        }
    }
    exit;
}

/**
 * Get a list of all notification URLs
 */
function getNotificationsList($config, $db) {
    $dbType = $config['database']['type'];
    $notifications = [];
    
    try {
        switch ($dbType) {
            case 'mysql':
                $table = $config['database']['mysql']['table'];
                $stmt = $db->query("SELECT id FROM $table ORDER BY created_at DESC");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $notifications[] = $config['baseUrl'] . $config['inboxPath'] . $row['id'];
                }
                break;
                
            case 'postgresql':
                $table = $config['database']['postgresql']['table'];
                $stmt = $db->query("SELECT id FROM $table ORDER BY created_at DESC");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $notifications[] = $config['baseUrl'] . $config['inboxPath'] . $row['id'];
                }
                break;
                
            case 'mongodb':
                $collection = $db->selectCollection($config['database']['mongodb']['collection']);
                $cursor = $collection->find([], [
                    'sort' => ['created_at' => -1],
                    'projection' => ['_id' => 1]
                ]);
                
                foreach ($cursor as $document) {
                    $notifications[] = $config['baseUrl'] . $config['inboxPath'] . $document['_id'];
                }
                break;
                
            default:
                throw new Exception("Unsupported database type: $dbType");
        }
    } catch (Exception $e) {
        // Log the error
        error_log("Error getting notifications list: " . $e->getMessage());
        return [];
    }
    
    return $notifications;
}

/**
 * Get a specific notification
 */
function getNotification($config, $db, $id) {
    $dbType = $config['database']['type'];
    
    try {
        switch ($dbType) {
            case 'mysql':
                $table = $config['database']['mysql']['table'];
                $stmt = $db->prepare("SELECT content FROM $table WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? $row['content'] : null;
                
            case 'postgresql':
                $table = $config['database']['postgresql']['table'];
                $stmt = $db->prepare("SELECT content FROM $table WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? $row['content'] : null;
                
            case 'mongodb':
                $collection = $db->selectCollection($config['database']['mongodb']['collection']);
                $document = $collection->findOne(['_id' => $id]);
                return $document ? $document['content'] : null;
                
            default:
                throw new Exception("Unsupported database type: $dbType");
        }
    } catch (Exception $e) {
        // Log the error
        error_log("Error getting notification: " . $e->getMessage());
        return null;
    }
}

/**
 * Handle OPTIONS requests - specify allowed methods and supported formats
 */
function handleOptions($config) {
    header('HTTP/1.1 200 OK');
    header('Allow: GET, HEAD, POST, OPTIONS');
    header('Accept-Post: ' . implode(', ', $config['supportedContentTypes']));
    exit;
}

/**
 * Generate a unique ID for a notification
 */
function generateUniqueId() {
    return bin2hex(random_bytes(16));
}
?>
