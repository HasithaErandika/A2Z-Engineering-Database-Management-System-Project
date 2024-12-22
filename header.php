<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php if ($showHeader): ?>
    <header>
        <div class="header-container">
    <div>
        <img src="path/to/logo.png" alt="A2Z Engineering" class="logo">
        <span class="company-name">A2Z Engineering</span>
    </div>
    <?php if (isset($_SESSION['username'])): ?>
        <form method="post" action="logout.php" class="logout-form">
            <button type="submit" name="logout">Logout</button>
        </form>
    <?php endif; ?>
</div>
    </header>
    <?php endif; ?>



