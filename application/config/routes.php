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

// === PRODUCT ROUTES ===
// Get all products or create a new product
$route['api/products'] = 'Products_api/products';

// ** ADD THIS NEW ROUTE HERE **
// Get products by type (e.g., api/products/type/Shawl)
// THIS MUST COME BEFORE the route for api/product/(:any)
$route['api/products/type/(:any)'] = 'Products_api/products_by_type/$1';

// Get, update, or delete a single product by ref_number
$route['api/product/(:num)'] = 'Products_api/product/$1';

/*
| -------------------------------------------------------------------------
| Default CodeIgniter Routes (Keep these)
| -------------------------------------------------------------------------
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;




//
// Blog API Routes
//

// /api/blogs (GET all, POST new)
$route['api/blogs'] = 'blogs_api/blogs';

// ** NEW ROUTES: These MUST come BEFORE the api/blog/(:any) route **
// POST /api/blog/some-slug/publish
$route['api/blog/(:any)/publish'] = 'blogs_api/publish/$1';
// POST /api/blog/some-slug/unpublish
$route['api/blog/(:any)/unpublish'] = 'blogs_api/unpublish/$1';

// /api/blog/some-slug (GET, PUT, DELETE by slug)
// This route must be LAST to catch all other methods
$route['api/blog/(:num)'] = 'blogs_api/blog/$1';

// ckeeditor uploda

$route['api/upload-image']['post'] = '/UploadImage/index';
