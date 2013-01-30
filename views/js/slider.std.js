/**
	Mi primer modulo externo de std.js, su estructura inicial fue un desastre (de hecho aun lo sigue siendo) ya que std no
	estaba orientado a la generación de modulos hasta la versión 0.0.9 en la que se desprendio modal de la libreria integral.
	Se a acoplado al uso de modulos externos de std, aunque sigue teniendo muchos desperfectos y asuntos para arreglar, a pesar
	de ser mi primer modulo externo de la libreria por su naturaleza, modal deberia ser conssiderada un mejor ejemplo del uso de
	modulos externos.

	@author: Marlon Ramirez
	@version: 0.1
**/

(function ($, window, undefined) {
	var FALSE = false,
		TRUE = true,
		document = window.document,
		opacity = 0.4;

	$.css(".slider-std .control .navLeft").set("opacity", opacity);
	$.css(".slider-std .control .navRight").set("opacity", opacity);
	$.css(".slider-std .control .link").set("opacity", opacity);

	function show(slider, img) {
		
		//create controls
		var ul = $("ul", slider)[0],
			lis = toArray($("li", ul)),
			control = createElement("li"),
			link = createElement("a"),
			navLeft = link.cloneNode(TRUE),
			navRight = link.cloneNode(TRUE),
			nav = createElement("div"),
			item = link.cloneNode(TRUE),
			now = 0,
			block = FALSE,
			prev = 0,
			timer;

		item.appendChild(createTextNode(0));
		nav.appendChild(item);
		/* ciclo que inicia los valores de todos lo elementos de lista
		excepto el principal */
		for(var i=1, li; li = lis[i]; i++) {
			item = link.cloneNode(TRUE);
			item.appendChild(createTextNode(i));
			item.className = "normal";
			$.css(li).set({
				display: "none",
				opacity: 0
			});
			nav.appendChild(item);
		}
		control.className = "control";
		navLeft.title = "previus image";
		navLeft.className = "navLeft";
		link.className = "link";
		navRight.title = "next image";
		navRight.className = "navRight";
		nav.className = "slider-nav";
		
		control.appendChild(navLeft);
		control.appendChild(navRight);
		control.appendChild(link);
		
		slider.appendChild(nav);
		ul.appendChild(control);
		//
		select();
		animSpan( $("span", lis[now])[0]);
		/*limpiar nodos vacios
		for (var i=0, node; node = controls[i];i++){
           if (node.nodeType == 3 && !/\S/.test(node.nodeValue))
               node.parentNode.removeChild(node);
        }
		*/
		
		$.css(slider).set({
			maxWidth: img.width+"px",
			display: "block"
		});
		
		//eventos
		$.evt.add([navLeft, navRight], {
			mouseover: function () {
				$.css(this).set("opacity", 0.7);
			},
			mouseout: function () {
				$.css(this).set("opacity", opacity);
			}
		});
		
		$.evt.add(navLeft, "click", function(){
			if(block) {
				return;
			}
			prev = now;
			if(now == 0) {
				now = lis.length-1;
			} else {
				now --;
			}
			anim();
		});
		$.evt.add(navRight, "click", next);
		
		$.evt.add($("a", nav), "click", function() {
			if(block) {
				return;
			}
			var index = this.innerHTML;
			if(index != now) {
				prev = now;
				now = this.innerHTML;
				anim();
			}
		});
		
		function next() {
			if(block) {
				return;
			}
			prev = now;
			if(now == (lis.length-1)) {
				now = 0;
			} else {
				now++;
			}
			anim();
		}
		
		function select() {
			var anchor = nav.childNodes,
				props = ["href", "rel", "target"],
				a, span;
			
			if(span = $("span", lis[prev])[0]) {
				span.style.marginTop = 0;
			}
			anchor[prev].className = "normal";
			anchor[now].className = "span";
			if(a = $("a", lis[now])[0]) {
				link.style.visibility = "visible";
				for (var i=0, prop; prop = props[i]; i++) {
					link[prop] = a[prop];
				}
			} else {
				link.style.visibility = "hidden";
			}
			
		}
		
		function animSpan (span) {
			if(span) {
				$.sfx.anim(span,{marginTop: -(span.offsetHeight)+"px"});
			}
			timer = setTimeout(next, 5000);
		}
		
		function anim() {
			var vel = 300;
			block = TRUE;
			clearTimeout(timer);
			select();
			$.sfx.anim(lis[prev], {opacity:0}, {
				duration: vel,
				onComplete: function(){
					var span = $("span", lis[now])[0];
					lis[prev].style.display = "none";
					lis[now].style.display = "block";
					if (span) {
						span.style.marginTop = 0;
					}
					$.sfx.anim(lis[now], {opacity: 1}, {
						duration: vel,
						onComplete: function() {
							animSpan(span);
							block = FALSE;
						}
					});
				}
			});
		}
		
		/*
			Ajustar el alto del li segun el ancho de la imagen, de esta manera se logra ocultar
			el span debajo de la imagen.
		*/
		var imgPrev = $("img", slider)[now],
			wp = imgPrev.offsetWidth;
		ul.style.height = imgPrev.offsetHeight+"px";
		$.evt.add(window, "resize", function() {
			var imgNow = $("img", slider)[now],
				wn = imgNow.offsetWidth;
			if(wp != wn && wn != 0) {
				wp = wn;
				ul.style.height = imgNow.offsetHeight+"px";
			}
		});
		
	}
	
	function toArray(obj) {
		 var array = [];
		 for (var i = obj.length >>> 0; i--;) {
			 array[i] = obj[i];
		}
		return array;
	}

	function load(slider) {
		var img = new Image();
		$.evt.add(img, "load", function() {
			show(slider, img);
		});
		img.src = $("img" ,slider)[0].src;
	}

	function createElement (element) {
		return document.createElement(element);
	}

	function createTextNode (text) {
		return document.createTextNode(text);
	}

	/* Exportar el modulo */
	$.extend($, { slider: load });
	
	$(function() {
		for (var sliders = $(".slider-std"), i = 0, slider; slider = sliders[i]; i++) {
			load(slider);
		}
	});

})(std, window);