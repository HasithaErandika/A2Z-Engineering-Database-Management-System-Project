<?php
session_start();

// Enable error reporting
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
    die("<div class='alert alert-danger'>Database connection details are not set in session.</div>");
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("<div class='alert alert-danger'>Connection failed: " . $conn->connect_error . "</div>");
}

// Variables to store the totals
$total_expenses = 0;
$total_invoices = 0;
$employee_wages = 0;
$total_amount = 0;
$expenses_by_category = [
    'Meals' => 0,
    'Tools' => 0,
    'Fuel' => 0,
    'Materials' => 0,
    'Hiring of labor' => 0,
    'Other' => 0
];
$employee_payments_by_type = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Calculate total expenses
    $sql_expenses = "SELECT SUM(Exp_amount) AS total_expenses, Expenses_Category FROM Operational_Expenses WHERE Expensed_Date BETWEEN ? AND ? GROUP BY Expenses_Category";
    $stmt_expenses = $conn->prepare($sql_expenses);
    if ($stmt_expenses === false) {
        die("<div class='alert alert-danger'>Error preparing statement: " . $conn->error . "</div>");
    }
    $stmt_expenses->bind_param("ss", $start_date, $end_date);
    $stmt_expenses->execute();
    $result_expenses = $stmt_expenses->get_result();
    if ($result_expenses === false) {
        die("<div class='alert alert-danger'>Error executing statement: " . $stmt_expenses->error . "</div>");
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
        die("<div class='alert alert-danger'>Error preparing statement: " . $conn->error . "</div>");
    }
    $stmt_invoices->bind_param("ss", $start_date, $end_date);
    $stmt_invoices->execute();
    $result_invoices = $stmt_invoices->get_result();
    if ($result_invoices === false) {
        die("<div class='alert alert-danger'>Error executing statement: " . $stmt_invoices->error . "</div>");
    }
    if ($row = $result_invoices->fetch_assoc()) {
        $total_invoices = $row['total_invoices'];
    }
    $stmt_invoices->close();

    // Calculate employee payments
    $sql_employee_payments = "SELECT SUM(Paid_Amount) AS total_amount, Payment_Type FROM Employee_Payments WHERE Payment_Date BETWEEN ? AND ? GROUP BY Payment_Type";
    $stmt_employee_payments = $conn->prepare($sql_employee_payments);
    if ($stmt_employee_payments === false) {
        die("<div class='alert alert-danger'>Error preparing statement: " . $conn->error . "</div>");
    }
    $stmt_employee_payments->bind_param("ss", $start_date, $end_date);
    $stmt_employee_payments->execute();
    $result_employee_payments = $stmt_employee_payments->get_result();
    if ($result_employee_payments === false) {
        die("<div class='alert alert-danger'>Error executing statement: " . $stmt_employee_payments->error . "</div>");
    }

    // Initialize array to store payments by type
    $employee_payments_by_type = [];

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* Body and container styles */
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header styles */
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
        }

        .btn-goback:hover {
            background-color: #0056b3;
            color: white;
        }

        /* Form styles */
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

        /* Card styles */
        .card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
        }

        .card-body {
            padding: 20px;
        }

        /* Table styles */
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

        /* Section heading styles */
        h3 {
            font-size: 24px;
            font-weight: 600;
            color: #343a40;
            margin-top: 30px;
        }

        /* Responsive adjustments */
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
                                <th>Employee Wages</th>
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

                    <!--<h3 class="mt-5">Financial Summary Chart</h3>-->
                    <!--<div style="display: flex;">-->
                    <!--    <canvas id="financialChart" width="400" height="200"></canvas>-->
                    <!--    <div class="legend-container">-->
                    <!--        <h4>Legend</h4>-->
                    <!--        <table class="legend-table"></table>-->
                    <!--    </div>-->
                    <!--</div>-->

                    <!--<script>-->
                    <!--    const ctx = document.getElementById('financialChart').getContext('2d');-->
                    <!--    const financialChart = new Chart(ctx, {-->
                    <!--        type: 'pie',-->
                    <!--        data: {-->
                    <!--            labels: ['Total Expenses', 'Total Invoices', 'Employee Wages', 'Profit'],-->
                    <!--            datasets: [{-->
                    <!--                data: [-->
                    <!--                    <?= $total_expenses ?>,-->
                    <!--                    <?= $total_invoices ?>,-->
                    <!--                    <?= $total_amount ?>,-->
                    <!--                    <?= $profit ?>-->
                    <!--                ],-->
                    <!--                backgroundColor: [-->
                    <!--                    'rgba(255, 99, 132, 0.2)',-->
                    <!--                    'rgba(54, 162, 235, 0.2)',-->
                    <!--                    'rgba(255, 206, 86, 0.2)',-->
                    <!--                    'rgba(153, 102, 255, 0.2)'-->
                    <!--                ],-->
                    <!--                borderColor: [-->
                    <!--                    'rgba(255, 99, 132, 1)',-->
                    <!--                    'rgba(54, 162, 235, 1)',-->
                    <!--                    'rgba(255, 206, 86, 1)',-->
                    <!--                    'rgba(153, 102, 255, 1)'-->
                    <!--                ],-->
                    <!--                borderWidth: 1-->
                    <!--            }]-->
                    <!--        },-->
                    <!--        options: {-->
                    <!--            responsive: true,-->
                    <!--            plugins: {-->
                    <!--                legend: {-->
                    <!--                    display: false-->
                    <!--                },-->
                    <!--                title: {-->
                    <!--                    display: true,-->
                    <!--                    text: 'Financial Summary'-->
                    <!--                },-->
                    <!--                tooltip: {-->
                    <!--                    callbacks: {-->
                    <!--                        label: function (tooltipItem) {-->
                    <!--                            let dataLabel = tooltipItem.label;-->
                    <!--                            let value = tooltipItem.raw;-->

                    <!--                            let total = tooltipItem.dataset.data.reduce((acc, val) => acc + val, 0);-->
                    <!--                            let percentage = ((value / total) * 100).toFixed(2);-->

                    <!--                            return `${dataLabel}: ${value.toLocaleString()} (${percentage}%)`;-->
                    <!--                        }-->
                    <!--                    }-->
                    <!--                }-->
                    <!--            }-->
                    <!--        },-->
                            // After the chart is rendered, generate the legend table
                    <!--        plugins: [{-->
                    <!--            afterRender: function (chart) {-->
                    <!--                const legendContainer = document.querySelector('.legend-container');-->
                    <!--                const legendTable = document.createElement('table');-->
                    <!--                legendTable.className = 'legend-table';-->
                    <!--                legendTable.innerHTML = `-->
                    <!--            <thead>-->
                    <!--                <tr>-->
                    <!--                    <th>Color</th>-->
                    <!--                    <th>Label</th>-->
                    <!--                    <th>Percentage</th>-->
                    <!--                </tr>-->
                    <!--            </thead>-->
                    <!--            <tbody>-->
                    <!--                ${chart.data.labels.map((label, index) => `-->
                    <!--                    <tr>-->
                    <!--                        <td><div style="width: 20px; height: 20px; background-color: ${chart.data.datasets[0].backgroundColor[index]}"></div></td>-->
                    <!--                        <td>${label}</td>-->
                    <!--                        <td>${((chart.data.datasets[0].data[index] / chart.data.datasets[0].data.reduce((acc, val) => acc + val, 0)) * 100).toFixed(2)}%</td>-->
                    <!--                    </tr>-->
                    <!--                `).join('')}-->
                    <!--            </tbody>-->
                    <!--        `;-->
                    <!--                legendContainer.appendChild(legendTable);-->
                    <!--            }-->
                    <!--        }]-->
                    <!--    });-->
                    <!--</script>-->
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
