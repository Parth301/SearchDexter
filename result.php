<?php 
session_start(); 

// Regenerate session ID to mitigate session fixation attacks
session_regenerate_id(true);

// Database credentials
$servername = "localhost";
$username = "kzrfzumm_searchdexter";
$password = "Pq48GukbE3wtCCSm8L6t";
$dbname = "kzrfzumm_searchdexter";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate a user token if not set or expired
if (!isset($_SESSION['user_token']) || $_SESSION['token_expiration'] < time()) {
    $length = 32;
    try {
        $_SESSION['user_token'] = substr(bin2hex(random_bytes($length)), 0, $length);
    } catch (Exception $e) {
        // Handle exception (e.g., log error)
    }
    $_SESSION['token_expiration'] = time() + (24 * 60 * 60); // Token expires in 24 hours
}

// Retrieve user token
$userToken = $_SESSION['user_token'];

/**
 * Save a search term to the database.
 */
function saveSearchTerm($conn, $searchTerm, $userToken)
{
    $searchTerm = $conn->real_escape_string($searchTerm);

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO searches (search_term, search_time, user_token) VALUES (?, NOW(), ?) ON DUPLICATE KEY UPDATE search_time = NOW()");
    $stmt->bind_param("ss", $searchTerm, $userToken);
    $stmt->execute();
    $stmt->close();
}

/**
 * Retrieve recent searches from the database.
 */
function getRecentSearches($conn, $userToken)
{
    $recentSearches = array();

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT search_term FROM searches WHERE user_token = ? ORDER BY search_time DESC LIMIT 4");
    $stmt->bind_param("s", $userToken);
    $stmt->execute();

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentSearches[] = $row['search_term'];
    }

    $stmt->close();
    return $recentSearches;
}

/**
 * Fetch search results from Google Custom Search API.
 */
function googleweb($searchTerm)
{
    $api_key = 'AIzaSyBE-RTu20sjAsA-3T1eJVGCt4cih2lWbvo';
    $cx = '32d433a2f0f444f66';
    $url = 'https://www.googleapis.com/customsearch/v1?q=' . urlencode($searchTerm) . '&key=' . $api_key . '&cx=' . $cx . '&safe=high';

    $api_response = @file_get_contents($url);

    if ($api_response !== false) {
        $result = json_decode($api_response, true);

        if (isset($result['items'])) {
            foreach ($result['items'] as $item) {
                echo "<div class='result-container'>";
                echo "<b><div id='rl'><a class='result-link' href='{$item["link"]}'>{$item["title"]}</a></div></b>";
                echo "<div><a class='result-url' href='{$item["link"]}'>{$item["link"]}</a></div>";
                echo "<span class='result-snippet'>" . (isset($item["snippet"]) ? substr($item["snippet"], 0, 100) : "No information available") . "...</span></div>";
            }
        } else {
            echo "<div style='font-size: 30px; text-align: center;'>No Result Found</div>";
        }
    } else {
        $error = error_get_last();
        echo "<div style='font-size: 30px; text-align: center;'>Error fetching results from the API. Error: {$error['message']}</div>";
    }
}

/**
 * Fetch images from Google Custom Search API.
 */
function fetchImagesFromGoogleAPI($searchTerm)
{
    $apiKey = 'AIzaSyBE-RTu20sjAsA-3T1eJVGCt4cih2lWbvo';
    $cx = '32d433a2f0f444f66';
    $url = "https://www.googleapis.com/customsearch/v1?q=" . urlencode($searchTerm) . "&key=$apiKey&cx=$cx&searchType=image&safe=high";

    $response = @file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['items'])) {
            foreach ($data['items'] as $photo) {
                echo "<div class='google-image'>";
                echo "<img src='{$photo['link']}' alt='{$photo['title']}'>";
                echo "<div>{$photo['title']}</div>";
                echo "</div>";
            }
        } else {
            echo "<div style='font-size: 30px; text-align: center;'>No images found for the given search term.</div>";
        }
    } else {
        echo "<div style='font-size: 30px; text-align: center;'>Error fetching images. Please try again with a valid search term.</div>";
    }
}

// Handle form submission
if (isset($_GET['searchbtn'])) {
    $search = trim($conn->real_escape_string($_GET['search']));
    if (!empty($search)) {
        saveSearchTerm($conn, $search, $userToken);
    }
} elseif (isset($_GET['imagebtn'])) {
    $search = trim($conn->real_escape_string($_GET['search']));
    if (!empty($search)) {
        saveSearchTerm($conn, $search, $userToken);
    }
}

// Retrieve search term and recent searches
$searchTerm = $_GET['search'] ?? '';
$recentSearches = getRecentSearches($conn, $userToken);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dexter Result Page</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<form action="#" method="GET" id="searchForm" onsubmit="return validateSearch()">
    <div id="logo">
        <a href="index.html"><img src="Se.png" alt="Logo"></a>
    </div>
    <div id="logo1">
        <a href="index.html"><img src="Dexter.png" alt="Logo"></a>
    </div>

    <div id="sbar">
        <label for="searchfield"></label>
        <input type="text" name="search" id="searchfield" value="<?php echo $searchTerm; ?>">
        <input type="submit" name="searchbtn" value="Go!" id="gobtn">
        <img id="voiceSearchBtn" src="https://icon-library.com/images/google-voice-search-icon/google-voice-search-icon-27.jpg" alt="Voice Search" onclick="startVoiceRecognition()">
        <div id="recentSearches">
            <strong>Recent Searches:</strong>
            <?php foreach ($recentSearches as $recentSearch): ?>
                <div class="recent-search-item">
                    <a href="#" onclick="useRecentSearch('<?php echo $recentSearch; ?>')"><?php echo $recentSearch; ?></a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="nav">
        <input type="submit" name="searchbtn" value="Web" id="webbtn" class="<?php echo isset($_GET['searchbtn']) && !isset($_GET['imagebtn']) ? 'active' : ''; ?>">
        <input type="submit" name="imagebtn" value="Images" id="imagebtn" class="<?php echo isset($_GET['imagebtn']) ? 'active' : ''; ?>">
    </div>
</form>

<?php
if (isset($_GET['searchbtn']) && !isset($_GET['imagebtn'])) {
    if (!empty($searchTerm)) {
        googleweb($searchTerm);
    } else {
        echo "<div style='font-size: 30px; text-align: center;'>Please enter a search term</div>";
    }
} elseif (isset($_GET['imagebtn'])) {
    fetchImagesFromGoogleAPI($searchTerm);
}
mysqli_close($conn);
?>

<script src="script.js"></script>
</body>
</html>
