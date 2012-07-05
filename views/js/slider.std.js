/**
	Mi primer modulo externo de std.js, su estructura inicial fue un desastre (de hecho aun lo sigue siendo) ya que std no
	estaba orientado a la generación de modulos hasta la versión 0.0.9 en la que se desprendio modal de la libreria integral.
	Se a acoplado al uso de modulos externos de std, aunque sigue teniendo muchos desperfectos y asuntos para arreglar, a pesar
	de ser mi primer modulo externo de la libreria por su naturaleza, modal deberia ser conssiderada un mejor ejemplo del uso de
	modulos externos.

	@author: Marlon Ramirez
	@version: 0.1
**/

(function ($) {
	$.extend($, {
		slider: (function () {
			var opacity = 0.4;
	
			$.css(".slider .control .navLeft").set("opacity", opacity);
			$.css(".slider .control .navRight").set("opacity", opacity);
			$.css(".slider .control .link").set("opacity", opacity);
	
			function show(slider, img) {
				
				//create controls
				var ul = $("ul", slider)[0],
					lis = toArray($("li", ul)),
					control = document.createElement("li"),
					link = document.createElement("a"),
					navLeft = link.cloneNode(true),
					navRight = link.cloneNode(true),
					nav = document.createElement("div"),
					item = link.cloneNode(true),
					now = 0,
					block = false,
					prev = 0,
					timer;
						
				item.appendChild(document.createTextNode(0));
				nav.appendChild(item);
				for(var i=1, li; li = lis[i]; i++) {
					item = link.cloneNode(true);
					item.appendChild(document.createTextNode(i));
					item.className = "normal";
					lis[i].style.display = "none";
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
				animSpan();
				var controls = control.childNodes;
				/*limpiar nodos vacios
				for (var i=0, node; node = controls[i];i++){
		           if (node.nodeType == 3 && !/\S/.test(node.nodeValue))
		               node.parentNode.removeChild(node);
		        }
				*/
				
				$.css(slider).set("maxWidth", img.width+"px");
				
				//eventos
				$.evt.add([controls[0],controls[1]], {
					mouseover: function () {
						$.css(this).set("opacity", 0.7);
					},
					mouseout: function () {
						$.css(this).set("opacity", opacity);
					}
				});
				
				$.evt.add(controls[0], "click", function(){
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
				$.evt.add(controls[1], "click", next);
				
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
						a, span;
					
					if(span = $("span", lis[prev])[0]) {
						span.style.marginTop = 0;
					}
					anchor[prev].className = "normal";
					anchor[now].className = "span";
					if(a =$("a", lis[now])[0]) {
						link.style.visibility = "visible";
						link.href = a.href;
						link.rel = a.rel;
					} else {
						link.style.visibility = "hidden";
					}
					
				}
				
				function animSpan () {
					var span;
					if(span = $("span", lis[now])[0]) {
						$.sfx.anim(span,{marginTop: -(span.offsetHeight)+"px"});
					}
					timer = setTimeout(next, 5000);
				}
				
				function anim() {
					var vel = 300;
					block = true;
					clearTimeout(timer);
					select();
					$.sfx.fade(lis[prev], {
						duration: vel,
						onComplete: function(){
							$.sfx.fade(lis[now], {
								duration: vel,
								onComplete: function() {
									animSpan();
									block = false;
								}
							});
						}
					});
				}
				
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
				var img = img = new Image();
				$.evt.add(img, "load", function() {
					show(slider, img);
				});
				img.src = $("img" ,slider)[0].src;
			}
			
			$(function() {
				var sliders = $(".slider");
				for (var i=0, slider; slider = sliders[i]; i++) {
					load(slider);
				}
			});
		})()
	});
})(std);