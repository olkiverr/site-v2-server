<?php

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the function name from the POST data
    $functionName = isset($_POST['functionname']) ? $_POST['functionname'] : '';

    // Call the appropriate function based on the function name
    if ($functionName === 'addImage') {
        // Get the other parameters
        $category = isset($_POST['category']) ? $_POST['category'] : '';
        $url = isset($_POST['url']) ? $_POST['url'] : '';
        $title = isset($_POST['title']) ? $_POST['title'] : '';

        // Call the function to handle the data
        $response = addImage($category, $url, $title);
    } else {
        // If the function name is not recognized
        echo json_encode(['error' => 'Function not found']);
    }
} else {
    // If the request method is not POST
    echo json_encode(['error' => 'Invalid request method']);
}

// Define your PHP function
function addImage($category, $url, $title) {
    include 'db.php';

    $sql = "INSERT INTO images (url, name, category) VALUES ('$url', '$title', '$category')";
    if ($conn->query($sql) === TRUE) {
        header("Location: ../index.php");
    };
    
    $conn->close();
}
?>