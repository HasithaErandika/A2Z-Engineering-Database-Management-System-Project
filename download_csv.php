<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname']) || !isset($_GET['table']) || !isset($_GET['start_date']) || !isset($_GET['end_date'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];
$table = $_GET['table'];
$startDate = $_GET['start_date'];
$endDate = $_GET['end_date'];

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$table = mysqli_real_escape_string($conn, $table);
$startDate = mysqli_real_escape_string($conn, $startDate);
$endDate = mysqli_real_escape_string($conn, $endDate);

// Define date column based on table name
switch ($table) {
    case 'Attendance':
        $dateColumn = 'Atd_Date';
        break;
    case 'Jobs':
        $dateColumn = 'Date_completed';
        break;
    case 'Operational_Expenses':
        $dateColumn = 'Expensed_Date';
        break;
    case 'Invoice_Data':
        $dateColumn = 'Invoice_Date';
        break;
    case 'Employee_Payments':
        $dateColumn = 'Payment_Date';
        break;
    default:
        die("Error: Invalid table name.");
}

$sql = "SELECT * FROM $table WHERE $dateColumn >= '$startDate' AND $dateColumn <= '$endDate'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $filename = "$table-" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Output column headings
    $fields = $result->fetch_fields();
    $headers = [];
    foreach ($fields as $field) {
        $headers[] = $field->name;
    }
    fputcsv($output, $headers);

    // Output data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }

    fclose($output);
} else {
    echo "No records found for the selected date range.";
}

$conn->close();
?>
