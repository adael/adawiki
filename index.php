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
define('ROOT', dirname(__FILE__) . DS);
define('WROOT', dirname($_SERVER['SCRIPT_NAME']));
define('LIBS', ROOT . 'libs' . DS);
define('VIEWS', ROOT . 'views' . DS);

define('DEBUG', 1);
define('POST', strcasecmp(getenv('REQUEST_METHOD'), 'post') == 0);
define('PAGES', ROOT . 'pages' . DS);
define('FILE_EXT', '.txt');
define('FLASHKEY', '__ADAFLASH');

// Enable error display
ini_set('display_errors', DEBUG ? 1 : 0);

// Disable magic quotes
ini_set('magic_quotes_gpc', 'Off');
ini_set('magic_quotes_runtime', 'Off');
ini_set('magic_quotes_sybase', 'Off');
#set_magic_quotes_runtime(0);

header('Content-Type: text/html; charset=utf-8');

function __autoload($class){
	if(is_file(LIBS . $class . '.php')){
		include LIBS . $class . '.php';
	}
}

class AdaWikiController{

	/**
	 * @var PageManager
	 */
	private $pm;

	function __construct(){
		$this->pm = new PageManager();
	}

	function index(){
		redirect('view/index');
	}

	function view($page = "index"){
		$content = $this->pm->getContent($page);
		if(!$content){
			redirect('edit/' . $page);
		}else{
			// Compruebo si markdown está disponible
			$markdown_path = dirname(__FILE__) . DS . "markdown" . DS . "markdown.php";
			$markdown = is_file($markdown_path);

			$content = file_get_contents($fpath);

			$patterns = array();

			// Enlace externo con alias
			$patterns['/\[(.+)\|(http\:\/\/[^\]]+)\]/iU'] = "<a href=\"\\2\" target='_blank'>\\1</a>";

			// Proceso los enlaces externos [http:://enlace]
			$patterns['/\[(http\:\/\/[^\]]+)\]/iU'] = "<a href=\"\\1\" target='_blank'>\\1</a>";

			// Enlace interno normal
			$patterns['/\[(?!http\:\/\/)([^\]]+)\]/eiU'] = "'<a href=\"index.php?r=view/\\1\" class=\"' . (\$this->pm->getSize('\\1') ? 'existe':'noexiste') . '\" title=\"Tama&ntilde;o: ' . \$this->pm->getSize('\\1') . '\">\\1</a>'";

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

			view::render('view', array('content' => &$content));
		}
	}

	function edit($page = "index"){
		if(POST){
			if($this->pm->setContent($page, $_POST['content'])){
				flash(array(true, "Se ha guardado $page correctamente"));
			}else{
				flash(array(false, "No se han podido guardar los datos"));
			}
			redirect("edit/$page");
		}else{
			view::render('edit', array('content' => $this->pm->getContent($page)));
		}
	}

	function manage(){
		view::render('manage', array('pages' => $this->pm->getAll()));
	}

	function rename($page){
		if(empty($page)){
			flash(array(false, "No se ha recibido la página"));
			redirect("manage");
		}

		if(POST && !empty($_POST['newname'])){
			$this->pm->rename($page, $_POST['newname']);
			flash(array(true, "Ok. <b>$page</b> => <b>$newname</b>"));
			redirect('manage');
		}

		view::render('rename', array('page' => $page));
	}

	function delete($page){
		if($this->pm->delete($page)){
			flash(array(true, "Ok. <b>$page</b> eliminada"));
		}else{
			flash(array(false, "No se ha podido eliminar <b>$page</b>"));
		}
		redirect('manage');
	}

	function help(){
		view::render('help');
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

function flash($val = null){
	if($val !== null){
		$_SESSION[FLASHKEY] = $val;
	}elseif(!empty($_SESSION[FLASHKEY])){
		$v = $_SESSION[FLASHKEY];
		unset($_SESSION[FLASHKEY]);
	}
}

function redirect($url){
	$path = WROOT . '/index.php?r=' . $url;
	header('location: ' . $path);
	die();
}

class View{

	private static $__last_view = null;

	static function render($__view__, $__data__ = array()){
		self::$__last_view = VIEWS . $__view__ . ".php";
		if(!empty($__data__) && is_array($__data__)){
			extract($__data__, EXTR_SKIP);
		}
		unset($__view__, $__data__); # exclude from current scope not desired vars
		if(is_file(self::$__last_view)){
			ob_start();
			include self::$__last_view;
			echo ob_get_clean();
		}else{
			echo "View not found: <b>" . self::$__last_view . "</b>";
		}
	}

}

class router{

	static function run($path){
		$parts = explode('/', $path);
		$parts = array_filter($parts);

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
		}else{
			throw new exception("No method found: $method");
		}
	}

}

// runing run method
router::run(isset($_GET['r']) ? $_GET['r'] : 'view/index');