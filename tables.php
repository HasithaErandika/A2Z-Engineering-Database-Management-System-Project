<?php
session_start();
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

$sql = "SHOW TABLES";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tables</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Database Tables</h2>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
        <div class="list-group">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_array()) {
                    $table = $row[0];
                    echo '<a href="manage_table.php?table=' . $table . '" class="list-group-item list-group-item-action">' . $table . '</a>';
                }
            } else {
                echo "<div class='alert alert-warning' role='alert'>No tables found in the database.</div>";
            }
            ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
