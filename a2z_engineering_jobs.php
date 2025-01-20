<?php
session_start();

// Enable error reporting
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Database connection details from session
$servername = "localhost";
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$password = isset($_SESSION['password']) ? $_SESSION['password'] : '';
$dbname = isset($_SESSION['dbname']) ? $_SESSION['dbname'] : '';

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

// Search filters
$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'All';

// Query to get filtered jobs
$query = "
    SELECT 
        J.Job_ID, J.Service_Category, J.Date_started, J.Date_completed,
        J.Customer_ref, J.Location, J.Job_capacity,
        I.Invoice_No, I.Invoice_Date, I.Invoice_Value, 
        I.Receiving_Payment, I.Received_amount, I.Payment_Received_Date
    FROM 
        Jobs J
    LEFT JOIN 
        Invoice_Data I ON J.Job_ID = I.Job_ID
    WHERE 
        J.Client_ref = 'A2Z Engineering'
";

// Apply filters
if (!empty($searchTerm)) {
    $query .= " AND (J.Job_ID LIKE '%$searchTerm%' OR J.Customer_ref LIKE '%$searchTerm%' OR J.Location LIKE '%$searchTerm%')";
}

if ($statusFilter !== 'All') {
    $statusValue = $statusFilter === 'Paid' ? 1 : ($statusFilter === 'Partially Paid' ? 0.5 : 0);
    $query .= " AND I.Receiving_Payment = '$statusValue'";
}

$result = $conn->query($query);

// Initialize variables for summary calculations
$unpaidAmount = 0;
$paidAmount = 0;
$totalAmount = 0;
$unpaidCount = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $totalAmount += $row['Invoice_Value'] ?: 0;
        if ($row['Receiving_Payment'] == 1) {
            $paidAmount += $row['Invoice_Value'] ?: 0;
        } elseif ($row['Receiving_Payment'] == 0) {
            $unpaidAmount += $row['Invoice_Value'] ?: 0;
            $unpaidCount++;
        }
    }
}

// Fetch customer list
$customersResult = $conn->query("SELECT DISTINCT Customer_ref FROM Jobs WHERE Client_ref = 'A2Z Engineering'");
$customers = [];
while ($row = $customersResult->fetch_assoc()) {
    $customers[] = $row['Customer_ref'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A2Z Engineering Jobs</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 20px;
        background-color: #f4f5f7; /* Slightly lighter grey for a fresher look */
        color: #343a40; /* Dark grey for better readability */
    }

    h1 {
        text-align: center;
        margin-bottom: 30px;
        color: #5a3d99; /* Deeper purple for a more professional tone */
        font-size: 2.5rem;
        font-weight: 700;
    }

    .go-back-btn {
        display: inline-block;
        text-decoration: none;
        background-color: #5a6268;
        color: #ffffff;
        padding: 10px 25px;
        border-radius: 6px;
        font-size: 16px;
        float: right;
        margin-bottom: 25px;
        transition: all 0.3s ease-in-out;
    }
    
    .go-back-btn:hover {
        background-color: #444e54;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        transform: translateY(-2px);
    }

    .go-back-btn i {
        margin-right: 10px;
    }

    form {
        background-color: #ffffff;
        padding: 25px 30px;
        border-radius: 12px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        max-width: 850px;
        margin: 0 auto;
        border-top: 6px solid #5a3d99;
    }

    .container-horizontal {
        display: flex;
        flex-wrap: wrap;
        gap: 25px;
    }

    .left-section,
    .right-section {
        flex: 1 1 calc(50% - 15px);
    }

    label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #495057;
        font-size: 15px;
    }

    input[type="text"],
    input[type="date"],
    select {
        width: 80%;
        padding: 12px;
        margin-bottom: 20px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        font-size: 16px;
        background-color: #fafbfc;
        transition: all 0.3s ease;
    }

    input[type="text"]:focus,
    input[type="date"]:focus,
    select:focus {
        border-color: #5a3d99;
        outline: none;
        box-shadow: 0 0 5px rgba(90, 61, 153, 0.2);
    }

    .form-footer {
        text-align: center;
    }

    .form-footer button {
        background-color: #007bff;
        color: #ffffff;
        border: none;
        padding: 12px 30px;
        font-size: 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .form-footer button:hover {
        background-color: #0056b3;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        transform: translateY(-2px);
    }

    .form-group {
        position: relative;
    }

    .form-group i {
        position: absolute;
        top: 50%;
        left: 15px;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 1.2rem;
    }

    .form-group input,
    .form-group select {
        padding-left: 45px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 30px 0;
        font-size: 15px;
        background-color: white;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.15);
        border-radius: 8px;
        overflow: hidden;
    }

    th {
        background-color: #5a3d99;
        color: #ffffff;
        text-align: center;
        padding: 15px;
        font-weight: bold;
    }

    td {
        padding: 15px;
        text-align: center;
        border: 1px solid #ddd;
    }

    tr:nth-child(even) {
        background-color: #f7f7f7;
    }

    tr:hover {
        background-color: #e9ecef;
    }

    .summary-container {
        display: flex;
        justify-content: space-between;
        gap: 25px;
        margin-top: 30px;
    }

    .summary-container div {
        flex: 1;
        padding: 15px;
        background-color: #ffffff;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
        font-size: 1rem;
        font-weight: 600;
    }

    .summary-container div:nth-child(odd) {
        border-top: 6px solid #5a3d99;
    }

    .summary-container div:nth-child(even) {
        border-top: 6px solid #007bff;
    }

    .section-header {
        background-color: #f1f3f5;
        font-weight: 700;
        padding: 15px;
        margin: 15px 0;
        border-left: 6px solid #007bff;
    }

    .total-cost {
        font-size: 1.25rem;
        font-weight: bold;
        color: #5a3d99;
        text-align: right;
        margin-top: 20px;
    }
    
</style>



</head>
<body>


<h1><i class="fas fa-briefcase"></i> A2Z Engineering Jobs</h1>

<a href="tables.php" class="btn btn-primary go-back-btn"><i class="fas fa-arrow-left"></i> Go Back</a>

<form method="get">
    <div class="container-horizontal">
        <!-- Left Section -->
        <div class="left-section">
            <div class="form-group">
                <label for="invoiceID"><i class="fas fa-file-invoice"></i> Invoice ID:</label>
                <input type="text" name="invoiceID" id="invoiceID" 
                    value="<?= htmlspecialchars($_GET['invoiceID'] ?? '') ?>" 
                    placeholder="Enter Invoice ID">
            </div>

            <div class="form-group">
                <label for="customerRef"><i class="fas fa-user"></i> Customer Name:</label>
                <select name="customerRef" id="customerRef">
                    <option value="">Select Customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= $customer ?>" 
                            <?= ($_GET['customerRef'] == $customer ? 'selected' : '') ?>>
                            <?= $customer ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <div class="form-group">
                <label for="status"><i class="fas fa-clipboard-check"></i> Status:</label>
                <select name="status" id="status">
                    <option value="All" <?= ($_GET['status'] == 'All' ? 'selected' : '') ?>>All</option>
                    <option value="Paid" <?= ($_GET['status'] == 'Paid' ? 'selected' : '') ?>>Paid</option>
                    <option value="Unpaid" <?= ($_GET['status'] == 'Unpaid' ? 'selected' : '') ?>>Unpaid</option>
                    <option value="Partially Paid" <?= ($_GET['status'] == 'Partially Paid' ? 'selected' : '') ?>>Partially Paid</option>
                </select>
            </div>

            <div class="form-group">
                <label for="fromDate"><i class="fas fa-calendar-alt"></i> From Date:</label>
                <input type="date" name="fromDate" id="fromDate" 
                    value="<?= htmlspecialchars($_GET['fromDate'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="toDate"><i class="fas fa-calendar-day"></i> To Date:</label>
                <input type="date" name="toDate" id="toDate" 
                    value="<?= htmlspecialchars($_GET['toDate'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="form-footer">
        <button type="submit"><i class="fas fa-search"></i> Search</button>
    </div>
</form>

    <?php
    // Build the query based on filters
    $query = "SELECT 
                J.Job_ID, J.Service_Category, J.Date_started, J.Date_completed, 
                J.Customer_ref, J.Location, J.Job_capacity,
                I.Invoice_No, I.Invoice_Date, I.Invoice_Value, 
                I.Receiving_Payment, I.Received_amount, I.Payment_Received_Date 
              FROM Jobs J 
              LEFT JOIN Invoice_Data I ON J.Job_ID = I.Job_ID 
              WHERE J.Client_ref = 'A2Z Engineering'";

    if (!empty($_GET['invoiceID'])) {
        $query .= " AND I.Invoice_No LIKE '%" . $_GET['invoiceID'] . "%'";
    }
    if (!empty($_GET['customerRef'])) {
        $query .= " AND J.Customer_ref = '" . $_GET['customerRef'] . "'";
    }
    if ($_GET['status'] != 'All') {
        $statusValue = ($_GET['status'] == 'Paid') ? 1 : ($_GET['status'] == 'Partially Paid' ? 0.5 : 0);
        $query .= " AND I.Receiving_Payment = '$statusValue'";
    }
    if (!empty($_GET['fromDate']) && !empty($_GET['toDate'])) {
        $query .= " AND J.Date_completed BETWEEN '" . $_GET['fromDate'] . "' AND '" . $_GET['toDate'] . "'";
    }

    // Execute the query
    $result = $conn->query($query);
    
    // Initialize total amounts
    $totalAmount = $paidAmount = $dueBalance = 0;
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $totalAmount += $row['Invoice_Value'];
            if ($row['Receiving_Payment'] == 1) {
                $paidAmount += $row['Invoice_Value'];
            }
            if ($row['Receiving_Payment'] != 1) {
                $dueBalance += $row['Invoice_Value'];
            }
        }
    }
    ?>

    <div class="summary-container">
        <div><strong>Total Amount:</strong> <?= number_format($totalAmount, 2) ?></div>
<div style="
    background-color: #d4edda;
    color: #155724;
    padding: 10px 15px;
    border: 1px solid #c3e6cb;
    border-radius: 6px;
    font-weight: bold;
    display: inline-block;
    margin: 10px 0;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
">
    <strong>Paid Amount:</strong> <?= number_format($paidAmount, 2) ?>
</div>


<div style="
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px 15px;
    border: 1px solid #f5c6cb;
    border-radius: 6px;
    font-weight: bold;
    display: inline-block;
    margin: 10px 0;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
">
    <strong>Due Balance:</strong> <?= number_format($dueBalance, 2) ?>
</div>

    </div>


<table>
    <thead>
        <tr>
            <th>Job ID</th>
            <th>Service Category</th>
            <th>Date Started</th>
            <th>Date Completed</th>
            <th>Customer Ref</th>
            <th>Location</th>
            <th>Job Capacity</th>
            <th>Cost</th>
            <th>Invoice Details</th>
            <th>Profit</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php foreach ($result as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['Job_ID'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($row['Service_Category'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($row['Date_started'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($row['Date_completed'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($row['Customer_ref'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($row['Location'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($row['Job_capacity'] ?: '-') ?></td>
                    <td>
                        <!-- Cost Table Section -->
                        <div class="container">
                            <div class="section-header">Expense Summary for Job: <?= htmlspecialchars($row['Job_ID']); ?></div>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Expense Category</th>
                                        <th>Total Amount ($)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch categorized expenses from Operational_Expenses
                                    $expenseQuery = "
                                        SELECT Expenses_Category, SUM(Exp_amount) AS total_expenses 
                                        FROM Operational_Expenses 
                                        WHERE Job_ID = ? 
                                        GROUP BY Expenses_Category
                                    ";

                                    $stmt = $conn->prepare($expenseQuery);
                                    $stmt->bind_param("s", $row['Job_ID']);
                                    $stmt->execute();
                                    $expenseResult = $stmt->get_result();

                                    // Initialize total expenses variable
                                    $totalExpenses = 0;

                                    // Display categorized expenses
                                    if ($expenseResult->num_rows > 0) {
                                        while ($expenseData = $expenseResult->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($expenseData['Expenses_Category']) . "</td>";
                                            echo "<td class='text-end'>" . number_format($expenseData['total_expenses'], 2) . "</td>";
                                            echo "</tr>";
                                            
                                            // Add to total expenses
                                            $totalExpenses += $expenseData['total_expenses'];
                                        }
                                    } else {
                                        echo "<tr><td colspan='2'>No categorized expenses available</td></tr>";
                                    }

                                    // Fetch site material cost from Material_List_Per_Site
                                    $materialCostQuery = "
                                        SELECT SUM(Total_Site_Cost) AS total_site_cost
                                        FROM Material_List_Per_Site
                                        WHERE Job_ID = ?
                                    ";

                                    $materialStmt = $conn->prepare($materialCostQuery);
                                    $materialStmt->bind_param("s", $row['Job_ID']);
                                    $materialStmt->execute();
                                    $materialResult = $materialStmt->get_result();
                                    $materialData = $materialResult->fetch_assoc();

                                    // Add site material cost to total expenses
                                    $siteMaterialCost = $materialData['total_site_cost'] ? $materialData['total_site_cost'] : 0;
                                    $totalExpenses += $siteMaterialCost;

                                    // Display site material cost
                                    echo "<tr>";
                                    echo "<td>Site Material Cost</td>";
                                    echo "<td class='text-end'>" . number_format($siteMaterialCost, 2) . "</td>";
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total Expenses</th>
                                        <th class="text-end"><?= number_format($totalExpenses, 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>

                            <div class="section-header">Labor Payments</div>

                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Days Worked Before Increment</th>
                                        <th>Days Worked After Increment</th>
                                        <th>Labor Payment ($)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch attendance data for employees
                                    $attendanceQuery = "
                                        SELECT Emp_ID, COUNT(DISTINCT Atd_Date) AS days_worked
                                        FROM Attendance
                                        WHERE Job_ID = ? AND Presence = 1
                                        GROUP BY Emp_ID
                                    ";

                                    $stmt = $conn->prepare($attendanceQuery);
                                    $stmt->bind_param("s", $row['Job_ID']);
                                    $stmt->execute();
                                    $attendanceResult = $stmt->get_result();

                                    // Initialize total labor payment variable
                                    $totalLaborPayment = 0;

                                    // Display labor payment details for each employee
                                    if ($attendanceResult->num_rows > 0) {
                                        while ($attendanceData = $attendanceResult->fetch_assoc()) {
                                            // Fetch employee details
                                            $employeeQuery = "
                                                SELECT Emp_Name, Daily_Wage
                                                FROM Employee
                                                WHERE Emp_ID = ?
                                            ";

                                            $empStmt = $conn->prepare($employeeQuery);
                                            $empStmt->bind_param("s", $attendanceData['Emp_ID']);
                                            $empStmt->execute();
                                            $empResult = $empStmt->get_result();
                                            $employeeData = $empResult->fetch_assoc();
                                            
                                            // Calculate labor payment
                                            $daysBeforeIncrement = $attendanceData['days_worked']; // Assuming no increment data
                                            $laborPaymentForEmployee = $daysBeforeIncrement * $employeeData['Daily_Wage'];
                                            $totalLaborPayment += $laborPaymentForEmployee;

                                            // Display labor payment
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($employeeData['Emp_Name']) . "</td>";
                                            echo "<td class='text-center'>" . $daysBeforeIncrement . "</td>";
                                            echo "<td class='text-center'>" . 0 . "</td>"; // No days after increment
                                            echo "<td class='text-end'>" . number_format($laborPaymentForEmployee, 2) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4'>No attendance data available</td></tr>";
                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total Labor Payment</th>
                                        <th class="text-end"><?= number_format($totalLaborPayment, 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>

                            <div class="total-cost">
                                <strong>Total Cost (Expenses + Labor Payment): </strong>
                                <?= number_format($totalExpenses + $totalLaborPayment, 2); ?>
                            </div>
                        </div>
                    </td>

                    <td>
    <div style="border: 1px solid #ccc; padding: 10px; border-radius: 5px; background-color: #f9f9f9;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td><strong>Invoice No:</strong></td>
                <td><?= htmlspecialchars($row['Invoice_No'] ?: '-') ?></td>
            </tr>
            <tr>
                <td><strong>Invoice Date:</strong></td>
                <td><?= htmlspecialchars($row['Invoice_Date'] ?: '-') ?></td>
            </tr>
            <tr>
                <td><strong>Invoice Value:</strong></td>
                <td><?= htmlspecialchars($row['Invoice_Value'] ?: '-') ?></td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <?php
                    $status = $row['Receiving_Payment'];
                    if ($status == 1) echo "Paid";
                    elseif ($status == 0.5) echo "Partially Paid";
                    elseif ($status == 0) echo "Unpaid";
                    else echo "-";
                    ?>
                </td>
            </tr>
            <tr>
                <td><strong>Received Amount:</strong></td>
                <td><?= htmlspecialchars($row['Received_amount'] ?: '-') ?></td>
            </tr>
            <tr>
                <td><strong>Payment Received Date:</strong></td>
                <td><?= htmlspecialchars($row['Payment_Received_Date'] ?: '-') ?></td>
            </tr>
        </table>
    </div>
</td>

                    <td>
                        <?php
                        // Calculate and display profit with color coding
                        $profit = $row['Invoice_Value'] - $totalExpenses - $totalLaborPayment;
                        $profitColor = ($profit >= 0) ? 'green' : 'red';
                        echo "<span style='color: $profitColor;'>" . number_format($profit, 2) . "</span>";
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="14">No results found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>


</body>
</html>

<?php $conn->close(); ?>
