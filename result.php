<?php
session_start();

// Regenerate session ID to mitigate session fixation attacks
session_regenerate_id(true);

$servername = "localhost";
$username = "id21897242_parth10";
$password = "Parth@191";
$dbname = "id21897242_searchdexter";


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user token is not set or has expired
if (!isset($_SESSION['user_token']) || $_SESSION['token_expiration'] < time()) {
    // Generate a new random token and update session
    $length = 32;
    try {
        $_SESSION['user_token'] = substr(bin2hex(random_bytes($length)), 0, $length);
    } catch (Exception $e) {
    }
    $_SESSION['token_expiration'] = time() + (24 * 60 * 60); // 24 hours expiration
}
// Retrieve the existing or newly generated user token
$userToken = $_SESSION['user_token'];

function saveSearchTerm($conn, $searchTerm, $userToken)
{
    $searchTerm = $conn->real_escape_string($searchTerm);

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO searches (search_term, search_time, user_Token) VALUES (?, NOW(), ?) ON DUPLICATE KEY UPDATE search_time = NOW()");
    $stmt->bind_param("ss", $searchTerm, $userToken);
    $stmt->execute();
    $stmt->close();
}

// Function to retrieve recent searches from the database
function getRecentSearches($conn, $userToken)
{
    $recentSearches = array();

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT search_term, COUNT(search_term) as count, MAX(search_time) as max_time FROM searches WHERE user_Token = ? GROUP BY search_term ORDER BY MAX(search_time) DESC LIMIT 4");
    $stmt->bind_param("s", $userToken);
    $stmt->execute();

    $result = $stmt->get_result();

    if (!$result) {
        die("Error in SQL query: " . $conn->error);
    }


    // Fetch recent searches into an array
    while ($row = mysqli_fetch_assoc($result)) {
        $recentSearches[] = $row['search_term'];
    }

    $stmt->close();

    return $recentSearches;
}

function googleweb($searchTerm){
    // Using file_get_contents for the Google Custom Search API
    $api_key = 'AIzaSyBE-RTu20sjAsA-3T1eJVGCt4cih2lWbvo';
    $cx = '32d433a2f0f444f66';
    $url = 'https://www.googleapis.com/customsearch/v1?q=' . urlencode($searchTerm) . '&key=' . $api_key . '&cx=' . $cx . '&safe=high';


    $api_response = @file_get_contents($url);

    if ($api_response !== false) {
        $result = json_decode($api_response, true);

        if (isset($result['items'])) {
            // Display results from the Google Custom Search API
            foreach ($result['items'] as $item) {
                echo "<div class='result-container'>";
                echo "<b><div id='rl'><a class='result-link' href='{$item["link"]}'>{$item["title"]}</a></div></b>";
                echo "<div><a class='result-url' href='{$item["link"]}'>{$item["link"]}</a></div>";
                if (!empty($item["snippet"])) {
                    echo "<span class='result-snippet'>" . substr($item["snippet"], 0, 100) . "...</span></div>";
                } else {
                    echo "<span class='result-snippet'>No information available</span></div>";
                }
                echo "</div>";
            }

        } else {
            echo "<br><div style=\" font-size: 30px; text-align: center;\">No Result Found</div>";
        }
    } else {
        $error = error_get_last();
        echo "<br><div style=\" font-size: 30px; text-align: center;\">Error fetching results from the API. Error: {$error['message']}</div>";
    }
}


function fetchImagesFromGoogleAPI($searchTerm)
{
    $apiKey = 'AIzaSyBE-RTu20sjAsA-3T1eJVGCt4cih2lWbvo';
    $cx = '32d433a2f0f444f66'; // Replace with your actual custom search engine ID
    $url = "https://www.googleapis.com/customsearch/v1?q=" . urlencode($searchTerm) . "&key=$apiKey&cx=$cx&searchType=image&safe=high";

    $response = @file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);

        if (isset($data['items'])) {
            $imagesPerRow = 5;
            $imageChunks = array_chunk($data['items'], $imagesPerRow);

            foreach ($imageChunks as $imageRow) {
                echo '<div class="image-row">';

                foreach ($imageRow as $photo) {
                    $title = $photo['title'] ?? 'Untitled';
                    $url = $photo['link'];

                    echo '<div class="google-image">';
                    echo '<img src="' . $url . '" alt="' . $title . '" onclick="openModal(\'' . $url . '\', \'' . $title . '\')">';
                    echo '<div id="imaget2">' . $title . '</div>';
                    echo '</div>';
                }

                echo '</div>';
            }
        } else {
            echo "<br><div style=\"font-size: 30px; text-align: center;\">No images found for the given search term.</div>";
        }
    } else {
        echo "<br><div style=\"font-size: 30px; text-align: center;\">Error fetching images, Please try again with Proper Search term.</div>";
    }
}


function searchImages($searchTerm)
{
    $apiKey = 'FyLIfZMIwS0v5rq8Ecwd9N9BKCG3EZi4lkDUX6qn_Ls';
    $url = "https://api.unsplash.com/photos/random?query=" . urlencode($searchTerm) . "&count=25&client_id=$apiKey&orientation=landscape&content_filter=high";

    $response = @file_get_contents($url);

    if ($response !== false) {
        $data = json_decode($response, true);

        $imagesPerRow = 5;
        $imageChunks = array_chunk($data, $imagesPerRow);

        foreach ($imageChunks as $imageRow) {
            echo '<div class="image-row">';

            foreach ($imageRow as $photo) {
                $title = $photo['alt_description'] ?? 'Untitled';
                $url = $photo['urls']['regular'];

                echo '<div class="unsplash-image">';
                echo '<img src="' . $url . '" alt="' . $title . '" onclick="openModal(\'' . $url . '\', \'' . $title . '\')">';
                echo '<div id="imaget2">' . $title . '</div>';
                echo '</div>';
            }

            echo '</div>';
        }
    } else {
        echo "<br><div style=\"font-size: 30px; text-align: center;\">Error fetching images, Please try again with Proper Search term.</div>";
    }
}
function generateFeatureSnippetDuckDuckGo($searchTerm): string
{
    // DuckDuckGo Instant Answer API endpoint
    $url = "https://api.duckduckgo.com/?q=" . urlencode($searchTerm) . "&format=json&skip_disambig=1&ia=web&iax=1&iai=1&no_redirect=1";

    // Make the API request
    $response = @file_get_contents($url);

    // Check if the request was successful
    if ($response !== false) {
        // Output the raw API response for debugging
        // echo "DuckDuckGo API Response: " . $response;

        // Decode the JSON response
        $data = json_decode($response, true);

        // Check if there is a related snippet
        if (isset($data['AbstractText'])) {
            // Truncate the snippet to 25 words
            $snippet = $data['AbstractText'];
            $words = preg_split('/\s+/', $snippet);
            return implode(' ', array_slice($words, 0, 50));
        }
    }

    return '';
}

function isShoppingRelated($searchTerm)
{
    $shoppingKeywords = array('shopping', 'buy', 'store', 'online shopping' , 'clothes', 'fashion' , 'cart' ,'amazon' ,'flipkart' , 'myntra' , 'mesho',);
    foreach ($shoppingKeywords as $keyword) {
        if (stripos($searchTerm, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

// Handle form submission
if (isset($_GET['searchbtn'])) {
    $search = trim($conn->real_escape_string($_GET['search']));
    if (!empty($search)) {
        // Save search term to the database
        saveSearchTerm($conn, $search, $userToken);
    }
} elseif (isset($_GET['imagebtn'])) {
    $search = trim($conn->real_escape_string($_GET['search']));
    if (!empty($search)) {
        // Save search term to the database
        saveSearchTerm($conn, $search, $userToken);
    }
}

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
        <label for="searchfield"></label><input type="text" name="search" id="searchfield"
                                                value="<?php echo $_GET['search'] ?? ''; ?>">
        <input type="submit" name="searchbtn" value="Go!" id="gobtn">
        <img id="voiceSearchBtn"
             src="https://icon-library.com/images/google-voice-search-icon/google-voice-search-icon-27.jpg"
             alt="Voice Search" onclick="startVoiceRecognition()">
        <!-- Display recent searches -->
        <div id="recentSearches">
            <strong>Recent Searches:</strong>
            <?php foreach ($recentSearches as $recentSearch) : ?>
                <div class="recent-search-item">
                    <div class="search-text">
                        <a href="#"
                           onclick="useRecentSearch('<?php echo $recentSearch; ?>')"><?php echo $recentSearch; ?></a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="nav">
        <!-- Modify the search options to call setSearchOption and submit the form -->
        <input type="submit" name="searchbtn" value="Web" id="webbtn"
               class="<?php echo isset($_GET['searchbtn']) && !isset($_GET['imagebtn']) ? 'active' : ''; ?>" onclick="setSearchOption('web')">

        <input type="submit" name="imagebtn" value="Images" id="imagebtn"
               class="<?php echo isset($_GET['imagebtn']) ? 'active' : ''; ?>" onclick="setSearchOption('images')">
    </div>
</form>
<div id="imageModal" class="modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <img id="modalImage" class="modal-content" alt="Zoomed Image">
    <div id="modalTitle" style="text-align: left; font-size: 20px; margin-top: 10px;"></div>
</div>


<?php
function dbResult($dbResult): void
{
    while ($row = mysqli_fetch_assoc($dbResult)) {
        echo "<div class='result-container'>";
        echo "<b><div id='rl'><a class='result-link' href='https://{$row["link"]}'>{$row["title"]}</a></div></b>";
        echo "<div><a class='result-url' href='https://{$row["link"]}'>https://{$row["link"]}</a></div>";
        echo "<span class='result-snippet'>" . substr($row["snippet"], 0, 100) . "...</span>";
        echo "<div class='external-links-count'>External Links: {$row['external_links_count']}</div>";
        echo "</div>";
    }
}


if (isset($_GET['searchbtn']) && !isset($_GET['imagebtn']))
{
    if (!empty($search)) {
        $searchTerm = mysqli_real_escape_string($conn, $search);


        $dbQuery = "SELECT w.*, COUNT(c.to_url) as external_links_count
                    FROM websites w
                    LEFT JOIN connections c ON w.link = c.to_url
                    WHERE w.link LIKE '%$searchTerm%'
                    GROUP BY w.link
                    ORDER BY external_links_count DESC, w.title
                    LIMIT 25";
        $dbResult = mysqli_query($conn, $dbQuery);
        if ($dbResult === false) {
            die("Error in SQL query: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($dbResult) > 10) {
            if (!empty(generateFeatureSnippetDuckDuckGo($searchTerm))) {
                echo '<div class="featured-snippet">';
                echo '<div class="snippet-content">' . generateFeatureSnippetDuckDuckGo($searchTerm) . '...</div>';
                echo '</div>';
            }
            // Add a sponsored website if the search term is related to shopping
            if ( isShoppingRelated($searchTerm)) {
                echo "<div class='result-container sponsored-result'>";
                echo "<b><div id='rl'><a class='result-link' href='https://example.com'><button> <span>Ad</span></button> Example </a></div></b>";
                echo "<div><a class='result-url' href='https://example.com'>https://example.com</a></div>";
                echo "<span class='result-snippet'>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</span></div>";
                echo "</div>";
            }
            dbResult($dbResult);
            echo "<br>";
        } else {
            if (!empty(generateFeatureSnippetDuckDuckGo($searchTerm))) {
                echo '<div class="featured-snippet">';
                echo '<div class="snippet-content" >' . generateFeatureSnippetDuckDuckGo($searchTerm) . '...</div>';
                echo '</div>';
            }
            if ( isShoppingRelated($searchTerm)) {
                echo "<div class='result-container sponsored-result'>";
                echo "<b><div id='rl'><a class='result-link' href='https://example.com'><button> <span>Ad</span></button> Example </a></div></b>";
                echo "<div><a class='result-url' href='https://example.com'>https://example.com</a></div>";
                echo "<span class='result-snippet'>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</span></div>";
                echo "</div>";
            }
            dbResult($dbResult);
            googleweb($searchTerm);
            echo "<br>";
        }
    } else {
        echo "<br><div style=\" font-size: 30px; text-align: center;\">Please enter a search term</div>";
    }
} elseif (isset($_GET['imagebtn'])) {
    fetchImagesFromGoogleAPI($searchTerm);
    searchImages($searchTerm);
}
mysqli_close($conn);
?>
<script src="script.js"></script>
</body>
</html>