<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller 
{
	//pungsi konstruktor
	public function __construct()
	{
		//panggil kontruksi pareen
		parent::__construct();
		//pungsi helper
		cek_login();
		// //cek session
		// if(!$this->session->userdata('email')){
		// 	redirect('auth');
		
	}

	public function index()
	{
		//title
		$data['title'] = 'Dasboard';
		//ambil data dari sesion
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		//memanggil view
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('admin/index', $data);
		$this->load->view('templates/footer');
	}

	//menampilkn semua role
	public function role()
	{
		//title
		$data['title'] = 'Role';
		//ambil data dari sesion
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

		//query role
		$data['role'] = $this->db->get('user_role')->result_array();
		//memanggil view
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('admin/role', $data);
		$this->load->view('templates/footer');
	}

	//menampilkn role dri role_id
	public function roleAccess($role_id)
	{
		//title
		$data['title'] = 'Role Access';
		//ambil data dari sesion
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

		//query role
		$data['role'] = $this->db->get_where('user_role', ['id' => $role_id])->row_array();

		//tidak menampilkn semua menu
		$this->db->where('id !=', 1);
		//query semua data menu
		$data['menu'] = $this->db->get('user_menu')->result_array();

		//memanggil view
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('admin/role-access', $data);
		$this->load->view('templates/footer');
	}


	public function changeAccess()
	{
		//ambil data dri ajak
		$menu_id = $this->input->post('menuId');
		$role_id = $this->input->post('roleId');

		//siapin datanya
		$data = [
			'role_id' => $role_id,
			'menu_id' => $menu_id
		];
		//query berdasarkan data
		$result = $this->db->get_where('user_access_menu', $data);

		//cek
		if ($result->num_rows() < 1) {
			$this->db->insert('user_access_menu', $data);
		} else {
			$this->db->delete('user_access_menu', $data);
		}

		$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Access Change!</div>');
	}

}