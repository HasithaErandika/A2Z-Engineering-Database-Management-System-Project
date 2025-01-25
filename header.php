<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A2Z Engineering - <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Operations'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          integrity="sha512-iecdLmaskl7CVkqk1w27APbCZZp+trN3v8TpgAm16FB46Z+9xjbBJCGSdOdQoNLwOp8aAgBxSsQfjJxFoq6+A=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" href="styles.css">
    <style>
        .header {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
             padding: 1rem 0;
              z-index: 1000;
        }

        .container {
             max-width: 1200px;
             padding: 0 20px; /* Added padding to container for small screens*/
             margin: 0 auto;

        }
        .header .logo {
            max-height: 60px;
            transition: transform 0.3s ease;
             border-radius: 5px;

        }

        .header .logo:hover {
           transform: scale(1.05);
        }

        .header .btn {
          padding: .5rem 1rem; /* Spacing for buttons */
          font-size: 1rem;

        }

        .header .btn-danger {
            background-color: #e74c3c;
            border-color: #c0392b;
             transition: background-color 0.3s ease;
        }

        .header .btn-danger:hover{
            background-color:#c0392b;
        }


      .header .btn-website_visiting {
           background-color: #3498db;
            border-color: #2980b9;
             color: #fff;
            transition: background-color 0.3s ease;
              position: absolute; /* Position the button */
            top: 50%; /* Center vertically */
            right: 20px; /* Position at right edge*/
            transform: translateY(-50%);/* Center vertically */
        }
        .header .btn-website_visiting:hover {
             background-color: #2980b9;

        }



         @media (max-width: 768px) {
          .header .container {
             padding: 0 15px;
          }

            .header .logo {
                max-height: 40px;
            }
            .header .btn {
              font-size: .9rem;
              padding: .3rem .7rem;
            }

        }



    </style>
</head>
<body>
    <header class="header fixed-top">
        <div class="container">
            <div class="row justify-content-between align-items-center">
                <div class="col-auto">
                    <a href="tables.php">
                      <img src="logo_black.jpg" alt="Logo" class="logo">
                    </a>
                </div>
                <div class="col-auto">
                    <?php if(isset($_SESSION['username'])): ?>
                        <a href="logout.php" class="btn btn-danger me-2">Logout</a>
                    <?php else: ?>
                        <a href="https://a2zengineering.lk/" class="btn btn-website_visiting" target="_blank">Visit Our Website</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
