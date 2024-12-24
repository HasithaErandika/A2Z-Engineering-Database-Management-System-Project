<?php
session_start();

// Check if session variables are set
if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname']) || !isset($_GET['table'])) {
    header("Location: index.php");
    exit();
}

// Database credentials
$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];
$table = $_GET['table'];

// Sanitize table name to prevent SQL injection
$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

// Establish database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get employees (excluding resigned employees)
function getEmployees($conn) {
    $employees = [];
    $sql = "SELECT Emp_ID, Emp_Name FROM Employee WHERE Date_of_resigned IS NULL"; // Corrected table name
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
    return $employees;
}

// Function to get the next auto increment value
function getNextAutoIncrementValue($conn, $table, $autoIncrementField) {
    $sql = "SELECT MAX($autoIncrementField) as max_id FROM $table"; // Corrected SQL function
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['max_id'] + 1;
    }
    return 1; // Default to 1 if no rows exist
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [];
    $values = [];
    $placeholders = [];
    $types = '';

    // Handle file uploads
    foreach ($_FILES as $key => $file) {
        if ($file['error'] == UPLOAD_ERR_OK) {
            $fileContent = file_get_contents($file['tmp_name']);
            $fields[] = $key;
            $values[] = $fileContent; // Store raw file content directly
            $placeholders[] = '?';
            $types .= 'b'; // 'b' for blob
        }
    }

    // Handle other form fields
    foreach ($_POST as $key => $value) {
        $fields[] = $key;
        $values[] = $value;
        $placeholders[] = '?';
        $types .= 's'; // Assume all are strings for simplicity
    }

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO $table (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$values);

    // Execute SQL statement
    if ($stmt->execute()) {
        header("Location: manage_table.php?table=$table");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close statement and connection
    $stmt->close();
}

function getJobDataForSelect2($conn)
{
    $jobData = [];
    $sql = "SELECT Job_ID, Service_Category, Customer_ref, Location, Client_ref, Job_capacity FROM Jobs"; // Corrected table name
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $jobData[] = [
                'id' => $row['Job_ID'],
                'text' => $row['Job_ID'] . ' - ' . $row['Service_Category'] . ' - ' . $row['Customer_ref']  // Customize the displayed text
            ];
        }
    }
    return json_encode($jobData);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Entry to <?php echo htmlspecialchars($table); ?></title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Arial', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .form-container {
            background-color: #ffffff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 550px;
        }

        h2 {
            font-size: 1.8rem;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 20px;
            text-align: center;
        }

        label {
            font-weight: 600;
            color: #495057;
        }

        .form-control {
            border-radius: 10px;
            font-size: 14px;
            padding: 12px;
        }

        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.25);
        }

        .select2-container .select2-selection--single {
            height: 40px;
            border-radius: 10px;
            padding: 5px 10px;
        }

        .btn {
            padding: 12px 20px;
            font-size: 14px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-block {
            width: 100%;
        }

        .btn-round {
            border-radius: 20px;
        }

        .btn-full-day {
            background-color: #28a745;
            color: white;
        }

        .btn-half-day {
            background-color: #ffc107;
            color: white;
        }

        .btn-not-attended {
            background-color: #dc3545;
            color: white;
        }

        .cancel-btn {
            background-color: #6c757d;
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .date-picker,
        .time-picker {
            display: flex;
            align-items: center;
        }

        .date-picker .calendar-icon,
        .time-picker .clock-icon {
            margin-left: -30px;
            margin-top: -3px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Add Entry to <?php echo htmlspecialchars($table); ?></h2>
        <form method="post" action="" enctype="multipart/form-data">
        <?php
                        // Establish database connection
                        $conn = new mysqli($servername, $username, $password, $dbname);
                        if ($conn->connect_error) {
                            die("Connection failed: " . $conn->connect_error);
                        }

                        $sql = "DESCRIBE $table";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            $autoIncrementField = null;
                            $employees = getEmployees($conn); // Fetch employees for the dropdown
                            $jobData = getJobDataForSelect2($conn); // Fetch job data for Select2

                            while ($row = $result->fetch_assoc()) {
                                if (strpos($row['Extra'], 'auto_increment') !== false) {
                                    $autoIncrementField = $row['Field'];
                                    break;
                                }
                            }

                            if ($autoIncrementField) {
                                $nextAutoIncrementValue = getNextAutoIncrementValue($conn, $table, $autoIncrementField);
                            }

                            $result->data_seek(0); // reset result pointer to reuse it
                            while ($row = $result->fetch_assoc()) {
                                echo '<div class="form-group">';
                                echo '<label for="' . $row['Field'] . '">' . htmlspecialchars($row['Field']) . ':</label>';

                                if ($row['Field'] == $autoIncrementField) {
                                    // Auto-increment field - make it readonly
                                    echo '<input type="text" id="' . $row['Field'] . '" name="' . $row['Field'] . '" class="form-control" value="' . $nextAutoIncrementValue . '" readonly>';
                                } elseif ($row['Field'] == 'Job_ID') {
                                    // Job_ID field - use Select2
                                    echo '<select id="Job_ID" name="Job_ID" class="form-control job-select" required>';
                                    echo '</select>'; // Options will be added by Select2
                                } elseif (($table == 'Employee_Payments' || $table == 'Attendance' || $table == 'Operational_Expenses') && $row['Field'] == 'Emp_ID') {
                                    // Employee selection dropdown
                                    echo '<select id="' . $row['Field'] . '" name="' . $row['Field'] . '" class="form-control employee-select" required>';
                                    echo '<option value="">Select Employee</option>';
                                    foreach ($employees as $employee) {
                                        echo '<option value="' . htmlspecialchars($employee['Emp_ID']) . '">' . htmlspecialchars($employee['Emp_Name']) . '</option>';
                                    }
                                    echo '</select>';
                                } elseif ($table == 'Operational_Expenses' && $row['Field'] == 'Expenses_Category') {
                                    // Expenses Category dropdown
                                    echo '<select id="' . $row['Field'] . '" name="' . $row['Field'] . '" class="form-control" required>';
                                    $categories = ['Meals', 'Tools', 'Fuel', 'Materials', 'Hiring of labor', 'Hiring of vehicle', 'Mobile', 'Other'];
                                    foreach ($categories as $category) {
                                        echo '<option value="' . htmlspecialchars($category) . '">' . htmlspecialchars($category) . '</option>';
                                    }
                                    echo '</select>';
                                } elseif ($table == 'Jobs' && $row['Field'] == 'Service_Category') {
                                    // Service Category dropdown
                                    echo '<select id="' . $row['Field'] . '" name="' . $row['Field'] . '" class="form-control" required>';
                                    $categories = ['Telecommunication Services', 'Civil Installations', 'Electrical Installations', 'Solar PV Systems', 'AC Maintenance and Installation', 'Other'];
                                    foreach ($categories as $category) {
                                        echo '<option value="' . htmlspecialchars($category) . '">' . htmlspecialchars($category) . '</option>';
                                    }
                                    echo '</select>';
                                } elseif ($table == 'Employee_Payments' && $row['Field'] == 'Payment_Type') {
                                    // Payment Type dropdown
                                    echo '<select id="' . $row['Field'] . '" name="' . $row['Field'] . '" class="form-control" required>';
                                    $categories = ['Advance Payment', 'Salary Payment', 'Other'];
                                    foreach ($categories as $category) {
                                        echo '<option value="' . htmlspecialchars($category) . '">' . htmlspecialchars($category) . '</option>';
                                    }
                                    echo '</select>';
                                } elseif (strpos($row['Type'], 'date') !== false) {
                                    // Date picker
                                    echo '<div class="datetime">';
                                    echo '<input type="text" id="' . $row['Field'] . '" name="' . $row['Field'] . '" class="form-control date-picker" required>';
                                    echo '<span class="calendar-icon"><i class="fa fa-calendar"></i></span>';
                                    echo '</div>';
                            }elseif (strpos($row['Type'], 'blob') !== false) {
                                // File upload field
                                echo '<input type="file" id="' . $row['Field'] . '" name="' . $row['Field'] . '" class="form-control">';
                            }elseif (strpos($row['Type'], 'time') !== false) {
                                    // Time picker
                                    echo '<div class="datetime">';
                                    echo '<input type="text" id="' . $row['Field'] . '" name="' . $row['Field'] . '" class="form-control time-picker" required>';
                                    echo '<span class="clock-icon"><i class="fa fa-clock"></i></span>';
                                    echo '</div>';
                                } elseif (strpos($row['Type'], 'blob') !== false) {
                                    // File upload field
                                    echo '<input type="file" id="' . $row['Field'] . '" name="' . $row['Field'] . '" class="form-control">';
                                } elseif (strpos($row['Type'], 'tinyint(1)') !== false) {
                                    // Boolean field (Yes/No buttons)
                                    echo '<div>';
                                    echo '<input type="hidden" id="' . $row['Field'] . '" name="' . $row['Field'] . '" value="0">'; // Default to false
                                    echo '<button type="button" class="btn btn-outline-success mr-2" onclick="setBooleanValue(\'' . $row['Field'] . '\', true)">Yes</button>';
                                    echo '<button type="button" class="btn btn-outline-danger" onclick="setBooleanValue(\'' . $row['Field'] . '\', false)">No</button>';
                                    echo '</div>';
                                } elseif ($table == 'Attendance' && $row['Field'] == 'Presence') {
                                    // Special handling for Presence field in Attendance table
                                    echo '<div>';
                                    echo '<input type="hidden" id="' . $row['Field'] . '" name="' . $row['Field'] . '" value="">';
                                    echo '<button type="button" class="btn btn-full-day mr-2" onclick="setPresenceValue(\'' . $row['Field'] . '\', 1.0)">Full Day</button>';
                                    echo '<button type="button" class="btn btn-half-day mr-2" onclick="setPresenceValue(\'' . $row['Field'] . '\', 0.5)">Half Day</button>';
                                    echo '<button type="button" class="btn btn-not-attended" onclick="setPresenceValue(\'' . $row['Field'] . '\', 0.0)">Not attended</button>';
                                    echo '</div>';
                                } else {
                                    // Default text input
                                    echo '<input type="text" id="' . $row['Field'] . '" name="' . $row['Field'] . '" class="form-control" required>';
                                }
                                echo '</div>';
                            }
                        } else {
                            echo "Error fetching table structure.";
                        }

                        // Close connection
                        $conn->close();
                        ?>

            <button type="button" class="btn cancel-btn btn-block mt-3" onclick="window.history.back()">Cancel</button>
            <button type="submit" class="btn btn-primary btn-block mt-3">Submit</button>
        </form>
    </div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2 for Job_ID
        $('.job-select').select2({
            data: <?php echo $jobData; ?>, // Pass the job data from PHP
            placeholder: 'Select Job ID',
            allowClear: true
        });

        // Initialize Flatpickr
        $('.date-picker').flatpickr({
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "F j, Y"
        });
        $('.time-picker').flatpickr({
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            altFormat: "h:i K"
        });

        // Initialize Select2 for employee dropdowns
        $('.employee-select').select2(); 
    });

    // Presence button logic
    function setPresenceValue(field, value) {
        document.getElementById(field).value = value;
    }

    // Boolean button logic
    function setBooleanValue(field, value) {
        document.getElementById(field).value = value ? 1 : 0;
    }
</script>
</body>
</html>