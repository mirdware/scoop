@extends 'layers/layer'
<h1>Error {$ex->getCode()}!</h1>
<h2>The page or resource you requested was {$ex->getMessage()}</h2>
<pre>{$ex->getTraceAsString()}</pre>
