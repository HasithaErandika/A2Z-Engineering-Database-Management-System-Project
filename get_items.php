<?php
session_start();

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// Check if session variables are set
if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['type'])) {
    $type = $_POST['type'];
    $query = "SELECT DISTINCT Item FROM Material WHERE Type = '$type' ORDER BY Item";
    $result = $conn->query($query);

    // Check if any items are available
    if ($result->num_rows > 0) {
        echo "<option value=''>Select Item</option>"; // Default option
        while ($row = $result->fetch_assoc()) {
            // Display each item as an option
            echo "<option value='" . $row['Item'] . "'>" . $row['Item'] . "</option>";
        }
    } else {
        echo "<option value=''>No items available</option>";
    }
}

$conn->close();
?>
