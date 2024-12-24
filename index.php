<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servername = "localhost";
    $username = $_POST['username'];
    $password = $_POST['password'];
    $dbname = "suramalr_operational_db";

    // Attempt database connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        $error = "Connection failed: " . htmlspecialchars($conn->connect_error);
        error_log("Database connection failed: " . $conn->connect_error);
    } else {
        // Store credentials in session
        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password; // Note: Only store plain text if absolutely necessary.
        $_SESSION['dbname'] = $dbname;
        header("Location: tables.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Login</title>
    <!--<link rel="stylesheet" href="styles.css">-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body {
        font-family: 'Arial', sans-serif;
        background: #f4f7fc;
        margin: 0;
        padding: 0;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login-container {
        width: 100%;
        max-width: 400px;
        margin: auto;
        padding: 20px;
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .login-card {
        padding: 20px;
    }

    .login-card h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
        font-size: 24px;
        font-weight: 600;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        font-size: 14px;
        color: #555;
        font-weight: 600;
    }

    .form-control {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        font-size: 14px;
        width: 100%;
    }

    .form-control:focus {
        border-color: #6c63ff;
        box-shadow: 0 0 5px rgba(108, 99, 255, 0.2);
    }

    .login-button {
        width: 100%;
        padding: 10px;
        background-color: #6c63ff;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.3s ease;
    }

    .login-button:hover {
        background-color: #5a52d1;
    }

    .alert-danger {
        padding: 10px;
        margin-bottom: 20px;
        background-color: #f8d7da;
        color: #721c24;
        border-radius: 5px;
        font-size: 14px;
        text-align: center;
    }

    /* Font Awesome icons */
    .form-group i {
        margin-right: 8px;
        color: #6c63ff;
    }

    @media (max-width: 576px) {
        .login-container {
            padding: 15px;
        }

        .login-card h2 {
            font-size: 20px;
        }
    }
    .filter-button, .btn-info, .btn-warning, .btn-danger, .btn-sm, .btn-invoice, .btn-invoice-yet-to-add, .btn-yet-to-add {
        margin-right: 10px; /* Add right margin to create space between buttons */
        margin-bottom: 10px; /* Add bottom margin for vertical spacing */
    }

    /* For filter buttons */
    .filter-button {
        margin-right: 10px; /* Ensure space between each filter button */
    }

    /* Button group spacing */
    .btn-container {
        margin-bottom: 10px; /* Add some spacing below each button */
    }

    /* Ensure buttons in a row have space on smaller screens */
    @media (max-width: 768px) {
        .filter-button, .btn-info, .btn-warning, .btn-danger, .btn-sm, .btn-invoice, .btn-invoice-yet-to-add, .btn-yet-to-add {
            margin-right: 5px; /* Reduce the margin for smaller screens */
            margin-bottom: 10px; /* Keep the vertical spacing */
        }

        /* Adjust for responsive layout on smaller devices */
        .btn-container {
            margin-bottom: 15px;
        }
    }
</style>

    
</head>
<body>
    <?php include 'header.php'; ?> <!-- Include header -->
    
    <div class="login-container">
        <div class="login-card">
            <h2>Database Login</h2>
            <?php if (isset($error)): ?>
                <div class="alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username </label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="login-button">Login</button>
            </form>
        </div>
    </div>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
