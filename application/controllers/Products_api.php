<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// 1. Extend the base CI_Controller
class Products_api extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('product_model');
        $this->load->helper('url');
        // Load the upload library for handling file uploads
        $this->load->library('upload');
    }

    /**
     * API Endpoint to get ALL products or CREATE a new product.
     * Handles:
     * - GET /api/products
     * - POST /api/products
     */
    public function products() {
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $products = $this->product_model->get_all_products();
                if ($products) {
                    
                    // Loop through products to add base_url to image paths
                    foreach ($products as $key => $product) {
                        if (!empty($product['images'])) {
                            foreach ($product['images'] as $img_key => $image_path) {
                                // Prepend the full URL
                                $products[$key]['images'][$img_key] = base_url($image_path);
                            }
                        }
                    }

                    $response = ['status' => 'success', 'data' => $products];
                    $this->output->set_status_header(200);
                } else {
                    $response = ['status' => 'error', 'message' => 'No products found.'];
                    $this->output->set_status_header(404);
                }
                break;

            case 'POST':
                // NOTE: For file uploads, we MUST use multipart/form-data,
                // so we read from $_POST (using $this->input->post())
                // not from php://input.
                
                // 1. Get text data from POST
                $product_data = $this->input->post();
                if (empty($product_data['title']) || empty($product_data['ref_number'])) {
                    $response = ['status' => 'error', 'message' => 'Title and Reference Number are required.'];
                    $this->output->set_status_header(400);
                    break; // Exit switch
                }

                // 2. Handle File Uploads
                $uploaded_image_paths = [];
                //  *** CHANGED THIS LINE ***
                $upload_path = './uploads/images/'; 
                
                if (!is_dir($upload_path)) {
                    mkdir($upload_path, 0777, TRUE);
                }

                $config['upload_path'] = $upload_path;
                $config['allowed_types'] = 'gif|jpg|png|jpeg';
                $config['max_size'] = 2048; // 2MB
                $config['encrypt_name'] = TRUE;

                $this->upload->initialize($config);

                if (!empty($_FILES['images']['name'][0])) {
                    $file_count = count($_FILES['images']['name']);

                    for ($i = 0; $i < $file_count; $i++) {
                        $_FILES['userfile']['name']     = $_FILES['images']['name'][$i];
                        $_FILES['userfile']['type']     = $_FILES['images']['type'][$i];
                        $_FILES['userfile']['tmp_name'] = $_FILES['images']['tmp_name'][$i];
                        $_FILES['userfile']['error']    = $_FILES['images']['error'][$i];
                        $_FILES['userfile']['size']     = $_FILES['images']['size'][$i];

                        if ($this->upload->do_upload('userfile')) {
                            $upload_data = $this->upload->data();
                            // *** CHANGED THIS LINE ***
                            $uploaded_image_paths[] = 'uploads/images/' . $upload_data['file_name'];
                        } else {
                            $error = $this->upload->display_errors('', '');
                            $response = ['status' => 'error', 'message' => 'Image upload failed: ' . $error];
                            $this->output->set_status_header(400);
                            // Output the response and stop the script
                            $this->output->set_content_type('application/json')->set_output(json_encode($response));
                            return; 
                        }
                    }
                }

                // 3. Create Product in Database
                $product_id = $this->product_model->create_product($product_data, $uploaded_image_paths);

                if ($product_id) {
                    $new_product = $this->product_model->get_product_by_ref($product_data['ref_number']);
                    $response = ['status' => 'success', 'message' => 'Product created successfully', 'data' => $new_product];
                    $this->output->set_status_header(201); // Created
                } else {
                    $response = ['status' => 'error', 'message' => 'Failed to create product.'];
                    $this->output->set_status_header(500); // Internal Server Error
                }
                break;

            default:
                $response = array('status' => 'error', 'message' => 'Method not allowed');
                $this->output->set_status_header(405); // Method Not Allowed
                break;
        }

        // Send the JSON response
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($response));
    }

    /**
     * API Endpoint to get, update, or delete a SINGLE product.
     * Handles:
     * - GET /api/product/304
     * - PUT /api/product/304
     * - DELETE /api/product/304
     *
     * @param string $ref_number The Product Reference Number
     */
    public function product($ref_number = null) {
        $method = $_SERVER['REQUEST_METHOD'];

        if (empty($ref_number)) {
            $response = ['status' => 'error', 'message' => 'Product reference number is required.'];
            $this->output->set_status_header(400);
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }
        
        switch ($method) {
            case 'GET':
                $product = $this->product_model->get_product_by_ref($ref_number);
                if ($product) {
                    // Re-format image paths to be full URLs
                    if (!empty($product['images'])) {
                        foreach ($product['images'] as $key => $image_path) {
                            $product['images'][$key] = base_url($image_path);
                        }
                    }
                    $response = ['status' => 'success', 'data' => $product];
                    $this->output->set_status_header(200);
                } else {
                    $response = ['status' => 'error', 'message' => 'Product not found.'];
                    $this->output->set_status_header(404);
                }
                break;

            case 'PUT':
                // For PUT, we read raw JSON input, just like your login example
                $update_data = json_decode(file_get_contents('php://input'), true);

                if (empty($update_data)) {
                    $response = ['status' => 'error', 'message' => 'No data provided for update.'];
                    $this->output->set_status_header(400);
                } else {
                    $success = $this->product_model->update_product($ref_number, $update_data);
                    if ($success) {
                        $updated_product = $this->product_model->get_product_by_ref($ref_number);
                        $response = ['status' => 'success', 'message' => 'Product updated.', 'data' => $updated_product];
                        $this->output->set_status_header(200);
                    } else {
                        $response = ['status' => 'error', 'message' => 'Product not found or no changes made.'];
                        $this->output->set_status_header(404);
                    }
                }
                break;

            case 'DELETE':
                $success = $this->product_model->delete_product($ref_number);
                if ($success) {
                    $response = ['status' => 'success', 'message' => 'Product deleted successfully.'];
                    $this->output->set_status_header(200);
                } else {
                    $response = ['status' => 'error', 'message' => 'Product not found.'];
                    $this->output->set_status_header(404);
                }
                break;
            
            default:
                $response = array('status' => 'error', 'message' => 'Method not allowed');
                $this->output->set_status_header(405); // Method Not Allowed
                break;
        }

        // Send the JSON response
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($response));
    }
}