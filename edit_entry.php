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
    $updateFields = [];
    foreach ($_POST as $field => $value) {
        $updateFields[] = "$field = '" . $conn->real_escape_string($value) . "'";
    }
    $sql = "UPDATE $table SET " . implode(", ", $updateFields) . " WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = "Entry updated successfully.";
        header("Location: manage_table.php?table=$table");
        exit();
    } else {
        $error = "Error: " . $sql . "<br>" . $conn->error;
    }
}

$sql = "SELECT * FROM $table WHERE id = $id";
$result = $conn->query($sql);
$entry = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Entry - <?php echo htmlspecialchars($table); ?></title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="logo">A2Z ENGINEERING</div>
        <a href="logout.php" class="logout">Logout</a>
    </header>
    <div class="container mt-5">
        <h2>Edit Entry in Table: <?php echo htmlspecialchars($table); ?></h2>
        <?php if (isset($error)) { echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>'; } ?>
        <form method="post" action="">
            <?php
            if ($result->num_rows > 0) {
                foreach ($entry as $field => $value) {
                    if ($field != 'id') {
                        echo '<div class="form-group">';
                        echo '<label>' . htmlspecialchars($field) . '</label>';
                        echo '<input type="text" name="' . htmlspecialchars($field) . '" class="form-control" value="' . htmlspecialchars($value) . '" required>';
                        echo '</div>';
                    }
                }
            }
            ?>
            <input type="submit" value="Update Entry" class="btn btn-primary">
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>
