<?php
session_start();

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// Check if session variables are set
if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname'])) {
    echo 'error';
    exit();
}

$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];

// Create a new connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the POST data exists
if (isset($_POST['jobID']) && isset($_POST['totalSiteCost'])) {
    // Ensure Job_ID is treated as an integer
    $jobID = intval($_POST['jobID']);
    $totalSiteCost = $_POST['totalSiteCost'];

    // Validate the totalSiteCost as a numeric value (this is an extra step to prevent issues)
    if (!is_numeric($totalSiteCost)) {
        echo 'Error: Invalid Total Site Cost';
        $conn->close();
        exit();
    }

    // Prepare the SQL statement
    $sql = "INSERT INTO Material_List_Per_Site (Job_ID, Total_Site_Cost) VALUES (?, ?)";

    // Prepare the statement and bind parameters
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('is', $jobID, $totalSiteCost); // 'i' for integer, 's' for string (for totalSiteCost)

        // Execute the statement
        if ($stmt->execute()) {
            echo 'success'; // Respond with success if data was inserted
        } else {
            // Show the specific SQL error
            echo 'Error: ' . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        echo 'Error: Could not prepare statement';
    }
} else {
    echo 'Error: Missing POST data';
}

// Close the connection
$conn->close();
?>
