@extends 'layers/layer'
<h3>v. {config->get('app.version')}</h3>
<h1>Welcome! You have installed {config->get('app.name')}</h1>
<blockquote>
    <span>{$quote}</span>
    <footer>— {$author}</footer>
</blockquote>
