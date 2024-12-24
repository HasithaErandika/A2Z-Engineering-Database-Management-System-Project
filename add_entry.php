<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname']) || !isset($_GET['table'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];
$table = $_GET['table'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = array_keys($_POST);
    $values = array_map([$conn, 'real_escape_string'], array_values($_POST));
    $sql = "INSERT INTO $table (" . implode(", ", $fields) . ") VALUES ('" . implode("', '", $values) . "')";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['message'] = "Entry added successfully.";
        header("Location: manage_table.php?table=$table");
        exit();
    } else {
        $error = "Error: " . $sql . "<br>" . $conn->error;
    }
}

$sql = "DESCRIBE $table";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Entry - <?php echo htmlspecialchars($table); ?></title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="logo">A2Z ENGINEERING</div>
        <a href="logout.php" class="logout">Logout</a>
    </header>
    <div class="container mt-5">
        <h2>Add Entry to Table: <?php echo htmlspecialchars($table); ?></h2>
        <?php if (isset($error)) { echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>'; } ?>
        <form method="post" action="">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    if ($row['Field'] != 'id') {
                        echo '<div class="form-group">';
                        echo '<label>' . htmlspecialchars($row['Field']) . '</label>';
                        echo '<input type="text" name="' . htmlspecialchars($row['Field']) . '" class="form-control" required>';
                        echo '</div>';
                    }
                }
            }
            ?>
            <input type="submit" value="Add Entry" class="btn btn-success">
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>
