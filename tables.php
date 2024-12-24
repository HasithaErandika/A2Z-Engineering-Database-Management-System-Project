<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tables - A2Z ENGINEERING</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="d-flex justify-content-between p-3 bg-dark text-white">
        <div class="logo">A2Z ENGINEERING</div>
        <a href="logout.php" class="btn btn-light">Logout</a>
    </header>
    <div class="container mt-5">
        <h2>Select Table</h2>
        <div class="d-flex flex-wrap justify-content-around">
            <a href="manage_table.php?table=attendance" class="btn btn-primary m-2">Attendance</a>
            <a href="manage_table.php?table=employee" class="btn btn-primary m-2">Employee</a>
            <a href="jobs.php" class="btn btn-primary m-2">Jobs</a>
            <a href="manage_table.php?table=projects" class="btn btn-primary m-2">Projects</a>
        </div>
    </div>
</body>
</html>
