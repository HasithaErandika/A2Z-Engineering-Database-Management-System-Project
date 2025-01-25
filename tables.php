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

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
           color: #ffffff; /* Default text color to white*/
        }

        .container {
            padding-top: 50px;
            padding-bottom: 50px;
        }

        .topic-container {
            padding: 15px;
            margin: 10px;
            background-color: #a888f7; /* Dark purple background */
            border-radius: 8px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            color: #ffffff; /* White text */
        }

       .topic-container h2 {
           text-align: center;
           font-size: 2.2em;
           font-weight: 500;
           color: #ffffff; /* White text for heading*/
           margin-bottom: 30px;
        }

        .main-content {
            background-color: #ede7f6;  /* Light grayish purple */
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
             padding: 25px;
              color: #333;
        }


        .card {
            background-color: #e3f2fd; /* light blue */
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            color: #333; /* text color to a darker gray */
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
             background-color: #d1c4e9; /* Slightly grayish purple on hover */
        }

        .card a {
            text-decoration: none;
             color: #3498db; /* Blue for links */
             font-size: 1.1em;
            font-weight: 500;
            display: block;
           padding: 10px 0;
        }

        .card i {
            font-size: 2.5em;
            margin-bottom: 10px;
            display: block;
             color: #777; /* Muted color for icons */
        }
          .card a:hover {
           color: #2980b9;
        }


    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="container">
        <div class="main-content">
            <div class="section-header">
                <div class="topic-container">
                    <h2>Operational Data</h2>
                </div>
                
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                         <a href="manage_table.php?table=Attendance">
                             <i class="fas fa-calendar-check"></i>
                             Attendance
                         </a>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <a href="manage_table.php?table=Employee">
                            <i class="fas fa-user-tie"></i>
                           Employee
                        </a>
                     </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <a href="manage_table.php?table=Employee_Bank_Details">
                            <i class="fa fa-university"></i>
                            Employee Bank Details
                        </a>
                     </div>
                </div>
                <div class="col-md-3">
                     <div class="card">
                       <a href="manage_table.php?table=Projects">
                         <i class="fas fa-project-diagram"></i>
                         Projects
                        </a>
                     </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <a href="manage_table.php?table=Jobs">
                            <i class="fas fa-briefcase"></i>
                           Jobs
                        </a>
                     </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <a href="manage_table.php?table=Operational_Expenses">
                            <i class="fas fa-receipt"></i>
                            Operational Expenses
                        </a>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <a href="manage_table.php?table=Invoice_Data">
                            <i class="fas fa-file-invoice"></i>
                            Invoice Data
                        </a>
                    </div>
                </div>
                 <div class="col-md-3">
                    <div class="card">
                       <a href="manage_table.php?table=Employee_Payments">
                        <i class="fas fa-money-check-alt"></i>
                         Employee Payments
                       </a>
                    </div>
                 </div>
                <div class="col-md-3">
                    <div class="card">
                        <a href="manage_table.php?table=Salary_Increments">
                            <i class="fas fa-money-check-alt"></i>
                            Salary Increments
                        </a>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <a href="manage_table.php?table=Material">
                           <i class="fa fa-list"></i>
                          Material List
                        </a>
                    </div>
                 </div>
                 <div class="col-md-3">
                   <div class="card">
                       <a href="manage_table.php?table=Material_List_Per_Site">
                           <i class="fa fa-list"></i>
                         Material List Per Site
                       </a>
                    </div>
                 </div>
             </div>
         </div>


        <div class="main-content mt-5">
            <div class="section-header">
                <div class="topic-container">
                    <h2>Reports</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                       <a href="wages_report.php">
                          <i class="fas fa-money-bill"></i>
                           Monthly Wages
                        </a>
                     </div>
                </div>
                 <div class="col-md-3">
                     <div class="card">
                         <a href="expenses_report.php">
                            <i class="fas fa-file-invoice-dollar"></i>
                            Expenses Report
                         </a>
                      </div>
                </div>
                 <div class="col-md-3">
                   <div class="card">
                         <a href="cost_calculation.php?table=Jobs">
                             <i class="fas fa-chart-pie"></i>
                             Site Cost Calculation
                        </a>
                    </div>
                </div>
                 <div class="col-md-3">
                      <div class="card">
                            <a href="material_find.php">
                                <i class="fa fa-cogs"></i>
                                Material Cost Calculation
                            </a>
                       </div>
                </div>
                 <div class="col-md-3">
                      <div class="card">
                            <a href="a2z_engineering_jobs.php">
                                <i class="fa fa-cogs"></i>
                                A2Z Engineering Jobs
                             </a>
                       </div>
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
