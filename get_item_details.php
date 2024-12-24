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

if (isset($_POST['item']) && isset($_POST['modelOrSize'])) {
    $item = $_POST['item'];
    $modelOrSize = $_POST['modelOrSize'];
    $query = "SELECT Item_Price FROM Material WHERE Item = '$item' AND ModelOrSize = '$modelOrSize'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row); // Return the price
    } else {
        echo json_encode(['Item_Price' => 'N/A']); // If no price found
    }
}

$conn->close();
?>
