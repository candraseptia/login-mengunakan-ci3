<?php 
function cek_login()
{
	//new istan ci baru ->panggil instansiasi ci nya
	$ci = get_instance(); // berpungsi unutuk memangil librari
	//cek sudah login atau belum
	if (!$ci->session->userdata('email')) {
		redirect('auth');
	}else {
		//cek role_id nya
		$role_id = $ci->session->userdata('role_id');
		$menu = $ci->uri->segment(1);

		//query tabel menu untk mndptkn menu_id
		$queryMenu = $ci->db->get_where('user_menu', ['menu' => $menu])->row_array();
		$menu_id = $queryMenu['id'];

		//query user akses nya
		$userAccess = $ci->db->get_where('user_access_menu', [
			'role_id' => $role_id,
			'menu_id' => $menu_id
		]);
		//cek akses
		if ($userAccess->num_rows() < 1) {
			redirect('auth/blocked');
		}
	}
	
}

function check_access($role_id, $menu_id)
{
	// $ci->db->get_where('user_access_menu', [
	// 	'role_id' => $role_id,
	// 	'menu_id' => $menu_id
	// ]);
	//panggil instance 
	$ci = get_instance();

	$ci->db->where('role_id', $role_id);
	$ci->db->where('menu_id', $menu_id);
	//query mencari ke user_access_menu
	$result = $ci->db->get('user_access_menu');

	if ($result->num_rows() > 0){
		return "checked='checked'";
	}
}


 ?>