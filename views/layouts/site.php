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
					<? if($page != 'indice'): ?>
						<a class='tab <?php echo $method == 'ver' || $method == 'guardar' ? 'current' : '' ?>' href='index.php?m=ver&p=<?php echo $page ?>'>Ver</a>
					<? else: ?>
						<a class='tab <?php echo $method == 'ver' || $method == 'guardar' ? 'current' : '' ?>' href='index.php?m=ver&p=indice'>&Iacute;ndice</a>
					<? endif; ?>
					<a class='tab <?php echo $method == 'editar' ? 'current' : '' ?>' href='index.php?m=editar&p=<?php echo $page ?>'>Editar</a>
					<a class='tab <?php echo $method == 'listar' ? 'current' : '' ?>' href='index.php?m=listar'>Listar</a>
					<a class="tab <?php echo $method == 'ayuda' ? 'current' : '' ?>" href='index.php?m=ayuda'>Ayuda</a>
				</div>
				<div class="page-title">
					Adawiki: <?php echo ucfirst($method) ?> <?php echo $method != 'ayuda' ? $page : ''; ?>
				</div>
				<br clear="all"/>
			</div>
			<div class="page-shadow">
				<div class='page-content'>
					<?php echo $content ?>
				</div>
				<div class='page-footer'>
					<div style="float: left;">
						<a href='index.php?m=ver&p=<?php echo $page ?>&print=true'>Imprimir</a>
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