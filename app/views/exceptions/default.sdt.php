@extends 'layers/layer'
<h1>Error {{$ex->getCode()}}!</h1>
<h2>{{$ex->getMessage()}}</h2>
@if DEBUG_MODE
    <pre>{{$ex}}</pre>
:if
