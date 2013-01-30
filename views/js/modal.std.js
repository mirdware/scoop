/**
	modal.std.js
	Por mucho tiempo (desde sus inicios) fue parate integral de la libreria std, de hecho la libreria nacio entorno a su
	uso, pues mi deseo inicial era crear una ventana (speudo)modal, pero al darme cuenta que podia reutilizar muchas 
	funciones para posteriores trabajos, cree mi liberia estandar de funciones (std.js). El modulo modal habia preservado
	su sitio; hasta la versión 0.0.9 de std.

	@author: Marlon Ramirez
	@version: 0.3
**/

(function ($, window, undefined) {
	var cache = window.sessionStorage || {},
		FALSE = false,
		TRUE = true,
		document = window.document,
		location = document.location,
		EventList = {ready: [], close: []},
		text = createElement("h2"),
		loading = createElement("div"),
		modal,
		overlay,
		local,
		body,
		core = {
			/**
				Renderiza el nodo la ventana modal dependiendo del enlace que la halla invocado, tomando el titulo de la misma etiqueta.
				Crea todo el marco de la ventana modal y el overlay que cubrira la pantalla del navegador.
				@see: std
			*/
			show: function (e) {
				var self = this,
					title = self.getAttribute("title") || "",
					isCache = self.rel.split("-")[1] == "cache",
					url = self.getAttribute("href"),
					path = location.href.replace(location.hash, ""),
					element;
					
				e && e.preventDefault();
				if (url.indexOf(path+"#") == 0) {
					url = url.replace(path, "");
				}

				if(!overlay) {
					$.css("#modal-std").set("opacity", 0);
					overlay = loading.cloneNode(FALSE);
					modal = loading.cloneNode(FALSE);
					var head = loading.cloneNode(FALSE),
						img = loading.cloneNode(FALSE);
					
					overlay.id = "overlay-std";
					modal.id = "modal-std";
					loading.id = "modal-loading"
					head.className = "modal-head";
					$.evt.add([img, overlay],"click",core.hide);
					head.appendChild(img);
					head.appendChild(text);
					modal.appendChild(head);
					body.appendChild(modal);
					body.appendChild(overlay);
					$.sfx.dyd(head, {
						mov: modal,
						area: overlay
					});
				}

				overlay.style.display = "";
				modal.style.display = "";

				if (modal.childNodes[1]) {
					$.sfx.anim(modal, {opacity: 0}, {
						onComplete: function(){
							removeLocal();
							core.show.call(self);
						},
						duration: 500
					});
					return;
				}

				if (url.indexOf("#") == 0) {
					local = $(url);
					element = local.cloneNode(TRUE);
					local.parentNode.removeChild(local);
				} else {
					var aux = createElement("div");
					if (!isCache || !(aux.innerHTML = cache[url])) {
						body.appendChild(loading);
						$.ajax.request(url, {
							callback: function(r){
								body.removeChild(loading);
								if (isCache) {
									cache[url] = r;
								}
								aux.innerHTML = r;
							},
							sync:TRUE
						});
					}
					element = aux.firstChild;
				}
				element.className += " modal-body";
				modal.appendChild(element);
				element.style.display = "block";
				text.innerHTML = title;
				$.sfx.anim(modal, {opacity:1});
				
				var childsModal = modal.childNodes,
					height = 0,
					width = element.offsetWidth;
				
				for (var i=0,h; h=childsModal[i]; i++) {
					height += h.offsetHeight;
				}
				$.css(modal).set({
					width: width+"px",
					height: height+"px",
					marginLeft: -(width/2)+"px",
					marginTop: -(height/2)+"px"
				});
				core.reset();
				bind ("ready");
			},
		
			/**
				Elimina el nodo principal de la ventana modal y oculta el resto de la estructura.
				@see: std
			*/
			hide: function () {
				$.sfx.anim(modal, {opacity: 0}, {
					onComplete: function (){
						overlay.style.display = "none";
						modal.style.display = "none";
						removeLocal();
					},
					duration: 500
				});
			},
		
			/**
				Restablece la posición de la ventana modal, siempre y cuando esta se encuentre dentro del DOM.
				@see: std
			*/
			reset: function (){
				modal && $.css(modal).set({
					top: "50%",
					left: "50%"
				});
			},

			/**
				Se encarga de agregar funciones para ejecutarlas al momento de cargar la ventana modal
				@param {function} fn es la función que sera agregada a la lista de eventos que se 
					dispararán cuando la ventana se cargue.
			*/
			ready: function (fn) {
				EventList.ready.push(fn);
			},

			/**
				Se encarga de agregar funciones para ejecutarlas al momento de cerrar la ventana modal
				@param {function} fn es la función que sera agregada a la lista de eventos que se 
					dispararán cuando la ventana se cierre.
			*/
			close: function (fn) {
				EventList.close.push(fn);
			}
		};
	
	function removeLocal () {
		var modalBody = modal.childNodes[1];
		if (modalBody) {
			local && body.appendChild(local);
			modal.removeChild(modalBody);
			bind ("close");
		} else {
			body.removeChild(loading);
		}
	}

	function bind (type) {
		var array = EventList[type],
			len = array.length,
			i = 0;
		for (; i < len; i++) {
			array[i]();
		}
	}

	function createElement (element) {
		return document.createElement(element);
	}

	/**
		Configuración de entorno para la creación de ventanas modal, aparte de generar el entorno
		para las ventanas, tambien permite omitir por (X)HTML el atributo target="_blank", pudiendolo
		reemplazar por rel="external"
	*/
	$.evt.add(window,"resize",core.reset);

	$(function (){
		body = document.body;
		$.evt.on(document,"a","click", function() {
			var self = this,
				rel = self.rel;
			if(rel.indexOf("modal") == 0 ) {
				core.show.apply(self,arguments);
			} else if(rel == "external" && self.target != "_blank") {
				self.target = "_blank";
			}
		});
	});

	$.extend($, {modal: core});
}) (std, window);