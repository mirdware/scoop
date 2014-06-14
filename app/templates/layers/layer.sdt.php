<!DOCTYPE html>
<html lang="es">
<head>
	<!-- Codificación de la pagina a utf-8 para que admita caracteres especiales -->
	<meta charset="utf-8" />
    <!-- Referencia a los datos del autor y material utilizado -->
    <link rel="author" href="${ROOT}public/humans.txt" />
    <!-- Visualización en cualquier dispositivo utilizando responsive disign -->
    <meta name="viewport" content="width=device-width">
    <!-- Icono de la aplicación -->
    <link rel="shortcut icon" type="image/x-icon" href="${ROOT}public/favicon.ico" />
    <!-- Enlace a la hoja de estilos general -->
    <link rel="stylesheet" href="${ROOT}public/css/project.scoop.min.css" />
    <!-- trabajar las rutas absolutas dentro de javascript -->
    <script type="text/javascript">
        var root = "${ROOT}";
    </script>
    <script src="${ROOT}public/js/project.scoop.min.js"></script>
    <!-- Titulo de la pagina -->
    <title>${title}</title>
</head>

<body>
    <header>
    </header>
    <div id="main">
    	${msg_scoop}
		${page}
    </div>
    <footer>
    </footer>
</body>
</html>