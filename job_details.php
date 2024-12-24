<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname']) || !isset($_GET['category']) || !isset($_GET['job_type'])) {
    header("Location: index.php");
    exit();
}

$category = $_GET['category'];
$job_type = $_GET['job_type'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details - A2Z ENGINEERING</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="logo">A2Z ENGINEERING</div>
        <a href="logout.php" class="logout">Logout</a>
    </header>
    <div class="container">
        <h2>Job Details</h2>
        <p>Category: <?php echo htmlspecialchars($category); ?></p>
        <p>Job Type: <?php echo htmlspecialchars($job_type); ?></p>
        <!-- Add additional content for job details here -->
    </div>
</body>
</html>
