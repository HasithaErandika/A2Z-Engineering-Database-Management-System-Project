<?php
session_start();

// Check if the user is logged in and a job_id is provided
if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname']) || !isset($_GET['job_id'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];
$job_id = $_GET['job_id'];
$table = 'Invoice_Data'; 

// Function to get the primary key of a table
function getPrimaryKey($conn, $table) {
    $primaryKeyResult = $conn->query("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'");
    if ($primaryKeyResult->num_rows > 0) {
        $primaryKeyRow = $primaryKeyResult->fetch_assoc();
        return $primaryKeyRow['Column_name'];
    } else {
        return null; 
    }
}

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch invoice data
$sql = "SELECT * FROM Invoice_Data WHERE Job_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $job_id); 
$stmt->execute();
$result = $stmt->get_result();

// Get the primary key 
$primaryKey = getPrimaryKey($conn, $table);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Invoice Details</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"> 
    
    <style>
.invoice-container {
  padding: 2rem;
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

.invoice-container h2 {
  font-size: 1.8rem;
  color: #343a40;
  margin-bottom: 1.5rem; 
}

.invoice-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 2rem;
}

.invoice-table th,
.invoice-table td {
  padding: 0.75rem;
  border: 1px solid #dee2e6;
  text-align: left; 
}

.invoice-table th {
  background-color: #f8f9fa; /* Light gray header */
  font-weight: 600;
}

.invoice-table .green { 
  color: #28a745; /* Green checkmark */
}

.invoice-table .red {
  color: #dc3545; /* Red cross mark */
}

.btn-secondary {
  background-color: #6c757d;
  color: #fff;
  border: none;
  padding: 0.5rem 1rem; 
  border-radius: 0.25rem;
  text-decoration: none; 
  transition: background-color 0.3s ease; /* Smooth transition on hover */
}

.btn-secondary:hover {
  background-color: #5a6268; /* Slightly darker shade on hover */
}
    </style>
</head>
<body>

<div class="container invoice-container mt-5">
    <h2>Invoice Details for Job ID: <?php echo htmlspecialchars($job_id); ?></h2>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered invoice-table">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Invoice Date</th>
                    <th>Invoice Value</th>
                    <th>Invoice</th> 
                    <th>Receiving Payment</th>
                    <?php 
                        // Conditionally add "Received Amount" and "Payment Received Date" columns to header
                        $firstRow = $result->fetch_assoc(); 
                        $result->data_seek(0); 
                        if ($firstRow['Receiving_Payment'] == 1): 
                    ?>
                        <th>Received Amount</th>
                        <th>Payment Received Date</th>
                    <?php endif; ?>
                    <th>Remarks</th>
                    <th>Actions</th> 
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['Invoice_No']); ?></td>
                        <td><?php echo htmlspecialchars($row['Invoice_Date']); ?></td>
                        <td><?php echo htmlspecialchars($row['Invoice_Value']); ?></td>
                        <td>
                            <?php if (!empty($row['Invoice'])): ?>
                                <a href="view_attachment.php?table=Invoice_Data&column=Invoice&id=<?php echo $row['Invoice_ID']; ?>" class="btn btn-primary btn-sm">View</a>
                            <?php else: ?>
                                <button class="btn btn-yet-to-add btn-sm" disabled>Yet to Add</button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if ($row['Receiving_Payment'] == 1) {
                                echo '<i class="fas fa-check green"></i>';
                            } else {
                                echo '<i class="fas fa-times red"></i>';
                            }
                            ?>
                        </td>
                        <?php 
                            // Conditionally display the "Received Amount" and "Payment Received Date" cells 
                            if ($row['Receiving_Payment'] == 1): 
                        ?>
                            <td><?php echo htmlspecialchars($row['Received_amount']); ?></td>
                            <td><?php echo htmlspecialchars($row['Payment_Received_Date']); ?></td>
                        <?php endif; ?> 
                        <td><?php echo htmlspecialchars($row['Remarks']); ?></td>
                        <td>
                            <a href="update_entry.php?table=<?php echo $table; ?>&id=<?php echo $row[$primaryKey]; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="delete_entry.php?table=<?php echo $table; ?>&id=<?php echo $row[$primaryKey]; ?>" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i></a>
                        </td> 
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No invoices found for this Job ID.</p>
    <?php endif; ?>

    <a href="manage_table.php?table=Jobs" class="btn btn-secondary mt-3">Back to Jobs</a>
</div>

</body>
</html>

<?php
$stmt->close(); 
$conn->close(); 
?>