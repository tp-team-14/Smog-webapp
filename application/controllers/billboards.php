<?php

class Billboards extends CI_Controller
{
	public function index()
	{
		$this->load->view('index_billboard');
	}

	public function show()
	{
		$this->load->view('show_billboard');
	}

	public function get_ulovky()
	{
		$this->load->model('Ulovok_model', 'model');
		$result = $this->model->get_ulovky();

		$json = array();
		foreach ($result as $row)
		{
			$json[] = $row;
		}

		echo json_encode($json);
	}

	public function add()
	{
		// called by ajax
		if (!empty($_FILES["photo"]))
		{
			$folder = __DIR__. "/../../assets/pics";
			$name   = $this->_get_filename($folder, $_FILES["photo"]["name"]);

			// vytvor rekurzivne dany folder ak neexistuje
			if (!file_exists($folder))
			{
				mkdir($folder, 0777, true);
			}

			if (!is_writable($folder))
			{
				echo "Chyba: Priecinok nieje zapisovatelny<br>";
				die;
			}

			$lat = &$_POST["lat"];
			$lng = &$_POST["lng"];
			if(empty($lat) || empty($lng)) {
				echo "Chyba: Neboli zadané GPS suradnice<br>";
				die;
			}
			$suradnice = "POINT($lat, $lng)";
			
			// move z tmp foldra
			if (!move_uploaded_file($_FILES["photo"]["tmp_name"], "$folder/$name"))
			{		
				echo "Chyba: Nepodarilo sa uploadovať billboard na server<br>";
				die;
			}

			/*if (chmod("$folder/$name", 0664))
			{
				echo "Práva pre uploadovaný billboard boli úspešne aktualizované<br>";
			}
			else
			{
				echo "Nepodarilo sa aktualizovať práva pre uploadovaný billboard<br>";
			}*/


			// vlozenie do databazy prostrednictvom modelu
			$this->load->model('Ulovok_model', 'model');
			$this->model->save_ulovok(1, 1, $suradnice, $name, '');
			
			$this->load->view('uploaded_billboard');
		}
		else
		{
			$this->load->view('add_billboard');
		}
	}

	// ------------------
	// private functions
	// ------------------

	private function _clean_name(&$name)
	{
		$table = array(
			" " => "-",
			"\xc3\xa1" => "a", "\xc3\xa4" => "a", "\xc4\x8d" => "c", "\xc4\x8f" => "d", "\xc3\xa9" => "e", "\xc4\x9b" => "e", "\xc3\xad" => "i", "\xc4\xbe" => "l", 
			"\xc4\xba" => "l", "\xc5\x88" => "n", "\xc3\xb3" => "o", "\xc3\xb6" => "o", "\xc5\x91" => "o", "\xc3\xb4" => "o", "\xc5\x99" => "r", "\xc5\x95" => "r", 
			"\xc5\xa1" => "s", "\xc5\xa5" => "t", "\xc3\xba" => "u", "\xc5\xaf" => "u", "\xc3\xbc" => "u", "\xc5\xb1" => "u", "\xc3\xbd" => "y", "\xc5\xbe" => "z"
		);

		$name = mb_strtolower($name, "utf-8");
		$name = strtr($name, $table);

		return preg_replace("/[^A-Za-z0-9\-\_\.]/", "", $name);
	}

	private function _get_filename(&$folder, &$name)
	{
		$name = $this->_clean_name($name);
		$ext  = pathinfo($name, PATHINFO_EXTENSION);
		$base = basename($name, ".$ext");

		for ($i = 1; file_exists("$folder/$name"); $i++)
		{
			$name = $base.($i < 2 ? "" : "_$i").".$ext";
		}

		return $name;
	}
}

?>