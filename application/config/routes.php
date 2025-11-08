<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| User API Routes (using Api controller)
| -------------------------------------------------------------------------
*/
// This route now points to Api/users
$route['api/users'] = 'Api/users';
$route['api/user/(:num)'] = 'Api/user/$1';
$route['api/login'] = 'Api/login';

/*
| -------------------------------------------------------------------------
| Product API Routes (using Products_api controller)
| -------------------------------------------------------------------------
*/
// This route now points to Products_api/products
$route['api/products'] = 'Products_api/products';

// This route now points to Products_api/product/ANY_REF_NUMBER
$route['api/product/(:any)'] = 'Products_api/product/$1';

/*
| -------------------------------------------------------------------------
| Default CodeIgniter Routes (Keep these)
| -------------------------------------------------------------------------
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;