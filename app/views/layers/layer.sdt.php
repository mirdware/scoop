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
                <img src="https://lh3.googleusercontent.com/mwmX8XNisMpYzvq3Bgi28XHQEM5Hoogf3r3EaW4tXRBr_Rjd7qOevfh4G351TaoJbdjL8i7vxe8QGI8PyfTsO-Q-zRGkyz9PfauHTs6mkFW2wgcP7WBXF3Cny_krGfkQVx_XCcjS6f7cvftFJAtiORZAGdbhcNM0vWJDWmsrvuF78C8KpAAVCHRaW8Av6-EyaqJuWk1uzX_psdH6mClOAwqfMBIPG3sS7h09KSW7ra180XVA1_a8dpjt4iVBITK7u_zYU_C3w__ONsvYlrZQnXAeVV2k6CY2ObajLSFEQE6x2bEQxBGjRVA76kjgL6C6I367S0RiSbyo8kDtCh4l0G6i3jbcPLcEiyRfrAFUQQn6aiyRYc1DHJh4ocRrnGYeZ2lF8hvjd7G-a7Ya1WS5xatKvcsCCXylRGsji7KrQts5dI1Az63hfi2vZzIAvnCNhkZtToanJr8HbzUrmc2mU9ARYObIcVfK9oCHnMImpm90Mofe9-dtPvYB6jH7q03XFlV5_ERo5rYLkC8yDdX8IwRUIBbVeLtpRfFUlMfIRKL9Kw88x7eEhgSBoLf9fpA3zAvcUHIYGhEqPQ-ubXwTgmrCIt9bbsIJvy3XcNNfum9awp6W=w300-h130-no" width="240" height="104" alt="scoop" />
            </a>
        </header>
        <section class="jumbotron">
            @sprout
        </section>
    </div>
    <footer class="main">
        <img src="https://lh3.googleusercontent.com/rD0NW1qQvQkC-qtUmdyDwZzcYZS-OSrvTCDrHVtPLUbTOfsz_uWttD8j3-Zs5IkGYUnPW5qpXVFsKjifigEdpB_EIr0FHF6zF7JXA4zJKbvzS-Ks3xyVYSbsb2WYRQxo6i8yMnalFetutvc4Cz1iytrb9NV90awvKWXCHlET1ZwUhOtmCFYunaNWwaoJ3SwkFW-vPYeNQxZuJliqe2mxzwgdLkwnwSWNLJacx7IZIkrfeLeUdO3CfOW-HA5b8GYBBz8K37JPlnUGanp9ZFX7IOhgPaCKRZzLW4nDY_PIMpzjTvymtlHabT0Qa6JhT03ocT8_LzYqmzRgsXN0pRKTy-85Jst4jxReHIAxbqekM3ZhiSmZcrQj6BBYWBB-ZDRcogkhFNGaBVUEdprm7FQEwDlPNcEGzWdXRLnMJAkgb_yR5C00xpHBVR0gtSF8-JKak-GdiqSVwIXRqDe9owhO2KtMeoEwkQzrHs8EC0jiAI0nLM25oWyPA8wBp34CX9OCRprZuZLQDJZVuxoj0wvTdURFAEKum591d55SJSKEqGfuw3Jy_N1c6CR5G5ipbrWNDS-5p1fHOMwZUVqL_EUQfKyFkLx66obZEasnWIhBmlyqw_tx=s50-no" width="50" height="50" alt="logo-company">
    </footer>
</body>
</html>
