<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <!-- Core Admin Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/admin_style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/admin_dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/admin_enhancements.css">
    
    <!-- Module Specific Styles -->
    <?php if (isset($currentModule)): ?>
        <?php if ($currentModule === 'inventory'): ?>
            <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/inventory.css">
        <?php endif; ?>
        <?php if ($currentModule === 'menu'): ?>
            <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/menu.css">
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom page-specific styles -->
    <?php if (isset($additionalStyles)): ?>
        <?php foreach ($additionalStyles as $style): ?>
            <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/<?php echo $style; ?>.css">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="admin-container">
