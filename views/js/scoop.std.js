(function ($, window, undefined) {
	var FALSE = false,
		TRUE = true,
		document = window.document,
		message = (function () {
			var duration = 400,
				timer,
				container;

			function hide (container) {
				timer = setTimeout(function () {
					$.sfx.anim(container, {
						opacity: 0,
						height: "0px"
					}, {
						duration: duration
					});
				}, 10000);
			}

			$(function () {
				container = $("#msg-error") || $("#msg-out") || $("#msg-alert");
				if (container) {
					$.css(container).set({
						opacity: 1,
						height: (container.offsetHeight-parseInt($.css(container).get("paddingTop"))*2)+"px"
					});
					hide(container);
				} else {
					$.css("#msg-error").set("opacity", 0);
					$.css("#msg-out").set("opacity", 0);
				}
			});

			return function (type, msg) {
				if (type != "error" && type != "out" && type != "alert") {
					return;
				}
				var height;
				container = $("#msg-error") || $("#msg-out") || $("#msg-alert") || $("#msg-not");
				container.id = "msg-"+type;
				container.innerHTML = msg;
				clearTimeout(timer);

				container.style.height = "";
				height = (container.offsetHeight-parseInt($.css(container).get("paddingTop"))*2)+"px";
				container.style.height = 0;
				$.sfx.anim(container, {
					opacity: 1,
					height: height
				}, {duration: duration});

				hide(container);
			};
		})(),
		/*
			Este par de funciones sirven como una suerte de efecto placeholder,
			de esta manera cuando obtengan el foco desaparecera el valor que tiene el
			input y si lo pierde estando vacio, recupera la información que poseia en un
			principio.
		*/
		placeholder = (function () {
			var originInput = {};

			function reset () {
				if (originInput[this.id] === undefined) {
					originInput[this.id] = this.value;
					this.value = "";
				} else if (this.value == originInput[this.id]) {
					this.value = "";
				}
			}

			function revert () {
				if (this.value == "") {
					this.value = originInput[this.id];
				}
			}

			return {
				add: function (input) {
					$.evt.add(input, {focus: reset, blur: revert});
				},
				reset: function () {
					originInput = {};
				}
			};
		})();

		function safePassword () {
			var clave = this.value,
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
				success = opt.success,
				trouble = opt.trouble,
				error,
				key;

			for (key in data) {
				error = $("#error-"+key);
				if (error) {
					error.style.visibility = "hidden";
				}
			}
			$.ajax.request(url, {
				callback: function (r) {
					var res;
					try { res = JSON.parse(r); } catch (ex) { res = false; }
					if (res) {
						if (res.out) {
							$.message("out", res.out);
							success&&success(form);
						} else if (res.error) {
							$.message("error", res.error);
							trouble&&trouble(form);
						} else {
							for (key in res) {
								var error = $("#"+key);
								error.title = res[key];
								error.style.visibility = "visible";
							}
							trouble&&trouble(form, res);
						}
					} else {
						success&&success(form, r);
					}
				},
				data: data
			});
		}

		$.extend($, {
			message: message,
			placeholder: placeholder,
			password: {
				safe: function (input) {
					$.evt.add(input, "keyup", safePassword);
				}
			}
		});

		$.extend($.ajax, {submit: submit});

})(std, window);