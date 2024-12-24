<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servername = "localhost";
    $username = $_POST['username'];
    $password = $_POST['password'];
    $dbname = "suramalr_operational_db";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } else {
        $_SESSION['username'] = $username;
        $_SESSION['password'] = $password;
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
    <title>Login - A2Z ENGINEERING</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container d-flex flex-column align-items-center justify-content-center vh-100">
        <h1 class="mb-3">Welcome to A2Z ENGINEERING</h1>
        <h3 class="mb-4">Operational Database</h3>
        <div class="login-box p-4 bg-maroon text-white rounded">
            <form method="post" action="">
                <div class="form-group">
                    <label for="username">Database Username:</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Database Password:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-light btn-block">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
