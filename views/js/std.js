/**
	std.js 
	Es un script generico en el que se abarcan varias funciones comunes para la realización de trabajos 
	no solo en javascript, si no tambien orientados a AJAX (Asynchronous JavaScript And XML) y con 
	soporte para JSON. Todo esto basandose en una interfaz sencilla.
	
	Para buscar secciones dentro del documento utiliza "****** sección ******" sin comillas, las secciones de std son:
		NUCLEO: Se encuentran los metodos propios de std(extend, cmode).
		DOM: Realiza trabajos con el DOM al igual que ejecuta acciones cuando este esta completamente listo(ready).
		EVENTOS: Contiene manejadores de eventosadd, remove, on) dentro del objeto evt y una verion de event estandarizada(get).
		ESTILOS: Se encuentra el manejador de estilos css que gestiona tanto elementos como reglas.
		AJAX: El objeto ajax se encarga de realizar peticiones asincronas al servidor.
		EFECTOS ESPECIALES: Contiene funciones dentro del objeto sfx encargadas de pequeñas animaciones.
		FUNCIONES PRIVADAS: Se hallan las funciones a las que más de 1 modulo debe tener acceso.
		CONFIGURACION DE ENTORNO: Se configura todo lo que no involucra directamente a std, en especial el entorno de ejecución.
			JSON: Se encarga de registrar el objeto JSON a window cuando este no existe.
	
	El camino más sencillo casi siempre es el más rapido y efectivo. Recuerda Keep It Simple, Stupid!!
	@author: Marlon Ramirez
	@version: 0.0.9
*/
(function(window, undefined) {
var TRUE = true,
	FALSE = false,
	NULL = null,
	document = window.document,
	_$ = window.$,
	encodeURIComponent = window.encodeURIComponent,
	parseInt = window.parseInt,
	parseFloat = window.parseFloat,
	testElement = document.documentElement,
	std = {
		/****** NUCLEO ******/

		/**
			Extiende o copia las propiedades de un objeto origen en otro objeto destino. Las propiedades del objeto origen se
			sobreescriben en el objeto destino.
			@param: {object} src es el objeto origen
			@param: {object} target es el objeto destino
			@return El objeto destino extendido con las propiedades del objeto origen
		*/
		extend: function (target, src) {
			for (var prop in src) {
				target[prop] = src[prop];
			}
			return target;
		},

		/**
			Elimina el uso del alias $ para hacer compatible el uso de la libreria con otras como:
			protorype, mootools, jQuery,etc. El nombre del metodo viene de Compatibility Mode (cmode).
			@return: {object} retorna todo std, de esta manera se le puede asignar un nuevo alias fuera del ambito.
		**/
		cmode: function () {
			if ( window.$ === std ) {
				window.$ = _$;
			}
			return std;
		},

		/****** DOM ******/
		dom: {
			/**
				Engloba la ejecución de varios getElements, para esto se usa un unico prefijo antes del nombre del identificador.
				Los prefijos permitidos son:
				# = getElementById
				. = getElementsByClassName
				@ = getElementByName
				Si se omite el prefijo se utilizara getElementsByTagName.
				@param: {string} id es el identificador del nodo que se busca, este identificador puede ser un id, una clase, un nombre o una etiqueta
				@param: {element} node es el padre del elemento que se busca
				@param: {string} tag es opcional y se utiliza para agilizar la busqueda de clases, es el tipo de etiqueta de la clase
				@return: El nodo o serie de nodos que se buscan con esta función
			*/
			get: function (id, node, tag) {
				var name = id.substr(1),
					prefix = id.charAt(0);
				
				if (node === null) {
					return testElement;
				}
				if (prefix == "#") {
					return document.getElementById(name);
				}
				node || (node = document);
				if(prefix == "@") {
					return node.getElementsByName(name);
				}
				if (prefix == ".") {
					if (node.getElementsByClassName) {
						return node.getElementsByClassName(name);
					}
					tag || (tag = "*");
					var classElements = [],
						els = std(tag),
						pattern = new RegExp("(^|\\s)"+name+"(\\s|$)");
					for (var i=0,el, j=0; el=els[i]; i++) {
						if ( pattern.test(el.className) ) {
							classElements[j] = el;
							j++;
						}
					}
					return classElements;
				}
				return node.getElementsByTagName(id);
			},
		
			/**
				Revisa si el DOM está listo para usarse. Es más util que el window.onload pues este debe esperar 
				a que todos los elementos de la pagina esten cargados (como scripts e imagenes) para ejecutar.
				@return: La api publica consta de una unica función que pide como parametro la función a ejecutar
			*/
			ready: (function(){
				var readyList = [];
				
				/**
					Revisa constantemente (cada 100 milisegundos) si el readyState del documento se encuentra completo,
					de ser asi se procesa la cola de ejecución.
					@param: {undefined} fn se utiliza como parte de XP (eXtreme Programming) para no inicializar el fn que toma 
							valores dentro del while
				*/
				function bindReady() {
					var called = FALSE;
					
					/*
						La función que carga las funciones del ready, despues de cargar cada función se remueven 
						los eventos asociados al principio.
					*/
					function ready() {
						if (!called){
							called = TRUE;
							do {
								readyList.shift()();
							} while (readyList.length);
							std.evt.remove(document, {
								"DOMContentLoaded": ready,
								"dataavailable": ready,
								"readystatechange": stateChange
							});
							std.evt.remove(window, "load", ready);
						}
					}

					//Prueba para leer el DOM <IE8
					function tryScroll () {
						try {
							testElement.doScroll("left");
							ready();
						} catch(e) {
							setTimeout(tryScroll, 0);
						}
					}

					//funcion que comprueba que el DOM halla completado
					function stateChange () {
						var readyState = document.readyState;
						if ( readyState == "complete" || readyState == "interactive" ) {
							ready();
						}
					}
					
					
					//Se tiene aparte una manera extra para cargar el DOM para <IE8
					if ( testElement.doScroll && window == window.top ) {
						tryScroll();
					}
					/*
						añadiendo manejadores a los eventos que controlan la carga del documento, de esta manera aparecen:
						DOMContentLoaded, dataavailable (Carga más rapido que DOMContenetLoaded), onreadystatechange y 
						para window load (document no lo acepta)
					*/ 
					std.evt.add(document, {
						"DOMContentLoaded": ready,
						"dataavailable": ready,
						"readystatechange": stateChange
					});
					std.evt.add(window, "load", ready);
				}
				
				/**
					Coloca en cola de ejecución una función para ser procesada cuando el DOM se encuentre completamente listo,
					si es la primera vez que se llama se ejecuta la función encargada de revisar el estado del documento.
					@param: {function} fn se ejecuta cuando se carga el DOM
				*/
				return function(handler) {
					if (!readyList.length) {
						bindReady();
					}
					readyList.push(handler);
				}
			})()
		},
		
		/****** EVENTOS ******/
		evt: (function() {
			var id = 0,//id unico para cada funcion que se vaya a asociar
				events = [],//array con las funciones asociadas a IE<9
				core = {
				/**
					Realiza la captura de ciertos eventos a un elemento.
					@param: {element} element es el elemento al cual se le va a asignar el evento
					@param: {string} nEvent es el nombre del evento que va a ser asignado
					@param: {function} fn es la funcion que se encargara de manejar el evento
					@param: {boolean} capture estable el flujo de eventos TRUE si es capture y FALSE si es bubbling
				*/
				add: function(element, nEvent, fn, capture) {
					if(loops(element, nEvent, fn, capture)) {
						if (element.addEventListener) {
							element.addEventListener(nEvent,fn,capture);
						} else if (element.attachEvent) {
							if (fn.id == undefined) {
								events[id] = function(){
									fn.call(element,event);
								};
								fn.id = id++;
							}
							element.attachEvent("on"+nEvent,events[fn.id]);
						} else {
							element["on"+nEvent] = fn;
						}
					}
				},
				
				/**
					Realiza la remoción de ciertos eventos a un elemento.
					@param: {element} element es el elemento al cual se le va a desasignar el evento
					@param: {string} nEvent es el nombre del evento que va a ser desasignado
					@param: {function} fn es la funcion que se encuentra manejando el evento
					@param: {boolean} capture establece como ocurria el flujo de eventos TRUE si es capture y FALSE si es bubbling 
				*/
				remove: function(element, nEvent, fn, capture){
					if(loops(element, nEvent, fn, capture)) {
						if (element.removeEventListener){
							element.removeEventListener(nEvent,fn,capture);
						} else if (element.detachEvent) {
							element.detachEvent("on"+nEvent,events[fn.id]);
						} else {
							element["on"+nEvent] = function(){};
						}
					}
				},
				
				/**
					Se encarga de poner a escuchar a un elemento los eventos que generan sus hijos, tiene como principal pilar el burbujeo 
					del evento, por lo cual no son soportados los eventos que no burbujean
					@param {element} element es el elemento que va a "observar" a sus elementos hijos
					@param {string} observe es el selector de los hijos que se van a observar, los prefijos utilizados son los mismos que para $
					@param {string} nEvent es el nombre del evento que va a ser asignado
					@param {function} fn es la funcion que se les asiganara a los elementos onservados
					@param {boolean} capture establece como ocurria el flujo de eventos TRUE si es capture y FALSE si es bubbling
				*/
				on: function(element, observe, nEvent, fn, capture) {
					if(loops(element, nEvent, fn, capture, observe)) {
						var prefix = observe.charAt(0),
							type = 	(prefix == "#")?"id":
									(prefix == ".")?"className":
									(prefix == "@")?"name":
									"nodeName",
							name = (type=="nodeName")?observe.toUpperCase():observe.substr(1);
						core.add(element, nEvent, function() {
							var target = core.get().target;
							if(observe == "*") {
								fn.apply(target, arguments);
							} else {
								while(target && target !== element) {
									var array = target[type].split(' ');
									for (var i=0, el; el = array[i]; i++) {
										if(el == name) {
											fn.apply(target, arguments);
										}
									}
									target = target.parentNode;
								}
							}
						}, capture);
					}
				},
				
				/**
					Se encarga de generar un objeto evento con un formato unico permitiendo asi una solución crossbrowser.
					@return: El evento formateado para su correcto uso
				*/
				get: function() {
					var e = window.event;	
					if (e) {
						if( navigator.appName == "Opera") {
							return e;
						}
						e.charCode = (e.type == "keypress") ? e.keyCode : 0;
						e.eventPhase = 2;
						e.isChar = (e.charCode > 0);
						e.pageX = e.clientX + document.body.scrollLeft;
						e.pageY = e.clientY + document.body.scrollTop;
						
						e.preventDefault = function() {
							this.returnValue = FALSE;
						};
						
						if (e.type == "mouseout") {
							e.relatedTarget = e.toElement;
						} else if (e.type == "mouseover") {
							e.relatedTarget = e.fromElement;
						}
							
						e.stopPropagation = function() {
							this.cancelBubble = TRUE;
						};
							
						e.target = e.srcElement;
						e.time = (new Date).getTime();
						return e;
					}
					
					return core.get.caller.arguments[0];
				}
			};
			
			/**
				Cuando un evento va a realizar multiples asignaciones tanto de funciones como de elementos, estas asignaciones se deben
				realizar por medio de ciclos, de esta manera se garatiza una ejecución limpia de los asignadores de eventos.
				@param {element} element es el o los elemento a los cuales se les va a asignar el evento
				@param {string} nEvent en caso de ser un objeto, tomara los valores de nEvent y fn dentro de una asignación limpia
				@param {function} fn tomara el valor de capture si nEvent es un objeto 
				@param {boolean} capture en caso de nEvent ser un objeto debera ser nulo y no se tomara en cuenta
				@param {string} observe puede ser undefined lo que indica que fue llamado desde add o remove
			*/
			function loops(element, nEvent, fn, capture, observe) {
				var caller = loops.caller;

				if (!element) {
					return FALSE;
				}
				if(!element.nodeType && element.length) {
					for(var i=0; el = element[i]; i++) {
						caller(el, nEvent, fn, capture);
					}
					return FALSE;
				}
				if(nEvent instanceof Object) {
					for(var attr in nEvent) {
						if(observe) {
							caller(element, observe, attr, nEvent[attr], fn);
						} else {
							caller(element, attr, nEvent[attr], fn);
						}
					}
					return FALSE;
				}
				return TRUE;
			}
			
			return core;
			
		})(),
		
		/****** ESTILOS ******/
		css: (function(parseInt){			
			/**
				Busca selectores CSS dentro de las hojas de estilos del documento que coincidan con la regla de estilo pasada como parametro,
				en caso de encontrarla procede a eliminarla o retornarla segun sea el caso.
				@param: {String} ruleName es el selector de la regla de estilo a buscar
				@param: {boolean} deleteFlag especifica si se desea o no eliminar la regla de estilo
				@return: Retorna la regla de estilo, si se paso como verdadero deleteFlag retorna TRUE si elimino la regla, en caso de 
						 no encontrala retorna FALSE.
			*/
			function getCSSRule(ruleName, deleteFlag) {
				//console.log(ruleName);
				var styleSheets = document.styleSheets,
					i = styleSheets.length,
					j, cssRules, cssRule, styleSheet;

				while (i--) {
					styleSheet = styleSheets[i];
					cssRules = styleSheet.cssRules || styleSheet.rules;
					j = cssRules.length;
					while(j--){
						cssRule = cssRules[j];
						if (cssRule.selectorText == ruleName) {
							if (deleteFlag) {
								if (styleSheet.cssRules) {
									styleSheet.deleteRule(j);
								} else {
									styleSheet.removeRule(j);
								}
								return TRUE;
							} else {
								return cssRule;
							}
						}
					}
				}
				return FALSE;
			}
			
			/**
				Aplica ciertos cambios a una propiedad CSS para que esta resulte estandar al usuario y pueda ser una solución crossbrowser.
				Al ser el argumento un array este es pasado por referencia.
				@param: {Array} args son los argumentos pasados a la función que la invoco (get o set) 
			*/
			function normalize(args) {
				var prop = args[0],
					value = args[1];
				if(prop == "opacity" && testElement.style[prop] == undefined) {
					if (!value && value !== 0) {
						value = 1;
					}
					prop = "filter";
					value = "alpha(opacity='"+value*100+"')";
				}
				if (prop.indexOf("-") != -1) {
					prop = prop.split( "-" );
					for (var i=1, word; word = prop[i]; i++) {
						prop[i] = word.charAt(0).toUpperCase()+word.substr(1);
					}
					prop = prop.join("");
				}
				args[0] = prop;
				args[1] = value;
			}
			
			/**
				Establece si se va a trabajar el estilo sobre una regla css o sobre el elemento directamente, en caso que una regla css no exista 
				esta función la crea. Tambien establece los metodos get y set de las propiedades css del elemento.
				@param: {String || element} ruleName es el selector de la regla de estilo a buscar o directamente el elemento con el cual trabajar
				@param: {boolean} deleteFlag especifica si se elimina una regla css, en caso de ruleName no ser una regla css este parametro 
						sera omitido
				@return: El objeto con los metodos get y set necesarios para trabajar los estilos de manera correcta y estandarizada
			*/
			return function(ruleName, deleteFlag) {
				var styleSheets = document.styleSheets,
					obj;
				if(typeof ruleName == "string") {
					if(deleteFlag) {
						getCSSRule(ruleName, deleteFlag)
					} else {
						if (!styleSheets.length) {
							$("head")[0].appendChild(document.createElement("style"));
						}
						var styleSheetEnd = styleSheets[styleSheets.length-1],
							lengthRule = styleSheetEnd.length;
						if (!getCSSRule(ruleName)) {

							if (styleSheetEnd.addRule) {
								styleSheetEnd.addRule(ruleName, NULL, lengthRule);
							} else {
								styleSheetEnd.insertRule(ruleName+" { }", lengthRule);
							}
						}
						obj = getCSSRule(ruleName);
					}
				} else {
					obj = ruleName;
				}
				return {
					/**
						Formatea correctamente la salida del valor de las propiedades del elemento.
						@see: FUNCIONES PRIVADAS
						@param: {String} prop es el nombre de la propiedad que se desea obtener
						@return: El valor de la propiedad que se a pasado como parametro				
					*/
					get: function(prop) {
						normalize(arguments);
						var style = (obj != ruleName)?obj.style[prop]:(obj.currentStyle || document.defaultView.getComputedStyle(obj, ""))[prop];
						//unificar a rgb la salida de colores
						if(style.indexOf("#") == 0) {
							style = "rgb("+hexToRGB(style).join(", ")+")";
						}
						if(prop == "filter") {
							style = (style == "")?"1":(parseInt(style.replace(/[^\d]/g,""))/100)+"";
						}
						return style;
					},
					
					/**
						Establece o modifica las propiedades de estilo al elemento o regla css, teniendo en cuenta la estandarización de 
						las mismas.
						@param: {String} prop es la propiedad que se desea establecer o modificar, si es un objeto se procedera en ciclo
						@param: {String} value es el nuevo valor que tomara la propiedad, en caso de prop ser un objeto este argumento sera omitido
					*/
					set: function(prop, value) {
						if(prop instanceof Object) {
							for(var attr in prop) {
								this.set(attr, prop[attr]);
							}
						} else {
							normalize(arguments);
							obj.style[prop] = value;
						}
					}
				};
			}
			
		})(parseInt),
		
		/****** AJAX ******/
		ajax: {
			/**
				Crea un objeto XMLHttpRequest crossbrowser
				@return: El objeto XMLHttpRequest dependiendo del browser en el que se realize la petición
			*/
			xhr: function() {
				try {
					return new XMLHttpRequest();
				} catch(e1) {
					try {
						return new ActiveXObject("Microsoft.XMLHTTP");
					} catch(e2) {
						try {
							return new ActiveXObject("Msxml2.XMLHTTP");
						} catch(e3) {
							return NULL;
						}
					}
				}
			},
			
			/**
				Realiza una petición asincrona al servidor
				@param: {String} url es la ruta del archivo del servidor que procesara la solicitud
				@param: {function} callback es la función que establece el comportamiento cuando el servidor retorna una respuesta 200
				@param: {object} opt es el conjunto de opciones que se le puede pasar al metodo, estas opciones son:
					response: Tipo de respuesta, puede ser text (por defecto) o XML
					feedback: Función que establece un comportamiento cuando el servidor no retorna una respuesta 200
					data: Datos que se envian al servidor desde el cliente
					method: Metodo utilizado para enviar los datos, puede ser POST (por defecto) o GET
					sync: Valor booleano que dice si la petición es sincrona, por defecto es false y la petición se realiza de modo asincrono
			*/
			request: function(url, callback, opt) {
				opt || (opt = {});
				var xmlHttp =std.ajax.xhr(),
					response = (opt.response || "Text").toUpperCase(),
					feedback = opt.feedback,
					data = opt.data,
					method = (opt.method || "POST").toUpperCase(),
					async = !opt.sync;
				
				xmlHttp.onreadystatechange = function() {
					if (xmlHttp.readyState == 4) {
						if (xmlHttp.status == 200) {
							callback( (response == "XML")?xmlHttp.responseXML:xmlHttp.responseText );
						} else if (feedback) {
							feedback(xmlHttp.status);
						}
					} else if (feedback) {
						feedback(xmlHttp.readyState);
					}
				};
				data = std.ajax.url(data);
				if (method == "GET" && data) {
					url = url+"?"+data;
					data = NULL;
				}
				xmlHttp.open(method, url, async);
				if (method == "POST") {
					xmlHttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
				}
				xmlHttp.setRequestHeader("X-Requested-With", "XMLHttpRequest");
				xmlHttp.send(data);
			},
			
			/**
				Genera una cadena segura para enviar por ajax o por url partiendo de un objeto dado.
				@param: {Object} obj es el objeto que se desea convertir en la cadena url segura
				@return: una cadena url segura para enviar via ajax o url
			*/
			url: function(obj) {
				if(!(obj instanceof Object)) {
					return obj;
				}
				var res=[], i=0, typeKey;
				for(var key in obj) {
					typeKey = typeof obj[key];
					if(typeKey == "string" || typeKey == "number") {
						res[i] = encodeURIComponent(key)+"="+encodeURIComponent(obj[key]);
						i++;
					}
				}

				return res.join("&");
			},
			
			/**
				Convierte un formulario en un objeto javascript que contiene los datos para ser enviados via a ajax.
				@see: NUCLEO
				@param: {element} form es la representación del formulario que se desea procesar
				@return: Un objeto que contiene todos los campos diligenciados del formulario (nombre del campo: valor del campo)
			*/
			form: function(form) {
				var obj = {};
				
				for(var i=0, inp; inp = form[i]; i++) {
					if(inp.name != undefined  && inp.name != "") {
						if(inp.type == "radio" || inp.type == "checkbox") {
							if(inp.checked) {
								obj[inp.name] = inp.value;
							}
						} else {
							obj[inp.name] = inp.value;
						}
					}
				}
				return obj;
			}
		},
		
		/****** EFECTOS ESPECIALES ******/
		sfx: {
			/**
				Genera un efecto drag and drop de un elemento dentro de otro elemento contenedor.
				@see: EVENTOS, ESTILOS
				@param: {Event} e es el evento que dispara la función que invoco al metodo
				@param: {Element} mov es el elemento que se va a mover dentro del contenedor
				@param: {Element} area es el contenedor sobre el cual se movera mov
			*/
			dyd: function (e, mov, area) {
				var cEjeX = e.clientX+testElement.scrollLeft+document.body.scrollLeft,
					cEjeY = e.clientY+testElement.scrollTop+document.body.scrollTop,
					cssMov = std.css(mov),
					marginL = parseInt(cssMov.get("marginLeft")) || 0,
					marginT = parseInt(cssMov.get("marginTop")) || 0,
					initX = mov.offsetLeft-marginL,
					initY = mov.offsetTop-marginT;
				
				/**
					Cambia la posición del elemento que se esta arrastrando dependiendo de la posición del puntero.
					@see: EVENTOS, ESTILOS
				*/
				function drag() {
					var e = std.evt.get(),
						nowX = e.clientX+testElement.scrollLeft+document.body.scrollLeft,
						nowY = e.clientY+testElement.scrollTop+document.body.scrollTop,
						aLeft = area.offsetLeft,
						aTop = area.offsetTop,
						aHeight = area.offsetHeight,
						aWidth = area.offsetWidth,
						x = initX+nowX-cEjeX,
						y = initY+nowY-cEjeY;
					
					if (x<=(marginL*-1)+aLeft) {
						x = (marginL*-1)+aLeft;
					} else if (x>=(aWidth+marginL+aLeft-(mov.offsetWidth+marginL*2))) {
						x = aWidth+marginL+aLeft-(mov.offsetWidth+marginL*2);
					}
					if (y<=(marginT*-1)+aTop) {
						y = (marginT*-1)+aTop;
					} else if (y>=(aHeight+marginT+aTop-(mov.offsetHeight+marginT*2))) {
						y = aHeight+marginT+aTop-(mov.offsetHeight+marginT*2);
					}
					if(cssMov.get("position") == "relative") {
						x = x-aLeft;
						y = y-aTop;
					}
					cssMov.set({
						left: x+"px",
						top: y+"px"
					});
					e.preventDefault();
				}
				
				/**
					Remueve los eventos mousemove y mouseup del documento
					@see: EVENTOS
				*/
				function drop() {
					std.evt.remove(document,{
						mousemove: drag,
						mouseup: drop
					}, TRUE);
				}
				
				std.evt.add(document,{
					mousemove: drag,
					mouseup: drop
				}, TRUE);
				
				e.preventDefault();
			},
			
			/**
				Genera pequeñas transiciones y animaciones sobre elementos del DOM
				@see: ESTILOS, FUNCIONES PRIVADAS
				@param: {Element} element es el elemento o nodo al cual se le aplicara la animación
				@param: {Object} props son las propiedades que se van a modificar durante la animación (propiedad: valor final)
				@param: {Object} opt son las opciones configurables durante la animación, estas son:
					duration: (por defecto 1000) es el tiempo que durara la animación representado en milisegundos
					fps: (por defecto 60) es el numero de frames por segundo o dicho de otra forma pasos por segundo
					onComplete: es un comportamiento final tras acabar la animación
			*/
			anim: function (element, props, opt) {
				opt || (opt={});
				var style = std.css(element),
					duration = parseInt(opt.duration) || 1000,
					fps = parseInt(opt.fps) || 60,
					onComplete = opt.onComplete,
					time,
					timer,
					from = [],
					to = [],
					post = [];
				
				for(var prop in props) {
					var cssProp = style.get(prop);
					if(cssProp.indexOf("rgb") == 0) {
						post[prop] = [];
						var value = props[prop];
						if(value.indexOf("#") == 0) {
							to[prop] = hexToRGB(value);
						} else {
							to[prop] = value.substring(4, value.length-1).split(",");
						}
						from[prop] = cssProp.substring(4, cssProp.length-1).split(",");
						for(var i=0; i<3; i++) {
							from[prop][i] = parseInt(from[prop][i]);
							post[prop][i] = (from[prop][i]-to[prop][i])/((duration/1000)*fps);
						}
					} else {
						from[prop] = parseFloat(cssProp);
						to[prop] = parseFloat(props[prop]);
						post[prop] = isNaN(props[prop])?props[prop].replace(/(\+|-)?\d+/g, ""):"";
					}
				}
				
				time = +new Date;
				timer = setInterval(function () {
					var currentTime = +new Date;
					if(currentTime < time + duration) {
						for(var prop in props) {
							if(style.get(prop).indexOf("rgb") == 0) {
								for(var i=0; i<3; i++) {
									from[prop][i] = Math.round(from[prop][i]-post[prop][i]);
								}
								style.set(prop, "rgb(" + from[prop].toString() + ")");
							} else {
								style.set(prop, (from[prop] + (to[prop] - from[prop]) * ((currentTime - time) / duration)) + post[prop]);
							}
						}
					} else {
						timer = clearInterval(timer);
						for(var prop in props) {
							style.set(prop, props[prop]);
						}
						if(onComplete) {
							onComplete();
						}
					}
				}, Math.round(1000/fps));

				return timer;
			}
		}
	};

/****** FUNCIONES PRIVADAS ******/
/**
	Cuando un color se encuentra en formato #hexadecimal esta función lo convierte a RGB, se bebe comprobar que el parametro pasado es un 
	hexadecimal ya que la función no realiza dicha comprobación.
	@param: {String} color es precesimente el color que debe estar en formato hexadecimal
	@return: Un arreglo con los valores [R,G,B].
*/
function hexToRGB(color) {
	color = color.substr(1);
	if (color.length==3) {
		var aux = color.split("");
		color = "";
		for (var i=0;i<3;i++){
			color+=aux[i]+aux[i];
		}
	}
	color = parseInt(color, 16);
	return [color >> 16, color >> 8 & 255, color & 255];
}

/****** CONFIGURACION DE ENTORNO ******/

/**
	Extendiende los objetos nativos javascript necesarios para el funcionamiento de la liberia,
	no se tiene en cuenta Object dado que provoca un mal funcionamiento del for in (verbosean)
*/
std.extend (String.prototype,{
	/**
		Limpia espacios a los lados de las cadenas
		@this {String}
		@return: La cadena sin ningun tipo de espacios a los lados
	*/
	trim: function() {
		return this.replace(/^[\s\t\r\n]+|[\s\t\r\n]+$/g,"");
	}
});



/****** JSON ******/
if(window.JSON == undefined) {
	window.JSON = {
		/**
			Genera una cadena JSON valida partiendo de un objeto javascript sin funciones.
			@param: {object} obj es el objeto suministrado para ser parseado a String
			@return: Una cadena formateada correctamente como JSON
			@deprecated
		*/
		stringify: function(obj) {
			if (!(obj instanceof Object)) {
				return;
			}
			var isArray = (obj instanceof Array),
				strJSON = isArray?"[":"{";
				
			for(var key in obj) {
				if(obj[key] instanceof Object) {
					if(!isArray) {
						strJSON += '"'+key+'" : ';
					}
					strJSON += JSON.stringify(obj[key])+", ";
				} else if(typeof obj[key] != "function") {
					if(!isArray) {
						strJSON += '"'+key+'" : ';
					}
					strJSON += '"'+obj[key]+'", ';
				}
			}
			
			return strJSON.substr(0, strJSON.length-2) + (isArray?" ]":" }");
		},
		/**
			Crea un objeto partiendo de una cadena JSON correctamente formateada.
			@param: {String} str es un string que debe se parseado a un objeto javascript
			@return: Dependiendo si es una cadena JSON valida se retornara el objeto, en caso contrario no se retorna nada
			@deprecated
		*/
		parse: function(strJSON) {
			if (typeof strJSON == "string" && /^[\],:{}\s]*$/
				.test(strJSON.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@')
				.replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']')
				.replace(/(?:^|:|,)(?:\s*\[)+/g, ''))) {
				return window[ "eval" ]("("+strJSON+")");
			}
			
			return;
		}
	};
}

/* Estableciendo un mismo atajo para std.dom.ready y std.dom.get */
window.$ = function () {
	var fun = (typeof arguments[0] == "function")? std.dom.ready: std.dom.get;
	return fun.apply(this, arguments);
}

/* Estableciendo std en el exterior */
std.extend (window.$, std);
std = window.std = window.$;

})(window);