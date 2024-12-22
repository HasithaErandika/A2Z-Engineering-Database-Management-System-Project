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

$sql = "SELECT * FROM $table";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Table - <?php echo htmlspecialchars($table); ?></title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container-fluid mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Table: <?php echo htmlspecialchars($table); ?></h2>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
        <div class="d-flex justify-content-end mb-3">
            <a href="add_entry.php?table=<?php echo $table; ?>" class="btn btn-success mr-2">Add Entry</a>
            <a href="delete_entry.php?table=<?php echo $table; ?>" class="btn btn-danger mr-2">Delete Entry</a>
            <a href="edit_entry.php?table=<?php echo $table; ?>" class="btn btn-warning">Edit Entry</a>
        </div>
        <input class="form-control mb-4" id="searchInput" type="text" placeholder="Search..">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <?php
                        if ($result->num_rows > 0) {
                            $fields = $result->fetch_fields();
                            foreach ($fields as $field) {
                                echo "<th>" . htmlspecialchars($field->name) . "</th>";
                            }
                            echo "<th>Actions</th>";
                        } else {
                            echo "<tr><th>No entries found in the table.</th></tr>";
                        }
                        ?>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            foreach ($row as $data) {
                                echo "<td>" . htmlspecialchars($data) . "</td>";
                            }
                            echo '<td>
                                    <a href="edit_entry.php?table=' . $table . '&id=' . $row['id'] . '" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="delete_entry.php?table=' . $table . '&id=' . $row['id'] . '" class="btn btn-danger btn-sm">Delete</a>
                                  </td>';
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            var input = this.value.toLowerCase();
            var rows = document.getElementById('tableBody').getElementsByTagName('tr');
            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName('td');
                var found = false;
                for (var j = 0; j < cells.length - 1; j++) { // Skip the last cell (actions)
                    if (cells[j].textContent.toLowerCase().includes(input)) {
                        found = true;
                        break;
                    }
                }
                rows[i].style.display = found ? '' : 'none';
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
