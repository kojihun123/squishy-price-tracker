<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product extends CI_Controller {

  public function index()
  {
    $this->load->model('Product_model');
    $data['products'] = $this->Product_model->get_all();
    $this->load->view('products', $data);
  }
}