<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends CI_Model {

    private $table = 'products';
    private $image_table = 'product_images';
    
    // Allowed fields for creating/updating
    private $allowed_fields = [
        'ref_number', 
        'title',
        'product_type',
        'price_text', 
        'description', 
        'size_feet', 
        'size_cms', 
        'material', 
        'colour', 
        'stock_status'
    ];

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all products, including their images.
     */
    public function get_all_products() {
        $this->db->from($this->table);
        $query = $this->db->get();
        $products = $query->result_array();

        if (empty($products)) return [];

        $product_ids = array_column($products, 'id');

        $this->db->from($this->image_table);
        $this->db->where_in('product_id', $product_ids);
        $image_query = $this->db->get();
        $images = $image_query->result_array();

        $image_map = [];
        foreach ($images as $image) {
            $image_map[$image['product_id']][] = $image['image_path'];
        }

        foreach ($products as &$product) {
            $product['images'] = isset($image_map[$product['id']]) ? $image_map[$product['id']] : [];
        }

        return $products;
    }

    /**
     * ** NEW FUNCTION **
     * Get a single product by its ID.
     */
    public function get_product_by_id($id) {
        $this->db->from($this->table);
        $this->db->where('id', $id);
        $query = $this->db->get();
        $product = $query->row_array();

        if ($product) {
            $this->db->from($this->image_table);
            $this->db->where('product_id', $product['id']);
            $image_query = $this->db->get();
            $images = $image_query->result_array();
            $product['images'] = array_column($images, 'image_path');
        }
        return $product;
    }

    /**
     * Get a single product by its Reference Number.
     * (We keep this in case you need it elsewhere)
     */
    public function get_product_by_ref($ref_number) {
        $this->db->from($this->table);
        $this->db->where('ref_number', $ref_number);
        $query = $this->db->get();
        $product = $query->row_array();

        if ($product) {
            $this->db->from($this->image_table);
            $this->db->where('product_id', $product['id']);
            $image_query = $this->db->get();
            $images = $image_query->result_array();
            $product['images'] = array_column($images, 'image_path');
        }
        return $product;
    }

    /**
     * Get all products by a specific type (e.g., "Shawl", "Carpet")
     */
  public function get_products_by_type($product_type, $limit, $offset) {
    // --- 1. GET TOTAL COUNT FIRST ---
    $this->db->from($this->table);
    $this->db->where('product_type', $product_type);
    $total_count = $this->db->count_all_results(); // This resets the query

    if ($total_count == 0) {
        return ['data' => [], 'total' => 0];
    }

    // --- 2. GET PAGINATED PRODUCTS ---
    $this->db->from($this->table);
    $this->db->where('product_type', $product_type);
    $this->db->limit($limit, $offset);
    $this->db->order_by('id', 'DESC'); // Good practice for consistent paging
    $query = $this->db->get();
    $products = $query->result_array();

    if (empty($products)) {
        return ['data' => [], 'total' => $total_count];
    }

    // --- 3. GET IMAGES (Your existing logic is good) ---
    // This part is efficient as it only gets images for the paginated products
    $product_ids = array_column($products, 'id');
    
    $this->db->from($this->image_table);
    $this->db->where_in('product_id', $product_ids);
    $image_query = $this->db->get();
    $images = $image_query->result_array();

    $image_map = [];
    foreach ($images as $image) {
        $image_map[$image['product_id']][] = $image['image_path'];
    }

    foreach ($products as &$product) {
        $product['images'] = isset($image_map[$product['id']]) ? $image_map[$product['id']] : [];
    }
    
    // --- 4. RETURN DATA AND TOTAL COUNT ---
    return ['data' => $products, 'total' => $total_count];
}

/**
 * NEW FUNCTION REQUIRED BY YOUR CONTROLLER
 * Add this function to your Product_model.php
 */
public function get_all_products_paginated($limit, $offset) {
    // 1. Get total count
    $total_count = $this->db->count_all($this->table);

    if ($total_count == 0) {
        return ['data' => [], 'total' => 0];
    }

    // 2. Get paginated products
    $this->db->from($this->table);
    $this->db->limit($limit, $offset);
    $this->db->order_by('id', 'DESC');
    $query = $this->db->get();
    $products = $query->result_array();

    if (empty($products)) {
        return ['data' => [], 'total' => $total_count];
    }

    // 3. Get images
    $product_ids = array_column($products, 'id');
    
    $this->db->from($this->image_table);
    $this->db->where_in('product_id', $product_ids);
    $image_query = $this->db->get();
    $images = $image_query->result_array();

    $image_map = [];
    foreach ($images as $image) {
        $image_map[$image['product_id']][] = $image['image_path'];
    }

    foreach ($products as &$product) {
        $product['images'] = isset($image_map[$product['id']]) ? $image_map[$product['id']] : [];
    }

    // 4. Return data and total
    return ['data' => $products, 'total' => $total_count];
}
    /**
     * Create a new product and add its images.
     */
    public function create_product($product_data, $image_paths) {
        // Filter for allowed product fields
        $insert_data = [];
        // ** FIXED: Use the class property $this->allowed_fields **
        foreach ($this->allowed_fields as $field) {
            if (isset($product_data[$field])) {
                $insert_data[$field] = $product_data[$field];
            }
        }

        $this->db->trans_start();

        $this->db->insert($this->table, $insert_data);
        $product_id = $this->db->insert_id();

        if ($product_id && !empty($image_paths)) {
            $image_batch_data = [];
            foreach ($image_paths as $path) {
                $image_batch_data[] = [
                    'product_id' => $product_id,
                    'image_path' => $path
                ];
            }
            $this->db->insert_batch($this->image_table, $image_batch_data);
        }

        $this->db->trans_complete();

        return ($this->db->trans_status() === FALSE) ? false : $product_id;
    }

    /**
     * ** NEW FUNCTION **
     * Update an existing product by its ID.
     */
    public function update_product_by_id($id, $data) {
        $update_data = [];
        // ** FIXED: Use the class property $this->allowed_fields **
        foreach ($this->allowed_fields as $field) {
            // We can't update ref_number, so we skip it
            if ($field === 'ref_number') continue;
            
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }

        if (empty($update_data)) {
            return false; // No valid fields to update
        }

        $this->db->where('id', $id);
        $this->db->update($this->table, $update_data);
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * (This is the old function, we keep it just in case)
     * Update an existing product by its Reference Number.
     */
    public function update_product($ref_number, $data) {
        $update_data = [];
        foreach ($this->allowed_fields as $field) {
            if ($field === 'ref_number') continue;
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }
        if (empty($update_data)) return false;
        $this->db->where('ref_number', $ref_number);
        $this->db->update($this->table, $update_data);
        return $this->db->affected_rows() > 0;
    }


    /**
     * ** NEW FUNCTION **
     * Delete a product by its ID.
     */
    public function delete_product_by_id($id) {
        $this->db->trans_start();

        // 1. Delete associated images
        $this->db->where('product_id', $id);
        $this->db->delete($this->image_table);
        
        // 2. Delete the product
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        
        $this->db->trans_complete();

        return ($this->db->trans_status() !== FALSE);
    }

    /**
     * (This is the old function, we keep it just in case)
     * Delete a product by its Reference Number.
     */
    public function delete_product($ref_number) {
        $this->db->select('id');
        $this->db->from($this->table);
        $this->db->where('ref_number', $ref_number);
        $query = $this->db->get();
        $product = $query->row_array();
        if (!$product) return false;
        return $this->delete_product_by_id($product['id']);
    }
}