<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller 
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
		$data['title'] = 'Profil Saya';
		//ambil data dari sesion
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		//memanggil view
		$this->load->view('templates/header', $data);
		$this->load->view('templates/sidebar', $data);
		$this->load->view('templates/topbar', $data);
		$this->load->view('user/index', $data);
		$this->load->view('templates/footer');
	}

	//metod edit
	public function edit()
	{
		//title
		$data['title'] = 'Edit Profil';
		//ambil data dari sesion
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();
		
		//rules
		$this->form_validation->set_rules('name', 'Full Name', 'required|trim');

		//validasi
		if ($this->form_validation->run() == false) {
			//memanggil view
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('user/edit', $data);
			$this->load->view('templates/footer');			
			
		} else {
			//jika berhasil
			$name = $this->input->post('name');
			$email = $this->input->post('email');

			//cek jk ada gmbar yg akan d upload
			$upload_image = $_FILES['image']['name'];

			if ($upload_image) {
				$config['allowed_types'] = 'gif|jpg|png';
				$config['max_size']     = '2048';
				$config['upload_path'] = './assets/img/profil/';

				$this->load->library('upload', $config);

				//upload
				if ($this->upload->do_upload('image')) {
					//cek gambar lama
					$old_image = $data['user']['image'];
					if ($old_image != 'default.jpg') {
						unlink(FCPATH . 'assets/img/profil/' . $old_image);
					}
					//jk brhasil
					$new_image = $this->upload->data('file_name');
					$this->db->set('image', $new_image);
				} else {
					//jk gagal
					echo $this->upload->display_errors();
				}
			}

			$this->db->set('name', $name);
			$this->db->where('email', $email);
			//query
			$this->db->update('user');

			//flash
			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Profil Kamu Berhasil Diubah!!!
				</div>');
			redirect('user');
		}
	}

	//metod ubah password
	public function changePassword()
	{
		//title
		$data['title'] = 'Change Password';
		//ambil data dari sesion
		$data['user'] = $this->db->get_where('user', ['email' => $this->session->userdata('email')])->row_array();

		//set rules
		$this->form_validation->set_rules('current_password', 'Current Password', 'required|trim');
		$this->form_validation->set_rules('new_password1', 'New Password', 'required|trim|min_length[3]|matches[new_password2]');
		$this->form_validation->set_rules('new_password2', 'Confirm New Password', 'required|trim|min_length[3]|matches[new_password1]');
		//validasi
		if ($this->form_validation->run() == false) {
			//memanggil view
			$this->load->view('templates/header', $data);
			$this->load->view('templates/sidebar', $data);
			$this->load->view('templates/topbar', $data);
			$this->load->view('user/changepassword', $data);
			$this->load->view('templates/footer');						
		
		} else {
			//cek curren password sama dengan yang ad d database
			$current_password = $this->input->post('current_password');

			$new_password = $this->input->post('new_password1');
			//cek
			if (!password_verify($current_password, $data['user']['password'])) {
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Password salah!!!!</div>');
				redirect('user/changepassword');
			} else {
				//jk paswoed benar
				if ($current_password == $new_password) {
					$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Password tidak boleh sama!</div>');
					redirect('user/changepassword');
				} else {
					//password sudh ok
					$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

					$this->db->set('password', $password_hash);
					$this->db->where('email', $this->session->userdata('email'));
					//ubh passwordnya
					$this->db->update('user');

					$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Password Change</div>');
					redirect('user/changepassword');
				}
			}

		}

	}
}