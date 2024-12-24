<?php
// session_check.php
session_start();
session_regenerate_id(true); 

if (!isset($_SESSION['username']) || empty($_SESSION['username']) || 
    !isset($_SESSION['dbname']) || empty($_SESSION['dbname'])) {
    header("Location: index.php");
    exit();
} 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operations</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <!--<link href="styles.css" rel="stylesheet">-->
    <style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f4f7fc;
        margin: 0;
        padding: 0;
    }

    .container {
        padding-top: 50px;
    }

    .main-content {
        padding: 20px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .section-header h2 {
        text-align: center;
        font-size: 28px;
        color: #333;
        margin-bottom: 30px;
        font-weight: bold;
    }

    .btn-container {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }

    .btn-custom {
        display: block;
        width: 100%;
        padding: 20px;
        background-color: #6c63ff;
        color: white;
        text-align: center;
        border-radius: 8px;
        font-size: 18px;
        font-weight: bold;
        text-decoration: none;
        transition: background-color 0.3s ease, transform 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-custom i {
        margin-bottom: 10px;
        font-size: 24px;
    }

    .btn-custom:hover {
        background-color: #5a52d1;
        transform: translateY(-5px);
    }

    .btn-custom:active {
        background-color: #4f47c0;
    }

    .row {
        margin-top: 20px;
    }

    @media (max-width: 767px) {
        .col-md-3 {
            flex: 0 0 48%;
            max-width: 48%;
            margin-bottom: 15px;
        }

        .btn-custom {
            font-size: 16px;
            padding: 15px;
        }
    }

    @media (max-width: 576px) {
        .col-md-3 {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .btn-custom {
            font-size: 14px;
            padding: 10px;
        }
    }
</style>

</head>

<body>
    <?php include 'header.php'; ?> <!-- Include header -->

    <div class="container">
        <div class="main-content">
            <div class="section-header">
                <h2>Operational Data</h2>
            </div>
            <div class="row">
                <div class="col-md-3 btn-container">
                    <a href="manage_table.php?table=Attendance" class="btn-custom">
                        <i class="fas fa-calendar-check"></i> <br> Attendance
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="manage_table.php?table=Employee" class="btn-custom">
                        <i class="fas fa-user-tie"></i> <br> Employee
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="manage_table.php?table=Employee_Bank_Details" class="btn-custom">
                        <i class="fa fa-university"></i> <br> Employee Bank Details
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="manage_table.php?table=Projects" class="btn-custom">
                        <i class="fas fa-project-diagram"></i> <br> Projects
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="manage_table.php?table=Jobs" class="btn-custom">
                        <i class="fas fa-briefcase"></i> <br> Jobs
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="manage_table.php?table=Operational_Expenses" class="btn-custom">
                        <i class="fas fa-receipt"></i> <br> Operational Expenses
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="manage_table.php?table=Invoice_Data" class="btn-custom">
                        <i class="fas fa-file-invoice"></i> <br> Invoice Data
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="manage_table.php?table=Employee_Payments" class="btn-custom">
                        <i class="fas fa-money-check-alt"></i> <br> Employee Payments
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="manage_table.php?table=Salary_Increments" class="btn-custom">
                        <i class="fas fa-money-check-alt"></i> <br> Salary Increments
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="manage_table.php?table=Material" class="btn-custom">
                        <i class="fa fa-list"></i> <br> Material List
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="manage_table.php?table=Material_List_Per_Site" class="btn-custom">
                        <i class="fa fa-list"></i> <br> Material List Per Site
                    </a>
                </div>
            </div>
        </div>

        <div class="main-content mt-5">
            <div class="section-header">
                <h2>Reports</h2>
            </div>
            <div class="row">
                <div class="col-md-3 btn-container">
                    <a href="wages_report.php" class="btn-custom">
                        <i class="fas fa-money-bill"></i> <br> Monthly Wages
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="expenses_report.php" class="btn-custom">
                        <i class="fas fa-file-invoice-dollar"></i> <br> Expenses Report
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="cost_calculation.php?table=Jobs" class="btn-custom">
                        <i class="fas fa-chart-pie"></i> <br> Site Cost Calculation
                    </a>
                </div>
                <div class="col-md-3 btn-container">
                    <a href="material_find.php" class="btn-custom">
                        <i class="fa fa-cogs"></i> <br> Material Cost Calculation
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?> <!-- Include footer -->

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>

