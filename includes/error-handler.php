<?php
class ErrorHandler {
    public static function handle($errno, $errstr, $errfile, $errline) {
        $message = "[$errno] $errstr in $errfile:$errline";
        error_log($message);
        
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            // In dev, we can show errors
            // But in production, we should show a generic 500 page
            return false; // Let PHP's internal handler take over
        } else {
            if ($errno === E_ERROR || $errno === E_USER_ERROR) {
                http_response_code(500);
                die("A serious error occurred. The team has been notified.");
            }
        }
    }
}
set_error_handler(['ErrorHandler', 'handle']);