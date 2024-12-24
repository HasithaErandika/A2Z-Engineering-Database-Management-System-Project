<?php
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search_query = "";
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
}

$sql = "SELECT e.Emp_id, e.name, e.department, e.position, e.salary, b.bank_name, b.account_number 
        FROM Employee e
        LEFT JOIN EmployeeBankDetails b ON e.Emp_id = b.Emp_id
        WHERE e.name LIKE '%$search_query%' OR e.department LIKE '%$search_query%'";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - A2Z ENGINEERING</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="d-flex justify-content-between align-items-center p-3 bg-maroon text-white">
        <div class="logo">A2Z ENGINEERING</div>
        <a href="logout.php" class="btn btn-dark">Logout</a>
    </header>
    <div class="container mt-5">
        <h2>Employee Management</h2>
        <div class="d-flex justify-content-between mb-3">
            <form class="form-inline" method="get" action="">
                <input class="form-control mr-sm-2" type="text" name="search" placeholder="Search">
                <button class="btn btn-outline-success" type="submit">Search</button>
            </form>
            <a href="add_employee.php" class="btn btn-primary">Add Entry</a>
        </div>
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Salary</th>
                    <th>Bank Name</th>
                    <th>Account Number</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['Emp_id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['department']; ?></td>
                            <td><?php echo $row['position']; ?></td>
                            <td><?php echo $row['salary']; ?></td>
                            <td><?php echo $row['bank_name']; ?></td>
                            <td><?php echo $row['account_number']; ?></td>
                            <td>
                                <a href="edit_employee.php?id=<?php echo $row['Emp_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_employee.php?id=<?php echo $row['Emp_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this entry?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No results found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php $conn->close(); ?>
