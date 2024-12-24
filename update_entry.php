<?php
session_start();

$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['username'], $_SESSION['password'], $_SESSION['dbname'], $_POST['table'], $_POST['id'])) {
        header("Location: index.php");
        exit();
    }

    $servername = "localhost";
    $username = $_SESSION['username'];
    $password = $_SESSION['password'];
    $dbname = $_SESSION['dbname'];
    $table = $_POST['table'];
    $id = $_POST['id'];

    // Connect to the database
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get the primary key of the table
    $primaryKeyResult = $conn->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
    if ($primaryKeyResult && $primaryKeyResult->num_rows > 0) {
        $primaryKeyRow = $primaryKeyResult->fetch_assoc();
        $primaryKey = $primaryKeyRow['Column_name'];
    } else {
        die("Error: Could not determine the primary key for the table $table.");
    }

    // Prepare the UPDATE query
    $updateQuery = "UPDATE `$table` SET ";
    $values = [];
    foreach ($_POST as $column => $value) {
        if ($column !== 'table' && $column !== 'id') {
            $values[] = "`$column` = '" . $conn->real_escape_string($value) . "'";
        }
    }

    $updateQuery .= implode(", ", $values);
    $updateQuery .= " WHERE `$primaryKey` = '" . $conn->real_escape_string($id) . "'";

    // Execute the query
    if ($conn->query($updateQuery) === TRUE) {
        $successMessage = "Record updated successfully!";
    } else {
        $errorMessage = "Error updating record: " . $conn->error;
    }

    $conn->close();
}

// Retrieve record data if available
$recordData = [];
$columnTypes = [];

if (isset($_SESSION['username'], $_SESSION['password'], $_SESSION['dbname'], $_GET['table'], $_GET['id'])) {
    $servername = "localhost";
    $username = $_SESSION['username'];
    $password = $_SESSION['password'];
    $dbname = $_SESSION['dbname'];
    $table = $_GET['table'];
    $id = $_GET['id'];

    // Connect to the database
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get the primary key
    $primaryKeyResult = $conn->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
    if ($primaryKeyResult && $primaryKeyResult->num_rows > 0) {
        $primaryKeyRow = $primaryKeyResult->fetch_assoc();
        $primaryKey = $primaryKeyRow['Column_name'];
    } else {
        die("Error: Could not determine the primary key for the table $table.");
    }

    // Retrieve record data
    $query = "SELECT * FROM `$table` WHERE `$primaryKey` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $recordData = $result->fetch_assoc();
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Record</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .modal-header {
            background-color: #28a745;
            color: white;
        }
        .modal-header.error {
            background-color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center">Update Record</h2>
        <div class="form-container mx-auto mt-4">
            <?php if ($recordData): ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                    <table class="table table-bordered">
                        <?php foreach ($recordData as $column => $value): ?>
                            <tr>
                                <th><?php echo htmlspecialchars($column); ?></th>
                                <td>
                                    <input type="text" class="form-control" name="<?php echo htmlspecialchars($column); ?>" value="<?php echo htmlspecialchars($value); ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Update
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning text-center">
                    No record found to update.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Success Modal -->
    <?php if ($successMessage): ?>
        <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Success</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Error Modal -->
    <?php if ($errorMessage): ?>
        <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header error">
                        <h5 class="modal-title">Error</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function () {
            <?php if ($successMessage): ?>
                $('#successModal').modal('show');
                setTimeout(() => {
                    window.location.href = "manage_table.php?table=<?php echo htmlspecialchars($table); ?>";
                }, 2000);
            <?php elseif ($errorMessage): ?>
                $('#errorModal').modal('show');
            <?php endif; ?>
        });
    </script>
</body>

</html>
