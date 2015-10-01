@extends 'layers/layer'
<div class="jumbotron">
    <a href="http://getscoop.org" target="_blank">
        <img src="{view->img('scoop.png')}" alt="scoop" width="200" />
    </a>
    <h3>v. {config->get('app.version')}</h3>
    <h1>Welcome, you have installed {config->get('app.name')} <i class="fa fa-spoon"></i></h1>
    <blockquote>
        <span>{$quote}</span>
        <footer>— {$author}</footer>
    </blockquote>
</div>
