<?php
session_start();

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// Check if session variables are set
if (!isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname'])) {
    header("Location: index.php");
    exit();
}

$servername = "localhost";
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$dbname = $_SESSION['dbname'];

$conn = new mysqli($servername, $username, $password, $dbname);

ini_set('display_errors', 1);
error_reporting(E_ALL);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Types for the first dropdown
$typeQuery = "SELECT DISTINCT Type FROM Material";
$typeResult = $conn->query($typeQuery);
$types = [];
if ($typeResult->num_rows > 0) {
    while ($row = $typeResult->fetch_assoc()) {
        $types[] = $row['Type'];
    }
}

// Fetch Job_ID, Customer_ref, and Location from the Jobs table
$jobQuery = "SELECT Job_ID, Customer_ref, Location FROM Jobs";
$jobResult = $conn->query($jobQuery);
$jobDetails = [];
if ($jobResult->num_rows > 0) {
    while ($row = $jobResult->fetch_assoc()) {
        // Store the details in an array
        $jobDetails[] = $row;
    }
}


?>

// Check if the POST data exists
if (isset($_POST['jobID']) && isset($_POST['totalSiteCost'])) {
    // Escape user inputs to prevent SQL injection
    $jobID = $conn->real_escape_string($_POST['jobID']);
    $totalSiteCost = $conn->real_escape_string($_POST['totalSiteCost']);

    // Prepare the SQL query to insert data into Material_List_Per_Site table
    $stmt = $conn->prepare("INSERT INTO Material_List_Per_Site (Job_ID, Total_Site_Cost) VALUES (?, ?)");

    // Check if the preparation of the statement was successful
    if ($stmt === false) {
        echo "Error in preparing statement: " . $conn->error;
        exit;
    }

    // Bind the parameters: 'i' for integer (Job_ID), 'd' for double (Total_Site_Cost)
    $stmt->bind_param("id", $jobID, $totalSiteCost);

    // Check if the binding was successful
    if ($stmt->errno) {
        echo "Error binding parameters: " . $stmt->error;
        exit;
    }

    // Execute the query
    if ($stmt->execute()) {
        echo 'success'; // Respond with success if data was inserted
    } else {
        // Output any error from the execution
        echo 'Error executing query: ' . $stmt->error;
    }

    // Close the statement
    $stmt->close();
} else {
    echo 'error'; // Respond with error if required POST data is not found
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Selection</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
    /* General Styles */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f9f9f9;
    color: #333;
    margin: 0;
    padding: 0;
}

.container {
    margin-top: 30px;
}
.container-fluid {
            margin-top: 30px;
        }

        .d-flex {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
        }

/* Card Styles */
.card {
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    background-color: #fff;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.card-body {
    padding: 20px;
}

.card-footer {
    background-color: #f1f1f1;
    border-top: 1px solid #ddd;
    padding: 10px;
    font-size: 0.9rem;
    color: #777;
}

/* Heading Styles */
h2 {
    font-size: 1.8rem;
    color: #5A5A5A;
    font-weight: bold;
}

/* Button Styles */
.btn {
    border-radius: 20px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: bold;
}

.btn-primary {
    background-color: #007bff;
    border: none;
    color: #fff;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-success {
    background-color: #28a745;
    border: none;
    color: #fff;
}

.btn-success:hover {
    background-color: #218838;
}

.btn-info {
    background-color: #17a2b8;
    border: none;
    color: #fff;
}

.btn-info:hover {
    background-color: #117a8b;
}

.btn-secondary {
    background-color: #6c757d;
    border: none;
    color: #fff;
}

.btn-secondary:hover {
    background-color: #5a6268;
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

/* Table Styles */
.table {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
}

.table-bordered {
    border: 1px solid #ddd;
}

.table th, .table td {
    padding: 12px 15px;
    text-align: center;
    vertical-align: middle;
}

.table th {
    background-color: #007bff;
    color: #fff;
    font-weight: bold;
}

.table tbody tr:nth-child(odd) {
    background-color: #f9f9f9;
}

.table tbody tr:nth-child(even) {
    background-color: #fff;
}

.table .btn {
    padding: 5px 10px;
    border-radius: 50%;
    font-size: 16px;
}

/* Input Fields */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: bold;
}

.form-control {
    border-radius: 10px;
    padding: 5px 15px;
    font-size: 14px;
    border: 1px solid #ccc;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
}

/* Alert Styles */
.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-color: #bee5eb;
    padding: 15px;
    border-radius: 5px;
}

.alert-info h5 {
    font-size: 1.1rem;
    font-weight: bold;
}

/* Advanced Calculation Summary */
#advanceTotalTable th,
#advanceTotalTable td {
    padding: 10px;
    text-align: center;
    font-weight: bold;
}

#advanceTotalTable tbody tr td {
    color: #555;
}

#advanceTotalTable tbody tr td:last-child {
    font-weight: bold;
}

#advanceTotalTable tbody tr td:first-child {
    font-weight: bold;
}

/* Add to Material List Button */
#addToMaterialList {
    margin-top: 10px;
}

/* Job ID and Total Site Cost Fields */
#jobID,
#totalSiteCost {
    margin-top: 10px;
}

/* Footer */
footer {
    text-align: center;
    padding: 15px;
    background-color: #f1f1f1;
    margin-top: 30px;
    font-size: 0.9rem;
}

/* Tooltip */
.tooltip-inner {
    background-color: #17a2b8;
    color: white;
}

    </style>
</head>
<body>
    
<div class="container mt-4">
    <div class="container-fluid mt-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Site Material Selection</h2>
                <a href="tables.php" class="btn btn-goback">Go Back</a>
        </div>
            
        </div>
    
    <div class="card mb-4">
            <div class="card-body">
    <form id="materialForm">
        <!-- Customer Details -->
        <div class="form-group">
            <label for="customerName">Customer Name</label>
            <input type="text" id="customerName" name="customerName" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" class="form-control" rows="3" required></textarea>
        </div>

        <div class="form-group">
            <label for="date">Date</label>
            <input type="date" id="date" name="date" class="form-control" required>
        </div>

        <!-- Material Selection -->
        <div id="materialSelection">
            <div class="form-group">
                <label for="type">Select Type</label>
                <select id="type" name="type" class="form-control">
                    <option value="">Select Type</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="item">Select Item</label>
                <select id="item" name="item" class="form-control">
                    <option value="">Select Item</option>
                </select>
            </div>

            <div class="form-group">
                <label for="modelOrSize">Select Model/Size</label>
                <select id="modelOrSize" name="modelOrSize" class="form-control">
                    <option value="">Select Model/Size</option>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Enter Quantity</label>
                <input type="number" id="quantity" name="quantity[]" class="form-control" min="1">
            </div>

            <button type="button" id="addItem" class="btn btn-primary">Add Item</button>
        </div>
        </div>
        </div>

        <!-- Summary -->
        <h4 class="mt-4">Selected Materials</h4>
        <div class="card mb-4">
            <div class="card-body">
        <table class="table table-bordered" id="summaryTable">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Item</th>
                    <th>Model/Size</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Rows will be dynamically added here -->
            </tbody>
        </table>

        <!-- Advance Calculation Button -->
        <div class="form-group">
            <button type="button" id="advanceCalculation" class="btn btn-info">Advance Calculation</button>
        </div>
        </div>
        </div>

        <!-- Advance Calculation Summary -->
        <h4 class="mt-4">Advance Calculation Summary</h4>
        <div class="card mb-4">
            <div class="card-body">
        <div class="alert alert-info">
            <h5>Profit Margins</h5>
            <ul>
                <li><b>Materials :</b> 10%</li>
                <li><b>Other Payments :</b> 10%</li>
                <li><b>Transport :</b> 20%</li>
                <li><b>Labor :</b> 30%</li>
            </ul>
            <h5>Variations</h5>
            <ul>
                <li><b>Total System Variations :</b> 5%</li>
            </ul>
        </div>
        <table class="table table-bordered" id="advanceTotalTable">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Total</th>
                    <th>Profit</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Materials</td>
                    <td id="materialsTotal">0.00</td>
                    <td id="materialsProfit">0.00</td>
                </tr>
                <tr>
                    <td>Other Payments</td>
                    <td id="otherPaymentsTotal">0.00</td>
                    <td id="otherPaymentsProfit">0.00</td>
                </tr>
                <tr>
                    <td>Transport</td>
                    <td id="transportTotal">0.00</td>
                    <td id="transportProfit">0.00</td>
                </tr>
                <tr>
                    <td>Labor</td>
                    <td id="laborTotal">0.00</td>
                    <td id="laborProfit">0.00</td>
                </tr>
                <tr>
                    <td>Raw Cost</td>
                    <td colspan="2"></td>
                    <td id="rawCost">0.00</td>
                </tr>
                <tr>
                    <td>Total Profit</td>
                    <td colspan="2"></td>
                    <td id="totalProfit">0.00</td>
                </tr>
                <tr>
                    <td>Price After Profit</td>
                    <td colspan="2"></td>
                    <td id="priceAfterProfit">0.00</td>
                </tr>
                <tr>
                    <td>Variations (5%)</td>
                    <td colspan="2"></td>
                    <td id="variations">0.00</td>
                </tr>
                <tr>
                    <td><strong>Total System Price</strong></td>
                    <td colspan="2"></td>
                    <td id="totalSystemPrice">0.00</td>
                </tr>
            </tbody>
        </table>
        
       <!-- Job ID Selection -->
<div class="form-group">
    <label for="jobID">Job ID</label>
    <select id="jobID" name="jobID" class="form-control" required>
        <option value="">Select Job ID</option>
        <?php foreach ($jobDetails as $job): ?>
            <option value="<?php echo $job['Job_ID']; ?>">
                <?php echo $job['Job_ID']; ?> - <?php echo $job['Customer_ref']; ?> - <?php echo $job['Location']; ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>



<div class="form-group">
    <label for="totalSiteCost">Total Site Cost</label>
    <input type="number" id="totalSiteCost" name="totalSiteCost" class="form-control" required>
</div>

<div class="form-group">
    <button type="button" id="addToMaterialList" class="btn btn-secondary">Add to Material List</button>
</div>


        <!-- Submit Button -->
        <div class="form-group">
            <button type="submit" class="btn btn-success">Submit</button>
        </div>
        </div>
        </div>
        

    </form>
</div>

<script>
$(document).ready(function () {
    // Fetch items based on selected type
    $('#type').on('change', function () {
        let selectedType = $(this).val();
        if (selectedType) {
            $.ajax({
                url: 'get_items.php',
                type: 'POST',
                data: { type: selectedType },
                success: function (data) {
                    $('#item').html(data);
                    $('#modelOrSize').html('<option value="">Select Model/Size</option>');
                },
                error: function () {
                    alert('Error fetching items.');
                }
            });
        } else {
            $('#item').html('<option value="">Select Item</option>');
            $('#modelOrSize').html('<option value="">Select Model/Size</option>');
        }
    });

    // Fetch model/size based on selected item and type
    $('#item').on('change', function () {
        let selectedItem = $(this).val();
        let selectedType = $('#type').val();
        if (selectedItem && selectedType) {
            $.ajax({
                url: 'get_models_sizes.php',
                type: 'POST',
                data: { item: selectedItem, type: selectedType },
                success: function (data) {
                    $('#modelOrSize').html(data);
                },
                error: function () {
                    alert('Error fetching models/sizes.');
                }
            });
        } else {
            $('#modelOrSize').html('<option value="">Select Model/Size</option>');
        }
    });

    // Add selected item to summary table
        $('#addItem').on('click', function () {
            let type = $('#type').val();
            let item = $('#item').val();
            let modelOrSize = $('#modelOrSize').val();
            let quantity = $('#quantity').val();

            if (!type || !item || !modelOrSize || !quantity || quantity <= 0) {
                alert('Please fill all fields and ensure quantity is greater than 0.');
                return;
            }

            $.ajax({
                url: 'get_item_details.php',
                type: 'POST',
                data: { item: item, modelOrSize: modelOrSize },
                success: function (data) {
                    try {
                        let itemDetails = JSON.parse(data);
                        let total = itemDetails.Item_Price * quantity;

                        let row = `
                            <tr>
                                <td>${type}</td>
                                <td>${item}</td>
                                <td>${modelOrSize}</td>
                                <td>${itemDetails.Item_Price}</td>
                                <td>${quantity}</td>
                                <td>${total}</td>
                                <td>
                                    <button class="btn btn-sm btn-warning editBtn"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-danger deleteBtn"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>`;
                        $('#summaryTable tbody').append(row);

                        $('#type').val('');
                        $('#item').html('<option value="">Select Item</option>');
                        $('#modelOrSize').html('<option value="">Select Model/Size</option>');
                        $('#quantity').val('');
                    } catch (error) {
                        console.error('Error parsing item details:', error);
                        alert('Invalid data received from the server.');
                    }
                },
                error: function () {
                    alert('Error fetching item details.');
                }
            });
        });

    // Remove an item from the summary table
    $(document).on('click', '.remove-item', function () {
        $(this).closest('tr').remove();
        calculateGrandTotal();
    });

    // Calculate grand total after adding/removing items
    function calculateGrandTotal() {
        let grandTotal = 0;
        $('#summaryTable tbody tr').each(function () {
            let total = parseFloat($(this).find('td:nth-child(6)').text());
            if (!isNaN(total)) {
                grandTotal += total;
            }
        });
        $('#grandTotal').text(grandTotal.toFixed(2));
    }

// Calculate advance summary
$('#advanceCalculation').on('click', function () {
    let materialsSum = 0, otherPaymentsSum = 0, transportSum = 0, laborSum = 0;

    $('#summaryTable tbody tr').each(function () {
        let type = $(this).find('td:nth-child(1)').text().trim();
        let total = parseFloat($(this).find('td:nth-child(6)').text());

        if (!isNaN(total)) {
            // Categorize by Material Type
            if (['Inverters', 'Panel Mounting Accessories', 'Water Proofing Materials', 'PVC', 'Materials', 'Cables and Wiring Accessories', 'DC SPD', 'AC SPD', 'MCB', 'DC Isolator', 'AC Lockable Isolator', 'HRC Fuse', 'Solar Panels'].includes(type)) {
                materialsSum += total;
            }
            // Categorize Other Payments
            else if (['Professional Charges', 'Documentation', 'Meter Change', 'Warranty', 'Survey and Maintenance'].includes(type)) {
                otherPaymentsSum += total;
            }
            // Categorize Transport
            else if (type === 'Transport') {
                transportSum += total;
            }
            // Categorize Labor
            else if (type === 'Labor Charges') {
                laborSum += total;
            }
        }
    });

    // Calculate Profit for each category
    let materialsProfit = materialsSum * 0.10; // 10% profit for Materials
    let otherPaymentsProfit = otherPaymentsSum * 0.10; // 10% profit for Other Payments
    let transportProfit = transportSum * 0.20; // 20% profit for Transport
    let laborProfit = laborSum * 0.30; // 30% profit for Labor

    // Calculate the sum of all profits
    let totalProfit = materialsProfit + otherPaymentsProfit + transportProfit + laborProfit;

    // Raw Cost (sum of all categories without profit)
    let rawCost = materialsSum + otherPaymentsSum + transportSum + laborSum;

    // Price after profit (Raw Cost + Total Profit)
    let priceAfterProfit = rawCost + totalProfit;

    // Calculate Variations (5% of Price after Profit)
    let variations = priceAfterProfit * 0.05;

    // Total System Price = Price after Profit + Variations
    let totalSystemPrice = priceAfterProfit + variations;

    // Display the results in the Advanced Calculation Summary Table
    $('#materialsTotal').text(materialsSum.toFixed(2));
    $('#otherPaymentsTotal').text(otherPaymentsSum.toFixed(2));
    $('#transportTotal').text(transportSum.toFixed(2));
    $('#laborTotal').text(laborSum.toFixed(2));

    $('#materialsProfit').text(materialsProfit.toFixed(2));
    $('#otherPaymentsProfit').text(otherPaymentsProfit.toFixed(2));
    $('#transportProfit').text(transportProfit.toFixed(2));
    $('#laborProfit').text(laborProfit.toFixed(2));

    $('#totalProfit').text(totalProfit.toFixed(2)); // Total Profit Value

    $('#rawCost').text(rawCost.toFixed(2)); // Raw Cost Value (this will be shown in the summary table)

    $('#priceAfterProfit').text(priceAfterProfit.toFixed(2));
    $('#variations').text(variations.toFixed(2));
    $('#totalSystemPrice').text(totalSystemPrice.toFixed(2));

    // Update the Total Site Cost field with Raw Cost value
    $('#totalSiteCost').val(rawCost.toFixed(2)); // This is where you update Total Site Cost with Raw Cost
});



    // Form submission (you can handle form data if needed)
    $('#materialForm').on('submit', function (e) {
        e.preventDefault();
        alert('Form submitted');
    });

// Collect selected materials and serialize them
$('#materialForm').on('submit', function () {
        const materials = [];
        $('#summaryTable tbody tr').each(function () {
            materials.push({
                type: $(this).find('td:nth-child(1)').text(),
                item: $(this).find('td:nth-child(2)').text(),
                model: $(this).find('td:nth-child(3)').text(),
                quantity: $(this).find('td:nth-child(5)').text(),
                price: $(this).find('td:nth-child(4)').text(),
            });
        });
        $('#materialsData').val(JSON.stringify(materials));
    });

});

$(document).ready(function () {
    // Handle the insert to Material_List_Per_Site table
    $('#addToMaterialList').on('click', function () {
        // Get Job ID and Total Site Cost values
        var jobID = $('#jobID').val();
        var totalSiteCost = $('#totalSiteCost').val();

        // Ensure both fields are filled and Job_ID is a valid integer
        if (!jobID || !totalSiteCost || isNaN(jobID) || jobID <= 0 || totalSiteCost <= 0) {
            alert('Please fill out both fields with valid Job ID (integer) and Total Site Cost (positive number).');
            return;
        }

        // Log the data being sent to ensure it's correct
        console.log('Sending data:', { jobID: jobID, totalSiteCost: totalSiteCost });

        // Send data to the server to insert into the Material_List_Per_Site table
        $.ajax({
            url: 'add_to_material_list.php',  // PHP script to handle the insert
            type: 'POST',
            data: {
                jobID: jobID,
                totalSiteCost: totalSiteCost
            },
            success: function (response) {
                console.log('Response from server:', response);
                if (response === 'success') {
                    alert('Data successfully added to Material List!');
                    // Optionally clear the input fields after successful insertion
                    $('#jobID').val('');
                    $('#totalSiteCost').val('');
                } else {
                    alert('Failed to add data. Error: ' + response);
                }
            },
            error: function () {
                alert('An error occurred while adding data.');
            }
        });
    });
});




</script>
</body>
</html>
