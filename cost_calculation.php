<?php
session_start();

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// Check if session variables are set
if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname']) || !isset($_GET['table'])) {
    header("Location: index.php");
    exit();
}

// Retrieve and sanitize session variables
$servername = "localhost";
$username = htmlspecialchars($_SESSION['username']);
$password = htmlspecialchars($_SESSION['password']);
$dbname = htmlspecialchars($_SESSION['dbname']);
$table = htmlspecialchars($_GET['table']);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to fetch job details, operational expenses, attendance, and invoice data
$sql = "
    SELECT 
        Jobs.Job_ID, 
        Jobs.Service_Category, 
        Jobs.Date_completed, 
        Jobs.Customer_ref, 
        Jobs.Location, 
        Jobs.Job_capacity,
        COALESCE(Summary.Expense_Summary, 'No expenses') AS Expense_Summary,
        IFNULL(SUM(Employee.Daily_Wage * Attendance.Presence), 0) AS Total_Salary,
        GROUP_CONCAT(CONCAT(Employee.Emp_ID, ': ', Employee.Daily_Wage * Attendance.Presence) SEPARATOR ', ') AS Employee_Details,
        Invoice_Data.Invoice_No,
        Invoice_Data.Invoice_Value,
        COALESCE(Materials.Total_Site_Cost, 0) AS Total_Site_Cost
    FROM 
        Jobs
    LEFT JOIN (
        SELECT 
            Job_ID,
            GROUP_CONCAT(CONCAT(Expenses_Category, ': ', Total_Expenses) SEPARATOR ', ') AS Expense_Summary
        FROM (
            SELECT 
                Job_ID,
                Expenses_Category,
                SUM(Exp_amount) AS Total_Expenses
            FROM 
                Operational_Expenses
            GROUP BY 
                Job_ID, Expenses_Category
        ) AS Expenses
        GROUP BY 
            Job_ID
    ) AS Summary ON Jobs.Job_ID = Summary.Job_ID
    LEFT JOIN 
        Attendance ON Jobs.Job_ID = Attendance.Job_ID
    LEFT JOIN 
        Employee ON Attendance.Emp_ID = Employee.Emp_ID
    LEFT JOIN 
        Invoice_Data ON Jobs.Job_ID = Invoice_Data.Job_ID
    LEFT JOIN 
        Material_List_Per_Site AS Materials ON Jobs.Job_ID = Materials.Job_ID
    WHERE 
        Jobs.Job_ID <> 1  -- Exclude Job_ID = 1
    GROUP BY 
        Jobs.Job_ID, 
        Jobs.Service_Category, 
        Jobs.Date_completed, 
        Jobs.Customer_ref, 
        Jobs.Location, 
        Jobs.Job_capacity, 
        Invoice_Data.Invoice_No, 
        Invoice_Data.Invoice_Value, 
        Materials.Total_Site_Cost";


$result = $conn->query($sql);

// Check for SQL query error
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Store result rows in an array
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

// Reverse the rows array (this will make the first entry appear at the bottom)
$rows = array_reverse($rows);

// Function to calculate the total Invoice Value and Net Profit by extracting Invoice No prefixes
function calculateTotalByPrefix($rows) {
    $groupedData = [];

    // Prefix mappings for display
    $prefixMappings = [
        'A' => 'A2Z Engineering',
        'HS' => 'Hayleys Solar',
        'EBC' => 'EB Creasy Solar',
        'DPS' => 'Davis Peries Solar',
        'Unknown' => 'Other Expenses'
    ];

    // Loop through each row and extract the prefix from Invoice No
    foreach ($rows as $row) {
        // Extract the prefix (first 2 to 3 characters) from Invoice No (A2Z, HS, etc.)
        $invoiceNo = $row['Invoice_No'];
        preg_match('/^[A-Za-z]+/', $invoiceNo, $matches);
        $prefix = isset($matches[0]) ? $matches[0] : 'Unknown';  // Default to 'Unknown' if no match

        // Get the mapped name for the prefix
        $prefixName = isset($prefixMappings[$prefix]) ? $prefixMappings[$prefix] : $prefixMappings['Unknown'];

        // Calculate the operational expenses and net profit
        $operationalExpensesTotal = 0;
        if ($row['Expense_Summary'] !== 'No expenses') {
            $expenseDetails = explode(', ', $row['Expense_Summary']);
            foreach ($expenseDetails as $expense) {
                $parts = explode(': ', $expense);
                $operationalExpensesTotal += floatval($parts[1]);
            }
        }

        // Calculate net profit
        $netProfit = $row['Invoice_Value'] - ($row['Total_Salary'] + $operationalExpensesTotal);

        // If the prefix is not in the groupedData array, initialize it
        if (!isset($groupedData[$prefixName])) {
            $groupedData[$prefixName] = [
                'Total_Invoice_Value' => 0,
                'Total_Net_Profit' => 0,
                'Job_Count' => 0
            ];
        }

        // Add values to the respective prefix group
        $groupedData[$prefixName]['Total_Invoice_Value'] += $row['Invoice_Value'];
        $groupedData[$prefixName]['Total_Net_Profit'] += $netProfit;
        $groupedData[$prefixName]['Job_Count'] += 1;
    }

    return $groupedData;
}


// Calculate the totals by prefix
$groupedData = calculateTotalByPrefix($rows);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cost Calculation</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqk1w27APbCZZp+trN3v8TpgAm16FB46Z+9xjbBJCGSdOdQoNLwOp8aAgBxSsQfjJxFoq6+A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
        }

        .container-fluid {
            margin-top: 30px;
        }

        .d-flex {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
        }

        h2, h3 {
            font-weight: bold;
            color: #2c3e50;
        }

        .go-back-btn {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }

        .go-back-btn:hover {
            background-color: #0056b3;
        }

        .card {
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .card-body {
            padding: 20px;
        }

        .table-bordered {
            border: 1px solid #dee2e6;
        }

        .thead-dark th {
            background-color: #343a40;
            color: white;
            font-weight: bold;
        }

        .thead-light th {
            background-color: #f8f9fa;
            color: #495057;
        }

        .font-weight-bold {
            font-weight: bold;
        }

        .table-container {
            overflow-x: auto;
        }

        .text-wrap {
            word-wrap: break-word;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f2f2f2;
        }

        .table th, .table td {
            vertical-align: middle;
        }

        /* Styling for the net profit values */
        .text-success {
            color: green;
        }

        .text-danger {
            color: red;
        }

    </style>
</head>
<body>
    <div class="container-fluid mt-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Cost Calculation Summary for Jobs</h2>
                <a href="tables.php" class="btn btn-primary go-back-btn">Go Back</a>
        </div>
        <!-- Display the total invoice value and net profit by prefix at the top -->
        <h3 class="mt-5">Invoice Value and Net Profit by Company</h3>
        <div class="card mb-4">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>Company</th>
                            <th>Total Invoice Value</th>
                            <th>Total Net Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupedData as $prefix => $data): ?>
                            <tr>
                                <td><?php echo $prefix; ?></td>
                                <td><?php echo number_format($data['Total_Invoice_Value'], 2); ?></td>
                                <td><?php echo number_format($data['Total_Net_Profit'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card shadow-lg">
            <div class="card-body">
                <?php
                if (count($rows) > 0) {
                    echo "<div class='table-container'>
                            <table class='table table-bordered table-striped'>
                                <thead class='thead-dark'>
                                    <tr>
                                        <th>Job ID</th>
                                        <th>Service Category</th>
                                        <th>Date Completed</th>
                                        <th>Customer Ref</th>
                                        <th>Location</th>
                                        <th>Job Capacity</th>
                                        <th>Invoice No</th>
                                        <th>Operational Expenses Summary</th>
                                        <th>Employee Details</th>
                                        <th>Total Salary</th>
                                        <th>Invoice Value</th>
                                        <th>Net Profit</th>
                                    </tr>
                                </thead>
                                <tbody>";
                    // In the loop where you process each row
foreach ($rows as $row) {
    // Calculate operational expenses total
    $operationalExpensesTotal = 0;
    
    // Include Site material cost (Total_Site_Cost) in the expenses
    $operationalExpensesTotal += floatval($row['Total_Site_Cost']);

    // If there are other expenses, process them
    if ($row['Expense_Summary'] !== 'No expenses') {
        $expenseDetails = explode(', ', $row['Expense_Summary']);
        foreach ($expenseDetails as $expense) {
            $parts = explode(': ', $expense);
            $operationalExpensesTotal += floatval($parts[1]);
        }
    }

    // Calculate net profit
    $netProfit = $row['Invoice_Value'] - ($row['Total_Salary'] + $operationalExpensesTotal);

    // Sum contributions by Emp_ID
    $employeeDetails = [];
    if ($row['Employee_Details'] !== null) {
        $employeeDetailsArr = explode(', ', $row['Employee_Details']);
        foreach ($employeeDetailsArr as $detail) {
            list($empId, $contribution) = explode(': ', $detail);
            if (!isset($employeeDetails[$empId])) {
                $employeeDetails[$empId] = 0;
            }
            $employeeDetails[$empId] += floatval($contribution);
        }
    }

    // Format employee details for display
    $formattedEmployeeDetails = [];
    foreach ($employeeDetails as $empId => $totalContribution) {
        $formattedEmployeeDetails[] = "{$empId}: " . number_format($totalContribution, 2);
    }
    $formattedEmployeeDetailsStr = implode(', ', $formattedEmployeeDetails);

    // Append Total Site Cost to Expense_Summary
    $expenseSummaryWithTotal = $row['Expense_Summary'] . ', <span class="font-weight-bold">Site Material Cost:</span> ' . number_format($row['Total_Site_Cost'], 2);

    // Determine the color for net profit
    $netProfitColor = $netProfit < 0 ? 'red' : 'green';

    echo "<tr>
            <td>{$row['Job_ID']}</td>
            <td>{$row['Service_Category']}</td>
            <td>{$row['Date_completed']}</td>
            <td>{$row['Customer_ref']}</td>
            <td>{$row['Location']}</td>
            <td>{$row['Job_capacity']}</td>
            <td>{$row['Invoice_No']}</td>
            <td class='text-wrap'>{$expenseSummaryWithTotal}</td>
            <td class='text-wrap'>{$formattedEmployeeDetailsStr}</td>
            <td>" . number_format($row['Total_Salary'], 2) . "</td>
            <td>" . number_format($row['Invoice_Value'], 2) . "</td>
            <td class='font-weight-bold' style='color: {$netProfitColor};'>" . number_format($netProfit, 2) . "</td>
          </tr>";
}

                    echo "</tbody></table></div>";
                } else {
                    echo "<p class='text-center'>No job data found.</p>";
                }

                
                echo "</tbody></table>";
                $conn->close();
                ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
