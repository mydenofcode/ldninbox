<?php
/**
 * LDN Database Setup Scripts
 * Scripts to set up databases for the LDN inbox implementation
 */

// Configuration - should match the configuration in the main script
$config = [
    'database' => [
        'mysql' => [
            'host' => 'localhost',
            'port' => 3306,
            'dbname' => 'ldn_inbox',
            'username' => 'ldn_user',
            'password' => 'your_password',
            'table' => 'notifications'
        ],
        'postgresql' => [
            'host' => 'localhost',
            'port' => 5432,
            'dbname' => 'ldn_inbox',
            'username' => 'ldn_user',
            'password' => 'your_password',
            'table' => 'notifications'
        ],
        'mongodb' => [
            'uri' => 'mongodb://localhost:27017',
            'database' => 'ldn_inbox',
            'collection' => 'notifications'
        ]
    ]
];

// Choose database type to set up
$dbType = isset($argv[1]) ? $argv[1] : null;

if (!in_array($dbType, ['mysql', 'postgresql', 'mongodb'])) {
    echo "Usage: php setup.php [mysql|postgresql|mongodb]\n";
    exit(1);
}

echo "Setting up $dbType database for LDN inbox...\n";

try {
    switch ($dbType) {
        case 'mysql':
            setupMySql($config);
            break;
        case 'postgresql':
            setupPostgresql($config);
            break;
        case 'mongodb':
            setupMongoDB($config);
            break;
    }
    
    echo "Database setup completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Set up MySQL database
 */
function setupMySql($config) {
    $dbConfig = $config['database']['mysql'];
    
    // Connect to MySQL server (without database)
    $pdo = new PDO(
        'mysql:host=' . $dbConfig['host'] . ';port=' . $dbConfig['port'],
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . $dbConfig['dbname']);
    echo "Database '" . $dbConfig['dbname'] . "' created or already exists.\n";
    
    // Connect to the database
    $pdo->exec("USE " . $dbConfig['dbname']);
    
    // Create table
    $pdo->exec("CREATE TABLE IF NOT EXISTS " . $dbConfig['table'] . " (
        id VARCHAR(36) PRIMARY KEY,
        content JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "Table '" . $dbConfig['table'] . "' created or already exists.\n";
}

/**
 * Set up PostgreSQL database
 */
function setupPostgresql($config) {
    $dbConfig = $config['database']['postgresql'];
    
    // Connect to PostgreSQL server
    $pdo = new PDO(
        'pgsql:host=' . $dbConfig['host'] . ';port=' . $dbConfig['port'] . ';dbname=postgres',
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Check if database exists
    $stmt = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '" . $dbConfig['dbname'] . "'");
    $exists = $stmt->fetchColumn();
    
    if (!$exists) {
        // Create database
        $pdo->exec("CREATE DATABASE " . $dbConfig['dbname']);
        echo "Database '" . $dbConfig['dbname'] . "' created.\n";
    } else {
        echo "Database '" . $dbConfig['dbname'] . "' already exists.\n";
    }
    
    // Connect to the database
    $pdo = new PDO(
        'pgsql:host=' . $dbConfig['host'] . ';port=' . $dbConfig['port'] . ';dbname=' . $dbConfig['dbname'],
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Create table
    $pdo->exec("CREATE TABLE IF NOT EXISTS " . $dbConfig['table'] . " (
        id VARCHAR(36) PRIMARY KEY,
        content JSONB NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "Table '" . $dbConfig['table'] . "' created or already exists.\n";
}

/**
 * Set up MongoDB database
 */
function setupMongoDB($config) {
    $dbConfig = $config['database']['mongodb'];
    
    // Connect to MongoDB
    $mongo = new MongoDB\Client($dbConfig['uri']);
    $db = $mongo->selectDatabase($dbConfig['database']);
    
    // Create collection (if it doesn't exist)
    if (!in_array($dbConfig['collection'], $db->listCollectionNames())) {
        $db->createCollection($dbConfig['collection']);
        echo "Collection '" . $dbConfig['collection'] . "' created.\n";
    } else {
        echo "Collection '" . $dbConfig['collection'] . "' already exists.\n";
    }
    
    // Create an index on the created_at field
    $collection = $db->selectCollection($dbConfig['collection']);
    $collection->createIndex(['created_at' => -1]);
    
    echo "Index created on 'created_at' field.\n";
}
?>
