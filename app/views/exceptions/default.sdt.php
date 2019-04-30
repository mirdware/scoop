@extends 'layers/layer'
<h1>Error {$ex->getCode()}! The page or resource you requested was {$ex->getMessage()}</h1>
<pre>{$ex->getTraceAsString()}</pre>
