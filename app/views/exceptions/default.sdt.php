@extends 'layers/layer'
<h1>Error {$ex->getCode()}! The page you requested was {$ex->getMessage()}</h1>
<pre>{$ex->getTraceAsString()}</pre>
