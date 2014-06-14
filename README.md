Scoop
=====
###Simple Characteristics of Object Oriented PHP
***
La creación de este conjunto de archivos (PHP, JS, CSS, PNG, etc.) esta dada por la necesidad de construir un sistema de arranque con el cual iniciar mis proyectos PHP, su real y principal finalidad es la de poder utilizar POO y MVC dentro de PHP de una forma más sencilla y siempre manteniendo el control sobre el código (Bendito problema de frameworks, CMS, etc.).

Este no es un framework en toda la extensión de la palabra; primero, es muy sencillo para ser considerado como tal, segundo, me parece que framework es una palabra que se ha usado hasta el cansancio y así como decidí llamar a std: mi librería JavaScript personal, he decidido llamar scoop: mi bootstrap php.

Bootstrap es el sistema de arranque de cualquier entorno informático y es por ello la elección de la definición del proyecto, pues scoop dista mucho de ser un completo marco de desarrollo en PHP como podrían ser: zend, synphony, codeigniter, etc; lo que si es scoop es un sistema de arranque para iniciar un desarrollo organizado y basado en POO y MVC.

Peticiones GET & POST
=====================
Lo primero que se debe tener en cuenta es la manera como SCOOP trata las peticiones mediante la URL, primero esta el protocolo, seguido del nombre del host, el controlador, el metodo y los argumentos.

	http(s)://host/controller/method/arg-1/arg-2/.../arg-n/

Los argumentos deben coincider en el orden a como seran recibidos por los párametros del método dentro del controlador. Se recomienda usar cada metodo de envido como ha sido definido en los protocolos RESTful y evitar en la medida de lo posible las query URL (http://host/?data=hello).

La manera correcta de escribir una URL SCOOP es mediante el uso final de la barra separadora (slash), de esta manera llegaremos a un estatus 200, de lo contrario se obtendrá un estatus 301 de redirección con sus respectivas consecuencias.

**Mal**

	http(s)://host/controller/method/arg-1

**Bien**

	http(s)://host/controller/method/arg-1/

Estilos
=======
Este bootstrap posee una serie de estilos predefinidos que no obligan al desarrollador, maquetador o diseñador a seguir un patrón o platilla prediseñada. Los cambios a los estilos son pocos y cuentan con algunos identificadores y clases que complementan tanto el bootstrap como la librería JavaScript que también se encuentra incluida.

Dentro de estos estilos se encuentran estilos especiales para los botones, para los mensajes de error del bootstrap, para las ventanas modales, etc. Un par de reglas de estilo interesantes son **custom-input-file e input-file** que básicamente lo que hacen es ocultar el input file, para poderle dar estilo a nuestro gusto, pero tienen una serie de reglas a tener en cuenta:

	
* No modificar nada en ".custom-input-file .input-file" a excepción del cursor.
* No modificar "overflow: hidden; position: relative;" en ".custom-input-file", pueden agregar más estilos
* Todo el contenido que agreguen dentro del div "custom-input-file", debe ir después de la etiqueta input file, no antes.
* Cuando cambien el cursor, deben cambiarlo en ".custom-input-file" y ".custom-input-file .input-file."

```html
<div class="btn custom-input-file">
	<input type="file" name="rips" class="input-file" id="rips" multiple="multiple" />Cargar RIPS
</div>​
```

Comprimiendo CSS y JavaScript
=============================
Dentro del proyecto se encuentra un archivo rar.bat que va a servir para unir y comprimir archivos JavaScript y CSS, gracias a [yuicompressor](http://developer.yahoo.com/yui/compressor/) se realiza esta ultima acción, generando de esta manera un solo archivo CSS y JavaScript optimizado y totalmente funcional para la web.

De momento la compresión de archivos solo funciona en entornos Windows, próximamente se usara un compresor automático en Linux. El orden de los archivos al realizar la concatenación se encuentran dentro del mismo rar.bat de la siguiente manera:

**JavaScript**

	copy /Y /B modernizr.js +std.js +scoop.std.js +modal.std.js +slider.std.js +fun.js scripts.js

**css**

	copy /Y /B stylescoop.css +styles.modal.css +styles.slider.css +styles.app.css styles.css

Para agregar archivos se debe colocar **+nombredearchivo.ext** en el orden que se desea concatenar dicho archivo (en caso de plugins, módulos, dependencias, etc), para eliminar un archivo simplemente se suprime de la línea.

MVC y POO
=========
La idea original de este bootstrap es la de trabajar PHP correctamente orientado a objetos y siguiendo una arquitectura bien definida como lo es la Modelo Vista Controlador, para empezar tenemos la interfaz model y la clase abstracta controller, para la vista se han definido plantillas que son renderizadas en el controlador.

Models
------
Los archivos que representan el modelo del negocio deberan implementar una interfaz model, lo cual obliga al uso de CRUD para la interacción con la base de datos.

```php
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
```

Views
-----
Las vistas son simplemente plantillas HTML, con archivos CSS y JavaScript, con esto podemos separar correctamente la lógica de la aplicación de su diseño. Los datos dentro de ${var} son variables que pueden ser remplazados mediante el controlador gracias al metodo **setView**.

```html
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8" />
    <link rel="stylesheet" href="${root}views/images/css/styles.css">
    <script src="${root}views/js/scripts.js"></script>
    <title>${title}</title>
</head>

<body>
    <div id="main">
    	${msg}
		${page}
    </div>
</body>
</html>
```

Al igual que las variables, tambien existen constantes de la vista, un ejemplo de ellas es ${root} que representa la ruta absoluta de la aplicación, para agregar más contantes a la vista se deben agregar al array indicado dentro de la clase Model en MVC dentro del core. Una constante puede ser sobreescrita dentro del script, pero al momento de renderizar la vista se mostrara la constante definida.

```php
//constantes SCOOP
$this->hashMap = array_merge($this->hashMap, array(
	'${root}' => ROOT,
	'${msg-scoop}' => $this->msg
));
```

Otra modificación que se le puede hacer al core es la obtención de layers, obiamente podemos tener varios layers, uno para la app y otra para el logín por ejemplo, para configurar esto se debe modificar el metodo getLayer dentro de la clase Controller en MVC del core.

**Antes**
```php
private function getLayer() {
	$key = 'layers-'.$this->layer;

	//opteniendo los layers por APC o sesión
	if (APC) {
		if ( apc_exists($key) ) {
			return apc_fetch($key);
		}
	} elseif ( isset($_SESSION[$key]) ) {
		return $_SESSION[$key];
	}
	//ubicacion completa del archivo
	$file = 'views/layers/'.$this->layer.'.html';
	$layer = file_get_contents($file);
	if (APC) {
		apc_store($key, $layer, 1200);
	}
	return $layer;
}
```
**Después**
```php
private function getLayer() {
	//validando si existe el layer de la sesión del usuario
	if ( isset($_SESSION['usuario']) ) {
		$usuario = $_SESSION['usuario']->getUser();
		if ( isset($_SESSION['session-'.$usuario]) ) {
			return $_SESSION['session-'.$usuario];
		}
	}

	$key = 'layers-'.$this->layer;
	//eliminar en producción
	apc_delete($key);

	//opteniendo los layers por APC o sesión
	if (APC) {
		if ( apc_exists($key) ) {
			return self::getSessionUser( apc_fetch($key) );
		}
	} elseif ( isset($_SESSION[$key]) ) {
		return self::getSessionUser( $_SESSION[$key] );
	}
	
	//ubicacion completa del archivo base
	$file = 'views/layers/layer.html';
	if ($this->layer === 'eps' || $this->layer === 'ips') {
		$layer = str_replace('${layer}', file_get_contents('views/layers/in-app.html'), file_get_contents($file));
		$layer = str_replace('${nav-user}', file_get_contents('views/layers/nav-'.$this->layer.'.html'), $layer);
		$layer = self::getSessionUser( $layer );
	} else if ($this->layer === 'out') {
		$layer = str_replace('${layer}', file_get_contents('views/layers/out-app.html'), file_get_contents($file));
	} else {
		$layer = str_replace('${layer}', '${msg-scoop}${page}', file_get_contents($file));
	}

	if (APC) {
		apc_store($key, $layer, 1200);
	}

	return $layer;
}
```
Controllers
-----------
Todo controlador debería heredar de la clase abstracta Controller, con lo cual se pueden acceder a una serie de métodos propios del controlador, como puede ser renderizar paginas HTML, el método main es obligatorio.

```php
class Home extends Controller {

	public function main () {
		#code...
	}

}
```