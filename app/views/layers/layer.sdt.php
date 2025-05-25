<!DOCTYPE html>
<html lang="{{#view->getConfig('language', 'es')}}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width" />
        <title>{{isset($title) ? $title : 'Welcome'}} » {{#view->getConfig('app.name')}}</title>
        @if getenv('VITE_HOST')
            <script type="module" src="{{getenv('VITE_HOST')}}/@vite/client"></script>
        :if
        <link rel="author" href="{{#view->asset('humans.txt')}}" />
        <link rel="shortcut icon" type="image/x-icon" href="{{#view->asset('favicon.ico')}}" />
        <link rel="stylesheet" href="{{#view->css(#view->getConfig('app.name').'.min.css')}}" rel="preload" as="style" />
        <script type="module" src="{{#view->js(#view->getConfig('app.name').'.min.js')}}" defer></script>
        @if isset($meta)
            @foreach $meta as $name => $value
                <meta name="{{$name}}" content="{{$value}}" />
            :foreach
        :if
    </head>

    <body>
        <sc-message></sc-message>
        <a href="https://github.com/mirdware/scoop" target="_blank">
            <img style="position: absolute; top: 0; left: 0; border: 0;" decoding="async" width="149" height="149" src="https://github.blog/wp-content/uploads/2008/12/forkme_left_gray_6d6d6d.png?resize=149%2C149" class="attachment-full size-full" alt="Fork me on GitHub" loading="lazy" data-recalc-dims="1">
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
