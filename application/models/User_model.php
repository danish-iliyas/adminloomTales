<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    private $table = 'users';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Get all users
     */
    public function get_all_users() {
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Get a single user by ID
     */
    public function get_user($id) {
        return $this->db->get_where($this->table, array('id' => $id))->row_array();
    }

    /**
     * Create a new user
     */
    public function create_user($data) {
        // Only allow 'name', 'email', 'password', and 'phone' fields
        $insert_data = array(
            'name' => isset($data['name']) ? $data['name'] : null,
            'email' => isset($data['email']) ? $data['email'] : null,
            'password' => isset($data['password']) ? $data['password'] : null, // Note: In a real app, you MUST hash this!
            'phone' => isset($data['phone']) ? $data['phone'] : null
        );
        $this->db->insert($this->table, $insert_data);
        return $this->db->insert_id();
    }

    /**
     * Update an existing user
     */
    public function update_user($id, $data) {
        // Only allow 'name', 'email', 'password', and 'phone' fields to be updated
        $update_data = array();
        if (isset($data['name'])) {
            $update_data['name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $update_data['email'] = $data['email'];
        }
        if (isset($data['password'])) {
            $update_data['password'] = $data['password']; // Note: In a real app, you MUST hash this!
        }
        if (isset($data['phone'])) {
            $update_data['phone'] = $data['phone'];
        }

        if (empty($update_data)) {
            return false; // No valid fields to update
        }

        $this->db->where('id', $id);
        $this->db->update($this->table, $update_data);
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Check user login credentials
     */
    public function check_login($email, $password) {
        $this->db->where('email', $email);
        $user = $this->db->get($this->table)->row_array();

        if ($user) {
            // !! SECURITY WARNING !!
            // This is checking plain text. In a real application,
            // you MUST hash passwords using password_hash() when creating
            // and check them using password_verify().
            // Example:
            // if (password_verify($password, $user['password'])) { ... }

            if ($password == $user['password']) {
                // Password is correct, remove it from the result
                unset($user['password']);
                return $user;
            }
        }

        // User not found or password incorrect
        return false;
    }
}