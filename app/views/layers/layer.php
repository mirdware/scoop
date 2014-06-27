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
    <a href="https://github.com/mirdware/scoop" target="_blank">
        <img style="position:absolute;top:0;right:0;border:0" src="https://camo.githubusercontent.com/a6677b08c955af8400f44c6298f40e7d19cc5b2d/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f677261795f3664366436642e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_gray_6d6d6d.png" />
    </a>
    <div id="main">
    	<?php echo $view->getMsg();
        \scoop\view\Maker::output(); ?>
    </div>
    <footer>
    </footer>
</body>
</html>