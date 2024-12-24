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
    <title>Jobs - A2Z ENGINEERING</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="d-flex justify-content-between p-3 bg-dark text-white">
        <div class="logo">A2Z ENGINEERING</div>
        <a href="logout.php" class="btn btn-light">Logout</a>
    </header>
    <div class="container mt-5">
        <h2>Jobs</h2>
        <div class="mt-4">
            <h3>Hayley's</h3>
            <a href="manage_table.php?table=Hayleys_Solar" class="btn btn-primary m-2">Solar Installation</a>
            <a href="manage_table.php?table=Hayleys_AC" class="btn btn-primary m-2">AC Maintenance</a>
            <a href="manage_table.php?table=Hayleys_Other" class="btn btn-primary m-2">Other</a>
        </div>
        <div class="mt-4">
            <h3>EB Creasy</h3>
            <a href="manage_table.php?table=EBC_Solar" class="btn btn-primary m-2">Solar Installation</a>
            <a href="manage_table.php?table=EBC_AC" class="btn btn-primary m-2">AC Maintenance</a>
            <a href="manage_table.php?table=EBC_Other" class="btn btn-primary m-2">Other</a>
        </div>
    </div>
</body>
</html>
