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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = $_POST['customerName'];
    $address = $_POST['address'];
    $date = $_POST['date'];
    $items = $_POST['item'];
    $quantities = $_POST['quantity'];

    foreach ($items as $index => $item) {
        $quantity = $quantities[$index];
        $query = "INSERT INTO Site_Material_List (CustomerName, Address, Date, Item, Quantity) 
                  VALUES ('$customerName', '$address', '$date', '$item', $quantity)";
        if ($conn->query($query) !== TRUE) {
            echo "Error: " . $conn->error;
        }
    }
    echo "Success";
}
$conn->close();
?>
