<?
/**
  Adawiki 1.0
  By Adael - www.adael.es

  Key Features:
 * No installation (Just copy this file)
 * No configuration needed
 * Ready to use & small
 * HTML Compatible
 * */
session_start();

// Enable error display
ini_set('display_errors', 1);

// Disable magic quotes
ini_set('magic_quotes_gpc', 'Off');
ini_set('magic_quotes_runtime', 'Off');
ini_set('magic_quotes_sybase', 'Off');
#set_magic_quotes_runtime(0);

if(!isset($_SESSION["options"])) {
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

define('BASE_PATH', dirname(__FILE__) . '/pages/');
define('FILE_EXT', '.txt');

class Adawiki {

    private $method;
    private $page;

    function _init() {
        $this->method = $this->_sget('m', 'ver');
        $this->page = $this->_sget('p', 'indice');

        ob_start();
        if(strpos($this->method, '_') === 0 || !method_exists($this, $this->method)) {
            echo '<p>M&eacute;todo incorrecto</p>';
        }else {
            $method = $this->method;
            $this->$method();
        }
        $content = ob_get_clean();
        if(isset($_GET["print"]))
            printLayout($content);
        else
            layout($content, $this->method, $this->page);
    }

    function _sget($key, $rval=null) {
        return isset($_GET[$key]) ? $_GET[$key] : $rval;
    }

    function _spost($key, $rval=null) {
        return isset($_POST[$key]) ? $_POST[$key] : $rval;
    }

    function _filepath($pagename = null) {
        if($pagename == null)
            $pagename = $this->page;
        return BASE_PATH . base64_encode(substr($pagename, 0, 150)) . FILE_EXT;
    }

    function _filesize($pagename = null) {
        $path = $this->_filepath($pagename);
        if(empty($path))
            return 0;
        return file_exists($path) ? $this->_format_bytes(filesize($path)) : 0;
    }

    function _format_bytes($bytes) {
        $s = array('B', 'Kb', 'MB', 'GB', 'TB', 'PB');
        $e = floor(log($bytes) / log(1024));
        return sprintf('%.2f ' . $s[$e], ($bytes / pow(1024, floor($e))));
    }

    function editar() {
        $content = "";
        $fpath = $this->_filepath();
        if(file_exists($fpath)) {
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

    function ver() {
        $content = "";
        $fpath = $this->_filepath();
        if(!file_exists($fpath)) {
            $this->editar();
        }else {

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
            $patterns['/\[(?!http\:\/\/)([^\]]+)\]/eiU'] = "'<a href=\"index.php?m=ver&p=\\1\" class=\"' . (\$this->_filesize('\\1') ? 'existe':'noexiste') . '\" title=\"Tamaño: ' . \$this->_filesize('\\1') . '\">\\1</a>'";

            if(!$markdown) {
                // Creo los <br /> para los saltos de linea
                $patterns['/([^\>\]])[\n\r]+$/im'] = '\\1<br />';

                // Proceso las listas multiple (con truco)
                $patterns['/^\*\*\*(.*)/m'] = '<div class=indent2><li class=indent>\\1</li></div>';

                // Proceso las listas multiple (con truco)
                $patterns['/^\*\*(.*)/m'] = '<div class=indent><li class=indent>\\1</li></div>';

                // Proceso las listas (con truco)
                $patterns['/^\*(.*)/m'] = '<li class=indent>\\1</li>';
            }

            foreach($patterns as $pat => $rep) {
                $content = preg_replace($pat, $rep, $content);
            }

            // Uso markdown si está disponible, para simplificarme la vida
            if($markdown) {
                include($markdown_path);
            }

            if(is_callable('Markdown')) {
                echo Markdown($content);
            }else {
                echo $content;
            }
        }
    }

    function guardar() {
        if(isset($_POST['content'])) {
            $content = $this->_spost("content");
            if(!is_dir(BASE_PATH))
                mkdir(BASE_PATH);
            $fpath = $this->_filepath();
            file_put_contents($fpath, $content);
            printf("<p class='saving'>Ok. %s bytes guardados en $this->page</p>", filesize($fpath));
        }
        $this->ver();
    }

    function eliminar() {
        $fpath = $this->_filepath();
        if(file_exists($fpath)) {
            unlink($fpath);
            echo "<p>Ok. <b>$this->page</b> eliminado</p>";
        }else {
            echo "<p>No se ha encontrado <b>$this->page</b></p>";
        }
    }

    function listar() {
        $olddir = getcwd();
        chdir(BASE_PATH);
        $pages = glob('*' . FILE_EXT);

        foreach($pages as $k => $v) {
            $pos = strrpos($v, '.');
            if($pos === false)
                continue;
            $v = substr($v, 0, $pos);
            $v = base64_decode($v);
            $pages[$k] = $v;
        }

        natcasesort($pages);

        echo "<div class='page'>";
        echo "<h1>Listado de p&aacute;ginas</h1>";
        echo "<ul>";
        foreach($pages as $v) {
            echo "<li>";
            echo "[<a href='index.php?m=ver&p=$v'>Ver</a>] ";
            echo "[<a href='index.php?m=editar&p=$v'>Editar</a>] ";
            echo "[<a href='index.php?m=renombrar&p=$v'>Renombrar</a>] ";
            echo "[<a href='index.php?m=eliminar&p=$v' onclick='return confirm(\"¿Seguro que desea eliminar esta p&aacute;gina?\");'>Eliminar</a>] ";
            echo " - <a href='index.php?m=ver&p=$v'>$v</a>";
            echo "</li>";
        }
        echo "</ul></div>";
        chdir($olddir);
    }

    function renombrar() {
        $newname = $this->_spost('newname', null);
        if($newname == null) {
            echo "<form method='post' action='index.php?m=renombrar&p=$this->page'>";
            echo "<p>Antiguo nombre: <b>$this->page</b></p>";
            echo "<p>Nuevo nombre: <input name='newname' type='text' size='50' maxlength='150'> (Max. 150 caracteres)</p>";
            echo "<input type='submit' value='enviar'>";
            echo "</form>";
        }else {
            rename($this->_filepath(), $this->_filepath($newname));
            echo "<p>Ok. <b>$this->page</b> => <b>$newname</b></p>";
        }
    }

    function ayuda() {
        ayuda();
    }

}

function layout($content, $method, $page) {
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
        <html>
            <head>
                <title>Wiki</title>
                <style type='text/css'>
                    body {
                        font-family: "arial", "sans-serif";
                        font-size: <?= $_SESSION["options"]["font-size"] ?>px;
                        margin: 0; padding: 0;
                    }
                    #page {
                        display: block; clear: both;
                        margin: 0 auto;
                        padding: 0 16px;
                    }
                    #page #header {
                        display: block; clear: both;
                    }
                    #page #body {
                        display: block; clear: both;
                        border: 1px solid black;
                        margin: 0; padding: 8px;
                    }
                    #page #body a.existe {
                        color: blue;
                    }
                    #page #body a.noexiste {
                        color: gray;
                    }
                    #page #body h1 {
                        font-size: 21px;
                        margin: 16px 0 4px 0;
                        padding: 4px;
                        background: #f1f1f1;
                    }
                    #page #body h2 {
                        font-size: 19px;
                        margin: 8px 0 4px 0;
                    }
                    #page #body h3 {
                        font-size: 15px;
                        margin: 8px 0 4px 0;
                    }
                    #page #footer {
                        display: block; clear: both;
                        padding: 8px;
                        text-align: right;
                        background: #444; color: #FFF;
                        border: 1px solid black; border-top: 0;
                    }
                    #page #footer a{color: yellow;}
                    #page #header h1 {color: gray;}
                    #page #header a.tab {
                        display: inline-block;
                        padding: 0 8px;
                        margin: 0 0 0 8px;
                        line-height: 24px;
                        height: 24px;
                        font-size: <?= ($_SESSION["options"]["font-size"] + 2) ?>px;
                        background: #F7C604;
                        color: black;
                        cursor: pointer;
                        text-decoration: none;
                    }
                    #page #header a.current { background: #FEF3C7; color: black;}
                    #page textarea.page { border: 1px solid #999; font: 1em monospace; display: block; width: 100%; background: #f1f1f1; color: darkblue; }
                    #page .saving {
                        display: block; margin: -8px -8px 4px -8px; padding: 2px; text-align: left;
                        font-size: 11px; color: #339900; background: #E8FBE9;
                        border-bottom: 1px solid #444;
                    }
                    .indent { margin-left: 25px; } .indent2 { margin-left: 50px; }
                    code,.code { display: block; padding:4px; border:1px solid #999; background: #EEE; font-size: 13px; font-family: 'courier new' monospace; }
                    .red {color: red;} .blue {color: blue;} .green {color: green;} .gray {color: gray;}
                    .right {text-align: right;} .justify {text-align: justify;}
                </style>
            </head>
            <body>
                <div id='page'>
                    <div id='header'>
                        <h1>Adawiki: <?= ucfirst($method) ?> <?= $page ?></h1>
                        <a class='tab <?= ($method == 'ver' && $page == 'indice') ? 'current' : '' ?>' href='index.php?m=ver&p=indice'>&Iacute;ndice</a>
                        <? if($page != 'indice'): ?>
                            <a class='tab <?= $method == 'ver' || $method == 'guardar' ? 'current' : '' ?>' href='index.php?m=ver&p=<?= $page ?>'>Ver</a>
    <? endif; ?>
                        <a class='tab <?= $method == 'editar' ? 'current' : '' ?>' href='index.php?m=editar&p=<?= $page ?>'>Editar</a>
                        <a class='tab <?= $method == 'listar' ? 'current' : '' ?>' href='index.php?m=listar'>Listar</a>
                    </div>
                    <div id='body'>
    <?= $content ?>
                    </div>
                    <div id='footer'>
                        Adawiki v1.0 Por Carlos Gant (adaelxp@gmail.com)
                        - [<a href='http://www.adael.es' target='_blank'>www.adael.es</a>]
                        [Fuente
                        <a href='index.php?m=<?= $method ?>&p=<?= $page ?>&aumentarfuente'>A</a> /
                        <a href='index.php?m=<?= $method ?>&p=<?= $page ?>&reducirfuente'>a</a> /
                        <a href='index.php?m=<?= $method ?>&p=<?= $page ?>&restablecerfuente'>normal</a>
                        ]
                        [<a href='index.php?m=ver&p=<?= $page ?>&print=true'>Imprimir</a> | <a href='index.php?m=ayuda'>Ayuda</a>]
                    </div>
                </div>
            </body>
        </html>
    <?
}

// End layout()

function printLayout($content) {
    ?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
            <html>
                <head>
                    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
                    <title>Wiki</title>
                    <style type='text/css'>
                        body {
                            font-family: "arial", "sans-serif";
                            text-align: justify;
                            font-size: 13px;
                        }
                        h1,h2,h3,h4,h5,h6 {
                            margin: 4px 0;
                            padding: 4px 0;
                            font-weight: bold;
                            text-align: left;
                        }
                        h1 {
                            text-align: center;
                            border-bottom: 2px solid #444;
                            font-size: 21px;
                            font-weight: bold;
                        }
                        h2 {
                            font-size: 19px;
                            color: #444;
                        }
                        h3 {
                            font-size: 17px;
                            color: #444;
                        }
                        h4 {
                        }
                        p {
                            text-indent: 25px;
                        }
                        .indent { margin-left: 25px; } .indent2 { margin-left: 50px; }
                    </style>
                </head>
                <body>
    <?= $content ?>
                </body>
            </html>
    <?
}

function ayuda() {
    ?>
            <h1>Ayuda de Adawiki</h1>

            <p>Adawiki ha sido ideado por desarrolladores y para desarrolladores</p>
            <p>Sus caracter&iacute;sticas clave son:</p>
            <ul>
                <li> Un &uacute;nico fichero de script
                <li> Sin instalaci&oacute;n
                <li> Sin configuraci&oacute;n requerida
                <li> F&aacute;cil de usar
                <li> Autogestionable
                <li> C&oacute;digo abierto
            </ul>
            <p>Toda funcionalidad adicional no se contempla, para ello hay montones de alternativas
                muy buenas como <a href='http://www.mediawiki.com' target='_blank'>mediawiki</a>.</p>

            <h3>Instalaci&oacute;n</h3>
            <p>Copiar index.php dentro de una carpeta con permiso de escritura.</p>

            <h3>Configuraci&oacute;n</h3>
            <p>No es necesaria, s&oacute;lo hay que entrar desde el navegador al sitio donde hayas
                instalado el wiki y te aparecer&aacute; una p&aacute;gina para crear el &iacute;ndice (o p&aacute;gina de inicio),
                a partir de ah&iacute; podr&aacute;s utilizar de modo normal Adawik.
                No osbtante el c&oacute;digo lo puedes toquetear para cambiar el estilo, colores,
                agregar im&aacute;genes o funcionalidades.</p>

            <h3>Seguridad</h3>
            <p>Se delega a otros sistemas, yo recomiendo proteger el directorio con contrase&ntilde;a
                mediante htaccess o similares.</p>

            <h3>Funcionalidades</h3>
            <p>Aunque son pocas, son realmente &uacute;tiles:

            <ul>
                <li><b>Nuevo!!:</b> Ahora con soporte para <a href='http://daringfireball.net/projects/markdown/'>markdown</a></li>
                <li>Sobre todo se usar&aacute; HTML
                <li>Si se introduce texto plano, los saltos de linea se respetan, pero s&oacute;lo uno por cada linea
                <li><b>Enlaces internos: </b> para crear enlaces internos basta introducir el nombre
                    del fichero entre corchetes (como en mediawiki, pero solo con un corchete), osea:
                    [Mi fichero 1], vale cualquier caracter, tildes, e&ntilde;es, caracteres raros, comillas, etc.
                    Los nombres se codifican por lo que no hay que preocuparse. La &uacute;nica restricci&oacute;n
                    es que el nombre del fichero no puede superar los 150 caracteres.
                <li><b>Enlaces externos: </b> Para crear enlaces externos basta con introducir el enlace
                    entre corchetes. Pero tiene que comenzar con "http://". Ej: [http://www.google.com]
                <li><b>Categor&iacute;as: </b> en realidad no lo son, pero se me ocurre que dada la poca
                    o ninguna restricci&oacute;n a la hora de crear nombres de ficheros, para categorizarlos
                    podr&iacute;a ser [categoria - nombre de fichero] o bien [categoria:nombre de fichero].
                <li><b>Lista r&aacute;pida: </b> si se utiliza el caracter * como primer caracter de una linea
                    se crea una lista (como esta que est&aacute;s viendo). Se pueden encadenar tantos * como se quiera
                    para hacer sublistas.
                    <ul><li>Ejemplo de una sublista</ul>
                <li><b>Estilos css:</b> se pueden editar estilos en cada p&aacute;gina, dentro de la t&iacute;pica
                    etiqueta de &lt;style&gt;&lt;/style&gt; .
                <li><b>Importacion de css:</b> Se pueden importar estilos css y aplicarlos a los
                    elementos, bien utilizando el import de css:
                    <code>&lt;style&gt;@import("/path/to/css.css");&lt;/style&gt;</code><br>
                    O bien utilizando el tipico link href
                    <code>&lt;link rel='stylesheet' type='text/css' href='/path/to/css.css' /&gt;</code>
                <li><b>Codigo fuente:</b> Entre las etiquetas [code] y [/code] se puede poner
                    codigo fuente y se representará tal y como lo pones.
                <li><b>Codigo fuente php:</b> entre las etiquetas [code php] y [/code] se puede
                    poner codigo fuente php y saldrá hasta con colorines.
                <li><b>Autogesti&oacute;n: </b> desde la pesta&ntilde;a "Gestion" se pueden gestionar las distintas
                    p&aacute;ginas.
            </ul>
        </p>
        <?
    }

// End ayuda();

    function printCode($source) {
        return '<table border="0" cellpadding="0" class="code"><tr><td>' . highlight_string($source, true) . '</td></tr></table>';
    }

    /* dark_messenger84 at yahoo dot co dot uk
      02-Sep-2007 10:31
      Here's an improved version of supremacy2k at gmail dot com's code. It's a small
      function that accepts either PHP syntax in plain text or from another script,
      and then parses it into an ordered list with syntax highlighting.
     */

    function printPhpCode($source_code, $display_lines = false) {

        if(is_array($source_code))
            return false;

        $source_code = explode("\n", str_replace(array("\r\n", "\r"), "\n", $source_code));
        $line_count = 1;
        $formatted_code = "";
        foreach($source_code as $code_line) {
            $formatted_code .= '<tr>';

            if($display_lines)
                $formatted_code .= '<td style="padding-right: 8px; border-right: 1px dashed #999;">' . $line_count . '</td>';
            $line_count++;

            $formatted_code .= '<td style="padding-left:4px;">';
            if(preg_match('/<\?(php)?[^[:graph:]]/', $code_line)) {
                $formatted_code .= str_replace(array('<code>', '</code>'), '', highlight_string($code_line, true));
            }else {
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