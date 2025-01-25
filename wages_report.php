<?php
session_start();

// Ensure that session variables are set
if (!isset($_SESSION['username'], $_SESSION['password'], $_SESSION['dbname'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch employees excluding resigned employees
$sql = "SELECT Emp_ID, Emp_Name, Designation, Daily_Wage, Basic_Salary FROM Employee WHERE Date_of_resigned IS NULL";
$employees_result = $conn->query($sql);
$employees = $employees_result->fetch_all(MYSQLI_ASSOC);

// Function to calculate employee payments by payment type
function calculate_employee_payment_type($conn, $emp_id, $start_date, $end_date, $payment_type) {
      $sql = "SELECT SUM(Paid_Amount) AS total_paid_amount
        FROM Employee_Payments
        WHERE Emp_ID = ? AND Payment_Date BETWEEN ? AND ? AND Payment_Type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $emp_id, $start_date, $end_date, $payment_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total_paid_amount'] ?? 0;
}


// Function to calculate presence for all employees
function calculate_presence_data($conn, $start_date, $end_date) {
    $presence_data = [];
    $sql = "SELECT Emp_ID, SUM(CASE WHEN Presence = 1 THEN 1 WHEN Presence = 0.5 THEN 0.5 ELSE 0 END) AS presence_count
            FROM Attendance
            WHERE Atd_Date BETWEEN ? AND ?
            GROUP BY Emp_ID";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $presence_data[$row['Emp_ID']] = $row['presence_count'];
    }
    return $presence_data;
}

// Function to get latest increment for a specified type and check if it's within the range
function get_latest_increment($conn, $emp_id, $type, $start_date, $end_date) {
    $sql = "SELECT Increment_Amount, New_Salary, Increment_Date
            FROM Salary_Increments
            WHERE Emp_ID = ? AND Type = ?
            ORDER BY Increment_Date DESC
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $emp_id, $type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $increment_date = $row['Increment_Date'];
        if (strtotime($increment_date) <= strtotime($end_date)) {
            return $row;
        }
    }
    return null;
}

function generateReport($conn, $employees, $start_date, $end_date, $report_type, $employee_id, $deductions_values) {
    $ETF_PERCENTAGE = 0.03;
    $EPF_EMPLOYEE_PERCENTAGE = 0.08;
    $EPF_COMPANY_PERCENTAGE = 0.12;
    $report_output = '';
    $report_data = [];
    // Calculate employee payments and presence data
    $presence_data = calculate_presence_data($conn, $start_date, $end_date);

    if ($report_type == 'individual' && $employee_id) {
         // Filter employees by ID
        $employees = array_filter($employees, function($emp) use ($employee_id) {
            return $emp['Emp_ID'] == $employee_id;
        });

          // Check if any employees were found
        if (empty($employees)) {
            return '<div class="alert alert-warning mt-4">Employee not found.</div>';
        }
        foreach ($employees as $employee) {
                // Retrieve employee details
            $employee_name = htmlspecialchars($employee['Emp_Name']);
            $employee_id = htmlspecialchars($employee['Emp_ID']);
            $designation = htmlspecialchars($employee['Designation']);

            // Get the latest increment values for daily wage and basic salary
            $daily_wage_increment = get_latest_increment($conn, $employee['Emp_ID'], 'Daily Wage', $start_date, $end_date);
            $basic_salary_increment = get_latest_increment($conn, $employee['Emp_ID'], 'Basic Salary', $start_date, $end_date);

            // Assign daily wage and basic salary values, using the increment if available
            $daily_wage = $daily_wage_increment['New_Salary'] ?? $employee['Daily_Wage'];
            $basic_salary = $basic_salary_increment['New_Salary'] ?? $employee['Basic_Salary'];

            // Count of employee's presence
            $presence_count = $presence_data[$employee['Emp_ID']] ?? 0;

            // Calculate the total payable amount
            $total_payable = $presence_count * $daily_wage;

            // Calculate ETF and EPF values
            $etf = $basic_salary * $ETF_PERCENTAGE;
            $epf_employee = $basic_salary * $EPF_EMPLOYEE_PERCENTAGE;
            $epf_company = $basic_salary * $EPF_COMPANY_PERCENTAGE;


            $salary_payment = calculate_employee_payment_type($conn, $employee['Emp_ID'], $start_date, $end_date, 'Salary Payment');
              $advance_payment = calculate_employee_payment_type($conn, $employee['Emp_ID'], $start_date, $end_date, 'Advance Payment');
             $paid_amount =  $salary_payment + $advance_payment;


            // Use the deduction value from the form input, ensure it's a valid float
             $deductions = isset($deductions_values) ? (float)$deductions_values : 0.0;

            // Calculate net payable after deductions
            $net_payable = $total_payable - ($paid_amount + $epf_employee + $deductions);

            // Output report for individual employee
            $report_data[] = '
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="salary-slip-container">
                                    <div class="header-section">
                                        <div class="company-info">
                                            <img src="logo_black.jpg" alt="Company Logo" class="company-logo">
                                            <p>116/E/1, Pitumpe, Padukka 10500</p>
                                        </div>
                                        <h2>Salary Slip</h2>
                                    </div>
                                    <div class="details-section">
                                        <div class="employee-details">
                                            <h3>Employee Details</h3>
                                            <p><strong>Name:</strong> ' . $employee_name . '</p>
                                            <p><strong>ID:</strong> ' . $employee_id . '</p>
                                            <p><strong>Designation:</strong> ' . $designation . '</p>
                                            <p><strong>Period:</strong> ' . date('F d, Y', strtotime($start_date)) . ' - ' . date('F d, Y', strtotime($end_date)) . '</p>
                                        </div>
                                        <div class="salary-summary">
                                            <h3>Salary Summary</h3>
                                            <p><strong>Daily Wage:</strong> LKR ' . number_format($daily_wage, 2) . '</p>
                                            <p><strong>Presence Days:</strong> ' . number_format($presence_count, 2) . '</p>
                                            <p><strong>Basic Salary:</strong> LKR ' . number_format($basic_salary, 2) . '</p>
                                        </div>
                                    </div>
                                    <div class="payment-section">
                                        <h3>Payment Breakdown</h3>
                                        <div class="breakdown">
                                            <p><strong>ETF:</strong> LKR ' . number_format($etf, 2) . '</p>
                                            <p><strong>EPF (Employee):</strong> LKR ' . number_format($epf_employee, 2) . '</p>
                                            <p><strong>EPF (Company):</strong> LKR ' . number_format($epf_company, 2) . '</p>
                                            <p><strong>Total Payable:</strong> LKR ' . number_format($total_payable, 2) . '</p>
                                            <p><strong>Paid Amount:</strong> LKR ' . number_format($paid_amount, 2) . '</p>
                                            <p><strong>Deductions:</strong> LKR ' . number_format($deductions, 2) . ' </p>
                                            <p><strong>Net Payable:</strong> <span class="net-payable">LKR ' . number_format($net_payable, 2) . '</span></p>
                                        </div>
                                    </div>
                                    <div class="footer-section">
                                        <div class="signature">
                                            <p><br><br><br><br>----------------------------------------------</p>
                                            <p><strong>Employee Signature</strong></p>
                                        </div>
                                        <div class="seal">
                                            <p><br><br><br><br>----------------------------------------------</p>
                                            <p><strong>Company Seal</strong></p>
                                        </div>
                                    </div>
                                </div>
                           </div>
                            <div class="card-footer">
                           <!-- Show download button if report is generated -->
                        <form method="POST" action="generate_pdf.php">
                            <input type="hidden" name="report_output" value="'. htmlspecialchars($report_data[count($report_data)-1]) .'" />
                            <button type="submit" name="download_pdf" class="btn btn-success">Download as PDF</button>
                        </form>
                        </div>
                    </div>';

        }
             return implode("",$report_data);
        }
    else {
            // All Employees Summary report
        $report_output = '
            <h3>All Employees Summary</h3>
             <div class="alert alert-info">
                <h5>Profit Margins</h5>
                <ul>
                    <li><b>ETF :</b> 3%</li>
                    <li><b>EPF (Employee) :</b> 8%</li>
                    <li><b>EPF (Company) :</b> 12%</li>
                </ul>
             </div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Presence Count</th>
                         <th>Daily Wage</th>
                        <th>Basic Salary</th>
                        <th>ETF</th>
                        <th>EPF (Employee)</th>
                       <th>EPF (Company)</th>
                        <th>Total Payable</th>
                         <th>Paid Amount Details</th>
                         <th>Deductions</th>
                        <th>Net Payable</th>
                    </tr>
                </thead>
                <tbody>';
                foreach ($employees as $employee) {
                        $daily_wage_increment = get_latest_increment($conn, $employee['Emp_ID'], 'Daily Wage', $start_date, $end_date);
                    $basic_salary_increment = get_latest_increment($conn, $employee['Emp_ID'], 'Basic Salary', $start_date, $end_date);

                    $daily_wage = $daily_wage_increment['New_Salary'] ?? $employee['Daily_Wage'];
                    $basic_salary = $basic_salary_increment['New_Salary'] ?? $employee['Basic_Salary'];

                    $presence_count = $presence_data[$employee['Emp_ID']] ?? 0;
                    $total_payable = $presence_count * $daily_wage;

                    // Calculating ETF, EPF Contributions
                    $etf = $basic_salary * $ETF_PERCENTAGE;
                    $epf_employee = $basic_salary * $EPF_EMPLOYEE_PERCENTAGE;
                    $epf_company = $basic_salary * $EPF_COMPANY_PERCENTAGE;

                    $salary_payment = calculate_employee_payment_type($conn, $employee['Emp_ID'], $start_date, $end_date, 'Salary Payment');
                    $advance_payment = calculate_employee_payment_type($conn, $employee['Emp_ID'], $start_date, $end_date, 'Advance Payment');
                    $total_paid_amount = $salary_payment + $advance_payment;

                     $paid_amount_details = '<div>Salary Payment: LKR ' . number_format($salary_payment, 2) . '</div>';
                     $paid_amount_details .= '<div>Advance Payment: LKR ' . number_format($advance_payment, 2) . '</div>';



                     $deductions = isset($deductions_values[$employee['Emp_ID']]) ? (float) $deductions_values[$employee['Emp_ID']] : 0.0;
                    $net_payable = $total_payable - ($total_paid_amount + $epf_employee + $deductions);

                        $report_output .= '
                            <tr>
                                <td>' . htmlspecialchars($employee['Emp_Name']) . '</td>
                                <td>' . number_format($presence_count, 2) . '</td>
                               <td>' . number_format($daily_wage, 2) . '</td>
                               <td>' . number_format($basic_salary, 2) . '</td>
                                <td>' . number_format($etf, 2) . '</td>
                                <td>' . number_format($epf_employee, 2) . '</td>
                                <td>' . number_format($epf_company, 2) . '</td>
                                <td>' . number_format($total_payable, 2) . '</td>
                                <td>' . $paid_amount_details . '</td>
                                <td><input type="number" class="form-control deduction-input" name="deductions['. $employee['Emp_ID'].']" value="' . $deductions . '" step="0.01" /></td>
                               <td><span class="net-payable">' . number_format($net_payable, 2) . '</span></td>
                            </tr>';
                    }
             $report_output .= '</tbody></table>';
             $report_output .= '<form method="POST" action="generate_pdf.php">
                                 <input type="hidden" name="report_output" value="' . htmlspecialchars($report_output) . '" />
                                <button type="submit" name="download_pdf" class="btn btn-success">Download as PDF</button>
                             </form>';
        return  $report_output;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_report'])) {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $report_type = $_POST['report_type'] ?? '';
    $employee_id = $_POST['employee_id'] ?? null;
    $deductions_values = $_POST['deductions'] ?? [];


    // Validate start and end date
    if ($report_type && strtotime($start_date) && strtotime($end_date)) {
        if (strtotime($start_date) > strtotime($end_date)) {
            $report_output = '<div class="alert alert-danger mt-4">End date must be later than start date.</div>';
        } else {
             $report_output = generateReport($conn, $employees, $start_date, $end_date, $report_type, $employee_id, $deductions_values);
        }
    } else {
        $report_output = '<div class="alert alert-warning mt-4">Please fill all fields to generate the report.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Salary Slip</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
       <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            padding: 20px;
            color: #333;
        }


        .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        }

       .header-section {
            text-align: center;
            border-bottom: 2px solid #eaeaea;
            padding-bottom: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center; /* Center the header content horizontally */
            flex-direction: column;
        }
        
        .company-info img {
            max-width: 550px;
            margin-bottom: 10px;
            
        }

        .company-info p {
            margin: 0;
            font-size: 14px;
            color: #555;
        }


        h2 {
            margin: 10px 0;
            color: #2c3e50;
            text-align: center;
        }

        .details-section {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .employee-details,
        .salary-summary {
            width: 48%;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #34495e;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        p {
            margin: 8px 0;
            font-size: 16px;
        }

        .payment-section {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .payment-section h3 {
            border: none;
            margin-bottom: 15px;
        }

        .breakdown p {
            display: flex;
            justify-content: space-between;
            font-size: 16px;
        }

        .breakdown input {
            width: 100px;
            text-align: right;
            font-size: 14px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .footer-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .signature,
        .seal {
            text-align: center;
            font-size: 14px;
        }

        .signature p,
        .seal p {
            margin: 0;
        }

        .go-back-btn {
            background-color: red !important;
            border-color: red !important;
            color: white !important;
            text-decoration: none;
            top: 10px;
            right: 10px;
        }

        /* Form section specific styles */
        .form-label {
            font-size: 14px;
            color: #555;
             margin-top: 10px; /* Add some spacing above the label */
        }

        .form-control {
            margin-bottom: 10px;
        }

        .btn {
            margin-top: 15px;
        }

          .btn-refresh {
            background-color: #ffc107;
            color: white;
            border-color: #ffc107;
            margin-top: 10px;
             font-weight: 500;
        }
      .alert-info {
         margin-bottom: 20px;
      }
        .salary-slip-section {
            margin-top: 40px;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .salary-slip-section h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .salary-slip {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }

        .salary-slip h3 {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 10px;
            color: #34495e;
        }

        .salary-slip p {
            font-size: 16px;
            margin: 8px 0;
        }
         .table th{
            text-align: Center;
            vertical-align: middle;
            padding: 12px;
        }
        .table td {
            text-align: right;
        }

        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .table td {
            padding: 12px;
        }

        .net-payable {
            font-weight: bold;
            color: green;
        }

         .table tr:hover {
            background-color: #f1f1f1;
        }
        .card {
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

/* Card Body */
.card-body {
    padding: 20px;
    background-color: #fff;
    color: #333;
}

.card-footer {
    background-color: #f1f1f1;
    border-top: 1px solid #ddd;
    padding: 10px;
    font-size: 0.9rem;
    color: #777;
}
    .salary-slip-container {
        width: 100%;
       max-width: 800px;
        margin: 0 auto;
         padding: 20px;
         background: white;
        }

    </style>
</head>

<body>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-center mb-4">Employee Salary Report Generator</h2>
            <a href="tables.php" class="btn btn-danger go-back-btn">Go Back</a>
        </div>

        <!-- Form Section -->
        <div class="card mb-4">
            <div class="card-body">
        <form method="POST" action="" class="mb-4">
            <div class="row">
                <!-- Start Date Input -->
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" required>
                </div>

                <!-- End Date Input -->
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" required>
                </div>

                <!-- Report Type Input -->
                <div class="col-md-4">
                    <label for="report_type" class="form-label">Report Type</label>
                    <select name="report_type" id="report_type" class="form-control" required>
                        <option value="all">All Employees</option>
                        <option value="individual">Individual Employee</option>
                    </select>
                </div>
            </div>

            <!-- Employee Selection (Initially Hidden) -->
            <div id="employee_id_input" class="row mt-3" style="display:none;">
                <div class="col-md-12">
                    <label for="employee_id" class="form-label">Employee</label>
                    <select name="employee_id" id="employee_id" class="form-control">
                        <option value="">Select an employee</option>
                        <?php foreach ($employees as $employee) { ?>
                            <option value="<?= $employee['Emp_ID']; ?>"><?= $employee['Emp_Name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            
            <!-- Deduction Input (only for Individual Employee Report) -->
            <div class="form-group" id="deduction_field" style="display:none;">
                <label for="deductions" class="form-label"><strong>Deductions:</strong></label>
                <input type="number" name="deductions" id="deductions" class="form-control" value="0" step="0.01" >
            </div>

            <!-- Generate Report Button -->
            <div class="mt-3">
                <button type="submit" name="generate_report" class="btn btn-primary">Generate Report</button>
                
                <!-- Refresh Button -->
                <button type="button" class="btn btn-refresh" onclick="window.location.reload();">Refresh</button>

            </div>
        </form>
            </div>
        </div>

        <!-- Report Output -->
        <?php echo $report_output; ?>
    </div>

     <script>
     document.getElementById('report_type').addEventListener('change', function () {
        var employeeIdInput = document.getElementById('employee_id_input');
        if (this.value === 'individual') {
            employeeIdInput.style.display = 'block';
        } else {
            employeeIdInput.style.display = 'none';
        }
         toggleDeductionField();
    });

    function toggleDeductionField() {
        var reportType = document.getElementById('report_type').value;
        var deductionField = document.getElementById('deduction_field');
        if (reportType === 'individual') {
            deductionField.style.display = 'block';
        } else {
            deductionField.style.display = 'none';
             updateNetPayable();
        }
    }
      function calculateNetPayable(totalPayable, paidAmount, epfEmployee, deductions) {
            return totalPayable - (paidAmount + epfEmployee + deductions);
        }
    function updateNetPayable() {
            if (document.getElementById('report_type').value === 'individual') {
            var totalPayableInput = document.querySelector('.payment-section .breakdown p:contains("Total Payable")');
             if (totalPayableInput) {
                var totalPayable = parseFloat(totalPayableInput.textContent.match(/LKR ([\d.]+)/)[1]) || 0;
                var paidAmountInput = document.querySelector('.payment-section .breakdown p:contains("Paid Amount")');
                var paidAmount = paidAmountInput ? parseFloat(paidAmountInput.textContent.match(/LKR ([\d.]+)/)[1]) || 0 : 0;

                var basicSalaryInput = document.querySelector('.salary-summary p:contains("Basic Salary")');
                var basicSalary =  basicSalaryInput ? parseFloat(basicSalaryInput.textContent.match(/LKR ([\d.]+)/)[1]) || 0 : 0;

                    var epfEmployee = basicSalary * 0.08;  // EPF Employee: 8% of Basic Salary
                     var etf = basicSalary * 0.03; // ETF: 3% of Basic Salary
                        var deductions = 0;
                        if (document.getElementById('deduction_field').style.display !== 'none') {
                            deductions = parseFloat(document.getElementById('deductions').value) || 0;
                        }
                        var netPayable = calculateNetPayable(totalPayable, paidAmount, epfEmployee, deductions);
                            var netPayableElement = document.querySelector('.net-payable');
                            if (netPayableElement) {
                            netPayableElement.textContent = 'LKR ' + netPayable.toFixed(2);
                        }
            }
        } else{
               var rows = document.querySelectorAll('.table tbody tr');
                 rows.forEach(function(row) {
                    var totalPayableCell = row.querySelector('td:nth-child(8)');
                    var paidAmountDetailsCell = row.querySelector('td:nth-child(9)');
                     if(totalPayableCell && paidAmountDetailsCell){
                        var totalPayable = parseFloat(totalPayableCell.textContent.replace(/,/g, '').match(/([\d.]+)/)[1]) || 0;
                        var paidAmount = 0;
                        var paidAmountElements = paidAmountDetailsCell.querySelectorAll('div');
                         paidAmountElements.forEach(function(div) {
                            var amountMatch = div.textContent.match(/LKR ([\d.]+)/);
                            if(amountMatch){
                                paidAmount += parseFloat(amountMatch[1]);
                            }
                        });
                        var basicSalaryCell = row.querySelector('td:nth-child(4)');
                         var basicSalary = basicSalaryCell ? parseFloat(basicSalaryCell.textContent.replace(/,/g, '').match(/([\d.]+)/)[1]) || 0:0;

                         var epfEmployee = basicSalary * 0.08;
                        var deductionInput = row.querySelector('.deduction-input');
                         var deductions = parseFloat(deductionInput.value) || 0;
                         var netPayable = calculateNetPayable(totalPayable, paidAmount, epfEmployee, deductions);
                        var netPayableCell = row.querySelector('td .net-payable');
                         netPayableCell.textContent =  netPayable.toFixed(2);
                    }

                });
        }
    }
        var reportTypeSelect = document.getElementById('report_type');
         reportTypeSelect.addEventListener('change', toggleDeductionField);
          var deductionInput = document.getElementById('deductions');
        if(deductionInput){
             deductionInput.addEventListener('input',updateNetPayable);
        }
        var allDeductionInputs = document.querySelectorAll('.deduction-input');
        allDeductionInputs.forEach(function(input){
          input.addEventListener('input',updateNetPayable);
        });
  
        toggleDeductionField();
</script>

</body>

</html>
