<?php
include '../php/db.php';

// Liste des genres interdits
$forbidden_genres = ['Ecchi', 'Erotica', 'Hentai'];

// Récupérer tous les genres uniques de la base de données en excluant les contenus interdits
$sql = "SELECT DISTINCT genres FROM pages 
        WHERE genres NOT LIKE '%Ecchi%' 
        AND genres NOT LIKE '%Erotica%' 
        AND genres NOT LIKE '%Hentai%'";
$result = $conn->query($sql);
$all_genres = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $genres = explode(',', $row['genres']);
        foreach ($genres as $genre) {
            $genre = trim($genre);
            if (!in_array($genre, $all_genres) && !empty($genre)) {
                $all_genres[] = $genre;
            }
        }
    }
}
sort($all_genres); // Trier les genres par ordre alphabétique

// Nous pouvons supprimer ces lignes car nous filtrons déjà dans la requête SQL
// $forbidden_genres = ['Ecchi', 'Erotica', 'Hentai'];
// $all_genres = array_diff($all_genres, $forbidden_genres);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Anime - MangaMuse</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <style>
        .anime-search-container {
            padding: 20px;
            color: white;
            max-width: 1200px;
            margin: 0 auto;
        }

        .anime-search-options {
            margin-bottom: 20px;
        }

        .anime-title-search {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            background-color: #333;
            border: 1px solid #444;
            color: white;
            border-radius: 4px;
        }

        .anime-genres-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .anime-genre-checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .anime-search-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .anime-result-card {
            background-color: #333;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
            cursor: pointer;
        }

        .anime-result-card:hover {
            transform: scale(1.05);
        }

        .anime-result-card img {
            width: 100%;
            height: 280px;
            object-fit: cover;
        }

        .anime-result-card .anime-title {
            padding: 10px;
            text-align: center;
        }

        .anime-search-button {
            background-color: #4a4a4a;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .anime-search-button:hover {
            background-color: #5a5a5a;
        }

        .status-filters {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 10px 0;
        }

        .anime-status-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .anime-status-checkbox input[type="checkbox"] {
            cursor: pointer;
        }

        .anime-status-checkbox label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        .anime-status-checkbox i {
            color: #ffd700;
        }

        .anime-status-checkbox:last-child i {
            color: #ffffff;
        }
    </style>
</head>
<body>
    <?php include '../partials/header.php'; ?>
    
    <main>
        <div class="anime-search-container">
            <h1>Search Anime</h1>
            
            <div class="anime-search-options">
                <input type="text" id="titleSearch" class="anime-title-search" placeholder="Search by title...">
                
                <div class="status-filters" style="margin-bottom: 20px;">
                    <div class="anime-status-checkbox">
                        <input type="checkbox" id="filter-favorite" value="favorite">
                        <label for="filter-favorite">
                            <i class="fas fa-star"></i> Favorites only
                        </label>
                    </div>
                    <div class="anime-status-checkbox">
                        <input type="checkbox" id="filter-watched" value="watched">
                        <label for="filter-watched">
                            <i class="fas fa-eye"></i> Watched only
                        </label>
                    </div>
                </div>

                <h3>Filter by Genres</h3>
                <div class="anime-genres-container">
                    <?php foreach ($all_genres as $genre): ?>
                    <div class="anime-genre-checkbox">
                        <input type="checkbox" id="genre-<?php echo htmlspecialchars($genre); ?>" value="<?php echo htmlspecialchars($genre); ?>">
                        <label for="genre-<?php echo htmlspecialchars($genre); ?>"><?php echo htmlspecialchars($genre); ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button class="anime-search-button" onclick="searchAnime()">Search</button>
            </div>
            
            <div id="searchResults" class="anime-search-results">
                <!-- Les résultats seront insérés ici -->
            </div>
        </div>
    </main>

    <?php include '../partials/footer.php'; ?>

    <script>
        function searchAnime() {
            const title = document.getElementById('titleSearch').value;
            const selectedGenres = Array.from(document.querySelectorAll('.anime-genre-checkbox input[type="checkbox"]:checked'))
                .map(cb => cb.value);
            const isFavoriteOnly = document.getElementById('filter-favorite').checked;
            const isWatchedOnly = document.getElementById('filter-watched').checked;
            
            const searchParams = new URLSearchParams();
            searchParams.append('title', title);
            selectedGenres.forEach(genre => searchParams.append('genres[]', genre));
            searchParams.append('favorite', isFavoriteOnly);
            searchParams.append('watched', isWatchedOnly);
            
            fetch(`../php/advanced_search.php?${searchParams.toString()}`)
                .then(response => response.json())
                .then(data => {
                    const resultsContainer = document.getElementById('searchResults');
                    resultsContainer.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(anime => {
                            const card = document.createElement('div');
                            card.className = 'anime-result-card';
                            card.innerHTML = `
                                <img src="${anime.img}" alt="${anime.title}">
                                <div class="anime-title">${anime.title}</div>
                            `;
                            card.addEventListener('click', () => {
                                window.location.href = `view_anime.php?id=${anime.id}`;
                            });
                            resultsContainer.appendChild(card);
                        });
                    } else {
                        resultsContainer.innerHTML = '<p>No results found</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    </script>
</body>
</html> 