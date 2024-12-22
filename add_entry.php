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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = array_keys($_POST);
    $values = array_values($_POST);

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "INSERT INTO $table (" . implode(", ", $fields) . ") VALUES ('" . implode("', '", $values) . "')";
    if ($conn->query($sql) === TRUE) {
        header("Location: manage_table.php?table=$table");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Entry to <?php echo htmlspecialchars($table); ?></title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Add Entry to <?php echo htmlspecialchars($table); ?></h2>
        <form method="post" action="">
            <?php
            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $sql = "DESCRIBE $table";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    if ($row['Field'] == 'id') continue; // Skip auto-increment field
                    echo '<div class="form-group">';
                    echo '<label for="' . $row['Field'] . '">' . htmlspecialchars($row['Field']) . ':</label>';
                    echo '<input type="text" id="' . $row['Field'] . '" name="' . $row['Field'] . '" class="form-control" required>';
                    echo '</div>';
                }
            }

            $conn->close();
            ?>
            <button type="submit" class="btn btn-primary btn-block">Add Entry</button>
        </form>
    </div>
</body>
</html>
