<?php

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the function name from the POST data
    $functionName = isset($_POST['functionname']) ? $_POST['functionname'] : '';

    // Call the appropriate function based on the function name
    if ($functionName === 'deleteImage') {
        // Get the other parameters
        $category = isset($_POST['category']) ? $_POST['category'] : '';
        $imageId = isset($_POST['imageId']) ? $_POST['imageId'] : '';

        // Call the function to handle the data
        $response = deleteImage($category, $imageId);
    } else {
        // If the function name is not recognized
        echo json_encode(['error' => 'Function not found']);
    }
} else {
    // If the request method is not POST
    echo json_encode(['error' => 'Invalid request method']);
}

// Define your PHP function
function deleteImage($category, $imageId) {
    include 'db.php';

    $sql = "DELETE FROM images WHERE id='$imageId'";
    if ($conn->query($sql) === TRUE) {
        header("Location: ../index.php");
    };

    $conn->close();
}
?>