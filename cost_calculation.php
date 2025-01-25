<?php
session_start();

// Check if session variables and table GET param are set
if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname']) || !isset($_GET['table'])) {
    header("Location: index.php");
    exit();
}

// Database configuration (ideally read from a config file)
$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];

//Sanitizing table name as a safeguard
$tableName = filter_var($_GET['table'], FILTER_SANITIZE_STRING);

// Configure session settings for HTTPOnly and Secure (if HTTPS)
ini_set('session.cookie_httponly', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Error connecting to the database. Please try again later.");
}

// Named Constant for the job id filter
define("EXCLUDED_JOB_ID", 1);

// Fetch distinct customer references
$customerSql = "SELECT DISTINCT Customer_ref FROM Jobs";
$customerResult = $conn->query($customerSql);

if (!$customerResult) {
    error_log("Database query failed: " . $conn->error . " SQL: " . $customerSql);
    die("Error fetching customer references. Please try again later.");
}

$customerRefs = [];
while ($row = $customerResult->fetch_assoc()) {
    $customerRefs[] = $row['Customer_ref'];
}
// Fetch all job details
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
        Invoice_Data.Invoice_No,
        Invoice_Data.Invoice_Value,
        Invoice_Data.Receiving_Payment,
        Invoice_Data.Received_amount,
        Invoice_Data.Payment_Received_Date,
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
        Jobs.Job_ID <> " . EXCLUDED_JOB_ID . "  -- Exclude Job_ID = 1
    GROUP BY 
        Jobs.Job_ID, 
        Jobs.Service_Category, 
        Jobs.Date_completed, 
        Jobs.Customer_ref, 
        Jobs.Location, 
        Jobs.Job_capacity, 
        Invoice_Data.Invoice_No, 
        Invoice_Data.Invoice_Value,
        Invoice_Data.Receiving_Payment,
        Invoice_Data.Received_amount,
        Invoice_Data.Payment_Received_Date,
        Materials.Total_Site_Cost";

$result = $conn->query($sql);

// Check for SQL query error
if (!$result) {
    error_log("Database query failed: " . $conn->error . " SQL: " . $sql);
    die("Error executing database query. Please try again later.");
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
         // Include Site material cost (Total_Site_Cost) in the expenses
        $operationalExpensesTotal += floatval($row['Total_Site_Cost']);

        if ($row['Expense_Summary'] !== 'No expenses') {
            $expenseDetails = explode(', ', $row['Expense_Summary']);
            foreach ($expenseDetails as $expense) {
                $parts = explode(': ', $expense);
                $operationalExpensesTotal += floatval($parts[1]);
            }
        }


        // Calculate net profit
        $netProfit = floatval($row['Invoice_Value']) - (floatval($row['Total_Salary']) + $operationalExpensesTotal);

        // If the prefix is not in the groupedData array, initialize it
        if (!isset($groupedData[$prefixName])) {
            $groupedData[$prefixName] = [
                'Total_Invoice_Value' => 0,
                'Total_Net_Profit' => 0,
                'Job_Count' => 0
            ];
        }

        // Add values to the respective prefix group
        $groupedData[$prefixName]['Total_Invoice_Value'] += floatval($row['Invoice_Value']);
        $groupedData[$prefixName]['Total_Net_Profit'] += $netProfit;
        $groupedData[$prefixName]['Job_Count'] += 1;
    }

    return $groupedData;
}


// Calculate the totals by prefix
$groupedData = calculateTotalByPrefix($rows);

// Calculate summary values
$totalInvoiceAmount = 0;
$totalPaidAmount = 0;
$totalUnpaidAmount = 0;
$unpaidInvoiceCount = 0;

foreach ($rows as $row) {
    $totalInvoiceAmount += floatval($row['Invoice_Value']);
    if (floatval($row['Received_amount']) > 0) {
        $totalPaidAmount += floatval($row['Received_amount']);
    } else {
        $totalUnpaidAmount += floatval($row['Invoice_Value']);
        $unpaidInvoiceCount++;
    }
}

$dueBalance = $totalInvoiceAmount - $totalPaidAmount;


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cost Calculation Report</title>
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
            border: 1px solid #ddd; /* Add a light border to cards */
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

         /* Styling for the summary */
        .summary-container {
            background-color: #f0f0f0;
            padding: 20px;
            border-radius: 8px;
             margin-bottom: 20px;
        }
        .summary-item {
            margin-bottom: 10px;
        }
        .amount-paid {
            color: green;
        }
          .amount-unpaid {
            color: red;
        }

        /* Styling for the net profit values */
        .text-success {
            color: green;
        }

        .text-danger {
            color: red;
        }
        /* additional Styling for better readability */
        .section-header {
            margin-bottom: 20px;
            border-bottom: 2px solid #ccc;
            padding-bottom: 10px;
        }
        .report-section {
            margin-bottom: 30px; /* Space between report sections */
        }
        .report-section h3 {
            margin-bottom: 15px;
        }
        .report-summary-table {
            width: 80%;
            margin: 0 auto;
        }
        .report-summary-table td,
        .report-summary-table th {
            padding: 10px;
        }
        .job-table-wrapper {
           overflow-x: auto;
        }
        .job-table {
            width: 100%; /* Ensure it takes full width */
        }
        .summary-box {
             display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .summary-box h3 {
            margin: 0;
             padding-bottom: 10px;
            border-bottom: 1px solid #ccc;
           
        }

        .summary-box-item {
            flex: 1;
            padding: 10px;
             border: 1px solid #ddd;
             text-align: center;
              background-color: #f8f9fa;
              border-radius: 4px;
        }
        .filter-box {
            display: flex;
            flex-wrap: wrap;
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
           justify-content: space-around;
           align-items: flex-end;
        }

        .filter-item {
          margin-bottom: 10px;
          margin-right: 10px;

        }
         .filter-item label {
             display: block;
        }
    .filter-item input,
    .filter-item select{
         padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .search-button{
         background-color: #007bff;
         color: white;
         padding: 10px 15px;
          border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    </style>
</head>
<body>
    <div class="container-fluid mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4 section-header">
                <h2>Cost Calculation Report</h2>
                <a href="tables.php" class="btn btn-primary go-back-btn">Go Back</a>
        </div>

        <!-- Search and Filter Section -->
    <div class="filter-box">
       <form method="get">
            <div class="filter-item">
                 <label for="invoice_id">Invoice ID:</label>
                  <input type="text" id="invoice_id" name="invoice_id" value="<?php echo isset($_GET['invoice_id']) ? htmlspecialchars($_GET['invoice_id']) : ''; ?>">
             </div>
            <div class="filter-item">
                <label for="customer_name">Customer Name:</label>
                 <select id="customer_name" name="customer_name">
                   <option value="">All</option>
                      <?php
                        foreach($customerRefs as $ref)
                           {
                           $selected = (isset($_GET['customer_name']) && $_GET['customer_name'] == $ref) ? 'selected' : '';
                             echo "<option value='$ref' $selected>$ref</option>";
                             }
                        ?>

                </select>
            </div>
             <div class="filter-item">
                <label for="status">Status:</label>
                 <select id="status" name="status">
                     <option value="">All</option>
                        <option value="paid" <?php echo (isset($_GET['status']) && $_GET['status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                         <option value="unpaid" <?php echo (isset($_GET['status']) && $_GET['status'] == 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                </select>
            </div>
               <div class="filter-item">
                <label for="from_date">From Date:</label>
                 <input type="date" id="from_date" name="from_date" value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : ''; ?>">
             </div>
             <div class="filter-item">
                 <label for="to_date">To Date:</label>
                 <input type="date" id="to_date" name="to_date" value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : ''; ?>">
             </div>
             <div class="filter-item">
                  <button type = "submit" class="search-button">Search</button>
             </div>
       </form>
     </div>

         <!-- Summary Section -->
        <div class="report-section">
            <div class="summary-container">
               <div class="summary-box">
                  <div class="summary-box-item">
                       <h3 >Unpaid Invoice Amount</h3>
                        <p class="amount-unpaid font-weight-bold"><?php echo number_format($totalUnpaidAmount, 2); ?></p>
                   </div>
                   <div class="summary-box-item">
                      <h3 >Paid Invoice Amount</h3>
                       <p class="amount-paid font-weight-bold"><?php echo number_format($totalPaidAmount, 2); ?></p>
                   </div>
                      <div class="summary-box-item">
                       <h3 >Unpaid Invoice Count</h3>
                         <p class="font-weight-bold"><?php echo htmlspecialchars($unpaidInvoiceCount); ?></p>
                   </div>
                    <div class="summary-box-item">
                        <h3 >All Invoice Amount</h3>
                         <p class="font-weight-bold"><?php echo number_format($totalInvoiceAmount, 2); ?></p>
                     </div>
               </div>
               <div class="summary-box">
                   <div class="summary-box-item">
                        <h3 >Total Amount</h3>
                        <p class="font-weight-bold"><?php echo number_format($totalInvoiceAmount, 2); ?></p>
                   </div>
                   <div class="summary-box-item">
                      <h3 >Paid Amount</h3>
                       <p class="amount-paid font-weight-bold"><?php echo number_format($totalPaidAmount, 2); ?></p>
                   </div>
                   <div class="summary-box-item">
                      <h3 >Due Balance</h3>
                      <p class="amount-unpaid font-weight-bold"><?php echo number_format($dueBalance, 2); ?></p>
                   </div>
               </div>
            </div>

        </div>


    <div class="report-section">
         <h3 class="mt-5">Detailed Job Analysis</h3>
                <div class="card shadow-lg">
                   <div class="card-body">
                <?php
                    if (count($rows) > 0) {
                    echo "<div class='job-table-wrapper'>
                            <table class='table table-bordered table-striped job-table'>
                                <thead class='thead-dark'>
                                    <tr>
                                        <th>Job Reference Number</th>
                                        <th>Service Category</th>
                                        <th>Date Completed</th>
                                        <th>Customer Reference Code</th>
                                        <th>Location</th>
                                        <th>Job Capacity</th>
                                        <th>Invoice No</th>
                                        <th>Operational Expenses Summary</th>
                                        <th>Employee Details</th>
                                        <th>Total Salary</th>
                                        <th>Invoice Value</th>
                                         <th>Payment Received</th>
                                         <th>Payment Received Date</th>
                                        <th>Outstanding Payment</th>
                                          <th>Net Profit</th>
                                    </tr>
                                </thead>
                                <tbody>";
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
                                $netProfit = floatval($row['Invoice_Value']) - (floatval($row['Total_Salary']) + $operationalExpensesTotal);
        
                                 // Fetch attendance and employee details with a single query
                                $laborQuery = "
                                     SELECT 
                                        a.Emp_ID,
                                        e.Emp_Name,
                                        e.Daily_Wage,
                                         COALESCE(
                                            (SELECT si.New_Salary
                                            FROM Salary_Increments si
                                            WHERE si.Emp_ID = a.Emp_ID
                                              AND si.Increment_Date <= Jobs.Date_completed
                                              ORDER BY si.Increment_Date DESC
                                            LIMIT 1), e.Daily_Wage) AS Effective_Daily_Wage,
                                       SUM(a.Presence) AS days_worked
                                    FROM 
                                        Attendance a
                                    JOIN 
                                        Employee e ON a.Emp_ID = e.Emp_ID
                                    JOIN
                                      Jobs ON a.Job_ID = Jobs.Job_ID
                                    WHERE 
                                        a.Job_ID = ? 
                                    GROUP BY 
                                        a.Emp_ID, e.Emp_Name, e.Daily_Wage
                                ";

                                $stmt = $conn->prepare($laborQuery);

                                if (!$stmt) {
                                    error_log("Database prepare failed: " . $conn->error);
                                    die("Error preparing the database statement. Please try again later.");
                                }
                                $stmt->bind_param("s", $row['Job_ID']);
                                $stmt->execute();
                                $laborResult = $stmt->get_result();

                                if (!$laborResult) {
                                    error_log("Database query failed: " . $conn->error);
                                    die("Error executing database query. Please try again later.");
                                }

                                // Initialize total labor payment variable
                                $totalLaborPayment = 0;

                                // Display labor payment details for each employee
                                $employeeDetailsList = 'No employee data found';
                                if ($laborResult->num_rows > 0) {
                                    // Start building the unordered list
                                   $employeeDetailsList = '<ul>';
                                    while ($laborData = $laborResult->fetch_assoc()) {
                                        // Calculate labor payment
                                         $daysWorked = $laborData['days_worked']; // Number of days worked
                                         $effectiveDailyWage = $laborData['Effective_Daily_Wage'];  // Daily wage for the employee
                                        $laborPaymentForEmployee = $daysWorked * $effectiveDailyWage;
                                        $totalLaborPayment += $laborPaymentForEmployee; // Running total of the labor costs
                                        $employeeDetailsList .= "<li>" . htmlspecialchars($laborData['Emp_Name']) . ": " . number_format($laborPaymentForEmployee, 2) . "</li>"; // Creating a string to build the list.
                                    }
                                    $employeeDetailsList .= '</ul>'; // Close the ul list after the loop
                                }

                                 // Calculate the outstanding payment for the job
                                  $outstandingPayment = floatval($row['Invoice_Value']) - floatval($row['Received_amount']);

                                  // Determine the color for net profit
                                $netProfitColor = $netProfit < 0 ? 'red' : 'green';
                            
                                 // Append Total Site Cost to Expense_Summary
                                $expenseSummaryWithTotal = htmlspecialchars($row['Expense_Summary']) . ', <span class="font-weight-bold">Site Material Cost:</span> ' . number_format($row['Total_Site_Cost'], 2);
                        
                                echo "<tr>
                                        <td>" . htmlspecialchars($row['Job_ID']) . "</td>
                                        <td>" . htmlspecialchars($row['Service_Category']) . "</td>
                                        <td>" . htmlspecialchars($row['Date_completed']) . "</td>
                                        <td>" . htmlspecialchars($row['Customer_ref']) . "</td>
                                        <td>" . htmlspecialchars($row['Location']) . "</td>
                                        <td>" . htmlspecialchars($row['Job_capacity']) . "</td>
                                        <td>" . htmlspecialchars($row['Invoice_No']) . "</td>
                                        <td class='text-wrap'>{$expenseSummaryWithTotal}</td>
                                        <td class='text-wrap'>{$employeeDetailsList}</td>
                                        <td>" . number_format($row['Total_Salary'], 2) . "</td>
                                        <td>" . number_format($row['Invoice_Value'], 2) . "</td>
                                        <td>" . htmlspecialchars($row['Received_amount']) . "</td>
                                        <td>" . htmlspecialchars($row['Payment_Received_Date']) . "</td>
                                          <td>" . number_format($outstandingPayment, 2) . "</td>
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


    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
