<?php
session_start();

// Function to get employee name from Emp_ID (Improved error handling)
function getEmployeeName($empId, $conn) {
    $sql = "SELECT Emp_Name FROM Employee WHERE Emp_ID = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("Error preparing statement: " . $conn->error . " (Query: $sql)");
        return 'Error'; 
    }

    $stmt->bind_param("s", $empId); // Error: Using string type instead of integer

    if (!$stmt->execute()) {
        error_log("Error executing statement: " . $stmt->error . " (Query: $sql)");
        return 'Error'; 
    }

    $stmt->bind_result($empName);
    $stmt->fetch();
    $stmt->close();
    return $empName ? htmlspecialchars($empName) : 'Unknown';
}

// Function to get boolean icon
function getBooleanIcon($value) {
    return $value ? '<i class="fas fa-check green"></i>' : '<i class="fas fa-times red"></i>';
}

// Function to get Service_Category and Customer_ref from Jobs table 
function getJobDetails($jobId, $conn) {
    $sql = "SELECT Service_Category, Customer_ref FROM Jobs WHERE Job_ID = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Error preparing statement: " . $conn->error . " (Query: $sql)");
        return 'Error - Error retrieving job details'; 
    }

    $stmt->bind_param("s", $jobId); // Error: Using string type instead of integer
    $stmt->execute();
    $stmt->bind_result($serviceCategory, $customerRef);
    $stmt->fetch();
    $stmt->close();

    $serviceCategory = $serviceCategory ? htmlspecialchars($serviceCategory) : '';
    $customerRef = $customerRef ? htmlspecialchars($customerRef) : '';

    return "{$serviceCategory} - {$customerRef}";
}

// Function to check if an invoice exists for a given Job_ID
function checkInvoiceExists($jobId, $conn) {
    $sql = "SELECT COUNT(*) FROM Invoice_Data WHERE Job_ID = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Error preparing statement: " . $conn->error . " (Query: $sql)");
        return false; // Assume no invoice if error occurs
    }

    $stmt->bind_param("s", $jobId); // Error: Using string type instead of integer
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname']) || !isset($_GET['table'])) {
    header("Location: index.php");
    exit();
}

// Function to calculate total Job_capacity
function calculateTotalJobCapacity($conn) {
    $totalCapacity = 0.0; // Initialize as a double

    // SQL query to fetch Job_capacity column
    $sql = "SELECT Job_capacity FROM Jobs";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Extract numeric part from varchar 
            if (preg_match('/\d+(\.\d+)?/', $row['Job_capacity'], $matches)) {
                // Cast to double and add to total
                $totalCapacity += (double)$matches[0]; 
            }
        }
    } else {
        // If no rows are found or query fails, you could log an error or handle as needed.
        echo "No records found or query failed.";
    }

    return $totalCapacity;
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

// Sanitize and get table name
$table = mysqli_real_escape_string($conn, $_GET['table']);

// Get primary key of the table
$primaryKeyResult = $conn->query("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
if (!$primaryKeyResult || $primaryKeyResult->num_rows == 0) { // Error: Combining error and no rows check incorrectly
    die("Error: Could not determine the primary key for the table $table.");
}

$primaryKeyRow = $primaryKeyResult->fetch_assoc();
$primaryKey = $primaryKeyRow['Column_name'];

// Handle job_id filtering for Attendance table
if ($table === 'Attendance' && isset($_GET['job_id'])) {
    $jobId = $_GET['job_id']; // Error: Missing sanitization
    $sql = "SELECT * FROM $table WHERE Job_ID = $jobId ORDER BY $primaryKey DESC"; // Error: Using unsanitized variable directly
} else {
    $sql = "SELECT * FROM $table ORDER BY $primaryKey DESC";
}

$result = $conn->query($sql);
if (!$result) {
    die("Error retrieving data from the table: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Table - <?php echo htmlspecialchars($table); ?></title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f4f7fc;
        margin: 0;
        padding: 0;
    }

    .container-fluid {
        margin-top: 30px;
        padding: 20px;
    }

    .d-flex {
        margin-bottom: 20px;
    }

    h2 {
        font-size: 28px;
        color: #333;
        font-weight: bold;
    }

    .btn-goback {
        background-color: #6c63ff;
        color: white;
        font-size: 16px;
        font-weight: bold;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .btn-goback:hover {
        background-color: #5a52d1;
    }

    .form-row .col {
        margin-bottom: 10px;
    }

    .search-box {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .search-box input {
        width: 75%;
        padding: 10px;
        font-size: 16px;
        border-radius: 5px;
        border: 1px solid #ddd;
    }

    .search-box a {
        display: flex;
        align-items: center;
        background-color: #28a745;
        color: white;
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 5px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .search-box a:hover {
        background-color: #218838;
    }

    .table-wrapper {
        margin-top: 30px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 12px;
        text-align: center;
        border: 1px solid #ddd;
        font-size: 14px;
    }

    thead {
        background-color: #6c63ff;
        color: white;
    }

    tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .btn-info {
        background-color: #17a2b8;
        color: white;
        font-size: 14px;
        padding: 8px 16px;
        border-radius: 5px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .btn-info:hover {
        background-color: #138496;
    }

    .btn-warning {
        background-color: #ffc107;
        color: white;
        font-size: 14px;
        padding: 8px 16px;
        border-radius: 5px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .btn-warning:hover {
        background-color: #e0a800;
    }

    .btn-danger {
        background-color: #dc3545;
        color: white;
        font-size: 14px;
        padding: 8px 16px;
        border-radius: 5px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .btn-danger:hover {
        background-color: #c82333;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 5px;
    }

    .green {
        color: #28a745;
        font-weight: bold;
    }

    .yellow {
        color: #ffc107;
        font-weight: bold;
    }

    .red {
        color: #dc3545;
        font-weight: bold;
    }

    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .filter-button {
        margin-right: 10px;
    }

    .filter-button:hover {
        background-color: #138496;
    }

    .btn-yet-to-add {
        background-color: #6c757d;
        color: white;
    }

    .btn-yet-to-add:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
    }

    .btn-invoice {
        background-color:#008000;
        color: white;
    }

    .btn-invoice-yet-to-add {
        background-color: #6c757d;
        color: white;
    }

    .btn-invoice:hover {
        background-color: #008000;
    }

    .btn-invoice-yet-to-add:hover {
        background-color: #5a6268;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .search-box {
            flex-direction: column;
            align-items: stretch;
        }

        .search-box input {
            width: 100%;
            margin-bottom: 10px;
        }

        .search-box a {
            width: 100%;
            margin-top: 10px;
        }

        table th, table td {
            font-size: 12px;
        }
    }
</style>
 
</head>
<body>
    <div class="container-fluid mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Table: <?php echo htmlspecialchars($table); ?></h2>
            <a href="tables.php" class="btn btn-goback">Go Back</a>
        </div>

        <div class="mb-3">
            <form id="dateRangeForm" method="GET" action="download_csv.php">
                <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">
                <div class="form-row">
                    <div class="col">
                        <input type="date" class="form-control" name="start_date" required>
                    </div>
                    <div class="col">
                        <input type="date" class="form-control" name="end_date" required>
                    </div>
                    <div class="col">
                        <button type="submit" class="btn btn-info">Download CSV</button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($table === 'Jobs') : ?>
            <div class="mb-3">
                <button class="btn btn-info filter-button" data-filter="Telecommunication Services">Telecommunication Services</button>
                <button class="btn btn-info filter-button" data-filter="Civil Installations">Civil Installations</button>
                <button class="btn btn-info filter-button" data-filter="Electrical Installations">Electrical Installations</button>
                <button class="btn btn-info filter-button" data-filter="Solar PV Systems">Solar PV Systems</button>
                <button class="btn btn-info filter-button" data-filter="AC Maintenance and Installation">AC Maintenance and Installation</button>
                <button class="btn btn-info filter-button" data-filter="Other">Other</button>
            </div>
            <div class="alert alert-info">
                <?php
                $totalJobCapacity = calculateTotalJobCapacity($conn);
                echo "Total Solar PV Job Capacity: " . htmlspecialchars($totalJobCapacity) . " kW";
                ?>
            </div>
        <?php endif; ?>

        <div class="search-box">
            <input class="form-control" id="searchInput" type="text" placeholder="Search..">
            <a href="add_entry.php?table=<?php echo htmlspecialchars($table); ?>" class="btn btn-success"><i class="fas fa-plus"></i> Add Entry</a>
        </div>
        
        <div class="table-wrapper">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <?php
                        if ($result->num_rows > 0) {
                            $fields = $result->fetch_fields();
                            foreach ($fields as $field) {
                                echo "<th>" . htmlspecialchars($field->name) . "</th>";
                            }
                            echo "<th>Actions</th>"; 
                        } else {
                            echo "<tr><th>No entries found in the table.</th></tr>";
                        }
                        ?>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            foreach ($row as $columnName => $data) {
                                echo "<td>";
                                if ($columnName == 'Job_ID' && in_array($table, ['Attendance', 'Operational_Expenses', 'Invoice_Data', 'Material_List_Per_Site'])) {
                                    echo getJobDetails($data, $conn);
                                } elseif ($columnName == 'Emp_ID' && $table != 'Employee') {
                                    echo getEmployeeName($data, $conn);
                                } elseif ($table == 'Attendance' && $columnName == 'Presence') {
                                    if ($data == 1.0) {
                                        echo '<span class="green">Full Day</span>';
                                    } elseif ($data == 0.5) {
                                        echo '<span class="yellow">Half Day</span>';
                                    } else {
                                        echo '<span class="red">Not Attended</span>';
                                    }
                                } else {
                                    if ($fields[array_search($columnName, array_column($fields, 'name'))]->type == 1) { // TINYINT (Boolean)
                                        echo getBooleanIcon($data);
                                    } else if (
                                        isset($fields[array_search($columnName, array_column($fields, 'name'))]) &&
                                        $fields[array_search($columnName, array_column($fields, 'name'))]->type == 252
                                    ) { // LONGBLOB
                                        if (!empty($data)) {
                                            echo '<a href="view_attachment.php?table=' . htmlspecialchars($table) . '&column=' . htmlspecialchars($columnName) . '&id=' . htmlspecialchars($row[$primaryKey]) . '" class="btn btn-primary btn-sm">View</a>';
                                        } else {
                                            echo '<button class="btn btn-yet-to-add btn-sm" disabled>Yet to Add</button>';
                                        }
                                    } else {
                                        echo !empty($data) ? htmlspecialchars($data) : '';
                                    }
                                }
                                echo "</td>";
                            }

                            // Actions column
                            echo '<td>';

                            if ($table === 'Jobs') {
                                if (checkInvoiceExists($row['Job_ID'], $conn)) {
                                    echo '<a href="view_invoice.php?job_id=' . htmlspecialchars($row['Job_ID']) . '" class="btn btn-invoice btn-sm">Invoice</a>';
                                } else {
                                    echo '<a href="add_entry.php?table=Invoice_Data&job_id=' . htmlspecialchars($row['Job_ID']) . '" class="btn btn-invoice-yet-to-add btn-sm">Yet to Add</a>'; 
                                }
                                
                                // echo '<a href="manage_table.php?table=Attendance&job_id=' . htmlspecialchars($row['Job_ID']) . '" class="btn btn-primary btn-sm">Attendance</a>';
                            }

                            // Update and Delete buttons using primary key
                            echo '<a href="update_entry.php?table=' . htmlspecialchars($table) . '&id=' . htmlspecialchars($row[$primaryKey]) . '" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> </a>'; 
                            echo '<a href="delete_entry.php?table=' . htmlspecialchars($table) . '&id=' . htmlspecialchars($row[$primaryKey]) . '" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash-alt"></i> </a>';

                            echo '</td>'; 
                            echo "</tr>";
                        } 
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div> 

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#searchInput').keyup(function () {
                var input = $(this).val().toLowerCase();
                $('#tableBody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(input) > -1);
                });
            });

            $('.filter-button').click(function () {
                var filter = $(this).data('filter').toLowerCase();
                $('#tableBody tr').filter(function () {
                    $(this).toggle($(this).text().toLowerCase().indexOf(filter) > -1);
                });
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
