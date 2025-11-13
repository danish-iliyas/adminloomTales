<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UploadImage extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(['url', 'form']);
        $this->load->library('upload');
    }

    public function index() 
    {
        if (!isset($_FILES['image'])) {
            echo json_encode(["error" => "No image received"]);
            return;
        }

        // Upload directory
        $uploadPath = './uploads/images/blog/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Upload config
        $config = [
            'upload_path'   => $uploadPath,
            'allowed_types' => 'jpg|jpeg|png|gif|webp',
            'encrypt_name'  => TRUE,
            'max_size'      => 5000
        ];

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('image')) {
            echo json_encode(["error" => strip_tags($this->upload->display_errors())]);
            return;
        }

        // File Uploaded Successfully
        $data = $this->upload->data();
        $fileUrl = base_url('uploads/images/blog/' . $data['file_name']);

        // Return format CKEditor needs:
        echo json_encode([
            "url" => $fileUrl
        ]);
    }
}
