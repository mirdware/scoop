<h1>std.php</h1>
<p>La creación de este conjunto de archivos (PHP, JS, CSS, PNG, etc.) esta dada por la necesidad de construir un marco de trabajo con el cual iniciar mis proyectos PHP, su real y principal finalidad es la de poder utilizar POO y MVC dentro de PHP de una forma más sencilla y siempre manteniendo el control sobre el código (Bendito problema de frameworks, CMS, etc.).</p>
<p>Este no es un framework en toda la extensión de la palabra; primero, es muy sencillo para ser considerado como tal, segundo, me parece que framework es una palabra que se ha usado hasta el cansancio y así como decidí llamar a std.js: mi librería personal, he decidido llamar std.php: mi bootstrap php.</p>
<p>Bootstrap es el sistema de arranque de cualquier entorno informático y es por ello la elección de la definición del proyecto, pues std.php dista mucho de ser un completo marco de desarrollo en PHP como podrían ser: zend, synphony, codeigniter, etc; lo que si es std.php es un sistema de arranque para iniciar un desarrollo organizado y basado en POO y MVC.</p>
<h2>Consideraciones</h2>
<h3>Peticiones GET & POST</h3>
<p>Lo primero que se debe tener en cuenta es la <b>eliminación de variables GET</b> dentro del entorno de trabajo, por lo menos de la manera común como se conocen, en std.php se envía información a los controladores de la siguiente forma:</p>
	http(s)://host/controller/method/arg-1/arg-2/.../arg-n/
<p>De esta manera los datos que serán enviados mediante la URL, deberán ser escritos después del método que se desea invocar y respetando el orden en que serán recibidos por el método. Como las variables GET han sido suprimidas del bootstrap, las peticiones de un formulario deben <b>realizarse mediante POST</b>, de otra manera el bootstrap no interpretara correctamente la información enviada y mostrara el respectivo error.</p>
<p>La manera correcta de enviar datos por GET es mediante el uso final de la barra separadora (slash), de esta manera llegaremos a un estatus 200, de lo contrario se obtendrá un estatus 301 de redirección con sus respectivas consecuencias.</p>
<div><b>Mal</b></div>
	http(s)://host/controller/method/arg-1
<div><b>Bien</b></div>
	http(s)://host/controller/method/arg-1/
<h3>Estilos</h3>
<p>Este bootstrap posee una serie de estilos predefinidos que trata de no obligar al desarrollador, maquetador o diseñador a seguir un patrón o platilla prediseñada. Los cambios a los estilos son pocos y cuentan con algunos identificadores y clases que complementan tanto el bootstrap como la librería JavaScript que también se encuentra incluida.</p>
<p>Dentro de estos estilos se encuentran estilos especiales para los botones, para los mensajes de error del bootstrap, para las ventanas modales, etc. Un par de reglas de estilo interesantes son <b>custom-input-file e input-file</b> que básicamente lo que hacen es ocultar el input file, para poderle dar estilo a nuestro gusto, pero tienen una serie de reglas a tener en cuenta:</p>
<ul>
	<li>No modificar nada en ".custom-input-file .input-file" a excepción del cursor.</li>
	<li>No modificar "“"overflow: hidden; position: relative;" en ".custom-input-file", pueden agregar más estilos.</li>
	<li>Todo el contenido que agreguen dentro del div "custom-input-file", debe ir después de la etiqueta input file, no antes.</li>
	<li>Cuando cambien el cursor, deben cambiarlo en ".custom-input-file" y ".custom-input-file .input-file."</li>
</ul>
<h3>Comprimiendo CSS y JavaScript</h3>
<p>Dentro del proyecto se encuentra un archivo rar.bat que va a servir para unir y comprimir archivos JavaScript y CSS, gracias a <a href="http://developer.yahoo.com/yui/compressor/">yuicompressor</a> se realiza esta ultima acción, generando de esta manera un solo archivo CSS y JavaScript optimizado y totalmente funcional para la web.</p>
<p>De momento la compresión de archivos solo funciona en entornos Windows, próximamente se usara un compresor automático en Linux. El orden de los archivos al realizar la concatenación se encuentran dentro del mismo rar.bat de la siguiente manera:</p>
<div><b>JavaScript</b></div>
	copy /Y /B modernizr.js +std.js +modal.std.js +slider.std.js +fun.js scripts.js
<div><b>css</b></div>
	copy /Y /B stylestd.css +styles.modal.css +styles.slider.css +styles.app styles.css
<p>Para agregar archivos se debe colocar <b>+nombredearchivo.ext</b> En el orden que se desea sea concatenado dicho archivo (en caso de plugins, módulos, dependencias, etc), en caso de querer eliminar un archivo simplemente se suprime de la línea.</p>
<h2>MVC y POO</h2>
<p>La idea original de este bootstrap es la de trabajar PHP correctamente orientado a objetos y siguiendo una arquitectura bien definida como lo es la Modelo Vista Controlador, para empezar tenemos la interfaz model y la clase abstracta controller, para la vista se han definido plantillas que son renderizadas en el controlador.</p>
<h3>Models</h3>
<p>Los archivos que representan el modelo del negocio deberan implementar una interfaz model, lo cual obliga al uso de CRUD para la interacción con la base de datos.</p>
	class Usuario implements Model {

		public static function create ($array) {
			#code...
		}

		public function read ( $data=array() ) {
			#code...
		}

		public function update ($array) {
			#code...
		}

		public function delete () {
			#code...
		}

	}
<h3>Views</h3>
<p>Las vistas son simplemente plantillas HTML, con archivos CSS y JavaScript, con esto podemos separar correctamente la lógica de la aplicación, de su diseño.</p>
<h3>Controllers</h3>
<p>Todo controlador debería heredar de la clase abstracta Controller, con lo cual se pueden acceder a una serie de métodos propios del controlador, como puede ser renderizar paginas HTML, el método main es obligatorio.</p>
	class Home extends Controller {

		public function main () {
			#code...
		}

	}