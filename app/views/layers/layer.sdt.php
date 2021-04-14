<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Codificación de la pagina a utf-8 para que admita caracteres especiales -->
    <meta charset="utf-8" />
    <!-- Visualización en cualquier dispositivo utilizando responsive disign -->
    <meta name="viewport" content="width=device-width" />
    @if isset($meta)
        @foreach $meta as $name => $value
            <meta name="{{$name}}" content="{{$value}}" />
        :foreach
    :if
    <!-- Referencia a los datos del autor y material utilizado -->
    <link rel="author" href="{{#view->asset('humans.txt')}}" />
    <!-- Icono de la aplicación -->
    <link rel="shortcut icon" type="image/x-icon" href="{{#view->asset('favicon.ico')}}" />
    <!-- Enlace a la hoja de estilos general -->
    <link rel="stylesheet" href="{{#view->css(#config->get('app.name').'.min.css')}}" />
    <!-- Descarga asincrona de javascript -->
    <script src="{{#view->js(#config->get('app.name').'.min.js')}}" defer></script>
    <!-- Titulo de la pagina -->
    <title>{{$title}} » {{#config->get('app.name')}}</title>
</head>

<body>
    {{#view->composeMessage()}}
    <a href="https://github.com/mirdware/scoop" target="_blank">
        <img style="position: absolute; top: 0; left: 0; border: 0;" src="https://camo.githubusercontent.com/c6625ac1f3ee0a12250227cf83ce904423abf351/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f6c6566745f677261795f3664366436642e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_left_gray_6d6d6d.png"/>
    </a>
    <div class="wrapper">
        <header>
            <a href="http://getscoop.org" target="_blank">
                <img src="http://res.cloudinary.com/dwserhw7s/image/upload/v1487892509/scoop_l67ae4.png" width="240" height="104" alt="scoop" />
            </a>
        </header>
        <section class="jumbotron">
            @sprout
        </section>
    </div>
    <footer class="main">
        <a href="http://mirdware.com" target="_blank">
            <img src="http://res.cloudinary.com/dwserhw7s/image/upload/v1487892514/logo-blanco_lrdxxo.png" width="50" height="50" alt="mirdware">
        </a>
    </footer>
</body>
</html>
