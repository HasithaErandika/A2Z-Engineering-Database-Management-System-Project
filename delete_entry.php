<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname']) || !isset($_GET['table']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];
$table = $_GET['table'];
$id = $_GET['id'];

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "DELETE FROM $table WHERE id = $id";

if ($conn->query($sql) === TRUE) {
    $_SESSION['message'] = "Entry deleted successfully.";
    header("Location: manage_table.php?table=$table");
    exit();
} else {
    $error = "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
