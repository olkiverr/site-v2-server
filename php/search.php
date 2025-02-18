<?php
include 'db.php';

if (isset($_GET['query'])) {
    $search = mysqli_real_escape_string($conn, $_GET['query']);
    
    $sql = "SELECT id, title, img FROM pages WHERE title LIKE '%$search%'";
    $result = $conn->query($sql);
    
    $searchResults = array();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $searchResults[] = array(
                'id' => $row['id'],
                'title' => $row['title'],
                'img' => $row['img']
            );
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($searchResults);
}

$conn->close();
?> 