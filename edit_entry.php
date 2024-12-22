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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = array_keys($_POST);
    $values = array_values($_POST);
    $sql = "UPDATE $table SET ";
    foreach ($fields as $index => $field) {
        $sql .= "$field = '" . $conn->real_escape_string($values[$index]) . "'";
        if ($index < count($fields) - 1) {
            $sql .= ", ";
        }
    }
    $sql .= " WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        header("Location: manage_table.php?table=$table");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} else {
    $sql = "SELECT * FROM $table WHERE id = $id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $entry = $result->fetch_assoc();
    } else {
        header("Location: manage_table.php?table=$table");
        exit();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Entry in <?php echo htmlspecialchars($table); ?></title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Edit Entry in <?php echo htmlspecialchars($table); ?></h2>
        <form method="post" action="">
            <?php
            foreach ($entry as $field => $value) {
                if ($field == 'id') continue; // Skip auto-increment field
                echo '<div class="form-group">';
                echo '<label for="' . htmlspecialchars($field) . '">' . htmlspecialchars($field) . ':</label>';
                echo '<input type="text" id="' . htmlspecialchars($field) . '" name="' . htmlspecialchars($field) . '" class="form-control" value="' . htmlspecialchars($value) . '" required>';
                echo '</div>';
            }
            ?>
            <button type="submit" class="btn btn-warning btn-block">Edit Entry</button>
        </form>
    </div>
</body>
</html>
