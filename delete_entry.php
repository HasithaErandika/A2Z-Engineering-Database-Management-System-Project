<?php
session_start();

// Ensure that the session variables and 'table' parameter are set
if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname']) || !isset($_GET['table'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];
$table = $_GET['table'];

// Sanitize and validate the table name to avoid SQL injection
$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);  // Only allow alphanumeric characters and underscores for table name

// Create a connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the primary key of the table
$primaryKeyResult = $conn->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
if ($primaryKeyResult->num_rows > 0) {
    $primaryKeyRow = $primaryKeyResult->fetch_assoc();
    $primaryKey = $primaryKeyRow['Column_name'];
} else {
    die("Error: Could not determine the primary key for the table $table.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate the id input
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $id = (int)$_POST['id'];  // Convert to integer for safety
        
        // Prepare and execute the delete statement
        $stmt = $conn->prepare("DELETE FROM `$table` WHERE `$primaryKey` = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            header("Location: manage_table.php?table=$table");
            exit();
        } else {
            $error_message = "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $error_message = "Invalid ID provided.";
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Entry from <?php echo htmlspecialchars($table); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f7f7f7;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 400px;
            width: 100%;
            background-color: #fff;
        }
        .card-title {
            font-size: 1.5rem;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-danger {
            background: linear-gradient(45deg, #ff3f34, #ff5e58);
            border: none;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            font-weight: bold;
        }
        .btn-danger:hover {
            background: linear-gradient(45deg, #ff5e58, #ff3f34);
            box-shadow: 0 6px 15px rgba(255, 0, 0, 0.3);
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .form-group input {
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .input-group-text {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2 class="card-title">Delete Entry from <?php echo htmlspecialchars($table); ?></h2>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger text-center" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <form method="post" action="">
                <div class="form-group mb-4">
                    <label for="id">ID of the entry to delete:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                        <input type="text" id="id" name="id" class="form-control" placeholder="Enter ID" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-danger">Delete Entry</button>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this entry? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirmation Modal Logic
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            modal.show();
        });

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            document.querySelector('form').submit();
        });
    </script>
</body>
</html>
