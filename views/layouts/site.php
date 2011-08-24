<?php
$tabitems = array(
	array(
		'text' => 'Ver',
		'link' => 'index.php/view/' . $page,
		'active' => ($metdod == 'guardar' || $method == 'ver'),
		'visible' => $page != 'index',
	),
	array(
		'text' => '&Iacute;ndice',
		'link' => 'index.php/view/index',
		'active' => ($metdod == 'guardar' || $method == 'ver'),
		'visible' => $page == 'index',
	),
	array(
		'text' => 'Editar',
		'link' => 'index.php/edit/' . $page,
		'active' => $method == 'edit',
	),
	array(
		'text' => 'Ayuda',
		'link' => 'index.php/help/',
		'active' => $method == 'help',
	)
);

$methodNames = array(
	'view' => 'Ver',
	'edit' => 'Editar',
	'list' => 'Listar',
	'help' => 'Ayuda',
);

$methodName = isset($methodNames[$method]) ? $methodNames[$method] : $method;
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<title>AdaWiki</title>
		<link rel="stylesheet" type="text/css" href="assets/site.css"/>
		<script type="text/javascript" src="assets/jquery.min.js"></script>
	</head>
	<body>
		<div class="page-wrapper">
			<div class='page-header'>
				<div class="page-tabs">
					<?php
					foreach($tabitems as $item){
						if($item['visible']){
							printf('<a href="%s" class="tab%s">%s</a>', $item['link'], $item['active'] ? ' current' : '', $item['text']);
						}
					}
					?>
				</div>
				<div class="page-title">
					Adawiki: <?php echo ucfirst($methodName) ?> <?php echo $method != 'help' ? $page : ''; ?>
				</div>
				<br class="clear"/>
			</div>
			<div class="page-shadow">
				<div class='page-content'>
					<?php echo $content ?>
				</div>
				<div class='page-footer'>
					<div style="float: left;">
						<?php if($method == 'view' || $method == 'help'): ?>
							<a href='index.php/print/<?php echo $page ?>'>Imprimir</a>
						<?php endif; ?>
						-
						Fuente:
						<a href='index.php?m=<?php echo $method ?>&p=<?php echo $page ?>&aumentarfuente'>A</a> /
						<a href='index.php?m=<?php echo $method ?>&p=<?php echo $page ?>&reducirfuente'>a</a> /
						<a href='index.php?m=<?php echo $method ?>&p=<?php echo $page ?>&restablecerfuente'>normal</a>
					</div>
					<div style="float:right;">
						Adawiki v1.0 Por Carlos Gant
					</div>
					<br class="clear"/>
				</div>
			</div>
		</div>
	</body>
</html>