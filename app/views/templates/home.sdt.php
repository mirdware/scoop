@extends 'layers/layer'
<a href="http://getscoop.org" target="_blank">
    <img src="{View::img('scoop.png')}" alt="scoop" width="200" />
</a>
<h3>v. {View::get('app.version')}</h3>
<h1>Welcome, you have installed {View::get('app.name')} <i class="fa fa-spoon"></i></h1>
<blockquote>
    <span>{$quote}</span>
    <footer>— {$author}</footer>
</blockquote>
