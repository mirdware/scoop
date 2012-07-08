/**
	modal.std.js
	Por mucho tiempo (desde sus inicios) fue parate integral de la libreria std, de hecho la libreria nacio entorno a su
	uso, pues mi deseo inicial era crear una ventana (speudo)modal, pero al darme cuenta que podia reutilizar muchas 
	funciones para posteriores trabajos, cree mi liberia estandar de funciones (std.js). El modulo modal habia preservado
	su sitio; hasta la versión 0.0.9 de std.

	@author: Marlon Ramirez
	@version: 0.1
**/

(function ($) {
	$.extend($, {
		modal: (function() {
			var cache = window.sessionStorage || {},
				FALSE = false,
				TRUE = true,
				NULL = true,
				modal,
				overlay,
				local,
				core = {
					/**
						Renderiza el nodo la ventana modal dependiendo del enlace que la halla invocado, tomando el titulo de la misma etiqueta.
						Crea todo el marco de la ventana modal y el overlay que cubrira la pantalla del navegador.
						@see: std
					*/
					show: function () {
						$.evt.get().preventDefault();
						var title = this.getAttribute("title") || "",
							isCache = this.rel.split("-")[1] == "cache",
							url = this.getAttribute("href"),
							path = document.location,
							element;

						if (url.indexOf(path+"#") == 0) {
							url = url.replace(path, "");
						}

						if(!overlay) {
							overlay = document.createElement("div");
							modal = overlay.cloneNode(FALSE);
							var head  = overlay.cloneNode(FALSE),
								img = overlay.cloneNode(FALSE),
								text = document.createElement("h2");
							
							overlay.id = "overlay";
							modal.id = "modal";
							head.className = "head";
							$.evt.add(head,"mousedown",function(){
								$.sfx.dyd($.evt.get(),modal,overlay);
							});
							$.evt.add([img, overlay],"click",core.hide);
							document.body.appendChild(overlay);
							head.appendChild(img);
							head.appendChild(text);
							modal.appendChild(core.round($.css(".head").get("backgroundColor"),"top"));
							modal.appendChild(head);							
							document.body.appendChild(modal);
						}
						
						if (modal.childNodes[2]) {
							var self = this,
								args = arguments;
							$.sfx.fade(modal, {onComplete:function(){
								removeLocal();
								core.show.apply(self, args);
							}, duration:500});
							return;
						} 
						
						if (url.indexOf("#") == 0) {
							local = $(url);
							var parent = local.parentNode;
							element = local.cloneNode(TRUE);
							parent.removeChild(local);
						} else {
							var aux = document.createElement("div");
							if (!isCache || !(aux.innerHTML = cache[url])) {
								$.ajax.request(url,function(r){
									//console.log(r);
									if (isCache) {
										cache[url] = r;
									}
									aux.innerHTML = r;
								}, {sync:TRUE});
							}
							element = aux.firstChild;
						}
						modal.appendChild(element);
						modal.appendChild(core.round($.css(element).get("backgroundColor"),"bottom"));
						element.style.display = "block";
						modal.childNodes[1].childNodes[1].innerHTML = title;
						
						$.css(overlay).set("display", "block");
						$.sfx.fade(modal);
						
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
						$.modal.reset();
					},
				
					/**
						Elimina el nodo principal de la ventana modal y oculta el resto de la estructura.
						@see: std
					*/
					hide: function () {
						$.sfx.fade(modal, {onComplete:function(){
							$.css(overlay).set("display", "none");
							removeLocal();
						}, duration:500});
					},
				
					/**
						Restablece la posición de la ventana modal, siempre y cuando esta se encuentre dentro del DOM.
						@see: std
					*/
					reset: function (){
						modal&&$.css(modal).set({
							top: "50%",
							left: "50%"
						});
					},
				
					/**
						Se encarga de crear bordes redondeados a la ventana modal.
						@param: {String} color es el color que se desea para el borde que se va a crear
						@param: {String} position es la posición en la que se colocaran los bordes redondeados, puede ser TOP o BOTTOM
						@return: Los bordes redondeados para ser incluidos en la ventana
					*/
					round: function (color, position) {
						position = position.toLowerCase();
						var border = document.createElement("b"),
							r = [],
							i = 0;
						while(i<4) {
							r[i] = border.cloneNode(FALSE);
							r[i].style.backgroundColor = color;
							r[i].className = "r"+(i+1);
							i++;
						}
						border.className = "round";
						
						if (position == "top"){
							i=0;
							while(i<4) {
								border.appendChild(r[i]);
								i++;
							}
						} else if (position == "bottom") {
							i=3;
							while(i>=0) {
								border.appendChild(r[i]);
								i--;
							}
						} else {
							return;
						}
						
						return border;
					}
				};
			
			function removeLocal () {
				if (local) {
					document.body.appendChild(local);
				}
				modal.removeChild(modal.childNodes[2]);
				modal.removeChild(modal.childNodes[2]);
			}


			/**
				Se encarga de gestionar los anchors que contengan atributos rel,
				ademas agrega los eventos necesarios para cuando se carga la libreria.
			*/
			$(function (){
				$.evt.add(window,"resize",$.modal.reset);
				$.evt.on(document,"a","click", function() {
					var rel = this.rel;
					if(rel.indexOf("modal") == 0 ) {
						$.modal.show.apply(this,arguments);
					} else if(rel == "external" && this.target != "_blank") {
						this.target = "_blank";
					}
				});
			});

			return core;
		})()
	});
}) (std);