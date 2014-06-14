<!DOCTYPE html>
<html lang="es">
<head>
	<!-- Codificación de la pagina a utf-8 para que admita caracteres especiales -->
	<meta charset="utf-8" />
    <!-- Referencia a los datos del autor y material utilizado -->
    <link rel="author" href="<?php echo ROOT ?>public/humans.txt" />
    <!-- Visualización en cualquier dispositivo utilizando responsive disign -->
    <meta name="viewport" content="width=device-width">
    <!-- Icono de la aplicación -->
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo ROOT ?>public/favicon.ico" />
    <!-- Enlace a la hoja de estilos general -->
    <link rel="stylesheet" href="<?php echo ROOT ?>public/css/project.scoop.min.css" />
    <!-- trabajar las rutas absolutas dentro de javascript -->
    <script type="text/javascript">
        var root = "<?php echo ROOT ?>";
    </script>
    <script src="<?php echo ROOT ?>public/js/project.scoop.min.js"></script>
    <!-- Titulo de la pagina -->
    <title><?php echo $title ?></title>
</head>

<body>
    <header>
    </header>
    <div id="main">
    	<?php echo $msg_scoop ?>
		<?php \scoop\view\Maker::output() ?>
    </div>
    <footer>
    </footer>
</body>
</html>