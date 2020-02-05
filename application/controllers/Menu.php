<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Menu extends CI_Controller 
{
	public function __construct()
	{
		//panggil kontruksi pareen
		parent::__construct();
		//pungsi helper
		cek_login();
		
	}
	public function index()
	{
		//title
		$data['title'] = 'Menu Management';
		//ambil data dari sesion
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

		//query data menu
		$data['menu'] = $this->db->get('user_menu')->result_array();

		//membuat rules
		$this->form_validation->set_rules('menu','Menu','required');

		//validasi form
		if($this->form_validation->run() == false){

		//memanggil view
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('menu/index', $data);
		$this->load->view('templates/footer');
		}else{
			//jika berhasil
			$this->db->insert('user_menu', ['menu' => $this->input->post('menu')]);
			//arahkan ke controller menu
			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">New menu add</div>');
						redirect('menu');
		}

	}
	public function submenu()
	{
		//title
		$data['title'] = 'Submenu Management';
		//ambil data dari sesion
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		$this->load->model('Menu_model','menu');

		//ambil dri model submenu
		$data['subMenu'] = $this->menu->getSubMenu();
		$data['menu'] = $this->db->get('user_menu')->result_array();

		$this->form_validation->set_rules('title','Title','required');
		$this->form_validation->set_rules('menu_id','Menu','required');
		$this->form_validation->set_rules('url','URL','required');
		$this->form_validation->set_rules('icon','icon','required');

		if($this->form_validation->run() == false){

		//memanggil view
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('menu/submenu', $data);
		$this->load->view('templates/footer');
			
		}else {
			
			$data = [
					'title' =>$this->input->post('title'),
					'menu_id' =>$this->input->post('menu_id'),
					'url' =>$this->input->post('url'),
					'icon' =>$this->input->post('icon'),
					'is_active' =>$this->input->post('is_active')
			];
			//insert ke database
			$this->db->insert('user_sub_menu', $data);
			//redirect
			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">New sub menu add</div>');
				redirect('menu/submenu');
		}
	}

}