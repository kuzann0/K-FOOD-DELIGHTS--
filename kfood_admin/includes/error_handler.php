<?php
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }

    $errorType = 'Unknown Error';
    switch ($errno) {
        case E_ERROR:
            $errorType = 'Fatal Error';
            break;
        case E_WARNING:
            $errorType = 'Warning';
            break;
        case E_PARSE:
            $errorType = 'Parse Error';
            break;
        case E_NOTICE:
            $errorType = 'Notice';
            break;
        case E_CORE_ERROR:
            $errorType = 'Core Error';
            break;
        case E_CORE_WARNING:
            $errorType = 'Core Warning';
            break;
        case E_USER_ERROR:
            $errorType = 'User Error';
            break;
        case E_USER_WARNING:
            $errorType = 'User Warning';
            break;
        case E_USER_NOTICE:
            $errorType = 'User Notice';
            break;
    }

    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        echo "<div style='border: 1px solid #f44336; padding: 20px; margin: 10px; background: #ffebee;'>";
        echo "<h2 style='color: #d32f2f; margin-top: 0;'>$errorType</h2>";
        echo "<p style='margin: 0;'><strong>Message:</strong> $errstr</p>";
        echo "<p style='margin: 5px 0;'><strong>File:</strong> $errfile</p>";
        echo "<p style='margin: 0;'><strong>Line:</strong> $errline</p>";
        echo "</div>";
    } else {
        // In production, log the error and show a user-friendly message
        error_log("$errorType: $errstr in $errfile on line $errline");
        echo "<div style='text-align: center; padding: 50px;'>";
        echo "<h1>Oops! Something went wrong.</h1>";
        echo "<p>We're sorry, but there was an error processing your request. Please try again later.</p>";
        echo "</div>";
    }

    return true;
}

// Set the custom error handler
set_error_handler("customErrorHandler");

// Set development environment for now
define('ENVIRONMENT', 'development');

// Make sure all errors are reported during development
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
