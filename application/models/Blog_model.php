<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Blog_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all blog posts from the 'blogs' table with pagination.
     */
    // --- FIX: Added $limit and $offset for pagination ---
    public function get_all_blogs($limit, $offset) {
        $this->db->order_by('post_date', 'DESC');
        // --- FIX: Added limit and offset ---
        $this->db->limit($limit, $offset);
        $query = $this->db->get('blogs');
        return $query->result_array();
    }

    /**
     * --- FIX: Added new function to count all blogs for pagination ---
     */
    public function count_all_blogs() {
        return $this->db->count_all('blogs');
    }

    /**
     * Get a single blog post by its ID.
     */
    // --- FIX: Renamed function and parameter to use ID ---
    public function get_blog_by_id($id) {
        $query = $this->db->get_where('blogs', array('id' => $id));
        return $query->row_array();
    }

    /**
     * Create a new blog post.
     */
    public function create_blog($data) {
        // Filter for allowed fields
        $allowed_fields = ['title', 'slug', 'content', 'category', 'post_date', 'featured_image_path', 'status'];
        $insert_data = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $insert_data[$key] = $value;
            }
        }
        
        // Auto-generate slug if not provided
        if (empty($insert_data['slug'])) {
            $this->load->helper('url');
            $insert_data['slug'] = url_title($insert_data['title'], 'dash', TRUE) . '-' . time();
        }

        $this->db->insert('blogs', $insert_data);
        return $this->db->insert_id();
    }

    /**
     * Update an existing blog post by its ID.
     */
    // --- FIX: Changed parameter from $slug to $id ---
    public function update_blog($id, $data) {
        // Filter for allowed fields
        // --- FIX: Removed 'slug' - it should not be updatable ---
        $allowed_fields = ['title', 'content', 'category', 'post_date', 'featured_image_path', 'status'];
        $update_data = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed_fields)) {
                $update_data[$key] = $value;
            }
        }
        
        // --- FIX: Changed where clause to use 'id' ---
        $this->db->where('id', $id);
        $this->db->update('blogs', $update_data);
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Delete a blog post by its ID.
     */
    public function delete_blog($id) {
        // You should fetch the post *before* deleting it if you need to unlink the image
        // --- FIX: Call get_blog_by_id instead of get_blog_by_slug ---
        $post = $this->get_blog_by_id($id); 
        if ($post && !empty($post['featured_image_path'])) {
            // Ensure the path is a server path, not a URL
            $image_path = FCPATH . $post['featured_image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // FIX: Use 'id' and the $id variable you received
        $this->db->where('id', $id);
        $this->db->delete('blogs');
        
        return $this->db->affected_rows() > 0;
    }
}