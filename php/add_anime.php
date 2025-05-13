<?php
// Include database connection
include('db.php');
include('session_config.php'); // Ajout pour accéder à $_SESSION

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id'])) {
    echo "Erreur: Vous devez être connecté pour ajouter des animes.";
    exit();
}

// Increase the execution time limit to avoid max execution time errors
set_time_limit(0); // 0 means no time limit

$page = isset($_GET['page']) ? $_GET['page'] : 1;
echo $page;

// Function to fetch and insert anime with retry logic
function insertAnime($url, $category, $conn, $user_id) {
    $maxRetries = 3;
    $attempts = 0;
    $success = false;
    
    while ($attempts < $maxRetries && !$success) {
        $attempts++;
        
        try {
            $response = file_get_contents($url);
            if ($response === false) {
                throw new Exception("Error fetching API data.");
            }

            $data = json_decode($response, true);
            if (!isset($data["data"]) || empty($data["data"])) {
                throw new Exception("No data found.");
            }

            // Process the anime data
            foreach ($data["data"] as $anime) {
                $id = $anime["mal_id"];
                $title = $anime["title_english"] ?? $anime["title"]; 
                $broadcast = $anime["broadcast"]["string"] ?? "Unknown";
                $episodes = $anime["episodes"] ?? 0;
                $img = $anime["images"]["jpg"]["image_url"] ?? "";
                $synopsis = $anime["synopsis"] ?? "No description available.";

                // Extract genres
                $genres = "Not specified";
                if (!empty($anime["genres"]) && is_array($anime["genres"])) {
                    $genresArray = array_column($anime["genres"], "name");
                    $genres = !empty($genresArray) ? implode(", ", $genresArray) : "Not specified";
                }

                // Extract studio
                $studio = "Not specified";
                if (!empty($anime["studios"]) && is_array($anime["studios"])) {
                    $studioArray = array_column($anime["studios"], "name");
                    $studio = !empty($studioArray) ? implode(", ", $studioArray) : "Not specified";
                }

                // SIMPLIFIED: Get mangaka (creator) by using anime ID to find manga ID
                $creator = "Not specified";

                // Get detailed anime information to find manga source
                $animeUrl = "https://api.jikan.moe/v4/anime/" . $id . "/full";
                $animeResponse = file_get_contents($animeUrl);

                if ($animeResponse !== false) {
                    $animeData = json_decode($animeResponse, true);

                    // Look for manga adaptation source
                    if (isset($animeData["data"]["relations"]) && is_array($animeData["data"]["relations"])) {
                        foreach ($animeData["data"]["relations"] as $relation) {
                            // Look specifically for manga that is a source material
                            if (isset($relation["relation"]) && 
                                ($relation["relation"] == "Adaptation" || $relation["relation"] == "Source")) {

                                if (!empty($relation["entry"]) && is_array($relation["entry"])) {
                                    foreach ($relation["entry"] as $entry) {
                                        if (isset($entry["type"]) && 
                                            (strtolower($entry["type"]) == "manga" || 
                                             strtolower($entry["type"]) == "light novel" || 
                                             strtolower($entry["type"]) == "novel")) {

                                            $mangaId = $entry["mal_id"];

                                            // Add delay to avoid rate limiting
                                            sleep(2);

                                            // Get manga details to find author
                                            $mangaUrl = "https://api.jikan.moe/v4/manga/" . $mangaId;
                                            $mangaResponse = file_get_contents($mangaUrl);

                                            if ($mangaResponse !== false) {
                                                $mangaData = json_decode($mangaResponse, true);

                                                // Look for authors (specifically mangaka)
                                                if (isset($mangaData["data"]["authors"]) && is_array($mangaData["data"]["authors"])) {
                                                    $authorsArray = [];

                                                    foreach ($mangaData["data"]["authors"] as $author) {
                                                        if (isset($author["name"])) {
                                                            $authorsArray[] = $author["name"];
                                                        }
                                                    }

                                                    if (!empty($authorsArray)) {
                                                        $creator = implode(", ", $authorsArray);
                                                        break 2; // Exit both loops after finding the mangaka
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // As a fallback, check if the mangaka is listed in the staff
                if ($creator == "Not specified") {
                    sleep(1);

                    $staffUrl = "https://api.jikan.moe/v4/anime/" . $id . "/staff";
                    $staffResponse = file_get_contents($staffUrl);

                    if ($staffResponse !== false) {
                        $staffData = json_decode($staffResponse, true);

                        if (isset($staffData["data"]) && is_array($staffData["data"])) {
                            $mangakaArray = [];

                            foreach ($staffData["data"] as $staff) {
                                if (isset($staff["positions"]) && is_array($staff["positions"])) {
                                    foreach ($staff["positions"] as $position) {
                                        // Look for positions that identify the original creator
                                        if (stripos($position, "Original") !== false || 
                                            stripos($position, "Original Creator") !== false || 
                                            stripos($position, "Original Story") !== false ||
                                            stripos($position, "Mangaka") !== false) {

                                            if (isset($staff["person"]["name"])) {
                                                $mangakaArray[] = $staff["person"]["name"];
                                            }
                                        }
                                    }
                                }
                            }

                            if (!empty($mangakaArray)) {
                                $creator = implode(", ", $mangakaArray);
                            }
                        }
                    }
                }

                // Check if anime already exists in the database
                $stmt = $conn->prepare("SELECT id FROM pages WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    echo "⚠️ Anime '$title' (ID: $id) is already in the database. Skipping...<br>";
                } else {
                    // Insert anime into database
                    $background_color = "#252525";
                    $border_color = "#333333";
                    $title_color = "#ffffff";
                    $label_color = "#ffffff";
                    $text_color = "#ffffff";

                    $style = "
                        .img-infos {
                            display: flex;
                            flex-direction: row;
                            height: 40%;
                            width: 100%;
                            padding: 10px 0;
                            background-color: $background_color;
                        }

                        .img {
                            display: flex;
                            justify-content: space-around;
                            width: 30%;
                            height: 100%;
                        }

                        .img > img {
                            height: 100%;
                            border-radius: 10px;
                        }

                        .infos {
                            width: 70%;
                            height: 100%;
                            border-left: 1px solid $border_color;
                            padding-left: 10px;
                        }

                        .infos h2 {
                            color: $title_color;
                        }

                        .infos strong {
                            color: $label_color;
                        }

                        .infos li {
                            color: $text_color;
                        }

                        .description {
                            color: $text_color;
                            height: 60%;
                            width: 100%;
                            padding: 10px 0;
                            background-color: $background_color;
                        }";

                    // Utiliser la procédure stockée pour insérer les données
                    $table_name = "pages";
                    $column_names = "id, title, creator, broadcast, genres, episodes, studio, img, category, description, style, background_color, border_color, title_color, label_color, text_color";
                    
                    // Échapper correctement les valeurs pour éviter les problèmes avec les apostrophes
                    $title = str_replace("'", "''", $title);
                    $creator = str_replace("'", "''", $creator);
                    $broadcast = str_replace("'", "''", $broadcast);
                    $genres = str_replace("'", "''", $genres);
                    $studio = str_replace("'", "''", $studio);
                    $img = str_replace("'", "''", $img);
                    $synopsis = str_replace("'", "''", $synopsis);
                    $style = str_replace("'", "''", $style);
                    
                    $values = "$id, '$title', '$creator', '$broadcast', '$genres', $episodes, '$studio', '$img', '$category', '$synopsis', '$style', '$background_color', '$border_color', '$title_color', '$label_color', '$text_color'";
                    
                    $callStmt = $conn->prepare("CALL insert_data_admin_only(?, ?, ?, ?)");
                    $callStmt->bind_param("isss", $user_id, $table_name, $column_names, $values);
                    $callStmt->execute();
                    
                    // Récupérer le résultat de la procédure
                    $result = $callStmt->get_result();
                    $response = $result->fetch_assoc();
                    
                    if ($response['success']) {
                        echo "✅ Anime '$title' added to database.<br>";
                    } else {
                        echo "❌ Error: " . $response['message'] . "<br>";
                    }
                    $callStmt->close();
                }
                $stmt->close();

                // Exit after processing one anime successfully
                $success = true;
            }

        } catch (Exception $e) {
            echo "❌ Attempt $attempts failed: " . $e->getMessage() . "<br>";

            // Wait before retrying (optional)
            sleep(5);
        }
    }

    // If still not successful after 3 attempts, skip the current anime
    if (!$success) {
        echo "❌ Failed to process anime after 3 attempts. Skipping...<br>";
    }
}

// Récupérer l'ID de l'utilisateur de la session
$user_id = $_SESSION['id'];

// 🔥 Fetch trending anime
insertAnime("https://api.jikan.moe/v4/top/anime?page=$page", "trending", $conn, $user_id);

// 📅 Fetch upcoming anime
insertAnime("https://api.jikan.moe/v4/seasons/upcoming?page=$page", "upcoming", $conn, $user_id);

// 🔍 Fetch general anime (non-categorized)
insertAnime("https://api.jikan.moe/v4/anime?page=$page", "none", $conn, $user_id);

// Close database connection
$conn->close();
?>
