<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
    }

    /**
     * Handles GET (all users) and POST (new user)
     */
    public function users() {
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                $users = $this->User_model->get_all_users();
                if (empty($users)) {
                    $response = array('status' => 'error', 'message' => 'No users found');
                    $this->output->set_status_header(404);
                } else {
                    $response = array('status' => 'success', 'data' => $users);
                    $this->output->set_status_header(200);
                }
                break;

            case 'POST':
                // Read the raw JSON input
                $input_data = json_decode(file_get_contents('php://input'), true);

                if (empty($input_data['name']) || empty($input_data['email']) || empty($input_data['password'])) {
                    $response = array('status' => 'error', 'message' => 'Name, email, and password are required');
                    $this->output->set_status_header(400); // Bad Request
                } else {
                    $user_id = $this->User_model->create_user($input_data);
                    $new_user = $this->User_model->get_user($user_id);
                    $response = array('status' => 'success', 'message' => 'User created', 'data' => $new_user);
                    $this->output->set_status_header(201); // Created
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
     * Handles GET (one user), PUT (update), and DELETE (remove)
     * @param int $id The User ID
     */
    public function user($id) {
        $method = $_SERVER['REQUEST_METHOD'];
        $id = (int)$id;

        switch ($method) {
            case 'GET':
                $user = $this->User_model->get_user($id);
                if (empty($user)) {
                    $response = array('status' => 'error', 'message' => 'User not found');
                    $this->output->set_status_header(404);
                } else {
                    $response = array('status' => 'success', 'data' => $user);
                    $this->output->set_status_header(200);
                }
                break;

            case 'PUT':
                // Read the raw JSON input
                $input_data = json_decode(file_get_contents('php://input'), true);

                if (empty($input_data)) {
                    $response = array('status' => 'error', 'message' => 'No data provided to update');
                    $this->output->set_status_header(400);
                } else {
                    $success = $this->User_model->update_user($id, $input_data);
                    if ($success) {
                        $updated_user = $this->User_model->get_user($id);
                        $response = array('status' => 'success', 'message' => 'User updated', 'data' => $updated_user);
                        $this->output->set_status_header(200);
                    } else {
                        $response = array('status' => 'error', 'message' => 'User not found or no changes made');
                        $this->output->set_status_header(404);
                    }
                }
                break;

            case 'DELETE':
                $success = $this->User_model->delete_user($id);
                if ($success) {
                    $response = array('status' => 'success', 'message' => 'User deleted');
                    $this->output->set_status_header(200);
                } else {
                    $response = array('status' => 'error', 'message' => 'User not found');
                    $this->output->set_status_header(404);
                }
                break;

            default:
                $response = array('status' => 'error', 'message' => 'Method not allowed');
                $this->output->set_status_header(405);
                break;
        }

        // Send the JSON response
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($response));
    }


    /**
     * Handles POST for user login
     */
    public function login() {
        $method = $_SERVER['REQUEST_METHOD'];

        if ($method != 'POST') {
            $response = array('status' => 'error', 'message' => 'Method not allowed');
            $this->output->set_status_header(405);
        } else {
            // Read the raw JSON input
            $input_data = json_decode(file_get_contents('php://input'), true);

            $email = isset($input_data['email']) ? $input_data['email'] : '';
            $password = isset($input_data['password']) ? $input_data['password'] : '';

            if (empty($email) || empty($password)) {
                $response = array('status' => 'error', 'message' => 'Email and password are required');
                $this->output->set_status_header(400); // Bad Request
            } else {
                $user = $this->User_model->check_login($email, $password);

                if ($user) {
                    $response = array('status' => 'success', 'message' => 'Login successful', 'data' => $user);
                    $this->output->set_status_header(200);
                } else {
                    $response = array('status' => 'error', 'message' => 'Invalid email or password');
                    $this->output->set_status_header(401); // Unauthorized
                }
            }
        }

        // Send the JSON response
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($response));
    }
}