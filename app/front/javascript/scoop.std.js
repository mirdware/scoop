(function ($, window, undefined) {
	var FALSE = false,
		TRUE = true,
		blockSubmit = FALSE,
		document = window.document,
		message = (function () {
			var duration = 400,
				timer,
				divMsg,
				cssMsg,
				msgTop,
				msgHeight;

			function hide ( ) {
				timer = setTimeout(function () {
					$.sfx.anim(divMsg, {
						opacity: 0,
						height: "0px",
						paddingTop: "0px",
						paddingBottom: "0px"
					}, {
						duration: duration,
						onComplete: function () {
							divMsg.id = "msg-not";
						}
					});
				}, 10000);
			}

			function onScroll (e) {
				if (divMsg.id !== "msg-not") {
					if (window.pageYOffset > msgHeight) {
						cssMsg.set({
							position: "fixed",
							top: msgTop+"px"
						});
					} else {
						cssMsg.set({
							position: "",
							top: ""
						});
					}
				}
			}

			$(function () {
				divMsg = $("#msg-error") || $("#msg-out") || $("#msg-alert");
				if (divMsg) {
					cssMsg = $.css(divMsg);
					msgHeight = divMsg.offsetHeight-parseInt(cssMsg.get("paddingTop"))*2;

					cssMsg.set({
						opacity: 1,
						height: msgHeight+"px"
					});
					hide( );
				} else {
					divMsg = $("#msg-not");
					cssMsg = $.css(divMsg);
					//$.css("#msg-out, #msg-error, #msg-alert, #msg-not").set("opacity", 0);
				}
				$.evt.add(window, "scroll", onScroll);
			});

			return function (type, msg) {
				if (type != "error" && type != "out" && type != "alert") {
					throw new Error(type+" no es un tipo de mensaje valido");
				}
				divMsg.id = "msg-"+type;
				divMsg.innerHTML = msg;
				msgTop = divMsg.offsetTop;
				clearTimeout(timer);

				cssMsg.set({
					opacity: 0,
					height: "",
					position: "",
					top: ""
				});

				if (window.pageYOffset > 0) {
					cssMsg.set({
						position: "fixed",
						top: msgTop+"px",
						paddingTop: "5px",
						paddingBottom: "5px"
					});
				}
				
				msgHeight = (divMsg.offsetHeight-parseInt(cssMsg.get("paddingTop"))*2);
				divMsg.style.height = 0;
				$.sfx.anim(divMsg, {
					opacity: 1,
					height: msgHeight+"px",
					paddingTop: "5px",
					paddingBottom: "5px"
				}, {duration: duration});

				hide( );
			};
		})(),
		/*
			Este par de funciones sirven como una suerte de efecto placeholder,
			de esta manera cuando obtengan el foco desaparecera el valor que tiene el
			input y si lo pierde estando vacio, recupera la información que poseia en un
			principio.
		*/
		placeholder = (function () {
			var sph = 0,
				originInput = {};

			function clear () {
				var id = getData(this, "sph");
				if (originInput[id] === undefined) {
					originInput[id] = this.value;
					this.value = "";
				} else if (this.value == originInput[id]) {
					this.value = "";
				}
			}

			function revert () {
				if (this.value == "") {
					this.value = originInput[getData(this, "sph")];
				}
			}

			function getData (el, id) {
				return el.getAttribute("data-"+id);
			}

			function setData (el, id, value) {
				el.setAttribute("data-"+id, value);
			}

			return {
				add: function (input) {
					if (input) {
						if (input.length) {
							for (var i=0, inp; inp=input[i]; i++) {
								if (inp.type === "text"|| inp.type === "search" || inp.type === "email") {
									setData(inp, "sph", sph++);
									$.evt.add(inp, {focus: clear, blur: revert});
								}
							}
						} else {
							setData(input, "sph", sph++);
							$.evt.add(input, {focus: clear, blur: revert});
						}
					}
				},
				reset: function () {
					originInput = {};
				}
			};
		})();

		function safePassword (e) {
			var clave = typeof e === "string"? e: this.value,
				color = 'rgb(173,255,47)',
				len = clave.length,
				nivel = "Muy alto",
				container = $("#control-pass"),
				numbers = "01234567890",
				lcLetters = "abcdefghijklmnñopqrstuvwxyza",
				ucLetters = "ABCDEFGHIJKLMNÑOPQRSTUVWXYZA",
				iNumbers = "98765432109",
				iLcLetters = "zyxwvutsrrqpoñnmlkjihgfedcbaz",
				iUcLetters = "ZYXWVUTSRRQPOÑNMLKJIHGFEDCBAZ",
				chars = "",
				ucChar = 0,
				lcChar = 0,
				numChar = 0,
				spChar = 0,
				cucChar = 0,
				clcChar = 0,
				cnumChar = 0,
				charRep = 0,
				cons = 0,
				only = 0,
				total;

			for (var i=0, charc, prev, union; i<len; i++) {
				charc = clave.charAt(i);
				union = prev+charc;

				if ( numbers.indexOf(union,0) != -1
					|| ucLetters.indexOf(union,0) != -1
					|| lcLetters.indexOf(union,0) != -1
					|| iNumbers.indexOf(union,0) != -1
					|| iUcLetters.indexOf(union,0) != -1
					|| iLcLetters.indexOf(union,0) != -1 ) {
					cons++;
				}

				if ( chars.indexOf(charc,0) == -1 ) {
					chars += charc;
				} else {
					charRep++;
				}

				if ( numbers.indexOf(charc,0) != -1 ) {
					if ( numbers.indexOf(prev,0) != -1 ) {
						cnumChar++;
					}
					numChar++;
				} else if ( lcLetters.indexOf(charc,0) != -1 ) {
					if ( lcLetters.indexOf(prev,0) != -1 ) {
						clcChar++;
					}
					lcChar++;
				} else if ( ucLetters.indexOf(charc,0) != -1 ) {
					if ( ucLetters.indexOf(prev,0) != -1 ) {
						cucChar++;
					}
					ucChar++;
				} else {
					spChar++;
				}

				prev = charc;
			}

			if ( (lcChar+ucChar) == len || numChar == len ) {
				only = len;
			}
			if (ucChar) {
				ucChar = ((len-ucChar)*3);
			}
			if (lcChar) {
				lcChar = ((len-lcChar)*3);
			}

			total = (len*7)+ucChar+lcChar+(numChar*4)+(spChar*5)
					-only-(charRep*3)-(cucChar*2)-(cnumChar*2)-(clcChar*2)-(cons*5);
			
			if (total<=0) {
				total = 0;
				nivel = "Nivel de seguridad";
			} else if (total<=20) {
				color = 'rgb(255,69,0)';
				nivel = "Muy bajo";
			} else if (total<=40) {
				color = 'rgb(255,165,0)';
				nivel = "Bajo";
			} else if (total<=60) {
				color = 'rgb(255,255,0)';
				nivel = "Medio";
			} else if (total<=80) {
				color = 'rgb(154,205,50)';
				nivel = "Alto"
			} else if (total >100) {
				total = 100;
			}

			$.css( $("b", container)[0] ).set({
				backgroundColor: color,
				width: total+"%"
			});
			$("span", container)[0].innerHTML = nivel;
		}

		function submit (evt, opt) { //opt = {success: function(){}, trouble: function() {}, url: String}
			opt || (opt = {});
			evt.preventDefault();
			var form = evt.target,
				data = $.ajax.form(form),
				url = opt.url || form.action,
				container = $("#msg-error") || $("#msg-out") || $("#msg-alert"),
				success = opt.success,
				trouble = opt.trouble,
				error,
				key;

			if (container) {
				container.id= "msg-not";
			}
			for (key in data) {
				error = $("#error-"+key.replace("_", "-"));
				if (error) {
					error.style.visibility = "hidden";
				}
			}

			if ( !blockSubmit ) {
				blockSubmit = TRUE;
				if ( form.enctype === "multipart/form-data" ) {
					var frame = $("#frame-scoop-ajax");
					if ( !frame ) {
						frame = document.createElement("iframe");
						frame.style.display = "none";
						frame.name = "frame-scoop-ajax";
						frame.id = "frame-scoop-ajax";
						document.body.appendChild(frame);
					}
					form.target = "frame-scoop-ajax";
					form.submit();
					frame.onload = function () {
						frame = (frame.contentWindow || frame.contentDocument);
						if (frame.document) {
							frame = frame.document.body.innerHTML;
						}
						//procesamiento de la respuesta
						callback(frame);
					};
				} else {
					$.ajax.request(url, {
						callback: callback,
						data: data
					});
				}
			}

			function callback (r) {
				blockSubmit = FALSE;
				var res = FALSE;
				try { res = JSON.parse(r); } catch (ex) {}

				if ( res ) {
					if (res.redirect) {
						window.location = res.redirect;
					}
					if (res.out) {
						message("out", res.out);
						success && success(form, res);
						return;
					}
					if (res.error) {
						if (typeof res.error === "string") {
							message("error", res.error);
						} else {
							for (key in res.error) {
								var error = $("#error-"+key);
								error.title = res.error[key];
								error.style.visibility = "visible";
							}
						}
						
						trouble && trouble(form, res);
						return;
					}
					r = res;
				}

				success && success(form, r);
			};

		}

		$.extend($, {
			message: message,
			placeholder: placeholder,
			password: {safe: safePassword}
		});

		$.extend($.ajax, {submit: submit});

})(std, window);