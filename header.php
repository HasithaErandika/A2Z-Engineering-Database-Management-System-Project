<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A2Z Engineering - <?php echo isset($pageTitle) ? $pageTitle : 'Operations'; ?></title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqk1w27APbCZZp+trN3v8TpgAm16FB46Z+9xjbBJCGSdOdQoNLwOp8aAgBxSsQfjJxFoq6+A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="styles.css"> 
</head>
<body>
    <header class="header fixed-top bg-light">
        <div class="container">
            <div class="row justify-content-between align-items-center">
                <div class="col-auto">
                    <a href="tables.php"> <img src="logo_black.jpg" alt="Logo" class="logo"></a>
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
