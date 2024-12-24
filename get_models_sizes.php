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

if (isset($_POST['item']) && isset($_POST['type'])) {
    $item = $_POST['item'];
    $type = $_POST['type'];
    $query = "SELECT DISTINCT ModelOrSize FROM Material WHERE Item = '$item' AND Type = '$type' ORDER BY ModelOrSize";
    $result = $conn->query($query);

    // Check if any model/size is available
    if ($result->num_rows > 0) {
        echo "<option value=''>Select Model/Size</option>"; // Default option
        while ($row = $result->fetch_assoc()) {
            echo "<option value='" . $row['ModelOrSize'] . "'>" . $row['ModelOrSize'] . "</option>";
        }
    } else {
        echo "<option value=''>No models/sizes available</option>";
    }
}

$conn->close();
?>
