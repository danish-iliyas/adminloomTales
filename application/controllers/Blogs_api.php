<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Blogs_api extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Blog_model');
        $this->load->helper('url'); // For base_url()
        $this->load->library('upload'); // For file uploads
    }

    /**
     * API Endpoint to get ALL blogs (paginated) or CREATE a new blog.
     * GET: /api/blogs?page=1
     * POST: /api/blogs
     */
    public function blogs() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                // --- FIX: Pagination logic ---
                $limit = 6;
                $page = $this->input->get('page') ? (int)$this->input->get('page') : 1;
                $offset = ($page - 1) * $limit;

                $blogs = $this->Blog_model->get_all_blogs($limit, $offset);
                $total_blogs = $this->Blog_model->count_all_blogs();
                $total_pages = ceil($total_blogs / $limit);

                if ($blogs) {
                    // Prepend base_url to all image paths
                    foreach ($blogs as $key => $blog) {
                        if (!empty($blog['featured_image_path'])) {
                            $blogs[$key]['featured_image_path'] = base_url($blog['featured_image_path']);
                        }
                    }
                    // --- FIX: Added pagination to response ---
                    $response = [
                        'status' => 'success', 
                        'data' => $blogs,
                        'pagination' => [
                            'page' => $page,
                            'total_pages' => $total_pages,
                            'total_results' => (int)$total_blogs,
                            'per_page' => $limit
                        ]
                    ];
                    $this->output->set_status_header(200);
                } else {
                    $response = ['status' => 'error', 'message' => 'No blogs found'];
                    $this->output->set_status_header(404);
                }
                break;

            case 'POST':
                $blog_data = [
                    'title'     => $this->input->post('title'),
                    'content'   => $this->input->post('content'),
                    'category'  => $this->input->post('category'),
                    'status'    => $this->input->post('status') ? $this->input->post('status') : 'draft',
                    'slug'      => $this->input->post('slug'), // Optional
                    'post_date' => $this->input->post('post_date') ? $this->input->post('post_date') : date('Y-m-d')
                ];
                
                // Handle File Upload
                $upload_path = './uploads/images/blog/';
                
                if (!is_dir($upload_path)) {
                    mkdir($upload_path, 0777, TRUE);
                }

                $config['upload_path'] = $upload_path;
                $config['allowed_types'] = 'gif|jpg|jpeg|png';
                $config['encrypt_name'] = TRUE;
                
                $this->upload->initialize($config);

                // --- FIX: Use 'featured_image_path' to match frontend FormData ---
                if (!empty($_FILES['featured_image_path']['name'])) {
                    if ($this->upload->do_upload('featured_image_path')) {
                        $upload_data = $this->upload->data();
                        $blog_data['featured_image_path'] = 'uploads/images/blog/' . $upload_data['file_name'];
                    } else {
                        // File upload failed
                        $response = ['status' => 'error', 'message' => $this->upload->display_errors('', '')];
                        $this->output->set_status_header(400); // Bad Request
                        $this->output->set_content_type('application/json')->set_output(json_encode($response));
                        return; // Stop execution
                    }
                }

                // Create Blog in Database
                $blog_id = $this->Blog_model->create_blog($blog_data);
                
                if ($blog_id) {
                    // --- FIX: Get new blog by ID ---
                    $new_blog = $this->Blog_model->get_blog_by_id($blog_id);
                    
                    if (!empty($new_blog['featured_image_path'])) {
                         $new_blog['featured_image_path'] = base_url($new_blog['featured_image_path']);
                    }
                    
                    $response = ['status' => 'success', 'message' => 'Blog post created', 'data' => $new_blog];
                    $this->output->set_status_header(201); // Created
                } else {
                    $response = ['status' => 'error', 'message' => 'Failed to create blog post'];
                    $this->output->set_status_header(500);
                }
                break;

            default:
                $response = ['status' => 'error', 'message' => 'Method not allowed'];
                $this->output->set_status_header(405);
                break;
        }

        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($response));
    }

    /**
     * API Endpoint to GET, UPDATE (POST w/ _method=PUT), or DELETE (POST w/ _method=DELETE) a SINGLE blog.
     * GET: /api/blog/[id]
     * POST: /api/blog/[id] (with _method: "PUT" for updates)
     * POST: /api/blog/[id] (with _method: "DELETE" for deletes)
     */
    // --- FIX: Changed parameter name to $id ---
    public function blog($id = null) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($id === null) {
            $response = ['status' => 'error', 'message' => 'Blog ID is required'];
            $this->output->set_status_header(400); // Bad Request
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
            return;
        }

        // --- FIX: Cast ID to integer ---
        $id = (int)$id;

        switch ($method) {
            case 'GET':
                // --- FIX: Call get_blog_by_id ---
                $blog = $this->Blog_model->get_blog_by_id($id);
                if ($blog) {
                    if (!empty($blog['featured_image_path'])) {
                         $blog['featured_image_path'] = base_url($blog['featured_image_path']);
                    }
                    $response = ['status' => 'success', 'data' => $blog];
                    $this->output->set_status_header(200);
                } else {
                    $response = ['status' => 'error', 'message' => 'Blog post not found'];
                    $this->output->set_status_header(404);
                }
                break;

            // --- FIX: Handle POST for method spoofing (PUT/DELETE) ---
            case 'POST':
                $method_override = $this->input->post('_method');

                if ($method_override === 'PUT') {
                    // --- THIS IS THE UPDATE LOGIC ---
                    $update_data = $this->input->post();
                    unset($update_data['_method']); // Don't save this to DB

                    // Handle file upload
                    $upload_path = './uploads/images/blog/';
                    if (!is_dir($upload_path)) {
                        mkdir($upload_path, 0777, TRUE);
                    }
                    $config['upload_path'] = $upload_path;
                    $config['allowed_types'] = 'gif|jpg|jpeg|png';
                    $config['encrypt_name'] = TRUE;
                    $this->upload->initialize($config);

                    if (!empty($_FILES['featured_image_path']['name'])) {
                        if ($this->upload->do_upload('featured_image_path')) {
                            // 1. Get old blog data to delete old image
                            $old_blog = $this->Blog_model->get_blog_by_id($id);
                            
                            // 2. Set new image path in data
                            $upload_data = $this->upload->data();
                            $update_data['featured_image_path'] = 'uploads/images/blog/' . $upload_data['file_name'];

                            // 3. Delete old image if it exists
                            if ($old_blog && !empty($old_blog['featured_image_path'])) {
                                $old_image_path = FCPATH . $old_blog['featured_image_path'];
                                if (file_exists($old_image_path)) {
                                    unlink($old_image_path);
                                }
                            }
                        } else {
                            // File upload failed
                            $response = ['status' => 'error', 'message' => $this->upload->display_errors('', '')];
                            $this->output->set_status_header(400);
                            $this->output->set_content_type('application/json')->set_output(json_encode($response));
                            return;
                        }
                    }

                    // Perform update
                    $success = $this->Blog_model->update_blog($id, $update_data);
                    if ($success) {
                        $updated_blog = $this->Blog_model->get_blog_by_id($id);
                        if (!empty($updated_blog['featured_image_path'])) {
                             $updated_blog['featured_image_path'] = base_url($updated_blog['featured_image_path']);
                        }
                        $response = ['status' => 'success', 'message' => 'Blog post updated', 'data' => $updated_blog];
                        $this->output->set_status_header(200);
                    } else {
                        $response = ['status' => 'error', 'message' => 'Blog post not found or no changes made'];
                        $this->output->set_status_header(404);
                    }

                } else if ($method_override === 'DELETE') {
                    // --- THIS IS THE DELETE LOGIC ---
                    $success = $this->Blog_model->delete_blog($id);
                    if ($success) {
                        $response = ['status' => 'success', 'message' => 'Blog post deleted'];
                        $this->output->set_status_header(200);
                    } else {
                        $response = ['status' => 'error', 'message' => 'Blog post not found'];
                        $this->output->set_status_header(404);
                    }

                } else {
                    $response = ['status' => 'error', 'message' => 'Invalid POST request. Missing or unknown _method.'];
                    $this->output->set_status_header(400);
                }
                break;
            
            // --- FIX: Removed 'PUT' and 'DELETE' cases as they are handled by 'POST' ---

            default:
                $response = ['status' => 'error', 'message' => 'Method not allowed'];
                $this->output->set_status_header(405);
                break;
        }

        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($response));
    }

    /**
     * API Endpoint to PUBLISH a blog post.
     * POST: /api/blog/[id]/publish
     */
    // --- FIX: Changed parameter to $id ---
    public function publish($id = null) {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $response = ['status' => 'error', 'message' => 'Method not allowed. Use POST.'];
            $this->output->set_status_header(405);
        } else if ($id === null) {
             $response = ['status' => 'error', 'message' => 'Blog ID is required'];
             $this->output->set_status_header(400);
        } else {
            // --- FIX: Call update_blog with id ---
            $success = $this->Blog_model->update_blog((int)$id, ['status' => 'published']);
            if ($success) {
                $response = ['status' => 'success', 'message' => 'Blog post published.'];
                $this->output->set_status_header(200);
            } else {
                $response = ['status' => 'error', 'message' => 'Blog post not found or already published.'];
                $this->output->set_status_header(404);
            }
        }
        
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($response));
    }

    /**
     * API Endpoint to UNPUBLISH a blog post.
     * POST: /api/blog/[id]/unpublish
     */
    // --- FIX: Changed parameter to $id ---
    public function unpublish($id = null) {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            $response = ['status' => 'error', 'message' => 'Method not allowed. Use POST.'];
            $this->output->set_status_header(405);
        } else if ($id === null) {
             $response = ['status' => 'error', 'message' => 'Blog ID is required'];
             $this->output->set_status_header(400);
        } else {
            // --- FIX: Call update_blog with id ---
            $success = $this->Blog_model->update_blog((int)$id, ['status' => 'draft']);
            if ($success) {
                $response = ['status' => 'success', 'message' => 'Blog post set to draft.'];
                $this->output->set_status_header(200);
            } else {
                $response = ['status' => 'error', 'message' => 'Blog post not found or already in draft.'];
                $this.output->set_status_header(404);
            }
        }
        
        $this->output
             ->set_content_type('application/json')
             ->set_output(json_encode($response));
    }
}