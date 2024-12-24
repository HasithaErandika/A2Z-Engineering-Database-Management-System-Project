<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

$sql = "SELECT * FROM Attendance WHERE MONTH(date) = MONTH('$month-01') AND YEAR(date) = YEAR('$month-01')";
$result = $conn->query($sql);

$attendance_sum = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employee_id = $row['employee_id'];
        if (!isset($attendance_sum[$employee_id])) {
            $attendance_sum[$employee_id] = 0;
        }
        $attendance_sum[$employee_id]++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - A2Z ENGINEERING</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="logo">A2Z ENGINEERING</div>
        <a href="logout.php" class="logout">Logout</a>
    </header>
    <div class="container">
        <h2>Attendance for <?php echo date('F Y', strtotime($month)); ?></h2>
        <form method="get" action="attendance.php" class="search-bar">
            <input type="month" name="month" value="<?php echo htmlspecialchars($month); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <?php if ($result->num_rows > 0) { ?>
            <table class="table full-width">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <h3>Attendance Summary</h3>
            <table class="table full-width">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Total Days</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance_sum as $employee_id => $total_days) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee_id); ?></td>
                            <td><?php echo htmlspecialchars($total_days); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p>No attendance records found for the selected month.</p>
        <?php } ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
