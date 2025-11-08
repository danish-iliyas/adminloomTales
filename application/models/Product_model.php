<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all products from the 'products' table.
     */
    public function get_all_products() {
        $query = $this->db->get('products');
        $products = $query->result_array();

        if (empty($products)) {
            return [];
        }

        // Get all product IDs
        $product_ids = [];
        foreach ($products as $product) {
            $product_ids[] = $product['id'];
        }

        // Fetch all images for these product IDs in a single query
        $this->db->where_in('product_id', $product_ids);
        $images_query = $this->db->get('product_images');
        
        // Map images to their product_id
        $images_map = [];
        if ($images_query->num_rows() > 0) {
            foreach ($images_query->result_array() as $row) {
                // This creates an array of images for each product_id
                $images_map[$row['product_id']][] = $row['image_path'];
            }
        }

        // Attach images to their corresponding product
        foreach ($products as $key => $product) {
            if (isset($images_map[$product['id']])) {
                $products[$key]['images'] = $images_map[$product['id']];
            } else {
                $products[$key]['images'] = []; // Ensure 'images' key always exists
            }
        }

        return $products;
    }

    /**
     * Get a single product by its Reference Number.
     * Fetches the product and all its associated images.
     */
    public function get_product_by_ref($ref_number) {
        
        $this->db->where('ref_number', $ref_number);
        $product_query = $this->db->get('products');

        if ($product_query->num_rows() > 0) {
            $product = $product_query->row_array();
            
            $this->db->where('product_id', $product['id']);
            $images_query = $this->db->get('product_images');
            
            $images = [];
            if ($images_query->num_rows() > 0) {
                foreach ($images_query->result_array() as $row) {
                    $images[] = $row['image_path'];
                }
            }
            
            $product['images'] = $images;
            return $product;
        }
        
        return false;
    }

    /**
     * Create a new product and add its images.
     */
    public function create_product($product_data, $image_paths) {
        
        // Filter for allowed product fields for security
        $allowed_fields = ['ref_number', 'title', 'price_text', 'description', 'size_feet', 'size_cms', 'material', 'colour', 'stock_status'];
        $insert_data = [];
        foreach ($allowed_fields as $field) {
            if (isset($product_data[$field])) {
                $insert_data[$field] = $product_data[$field];
            }
        }

        $this->db->trans_start();

        $this->db->insert('products', $insert_data);
        $product_id = $this->db->insert_id();

        if ($product_id && !empty($image_paths)) {
            $image_batch_data = [];
            foreach ($image_paths as $path) {
                $image_batch_data[] = [
                    'product_id' => $product_id,
                    'image_path' => $path
                ];
            }
            $this->db->insert_batch('product_images', $image_batch_data);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return false;
        }

        return $product_id;
    }

    /**
     * Update an existing product by its Reference Number.
     */
    public function update_product($ref_number, $data) {
        
        // Filter for allowed product fields for security
        $allowed_fields = ['title', 'price_text', 'description', 'size_feet', 'size_cms', 'material', 'colour', 'stock_status'];
        $update_data = [];
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        if (empty($update_data)) {
            return false; // No valid fields to update
        }

        $this->db->where('ref_number', $ref_number);
        $this->db->update('products', $update_data);
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Delete a product by its Reference Number.
     * The database's "ON DELETE CASCADE" will automatically
     * delete all associated images from 'product_images'.
     */
    public function delete_product($ref_number) {
        $this->db->where('ref_number', $ref_number);
        $this->db->delete('products');
        
        return $this->db->affected_rows() > 0;
    }
}