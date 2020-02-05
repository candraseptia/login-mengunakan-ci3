<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {
	//melelkuakn valdasi pada form yang harus di lakukan,panggil dulu library foem validasinya
	public function __construct(){
		parent::__construct(); // memanggil metod cntructor yang ada di CI Controler
		$this->load->library('form_validation');

	}
	
	public function index()
	{
		//cek sessio login
		if ($this->session->userdata('email')){
			redirect('user');
		}

		$this->form_validation->set_rules('email','Email', 'trim|required|valid_email');
		$this->form_validation->set_rules('password','Password', 'trim|required');
		//validasi login
		if ($this->form_validation->run() == false) {
			$data['title'] = 'Login Page';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/login');
			$this->load->view('templates/auth_footer');
		}else {
			//validasi succes
			$this->_login();
		}
		
	}

	private function _login(){
		$email = $this->input->post('email');
		$password = $this->input->post('password');
	
		//query ke database mencari user yang emailnya sesuai dengan yang di tulis
		$user = $this->db->get_where('user', ['email' => $email])->row_array();
		//cek jika ada user benar
		if ($user) {
			// jika usernya aktif
			if ($user['is_active'] == 1) {
				//cek password
				if (password_verify($password, $user['password'])) {
					$data = [
						'email'=> $user['email'],
						'role_id' => $user['role_id'] //untuk menentukan menu nya
					];
					$this->session->set_userdata($data);
					//cek role nya apa
					//arahkan ke controler yang di inginkan 
					if ($user['role_id'] == 1) {
						redirect('admin');
					}else {
					redirect('user');
					}
				}else {
						$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">password salah!!!</div>');
						redirect('auth');
				}

			}else{
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">email belum diaktifasi!!!</div>');
				redirect('auth');
			}

		}else{
			// usernya tidak ada
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">email belum terdaftar!!!</div>');
			redirect('auth');
			}

		}

	//metod registrasi
	public function registration(){

		//cek sessio login
		if ($this->session->userdata('email')){
			redirect('user');
		}

		//memberikan rules
		$this->form_validation->set_rules('name','Name','required|trim');
		$this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[user.email]', [
				'is_unique' => 'This email has ready!!!'
		]);
		$this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[3]|matches[password2]', 
			[
				'matches' => 'Password dont match!!!',
				'min_length' => 'Password too short!!!'
			]);
		$this->form_validation->set_rules('password2','Password','required|trim|matches[password1]');

		if ($this->form_validation->run() == false) {
			//judul halaman
			$data['title'] = 'user registration';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/registration');
			$this->load->view('templates/auth_footer');
		}else{
			$email = $this->input->post('email', true);
			//insert data ke tabel register
			$data = [

				'name' => htmlspecialchars($this->input->post('name',true)),
				'email' => htmlspecialchars($email),
				'image' => 'default.jpg',
				'password' => password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
				'role_id' => 2,
				'is_active' => 0,
				'date_created' => time()
			];

			//siapkan token
			$token = base64_encode(random_bytes(32));
			//siapkan user token
			$user_token = [
				'email' => $email,
				'token' => $token,
				'date_created' => time()
			];

			$this->db->insert('user', $data);
			//inser ke tabel user_token
			$this->db->insert('user_token', $user_token);

			//kirim email ke email rgistrasi
			//fitur kirim email
			$this->_sendEmail($token, 'verify');
			//pesan flash data sebelum ridairect
			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Berhasil registrasi, Mohon aktivasi akun anda..
				</div>');
			redirect('auth');
		}
		
	}

	private function _sendEmail($token, $type)
	{
		//cofigurasi email
		$config = [
			'protocol'	=> 'smtp',
			'smtp_host' => 'ssl://smtp.googlemail.com',
			'smtp_user'	=> 'lagidan006@gmail.com',
			'smtp_pass'	=> 'cobalagi1205',
			'smtp_port' => 465,
			'mailtype'	=> 'html',
			'charset'	=> 'utf-8',
			'newline'	=> "\r\n"
		];

		//panggil library email di ci
		$this->email->initialize($config);

		//siapkan emailnya
		$this->email->from('lagidan006@gmail.com', 'Info Lalu Lintas');
		$this->email->to($this->input->post('email'));

		if ($type == 'verify') {

			$this->email->subject('Verifiksi akun');
			$this->email->message('Klik untuk verifikasi akun : <a href="'. base_url() . 'auth/verify?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '">Aktivasi</a>');
		} else if($type == 'forgot') {
			$this->email->subject('Reset Password');
			$this->email->message('Klik untuk link untuk reset password : <a href="'. base_url() . 'auth/resetpassword?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '">Reset Password</a>');
		}

		if ($this->email->send()) {
			return true;
		} else {
			echo $this->email->print_debugger();
			die;
		}
	}


	public function verify()
	{
		//ambil dta email dan token
		$email = $this->input->get('email');
		$token = $this->input->get('token');

		//ambil user berdasarkn email
		$user = $this->db->get_where('user', ['email' => $email])->row_array();

		//cekk
		if($user) {
			//jk berhasil
			$user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();

			if ($user_token) { 
				//cek tgl kdalaursa aktivasi
				if(time() - $user_token['date_created'] < (60*60)) {
					$this->db->set('is_active', 1);
					$this->db->where('email', $email);
					//jk bener update tabel usernya	
					$this->db->update('user');

					//hapus user tokennya
					$this->db->delete('user_token', ['email' => $email]);

					$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">'. $email .' sudah aktif, silahkan login.</div>');
						redirect('auth');

				} else {

					//hapus akun kadaluarsa
					$this->db->delete('user', ['email' => $email]);
					$this->db->delete('user_token', ['email' => $email]);

					$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Aktivasi akun Gaga, Token kadaluarsa.</div>');
					redirect('auth');
				}

			} else {
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Aktivasi akun Gaga, Token salah.</div>');
				redirect('auth');
			}

		} else {
			//jk tidk ada
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Aktivasi akun Gagal!!!, Email salah.
				</div>');
				redirect('auth');
		}
	}


	public function logout()
	{
		//membersihkn session dan mengembalikn ke halamn login
		$this->session->unset_userdata('email');
		$this->session->unset_userdata('role_id');

		$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Berhasil Keluar!!!
				</div>');
			redirect('auth');
	}

	public function blocked()
	{
		$this->load->view('auth/blocked');
	}

	public function forgotPassword()
	{
		//set rulesya
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
		//validasi
		if ($this->form_validation->run() == false) {
			
			$data['title'] = 'Forgot Password';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/forgot-password');
			$this->load->view('templates/auth_footer');
		} else {
			//jk berhasil
			$email = $this->input->post('email');
			//cek emaild db
			$user = $this->db->get_where('user', ['email' => $email, 'is_active' => 1])->row_array();

			if ($user) {
				$token = base64_encode(random_bytes(32));
				$user_token = [
					'email' => $email,
					'token' => $token,
					'date_created' => time()
				];

				//insert ke tabel 
				$this->db->insert('user_token', $user_token);
				$this->_sendEmail($token, 'forgot');

				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Mohon cek email untuk reset password.</div>');
				redirect('auth/forgotpassword');


			} else {
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Email belum terdaftar atau belum aktif.</div>');
				redirect('auth/forgotpassword');
			}
		}
	}

	public function resetPassword()
	{
		$email = $this->input->get('email');
		$token = $this->input->get('token');

		//cek ke db
		$user = $this->db->get_where('user', ['email' => $email])->row_array();

		if($user) {
			//jk email ada
			$user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
			if ($user_token) {
				//set session supaya server saja yg tau
				$this->session->set_userdata('reset_email', $email);
				$this->changePassword();

			} else{
				$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Reset password gagal, token salah.</div>');
				redirect('auth');
			}
		} else{
			$this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Reset password gagal, email salah.</div>');
				redirect('auth');
		}
	}

	public function changePassword()
	{
		if(!$this->session->userdata('reset_email')) {
			redirect('auth');
		}

		$this->form_validation->set_rules('password1', 'Password', 'trim|required|min_length[4]|matches[password2]');
		$this->form_validation->set_rules('password2', 'Password', 'trim|required|min_length[4]|matches[password1]');

		if ($this->form_validation->run() == false){
			$data['title'] = 'Change Password';
			$this->load->view('templates/auth_header', $data);
			$this->load->view('auth/change-password');
			$this->load->view('templates/auth_footer');
			
		} else{
			//jk berhasil
			//enkrifsi password
			$password = password_hash($this->input->post('password1'), PASSWORD_DEFAULT);
			$email = $this->session->userdata('reset_email');

			$this->db->set('password', $password);
			$this->db->where('email', $email);
			$this->db->update('user');

			//hapus session
			$this->session->unset_userdata('reset_email');

			$this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">password telah berubah, silahkan loin.</div>');
				redirect('auth');
		}
	}
}
