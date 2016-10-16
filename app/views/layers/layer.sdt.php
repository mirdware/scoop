<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Codificación de la pagina a utf-8 para que admita caracteres especiales -->
    <meta charset="utf-8" />
    <!-- Referencia a los datos del autor y material utilizado -->
    <link rel="author" href="{view->overt('humans.txt')}" />
    <!-- Visualización en cualquier dispositivo utilizando responsive disign -->
    <meta name="viewport" content="width=device-width">
    <!-- Icono de la aplicación -->
    <link rel="shortcut icon" type="image/x-icon" href="{view->overt('favicon.ico')}" />
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
    {view->getMsg()}
    <a href="https://github.com/mirdware/scoop" target="_blank">
        <img style="position: absolute; top: 0; left: 0; border: 0;" src="https://camo.githubusercontent.com/c6625ac1f3ee0a12250227cf83ce904423abf351/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f6c6566745f677261795f3664366436642e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_left_gray_6d6d6d.png"/>
    </a>
    <header class="main">
        <a href="http://getscoop.org" target="_blank">
            <img src="https://lh3.googleusercontent.com/j7jyylCbGH1xgiYYq58XsJH4pBQaKzG_rkrhuPxGEr24RyId50BRkuvvUdK93C7fImNFuhEYydiOZfZYf_jAYvZcC7Xo2i0nM16DjYq1WVRgfa7CFaf_S_IupoC3pnzFUQED3xNIGaArhplPdqwWUusGeb3uTG2MYlKolot-P9FAjz5FV1GG7hAmCz_FDLLoZpfpQJRPRL4TjmM4Q4_sgLldLTVamhHOvDhgM5CLHQdqPJBuLAAEeFlNnH1FkVt0sr1_pcA_TPBlyLP0gy30csV_v4sln8rykK7qPcXDVG0cOPV-K8R_aT6VQ-PBJdB3r09PMdVl2SWnYYwsNOfbAorj1YKgoHIh-t9pj26CIuM6RXw192YrjYdHqPn7sDJqXsXBwA-mUgtnSoYLitt1ZIZayubPx61-9cQC6wgdJtpURgHCXTYqlhHoMc3yO5kJjsa60vXETHjAtm8l3NHZc7FL8gUi-VQo-ZBnsY8Li7KtpTbjnxYPv_mstHvXqmnvVdNOv6HwubVFOZ77SLqNhX2mXlsfSZDjRCSlWYVztIWeCFlgn5QU6YEhwxcfN1cUQQBGXDwssjKVvSxg0Bn4AkV4yaBgehlVWOmMVWTHnnK8AfTW=w300-h130-no" width="240" height="104" alt="scoop" />
        </a>
    </header>
    <section class="jumbotron">
        @sprout
    </section>
    <footer class="main">
        <img src="https://lh3.googleusercontent.com/rD0NW1qQvQkC-qtUmdyDwZzcYZS-OSrvTCDrHVtPLUbTOfsz_uWttD8j3-Zs5IkGYUnPW5qpXVFsKjifigEdpB_EIr0FHF6zF7JXA4zJKbvzS-Ks3xyVYSbsb2WYRQxo6i8yMnalFetutvc4Cz1iytrb9NV90awvKWXCHlET1ZwUhOtmCFYunaNWwaoJ3SwkFW-vPYeNQxZuJliqe2mxzwgdLkwnwSWNLJacx7IZIkrfeLeUdO3CfOW-HA5b8GYBBz8K37JPlnUGanp9ZFX7IOhgPaCKRZzLW4nDY_PIMpzjTvymtlHabT0Qa6JhT03ocT8_LzYqmzRgsXN0pRKTy-85Jst4jxReHIAxbqekM3ZhiSmZcrQj6BBYWBB-ZDRcogkhFNGaBVUEdprm7FQEwDlPNcEGzWdXRLnMJAkgb_yR5C00xpHBVR0gtSF8-JKak-GdiqSVwIXRqDe9owhO2KtMeoEwkQzrHs8EC0jiAI0nLM25oWyPA8wBp34CX9OCRprZuZLQDJZVuxoj0wvTdURFAEKum591d55SJSKEqGfuw3Jy_N1c6CR5G5ipbrWNDS-5p1fHOMwZUVqL_EUQfKyFkLx66obZEasnWIhBmlyqw_tx=s50-no" width="50" height="50" alt="logo-company">
    </footer>
</body>
</html>
