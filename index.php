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

session_start();

// Enable error display
ini_set('display_errors', 1);

// Disable magic quotes
ini_set('magic_quotes_gpc', 'Off');
ini_set('magic_quotes_runtime', 'Off');
ini_set('magic_quotes_sybase', 'Off');
#set_magic_quotes_runtime(0);

if(!isset($_SESSION["options"])){
	$_SESSION["options"] = array(
		'font-size' => 13
	);
}

if(isset($_GET["aumentarfuente"]))
	$_SESSION["options"]["font-size"] += 2;
elseif(isset($_GET["reducirfuente"]))
	$_SESSION["options"]["font-size"] -= 2;
elseif(isset($_GET["restablecerfuente"]))
	$_SESSION["options"]["font-size"] = 13;

define('PAGES', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR);
define('FILE_EXT', '.txt');

/**
 * debug function
 */
function prd(){
	echo "<pre style='border: 1px solid black; padding: 3px; font-size: 11px; font-family: courier new; background: #EEE; color: #000;'>";
	print_r(func_get_args());
	echo "</pre>";
	die();
}

class Adawiki{

	private $method;
	private $page;

	function _init(){
		$this->method = $this->_sget('m', 'ver');
		$this->page = $this->_sget('p', 'indice');

		ob_start();
		if(strpos($this->method, '_') === 0 || !method_exists($this, $this->method)){
			echo '<p>M&eacute;todo incorrecto</p>';
		}else{
			$method = $this->method;
			$this->$method();
		}
		$content = ob_get_clean();
		if(isset($_GET["print"]))
			printLayout($content);
		else
			layout($content, $this->method, $this->page);
	}

	function _sget($key, $rval=null){
		return isset($_GET[$key]) ? $_GET[$key] : $rval;
	}

	function _spost($key, $rval=null){
		return isset($_POST[$key]) ? $_POST[$key] : $rval;
	}

	function _filepath($pagename = null){
		if($pagename == null)
			$pagename = $this->page;
		return PAGES . base64_encode(substr($pagename, 0, 150)) . FILE_EXT;
	}

	function _filesize($pagename = null){
		$path = $this->_filepath($pagename);
		if(empty($path))
			return 0;
		return file_exists($path) ? $this->_format_bytes(filesize($path)) : 0;
	}

	function _format_bytes($bytes){
		$s = array('B', 'Kb', 'MB', 'GB', 'TB', 'PB');
		$e = floor(log($bytes) / log(1024));
		return sprintf('%.2f ' . $s[$e], ($bytes / pow(1024, floor($e))));
	}

	function editar(){
		$content = "";
		$fpath = $this->_filepath();
		if(file_exists($fpath)){
			$content = file_get_contents($fpath);
		}
		echo "<form method='post' action='index.php?m=guardar&p=$this->page'>";
		echo "<textarea class='page' rows='25' cols='80' name='content' onkeydown='insertTab(this, event);'>$content</textarea><br/>";
		echo "<input type='submit' value='Guardar'>";
		echo "</form>";
		?>
		<script>
			function insertTab(o, e) {
				var kC = e.keyCode ? e.keyCode : e.charCode ? e.charCode : e.which;
				if (kC == 9 && !e.shiftKey && !e.ctrlKey && !e.altKey) {
					var oS = o.scrollTop;
					if (o.setSelectionRange) {
						var sS = o.selectionStart;
						var sE = o.selectionEnd;
						o.value = o.value.substring(0, sS) + "\t" + o.value.substr(sE);
						o.setSelectionRange(sS + 1, sS + 1);
						o.focus();
					} else if (o.createTextRange) {
						document.selection.createRange().text = "\t";
						e.returnValue = false;
					}
					o.scrollTop = oS;
					if (e.preventDefault) {
						e.preventDefault();
					}
					return false;
				}
				return true;
			}
		</script>
		<?php
	}

	function ver(){
		$content = "";
		$fpath = $this->_filepath();
		if(!file_exists($fpath)){
			$this->editar();
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
			}

			if(is_callable('Markdown')){
				echo Markdown($content);
			}else{
				echo $content;
			}
		}
	}

	function guardar(){
		if(isset($_POST['content'])){
			$content = $this->_spost("content");
			if(!is_dir(PAGES))
				mkdir(PAGES);
			$fpath = $this->_filepath();
			file_put_contents($fpath, $content);
			printf("<p id='saving-msg' class='saving'>Ok. %s bytes guardados en $this->page</p>", filesize($fpath));
			echo "<script>setTimeout(function(){ $('#saving-msg').slideUp(); }, 3000);</script>";
		}
		$this->ver();
	}

	function eliminar(){
		$fpath = $this->_filepath();
		if(file_exists($fpath)){
			unlink($fpath);
			echo "<p>Ok. <b>$this->page</b> eliminado</p>";
		}else{
			echo "<p>No se ha encontrado <b>$this->page</b></p>";
		}
	}

	function listar(){
		if(is_dir(PAGES)){
			$pages = glob(PAGES . "*" . FILE_EXT);
			foreach($pages as $k => $v){
				$v = preg_replace('/^' . preg_quote(PAGES) . '/i', '', $v);
				$v = preg_replace('/' . preg_quote(FILE_EXT) . '$/i', '', $v);
				$v = base64_decode($v);
				$pages[$k] = $v;
			}
			natcasesort($pages);
		}else{
			$pages = array();
		}
	}

	function renombrar(){
		$newname = $this->_spost('newname', null);
		if($newname == null){
			echo "<form method='post' action='index.php?m=renombrar&p=$this->page'>";
			echo "<p>Antiguo nombre: <b>$this->page</b></p>";
			echo "<p>Nuevo nombre: <input name='newname' type='text' size='50' maxlength='150'> (Max. 150 caracteres)</p>";
			echo "<input type='submit' value='enviar'>";
			echo "</form>";
		}else{
			rename($this->_filepath(), $this->_filepath($newname));
			echo "<p>Ok. <b>$this->page</b> => <b>$newname</b></p>";
		}
	}

	function ayuda(){
		ayuda();
	}

}

function layout($content, $method, $page){
}

// End layout()

function printLayout($content){
	?>

		<?
	}

	function ayuda(){
		?>

	<?
}

// End ayuda();

function printCode($source){
	return '<table border="0" cellpadding="0" class="code"><tr><td>' . highlight_string($source, true) . '</td></tr></table>';
}

/* dark_messenger84 at yahoo dot co dot uk
  02-Sep-2007 10:31
  Here's an improved version of supremacy2k at gmail dot com's code. It's a small
  function that accepts either PHP syntax in plain text or from another script,
  and then parses it into an ordered list with syntax highlighting.
 */

function printPhpCode($source_code, $display_lines = false){

	if(is_array($source_code))
		return false;

	$source_code = explode("\n", str_replace(array("\r\n", "\r"), "\n", $source_code));
	$line_count = 1;
	$formatted_code = "";
	foreach($source_code as $code_line){
		$formatted_code .= '<tr>';

		if($display_lines)
			$formatted_code .= '<td style="padding-right: 8px; border-right: 1px dashed #999;">' . $line_count . '</td>';
		$line_count++;

		$formatted_code .= '<td style="padding-left:4px;">';
		if(preg_match('/<\?(php)?[^[:graph:]]/', $code_line)){
			$formatted_code .= str_replace(array('<code>', '</code>'), '', highlight_string($code_line, true));
		}else{
			$formatted_code .= preg_replace('/(&lt;\?php&nbsp;)+/', '', str_replace(array('<code>', '</code>'), '', highlight_string('<?php ' . $code_line, true)));
		}
		$formatted_code .= '</td></tr>';
	}

	return '<table border="0" cellpadding="0" class="code">' . $formatted_code . '</table>';
}

// End printCode

$wiki = new Adawiki();
$wiki->_init();
?>