<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Products_api extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Product_model');
        $this->load->helper('url');
        
        // This is handled by your CorsHook.php
        // header('Content-Type: application/json');
    }

    /**
     * Handles GET (all products) and POST (new product)
     */
  
// In application/controllers/Products_api.php

public function products() {
        $method = $_SERVER['REQUEST_METHOD'];

        // Handle OPTIONS pre-flight request for CORS
        if ($method == 'OPTIONS') {
            $this->output->set_status_header(200);
            return;
        }

        switch ($method) {
            case 'GET':
                // Get pagination params
                $product_type = $this->input->get('type');
                $limit = (int)$this->input->get('limit') ?: 6; // Default to 6
                $page = (int)$this->input->get('page') ?: 1;   // Default to page 1
                $offset = ($page - 1) * $limit;
                
                $result = []; 

                if ($product_type) {
                    // Get paginated results BY TYPE
                    $result = $this->Product_model->get_products_by_type($product_type, $limit, $offset);
                } else {
                    // Get paginated results for ALL products
                    $result = $this->Product_model->get_all_products_paginated($limit, $offset);
                }
                
                $products = $result['data'];
                $total_count = $result['total'];

                if (empty($products)) {
                    $message = $product_type ? 'No products found for type: ' . $product_type : 'No products found';
                    $response = array('status' => 'error', 'message' => $message);
                    $this->output->set_status_header(404);
                } else {
                    // Add base_url to all image paths
                    foreach ($products as &$product) {
                        if (isset($product['images']) && is_array($product['images'])) {
                            foreach ($product['images'] as &$image_path) {
                                if (!preg_match("~^(?:f|ht)tps?://~i", $image_path)) {
                                    $image_path = base_url($image_path);
                                }
                            }
                        }
                    }
                    
                    // Return the new paginated response
                    $response = array(
                        'status' => 'success',
                        'data' => $products,
                        'pagination' => array(
                            'total_items' => (int)$total_count,
                            'per_page' => (int)$limit,
                            'current_page' => (int)$page,
                            'total_pages' => (int)ceil($total_count / $limit)
                        )
                    );
                    $this->output->set_status_header(200);
                }
                break;

            case 'POST':
                // This is your 'create_product' logic from before
                $form_data = $this->input->post();
                $data = array(
                    'ref_number' => $form_data['ref_number'] ?? null,
                    'title' => $form_data['title'] ?? null,
                    'product_type' => $form_data['product_type'] ?? null,
                    'price_text' => $form_data['price_text'] ?? null,
                    'description' => $form_data['description'] ?? null,
                    'size_feet' => $form_data['size_feet'] ?? null,
                    'size_cms' => $form_data['size_cms'] ?? null,
                    'material' => $form_data['material'] ?? null,
                    'colour' => $form_data['colour'] ?? null,
                    'stock_status' => $form_data['stock_status'] ?? 1,
                );

                if (empty($data['title']) || empty($data['ref_number'])) {
                    $response = array('status' => 'error', 'message' => 'Title and Reference Number are required');
                    $this->output->set_status_header(400);
                    break; // Use break, not return/echo
                }

                $uploaded_image_paths = [];
                $upload_path = './uploads/images/';
                if (!is_dir($upload_path)) {
                    mkdir($upload_path, 0777, TRUE);
                }

                $config['upload_path'] = $upload_path;
                $config['allowed_types'] = 'gif|jpg|jpeg|png';
                $config['encrypt_name'] = TRUE;
                $config['max_size'] = 2048;

                $this->load->library('upload');

                if (!empty($_FILES['images']['name'][0])) {
                    $filesCount = count($_FILES['images']['name']);
                    for ($i = 0; $i < $filesCount; $i++) {
                        $_FILES['userfile']['name']     = $_FILES['images']['name'][$i];
                        $_FILES['userfile']['type']     = $_FILES['images']['type'][$i];
                        $_FILES['userfile']['tmp_name'] = $_FILES['images']['tmp_name'][$i];
                        $_FILES['userfile']['error']    = $_FILES['images']['error'][$i];
                        $_FILES['userfile']['size']     = $_FILES['images']['size'][$i];

                        $this->upload->initialize($config);
                        if ($this->upload->do_upload('userfile')) {
                            $fileData = $this->upload->data();
                            $uploaded_image_paths[] = 'uploads/images/' . $fileData['file_name'];
                        } else {
                            $response = array('status' => 'error', 'message' => $this->upload->display_errors('', ''));
                            $this->output->set_status_header(400);
                            echo json_encode($response); // Echo and return here is ok to stop upload
                            return;
                        }
                    }
                }

                $product_id = $this->Product_model->create_product($data, $uploaded_image_paths);
                
                if ($product_id) {
                    $new_product = $this->Product_model->get_product_by_id($product_id); 
                    // Add base_url to images for the response
                    if (isset($new_product['images']) && is_array($new_product['images'])) {
                        foreach ($new_product['images'] as &$image_path) {
                            $image_path = base_url($image_path);
                        }
                    }
                    $response = array('status' => 'success', 'message' => 'Product created successfully', 'data' => $new_product);
                    $this->output->set_status_header(201);
                } else {
                    $response = array('status' => 'error', 'message' => 'Failed to create product');
                    $this->output->set_status_header(500);
                }
                break;

            default:
                $response = array('status' => 'error', 'message' => 'Method not allowed');
                $this->output->set_status_header(405);
                break;
        }

        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }
    /**
     * Handles GET (one product), PUT (update), and DELETE (remove)
     * @param int $id The Product ID
     */
    public function product($id) {
        
        // ** The debug code has been removed. **
        
        $method = $_SERVER['REQUEST_METHOD'];
        $id = (int)$id; // Ensure it's an integer
        
        switch ($method) {
            case 'GET':
                $product = $this->Product_model->get_product_by_id($id);
                if (empty($product)) {
                    $response = array('status' => 'error', 'message' => 'Product not found');
                    $this->output->set_status_header(404);
                } else {
                    if (isset($product['images']) && is_array($product['images'])) {
                        foreach ($product['images'] as &$image_path) {
                            if (!preg_match("~^(?:f|ht)tps?://~i", $image_path)) {
                                $image_path = base_url($image_path);
                            }
                        }
                    }
                    $response = array('status' => 'success', 'data' => $product);
                    $this->output->set_status_header(200);
                }
                break;

            case 'PUT':
                $input_data = json_decode(file_get_contents('php://input'), true);

                if (empty($input_data)) {
                    $response = array('status' => 'error', 'message' => 'No data provided to update');
                    $this->output->set_status_header(400);
                } else {
                    $success = $this->Product_model->update_product_by_id($id, $input_data);
                    if ($success) {
                        $updated_product = $this->Product_model->get_product_by_id($id);
                        $response = array('status' => 'success', 'message' => 'Product updated', 'data' => $updated_product);
                        $this->output->set_status_header(200);
                    } else {
                        $response = array('status' => 'error', 'message' => 'Product not found or no changes made');
                        $this->output->set_status_header(404);
                    }
                }
                break;

            case 'DELETE':
                $product = $this->Product_model->get_product_by_id($id);
                if (empty($product)) {
                    $response = array('status' => 'error', 'message' => 'Product not found');
                    $this->output->set_status_header(404);
                    echo json_encode($response);
                    return;
                }
                
                if (!empty($product['images'])) {
                    foreach ($product['images'] as $image_path_with_base) {
                        // Remove base_url() to get the relative server path
                        $image_path = str_replace(base_url(), '', $image_path_with_base);
                        
                        // FCPATH is the path to your index.php
                        // We assume the 'uploads' folder is at the same level
                        $file_path = FCPATH . $image_path; 
                        
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }
                }
                
                $success = $this->Product_model->delete_product_by_id($id);
                if ($success) {
                    $response = array('status' => 'success', 'message' => 'Product and associated images deleted');
                    $this->output->set_status_header(200);
                } else {
                    $response = array('status' => 'error', 'message' => 'Failed to delete product from database');
                    $this->output->set_status_header(500);
                }
                break;

            default:
                $response = array('status' => 'error', 'message' => 'Method not allowed');
                $this->output->set_status_header(405);
                break;
        }

        echo json_encode($response);
    }
    
    /**
     * Handles GET for products filtered by type
     * @param string $type The product type (e.g., "Shawl")
     */
    public function products_by_type($type) {
        // This logic is now inside the main products() function
        $response = array('status' => 'error', 'message' => 'This endpoint is deprecated. Use /api/products?type=' . $type);
        $this->output->set_status_header(404);
        echo json_encode($response);
    }
}