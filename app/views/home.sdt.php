@extends 'layers/layer'
<h3>v. {{#view->getConfig('app.version')}} on PHP {{phpversion()}}</h3>
<h1>Welcome! You have installed {{#view->getConfig('app.name')}}</h1>
<blockquote>
    <span>{{$quote}}</span>
    <footer>— {{$author}}</footer>
</blockquote>
