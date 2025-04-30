<?php
/**
 * LDN Client Example
 * Example code to interact with an LDN inbox
 */

// Configuration
$config = [
    'inboxUrl' => 'https://example.com/inbox/',
    'contentType' => 'application/ld+json'
];

// Example 1: Send a notification to the inbox
function sendNotification($inboxUrl, $contentType) {
    // Create an Activity Streams 2.0 notification
    $notification = [
        '@context' => [
            'https://www.w3.org/ns/activitystreams',
            'https://www.w3.org/ns/ldp'
        ],
        'type' => 'Create',
        'actor' => 'https://sender.example.org/users/alice',
        'object' => [
            'type' => 'Note',
            'content' => 'This is a test notification sent to an LDN inbox.',
            'published' => date(DATE_ISO8601)
        ],
        'published' => date(DATE_ISO8601),
        'to' => ['https://recipient.example.org/users/bob']
    ];
    
    // Initialize cURL
    $ch = curl_init($inboxUrl);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: ' . $contentType,
        'Accept: ' . $contentType
    ]);
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $location = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    
    // Close cURL
    curl_close($ch);
    
    // Output the result
    echo "Sent notification to $inboxUrl\n";
    echo "HTTP Code: $httpCode\n";
    
    if ($httpCode == 201) {
        echo "Success! Notification created at: $location\n";
        return $location;
    } else {
        echo "Error sending notification: $response\n";
        return false;
    }
}

// Example 2: Get a list of all notifications in an inbox
function getNotifications($inboxUrl) {
    // Initialize cURL
    $ch = curl_init($inboxUrl);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/ld+json'
    ]);
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Close cURL
    curl_close($ch);
    
    // Output the result
    echo "Retrieved notifications from $inboxUrl\n";
    echo "HTTP Code: $httpCode\n";
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        echo "Inbox contains " . count($data['contains']) . " notifications:\n";
        foreach ($data['contains'] as $notification) {
            echo "- $notification\n";
        }
        return $data;
    } else {
        echo "Error retrieving notifications: $response\n";
        return false;
    }
}

// Example 3: Get a specific notification
function getNotification($notificationUrl) {
    // Initialize cURL
    $ch = curl_init($notificationUrl);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/ld+json'
    ]);
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Close cURL
    curl_close($ch);
    
    // Output the result
    echo "Retrieved notification from $notificationUrl\n";
    echo "HTTP Code: $httpCode\n";
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        echo "Notification content:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        return $data;
    } else {
        echo "Error retrieving notification: $response\n";
        return false;
    }
}

// Example 4: Send a notification with more complex content
function sendComplexNotification($inboxUrl, $contentType) {
    // Create a more complex Activity Streams 2.0 notification
    $notification = [
        '@context' => [
            'https://www.w3.org/ns/activitystreams',
            'https://www.w3.org/ns/ldp'
        ],
        'type' => 'Create',
        'actor' => [
            'type' => 'Person',
            'id' => 'https://sender.example.org/users/alice',
            'name' => 'Alice Smith',
            'url' => 'https://sender.example.org/users/alice'
        ],
        'object' => [
            'type' => 'Article',
            'id' => 'https://sender.example.org/articles/123',
            'name' => 'An interesting article about LDN',
            'content' => 'This is a test notification with more complex content structure.',
            'published' => date(DATE_ISO8601),
            'url' => 'https://sender.example.org/articles/123',
            'attributedTo' => 'https://sender.example.org/users/alice'
        ],
        'published' => date(DATE_ISO8601),
        'to' => [
            'https://recipient.example.org/users/bob',
            'https://recipient.example.org/users/charlie'
        ]
    ];
    
    // Initialize cURL
    $ch = curl_init($inboxUrl);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: ' . $contentType,
        'Accept: ' . $contentType
    ]);
    
    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $location = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    
    // Close cURL
    curl_close($ch);
    
    // Output the result
    echo "Sent complex notification to $inboxUrl\n";
    echo "HTTP Code: $httpCode\n";
    
    if ($httpCode == 201) {
        echo "Success! Notification created at: $location\n";
        return $location;
    } else {
        echo "Error sending notification: $response\n";
        return false;
    }
}

// Run the examples
echo "=== Example 1: Send a basic notification ===\n";
$location = sendNotification($config['inboxUrl'], $config['contentType']);

echo "\n=== Example 2: Get all notifications ===\n";
$inbox = getNotifications($config['inboxUrl']);

if ($location) {
    echo "\n=== Example 3: Get a specific notification ===\n";
    getNotification($location);
}

echo "\n=== Example 4: Send a complex notification ===\n";
$complexLocation = sendComplexNotification($config['inboxUrl'], $config['contentType']);
?>
