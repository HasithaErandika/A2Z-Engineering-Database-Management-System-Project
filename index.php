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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">    
    
    <style>
        body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to bottom, #a3a3a3, #e1e1e1 ); /* Adjust the colors as needed */
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
    color: #fff;
}
.container {
    display: flex;
    width: 80%;
    max-width: 1200px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.left-section {
    flex: 1;
    padding: 50px;
    background: linear-gradient(to bottom, #a8c5d8, #dceaf4) ;  /* Adjust gradient */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.design-credit {
    font-size: 12px;
    margin: 0;
    margin-top: 20px;
    font-style: italic;
    text-align: right;
}
.right-section {
    flex: 1;
    padding: 50px;
    background-color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #333;
}
.right-section h2 {
    margin-bottom: 30px;
    color:#6e6e6e;
    font-weight: 400;
}

.input-group {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    border-radius: 10px;
    border:1px solid #efefef;
    padding: 5px 10px;
    background:#f9f9f9;
    transition: border 0.3s ease;
}

.input-group:hover {
    border:1px solid #d0c8e3;
}

.input-group i{
    margin-right: 10px;
    color:#948ea0;
}

.input-group input {
    border: none;
    background: transparent;
    outline: none;
    padding: 10px;
    color: #444;
    flex:1;
}
.remember-forgot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    font-size: 14px;
}
.remember-forgot input[type="checkbox"] {
    margin-right: 5px;
}
.remember-forgot a {
    color: #714ed3;
    text-decoration: none;
}
button {
    background: linear-gradient(to right, #a069e1, #cb6eb2);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    width: 100%;
    font-size: 16px;
    font-weight: 500;
}
button:hover{
   background: linear-gradient(to right, #7f3dc0, #ab52a7);
}
h1 {
    font-size: 2.5em;
    margin-bottom: 20px;
}
p {
    line-height: 1.6;
    font-size: 16px;
    opacity:0.9;
}

@media screen and (max-width: 768px) {
    .container {
        flex-direction: column;
    }
    .left-section {
        padding: 30px;
    }
    .right-section {
        padding: 30px;
    }
}
    </style>

    
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <div class="left-section">
            <h1>Welcome to A2Z Engineering Records</h1>
            <p>This system provides comprehensive support for your operational needs, including efficient attendance tracking, automated salary calculations, and the generation of detailed monthly reports. All data is securely managed through our robust database management system (DBMS), ensuring accuracy and reliability.</p>
            <p class="design-credit">designed by <span style="color:#333; font-weight: bold;">heCre</span></p>
        </div>
        <div class="right-section">
            <h2>USER LOGIN</h2>
            <?php if (isset($error)): ?>
                <div class="alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post" action="">
                    <div class="input-group">
                    <label for="username"><i class="fas fa-user"></i> Username </label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="input-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="remember-forgot">
                    <label>
                        <input type="checkbox"> Remember
                    </label>
                    <a href="#">Forgot password?</a>
                </div>
                <button type="submit">LOGIN</button>
            </form>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
