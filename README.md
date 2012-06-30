<h1>std.php</h1>
<p>La creación de este conjunto de archivos (PHP, JS, CSS, PNG, etc) esta dada por la necesidad de construir un marco de trabajo con el cual iniciar mis proyectos PHP, su real y principal finalidad es la de poder utilizar POO y MVC dentro de php de una forma más sencilla y siempre manteniendo el control sobre el codigo (Bendito problema de frameworks, CMS, etc).</p>
<p>Este no es un framework en toda la extención de la palabara; primero, es muy sencillo para ser considerado como tal, segundo, me parece que framework es una palabra que se ha "Perratiado" hasta el cansancio y asi como decidi llamar a std.js: mi libreria personal, he decidido llamar std.php: mi bootstrap php.</p>
<p>Bootstrap es el sistema de arranque de cualquier entorno informatico y es por ello la elección de la definición del proyecto, pues std.php dista mucho de ser un completo marco de desarrollo en PHP como podrian ser: zend, synphony, codeigniter, etc; lo que si es std.php es un sistema de arranque para iniciar un desarrollo organizado y basado en POO y MVC.</p>
<h2>Consideraciones</h2>
<h3>Peticiones GET & SET</h3>
<p>Lo primero que se debe tener en cuenta es la completa <b>eliminación de variables GET</b> dentro del entorno de trabajo, dentro de std.php se envia información a los controladores de la siguiente forma:</p>
<p><b>http/s://host/controller/method/arg-1/arg-2/.../arg-n/</b></p>
<p>De esta manera los datos que deban ser enviados mediante la url, deberan ser escritos despues del metodo que se desea invocar y respetando el orden en que seran resividos por el metodo. Como las variables GET an sido suprimidas del bootstrap las peticiones de un formulario deben <b>realizarse mediante POST</b>, de otra manera el bootstrap se quedara en un bucle de servidor y mostrara el respectivo error.</p>
<p>Cuando se envia información mediante el metodo POST se debe tener especial cuidado en enviarla, pues si seprume el ultimo slash de la url, el navegador llegara a la dirección correcta, pero perdera las variables en el camino.</p>
<p><b>http/s://host/controller/method/arg-1</b><span style="color:#C00;">Mal</span></p>
<p><b>http/s://host/controller/method/arg-1/</b><span style="color:#45B339">Bien</span></p>
<h3>Estilos</h3>
<p>Este bootstrap posee una serie de estilos predefinidos, pero que tratan de no obligar al desarrollados, maquetador o diseñador a seguir un patron o platilla prediseñada. Los cambios a los estilos son pocos y cuenta con algunas clases e ids que complementanto tanto el bootstrap como la libreria javascript que tambien se encuentra incluida.</p>
<p>Dentro de estos estilos se encuentran estilos especiales para los botones, para los mensajes de error del bootstrap, para las ventanas modales, etc. Un par de reglas de estilo interesantes son <b>custom-input-file y input-file</b> que basicamente lo que hacen es camuflajear el input file, para poderle dar estilo a nuestro gusto, pero tienen una serie de reglas a tener en cuenta:</p>
<ol>
	<li>No modificar nada en ".custom-input-file .input-file" a excepción del cursor.</li>
	<li>No modificar "“"overflow: hidden; position: relative;" en ".custom-input-file", pueden agregar más estilos.</li>
	<li>Todo el contenido que agreguen dentro del div "custom-input-file", debe ir despues de la etiqueta input file, no antes.</li>
	<li>Cuando cambien el cursor, deben cambiarlo en ".custom-input-file" y ".custom-input-file .input-file."</li>
</ol>