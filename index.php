<?

/*
  License:
  Adawiki
  Copyright (c) 2010-2011 Carlos Gant
  <http://carlosgant.blogspot.com/>
  All rights reserved.

  Redistribution and use in source and binary forms, with or without
  modification, are permitted provided that the following conditions are
  met:

 * Redistributions of source code must retain the above copyright notice,
  this list of conditions and the following disclaimer.

 * Redistributions in binary form must reproduce the above copyright
  notice, this list of conditions and the following disclaimer in the
  documentation and/or other materials provided with the distribution.

 * Neither the name "Adawiki" nor the names of its contributors may
  be used to endorse or promote products derived from this software
  without specific prior written permission.

  This software is provided by the copyright holders and contributors "as
  is" and any express or implied warranties, including, but not limited
  to, the implied warranties of merchantability and fitness for a
  particular purpose are disclaimed. In no event shall the copyright owner
  or contributors be liable for any direct, indirect, incidental, special,
  exemplary, or consequential damages (including, but not limited to,
  procurement of substitute goods or services; loss of use, data, or
  profits; or business interruption) however caused and on any theory of
  liability, whether in contract, strict liability, or tort (including
  negligence or otherwise) arising in any way out of the use of this
  software, even if advised of the possibility of such damage.
 */

/*
  Key Features:
 * No installation (Just copy this file)
 * No configuration needed
 * Ready to use & small
 * HTML Compatible
 * Markdown syntax available
 */

define('DS', DIRECTORY_SEPARATOR);
define('DEBUG', 1);
define('POST', strcasecmp($_SERVER['REQUEST_METHOD'], 'POST'));
define('ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('PAGES', ROOT . 'pages' . DIRECTORY_SEPARATOR);
define('FILE_EXT', '.txt');


// Enable error display
ini_set('display_errors', DEBUG ? 1 : 0);

// Disable magic quotes
ini_set('magic_quotes_gpc', 'Off');
ini_set('magic_quotes_runtime', 'Off');
ini_set('magic_quotes_sybase', 'Off');
#set_magic_quotes_runtime(0);

class AdaWikiController{

	function index(){
		$this->view();
	}

	function view($page = "index"){
		$fpath = $this->_filepath($page);
		if(!is_file($fpath)){
			redirect('edit/' . $page);
		}else{
			// Compruebo si markdown está disponible
			$markdown_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . "markdown" . DIRECTORY_SEPARATOR . "markdown.php";
			$markdown = is_file($markdown_path);

			$content = file_get_contents($fpath);

			$patterns = array();

			// Proceso las etiquetas code
			$patterns['/\[code\](.*)\[\/code\]/iseU'] = "printCode(trim('\\1'))";

			// Proceso las etiquetas code php
			//$patterns['/\[code php\](.*)\[\/code\]/iseU'] = "'<span class=code>'.highlight_string('\\1', true).'</span>'";
			$patterns['/\[code php\](.*)\[\/code\]/iseU'] = "printPhpCode(trim('\\1'))";

			// Etiquetas sha1
			$patterns['/\[sha1\](.*)\[\/sha1\]/iseU'] = "sha1('\\1')";

			// Enlace externo con alias
			$patterns['/\[(.+)\|(http\:\/\/[^\]]+)\]/iU'] = "<a href=\"\\2\" target='_blank'>\\1</a>";

			// Proceso los enlaces externos [http:://enlace]
			$patterns['/\[(http\:\/\/[^\]]+)\]/iU'] = "<a href=\"\\1\" target='_blank'>\\1</a>";

			// Enlace interno normal
			$patterns['/\[(?!http\:\/\/)([^\]]+)\]/eiU'] = "'<a href=\"index.php?m=ver&p=\\1\" class=\"' . (\$this->_filesize('\\1') ? 'existe':'noexiste') . '\" title=\"Tama&ntilde;o: ' . \$this->_filesize('\\1') . '\">\\1</a>'";

			if(!$markdown){
				// Creo los <br /> para los saltos de linea
				$patterns['/([^\>\]])[\n\r]+$/im'] = '\\1<br />';

				// Proceso las listas multiple (con truco)
				$patterns['/^\*\*\*(.*)/m'] = '<div class=indent2><li class=indent>\\1</li></div>';

				// Proceso las listas multiple (con truco)
				$patterns['/^\*\*(.*)/m'] = '<div class=indent><li class=indent>\\1</li></div>';

				// Proceso las listas (con truco)
				$patterns['/^\*(.*)/m'] = '<li class=indent>\\1</li>';
			}

			foreach($patterns as $pat => $rep){
				$content = preg_replace($pat, $rep, $content);
			}

			// Uso markdown si está disponible, para simplificarme la vida
			if($markdown){
				include($markdown_path);
				if(is_callable('Markdown')){
					$content = Markdown($content);
				}
			}

			$this->_render('view', array('content' => &$content));
		}
	}

	function edit($page = "index"){
		if(POST){
			if(!is_dir(PAGES)){
				mkdir(PAGES);
			}
			$fpath = $this->_filepath($page);
			file_put_contents($fpath, $content);
			flash('msg', array(true, sprintf("Ok. %s bytes guardados en $this->page", hfilesize($fpath))));
			redirect("edit/$page");
		}
		$content = "";
		$fpath = $this->_filepath();
		if(file_exists($fpath)){
			$content = file_get_contents($fpath);
		}
	}

	function manage(){
		if(is_dir(PAGES)){
			$pages = glob(PAGES . "*" . FILE_EXT);
			foreach($pages as $k => $v){
				$pages[$k] = $this->_filename($v);
			}
			natcasesort($pages);
		}else{
			$pages = array();
		}
	}

	function rename($page){
		if(empty($page)){
			redirect("manage", "No se ha recibido la página", "error");
		}
		if(!empty($_POST['newname'])){
			rename($this->_filepath($page), $this->_filepath($newname));
			redirect("manage", "Ok. <b>$page</b> => <b>$newname</b>", "success");
		}
	}

	function delete(){
		$fpath = $this->_filepath();
		if(file_exists($fpath)){
			unlink($fpath);
			echo "<p>Ok. <b>$this->page</b> eliminado</p>";
		}else{
			echo "<p>No se ha encontrado <b>$this->page</b></p>";
		}
	}

	function listar(){

	}

	function renombrar(){

	}

	function ayuda(){
		ayuda();
	}

	private function _render($__view__, $__data__ = array()){
		$this->$__last_view = ROOT . "views" . DIRECTORY_SEPARATOR . $__view__ . ".php";
		if(!empty($__data__) && is_array($__data__)){
			extract($__data__, EXTR_SKIP);
		}
		unset($__view__, $__data__); # exclude from current scope not desired vars
		if(is_file($this->$__last_view)){
			ob_start();
			include $this->$__last_view;
			return ob_get_clean();
		}else{
			return "View not found: <b>$this->$__last_view</b>";
		}
	}

	private function _filepath($mame){
		return PAGES . base64_encode(substr($name, 0, 150)) . FILE_EXT;
	}

	private function _filename($path){
		$path = preg_replace('/^' . preg_quote(PAGES) . '/i', '', $path);
		$path = preg_replace('/' . preg_quote(FILE_EXT) . '$/i', '', $path);
		return base64_decode($path);
	}

	function _filesize($pagename = null){
		return hfilesize($this->_filepath($pagename));
	}

}

/**
 * debug function
 */
function prd(){
	echo "<pre style='border: 1px solid black; padding: 3px; font-size: 11px; font-family: courier new; background: #EEE; color: #000;'>";
	print_r(func_get_args());
	echo "</pre>";
	die();
}

function hfilesize($path){
	$size = $path && is_file($path) ? filesize($path) : 0;
	return format_bytes($size);
}

function format_bytes($bytes){
	$s = array('B', 'Kb', 'MB', 'GB', 'TB', 'PB');
	$e = floor(log($bytes) / log(1024));
	return sprintf('%.2f ' . $s[$e], ($bytes / pow(1024, floor($e))));
}

function flash($key, $val = null){
	if($val !== null){
		$_SESSION[$key] = $val;
	}elseif(!empty($_SESSION[$key])){
		$v = $_SESSION[$key];
		unset($_SESSION[$key]);
	}
}

function redirect($url){
	header('location: index.php/' . $url);
	die();
}

function run(){
	$parts = explode('/', $_GET['PATH_INFO']);
	if(!empty($parts)){
		$method = array_shift($parts);
		$args = $parts;
	}else{
		$method = "index";
		$args = array();
	}

	$c = new AdaWikiController();
	if(is_callable(array($c, $method))){
		call_user_func_array(array($c, $method), $args);
	}
}

// runing run method
run();