<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CorsHook Class
 *
 * This hook handles Cross-Origin Resource Sharing (CORS) pre-flight
 * requests and adds the necessary headers to all responses.
 * This allows a React (or any) frontend from a different domain
 * to access this API.
 */
class CorsHook {

    public function handleCors() {
        // Get a reference to the CodeIgniter instance
        $CI =& get_instance();
        
        // Define which origins are allowed.
        // Use '*' for public access or 'http://localhost:3000' for your React dev server.
        $allowed_origin = '*'; // Or '*' for public
        
        // Check if the request origin is set
        $origin = $CI->input->server('HTTP_ORIGIN');

        // For security, you might want to check $origin against a whitelist
        // if ($origin === $allowed_origin || $origin === 'http://your-live-react-app.com') {
        //     header("Access-Control-Allow-Origin: $origin");
        // } else {
        //     header("Access-Control-Allow-Origin: null"); // Or just don't set it
        // }
        
        // For now, let's allow all or a specific one
        header("Access-Control-Allow-Origin: " . $allowed_origin);
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        
        // This is the "pre-flight" request
        // The browser sends an 'OPTIONS' request first to check permissions
        if ($CI->input->method() == 'options') {
            // Send a 200 OK status and exit
            // This stops CodeIgniter from trying to route the OPTIONS request
            http_response_code(200);
            exit();
        }

        // --- Clean up for all responses ---
        // Since this hook now adds the JSON content-type header for all
        // API responses, you can remove the manual
        // `header('Content-Type: application/json');`
        // or
        // `$this->output->set_content_type('application/json')`
        // from all your API controllers to make them cleaner.
        
        // Note: We only set this for non-OPTIONS requests
        header("Content-Type: application/json");
    }
}