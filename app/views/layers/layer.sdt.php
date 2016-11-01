<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Codificación de la pagina a utf-8 para que admita caracteres especiales -->
    <meta charset="utf-8" />
    <!-- Referencia a los datos del autor y material utilizado -->
    <link rel="author" href="{view->asset('humans.txt')}" />
    <!-- Visualización en cualquier dispositivo utilizando responsive disign -->
    <meta name="viewport" content="width=device-width">
    <!-- Icono de la aplicación -->
    <link rel="shortcut icon" type="image/x-icon" href="{view->asset('favicon.ico')}" />
    <!-- Enlace a la hoja de estilos general -->
    <link rel="stylesheet" href="{view->css(config->get('app.name').'.min.css')}" />
    <!-- trabajar las rutas absolutas dentro de javascript -->
    <script type="text/javascript">
        var root = "{ROOT}";
    </script>
    <script src="{view->js(config->get('app.name').'.min.js')}" async></script>
    <!-- Titulo de la pagina -->
    <title>{$title} » {config->get('app.name')}</title>
</head>

<body>
    {view->compose('message')}
    <a href="https://github.com/mirdware/scoop" target="_blank">
        <img style="position: absolute; top: 0; left: 0; border: 0;" src="https://camo.githubusercontent.com/c6625ac1f3ee0a12250227cf83ce904423abf351/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f6c6566745f677261795f3664366436642e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_left_gray_6d6d6d.png"/>
    </a>
    <div class="wrapper">
        <header>
            <a href="http://getscoop.org" target="_blank">
                <img src="https://lh3.googleusercontent.com/yDyfM5OBMRscGBKxg6JVjdo6KG9usq95CrDvekb4mlYtaoqk6V-Up-Pi-WzwOA31QXp6u1oak34y7JTPJh0x-lCAIP6GmtBsLhFVGuRcjgbbkTISB7x7DGK4u927x-RzZiGYk64I1vzkQ0PJYMMO3a8kppY7f0iawfOIdmQWlvCrnoqjE7pUmAwHXynFzEmQ_0V1EaAimrokm-V9PjAxLL5Z16dI8icYaqNzXcv1LE6W9IloRvjcAKpI1QMzAbfH4Xj6qYupmWVrKtTbUBmYEO1WOYoqhNKjRtdY3gsei1HOfTSJtTTmHz_gOLLSPorVMuILFCJ1rrmfl-jd4dRCMGPQDJnBQdn-60w_LvNN9oYo4sdWBO0EgXTgu7tXDkewt6BYpAOtkbR26-akPlx9NzN32NAqeVTdx1lfOPPySnGhfdB6pX8OzqWcXeAF3gMgL9oQQGs59vrIj8elu5FxMqH7Z6XqE4GFhWoVrVWbw9C7T8Fpo4FDqjp1ID1Kyl4xtfpLuPOZJmreL9QnNGsqFdDw_f3FGtxB2cZDXw1zl6lowkUYmmucMydukRLi57P3uez-8dTOFfVNhRZwB5mSpwLCZ_kr9JfQsxZ2gGdt-tk3RjPu=w300-h130-no" width="240" height="104" alt="scoop" />
            </a>
        </header>
        <section class="jumbotron">
            @sprout
        </section>
    </div>
    <footer class="main">
        <a href="http://mirdware.com" target="_blank">
            <img src="https://lh3.googleusercontent.com/rD0NW1qQvQkC-qtUmdyDwZzcYZS-OSrvTCDrHVtPLUbTOfsz_uWttD8j3-Zs5IkGYUnPW5qpXVFsKjifigEdpB_EIr0FHF6zF7JXA4zJKbvzS-Ks3xyVYSbsb2WYRQxo6i8yMnalFetutvc4Cz1iytrb9NV90awvKWXCHlET1ZwUhOtmCFYunaNWwaoJ3SwkFW-vPYeNQxZuJliqe2mxzwgdLkwnwSWNLJacx7IZIkrfeLeUdO3CfOW-HA5b8GYBBz8K37JPlnUGanp9ZFX7IOhgPaCKRZzLW4nDY_PIMpzjTvymtlHabT0Qa6JhT03ocT8_LzYqmzRgsXN0pRKTy-85Jst4jxReHIAxbqekM3ZhiSmZcrQj6BBYWBB-ZDRcogkhFNGaBVUEdprm7FQEwDlPNcEGzWdXRLnMJAkgb_yR5C00xpHBVR0gtSF8-JKak-GdiqSVwIXRqDe9owhO2KtMeoEwkQzrHs8EC0jiAI0nLM25oWyPA8wBp34CX9OCRprZuZLQDJZVuxoj0wvTdURFAEKum591d55SJSKEqGfuw3Jy_N1c6CR5G5ipbrWNDS-5p1fHOMwZUVqL_EUQfKyFkLx66obZEasnWIhBmlyqw_tx=s50-no" width="50" height="50" alt="mirdware">
        </a>
    </footer>
</body>
</html>
