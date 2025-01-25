<?php
session_start();

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection details from session
$servername = "localhost";
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$password = isset($_SESSION['password']) ? $_SESSION['password'] : '';
$dbname = isset($_SESSION['dbname']) ? $_SESSION['dbname'] : '';
$table = isset($_GET['table']) ? $_GET['table'] : '';

// Check if session variables are set
if (empty($username) || empty($password) || empty($dbname)) {
    // Redirect to login or display error and exit
    die("<div class='alert alert-danger'>Database connection details are not set in session. Please log in.</div>");
    // For actual implementation, redirect to login page.
    // header("Location: login.php");
    // exit();
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    $error_message = "Connection failed: " . $conn->connect_error;
    error_log($error_message);
    die("<div class='alert alert-danger'>Error: Failed to connect to database. Please try again later.</div>");
}

// Variables to store the totals
$total_expenses = 0;
$total_invoices = 0;
$total_amount = 0;
$expenses_by_category = [
    'Meals' => 0,
    'Tools' => 0,
    'Fuel' => 0,
    'Materials' => 0,
    'Hiring of labor' => 0,
    'Other' => 0,
    'EPF' => 0,
];
$employee_payments_by_type = [];
$profit = 0;


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Validate dates
    if (empty($start_date) || empty($end_date)) {
        echo "<div class='alert alert-danger'>Please select start and end dates.</div>";
        exit;
    }

    if ($start_date > $end_date) {
        echo "<div class='alert alert-danger'>Start date cannot be greater than the end date.</div>";
        exit;
    }

    // Fetch employee salaries and calculate EPF
    $sql_employees = "SELECT Emp_ID, Basic_Salary, Date_of_resigned FROM Employee";
    $stmt_employees = $conn->prepare($sql_employees);
    if ($stmt_employees === false) {
        $error_message = "Error preparing statement (employees): " . $conn->error;
        error_log($error_message);
        echo "<div class='alert alert-danger'>Error: Could not retrieve employee data. Please try again later.</div>";
        exit;
    }
    $stmt_employees->execute();
    $result_employees = $stmt_employees->get_result();
    if ($result_employees === false) {
        $error_message = "Error executing statement (employees): " . $stmt_employees->error;
        error_log($error_message);
        echo "<div class='alert alert-danger'>Error: Could not retrieve employee data. Please try again later.</div>";
        exit;
    }

   while ($row = $result_employees->fetch_assoc()) {
        $employee_resigned_date = $row['Date_of_resigned'];

       // Check if the employee resigned *before* the start date
        if ($employee_resigned_date == '0000-00-00' || $employee_resigned_date > $start_date) {
            $epf_amount = $row['Basic_Salary'] * 0.12;
            $total_amount += $epf_amount; // Add to total amount
            $expenses_by_category['EPF'] += $epf_amount; // Add to total EPF
        }
    }

    $stmt_employees->close();


    // Calculate total expenses
    $sql_expenses = "SELECT SUM(Exp_amount) AS total_expenses, Expenses_Category FROM Operational_Expenses WHERE Expensed_Date BETWEEN ? AND ? GROUP BY Expenses_Category";
    $stmt_expenses = $conn->prepare($sql_expenses);
    if ($stmt_expenses === false) {
        $error_message = "Error preparing statement (expenses): " . $conn->error;
        error_log($error_message);
        echo "<div class='alert alert-danger'>Error: Could not retrieve expenses. Please try again later.</div>";
        exit;
    }
    $stmt_expenses->bind_param("ss", $start_date, $end_date);
    $stmt_expenses->execute();
    $result_expenses = $stmt_expenses->get_result();
    if ($result_expenses === false) {
        $error_message = "Error executing statement (expenses): " . $stmt_expenses->error;
        error_log($error_message);
        echo "<div class='alert alert-danger'>Error: Could not retrieve expenses. Please try again later.</div>";
        exit;
    }

    while ($row = $result_expenses->fetch_assoc()) {
        $expenses_by_category[$row['Expenses_Category']] = $row['total_expenses'];
        $total_expenses += $row['total_expenses'];
    }
    $stmt_expenses->close();


    // Calculate total invoices
    $sql_invoices = "SELECT SUM(Invoice_Value) AS total_invoices FROM Invoice_Data WHERE Invoice_Date BETWEEN ? AND ?";
    $stmt_invoices = $conn->prepare($sql_invoices);
    if ($stmt_invoices === false) {
        $error_message = "Error preparing statement (invoices): " . $conn->error;
        error_log($error_message);
         echo "<div class='alert alert-danger'>Error: Could not retrieve invoices. Please try again later.</div>";
        exit;
    }
    $stmt_invoices->bind_param("ss", $start_date, $end_date);
    $stmt_invoices->execute();
    $result_invoices = $stmt_invoices->get_result();
     if ($result_invoices === false) {
        $error_message = "Error executing statement (invoices): " . $stmt_invoices->error;
        error_log($error_message);
        echo "<div class='alert alert-danger'>Error: Could not retrieve invoices. Please try again later.</div>";
         exit;
    }
    if ($row = $result_invoices->fetch_assoc()) {
        $total_invoices = $row['total_invoices'];
    }
    $stmt_invoices->close();


   // Calculate employee payments
    $sql_employee_payments = "SELECT SUM(Paid_Amount) AS total_amount, Payment_Type FROM Employee_Payments WHERE Payment_Date BETWEEN ? AND ? GROUP BY Payment_Type";
    $stmt_employee_payments = $conn->prepare($sql_employee_payments);
    if ($stmt_employee_payments === false) {
       $error_message = "Error preparing statement (employee payments): " . $conn->error;
       error_log($error_message);
        echo "<div class='alert alert-danger'>Error: Could not retrieve employee payments. Please try again later.</div>";
        exit;
    }
    $stmt_employee_payments->bind_param("ss", $start_date, $end_date);
    $stmt_employee_payments->execute();
    $result_employee_payments = $stmt_employee_payments->get_result();
    if ($result_employee_payments === false) {
       $error_message = "Error executing statement (employee payments): " . $stmt_employee_payments->error;
        error_log($error_message);
        echo "<div class='alert alert-danger'>Error: Could not retrieve employee payments. Please try again later.</div>";
        exit;
    }

    // Initialize array to store payments by type
    while ($row = $result_employee_payments->fetch_assoc()) {
        // Store total amount by payment type
        $employee_payments_by_type[$row['Payment_Type']] = $row['total_amount'];

        // Accumulate total amount
        $total_amount += $row['total_amount'];
    }

    // Close statement
    $stmt_employee_payments->close();

   // Calculate profit
    $profit = $total_invoices - ($total_expenses + $total_amount);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses Report</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
    body {
        background-color: #f8f9fa;
        font-family: 'Arial', sans-serif;
        padding: 20px;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
    }

    h2 {
        font-size: 28px;
        font-weight: 600;
        color: #343a40;
    }

    .btn-goback {
        font-size: 16px;
        background-color: #007bff;
        color: white;
        border-radius: 5px;
        padding: 8px 15px;
        text-decoration: none;
    }

    .btn-goback:hover {
        background-color: #0056b3;
        color: white;
    }

    .form-group label {
        font-size: 16px;
        font-weight: 500;
        color: #495057;
    }

    .form-control {
        border-radius: 5px;
        box-shadow: none;
        border: 1px solid #ced4da;
    }

    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.25);
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        font-size: 16px;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #0056b3;
    }

    .card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        background-color: #ffffff;
    }

    .card-body {
        padding: 20px;
    }

    .table {
        font-size: 16px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        margin-top: 20px;
    }

    .thead-dark {
        background-color: #343a40;
        color: white;
    }

    .thead-light {
        background-color: #f8f9fa;
    }

    .table th,
    .table td {
        vertical-align: middle;
        padding: 12px;
    }

    .table-bordered {
        border: 1px solid #dee2e6;
    }

    .table-bordered th,
    .table-bordered td {
        border: 1px solid #dee2e6;
    }

    h3 {
        font-size: 24px;
        font-weight: 600;
        color: #343a40;
        margin-top: 30px;
    }

    @media (max-width: 768px) {
        .container {
            padding: 10px;
        }

        .form-row {
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 15px;
        }
    }
</style>
</head>

<body>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mt-5">Generate Expenses Report</h2>
            <a href="tables.php" class="btn btn-goback">Go Back</a>
        </div>
        <div class="card mb-4">
            <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mt-3">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="start_date">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                </div>
                <div class="form-group col-md-6">
                    <label for="end_date">End Date:</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </form>
            </div>
        </div>

        <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <h2 class="mt-5">Report</h2>
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered mt-3">
                        <thead class="thead-dark">
                            <tr>
                                <th>Total Expenses</th>
                                <th>Total Invoices</th>
                                <th>Employee Payments</th>
                                <th>Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo number_format($total_expenses, 2); ?></td>
                                <td><?php echo number_format($total_invoices, 2); ?></td>
                                <td><?php echo number_format($total_amount, 2); ?></td>
                                <td><?php echo number_format($profit, 2); ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <h3 class="mt-5">Expenses by Category</h3>
                    <table class="table table-bordered mt-3">
                        <thead class="thead-light">
                            <tr>
                                <th>Category</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses_by_category as $category => $amount): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category); ?></td>
                                    <td><?php echo number_format($amount, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h3 class="mt-5">Employee Payments by Type</h3>
                    <table class="table table-bordered mt-3">
                        <thead class="thead-light">
                            <tr>
                                <th>Payment Type</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employee_payments_by_type as $payment_type => $amount): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment_type); ?></td>
                                    <td><?php echo number_format($amount, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
