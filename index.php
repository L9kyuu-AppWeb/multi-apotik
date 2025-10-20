<?php
/**
 * Main Entry Point
 * Redirect ke login atau dashboard
 */

define('APP_ACCESS', true);
require_once 'config.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to dashboard if already logged in
    redirect('dashboard.php');
} else {
    // Redirect to landing page
    header('Location: index.html');
    exit;
}
?>