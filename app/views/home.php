<?php \scoop\view\Maker::expand('layers/layer', $this->viewData) ?>
<style>
body {
	width: 90%;
	margin: 0 auto;
}
label {
	width: 100px;
}
span {
	padding-left: 1em;
}
form {
	width: 580px;
}
</style>
<a href="http://mirdware.org"><img src="<?php echo ROOT ?>public/images/scoop.png" alt="scoop" /></a>
<a href="https://github.com/mirdware/scoop"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png" alt="Fork me on GitHub"></a>
<h1>Pagina de prueba usando el bootstrap <?php echo APP_NAME ?></h1>
<p>Puedes probar como funciona el sistema de notificaciones y el routedaor interno de url adicionando <b>home/msj/Hola+mundo/</b> en la barra de direcciones, si deseas que aparesca como tipo error o alert debes enviar como ultimo parametro el tipo notificación, en esta pagina se pondra a prueba el metodo sedMail que fue adicionado a la clase Helper:</p>
<form method="post" action="<?php echo ROOT ?>home/send/" enctype="multipart/form-data" target="frame-ajax" class="control-pick" >
	<label>de:</label><input type="text" name="from" class="input-text" size="30" required /><br />
	<label>para:</label><input type="text" name="to" class="input-text" size="30" required /><br />
	<label>Asunto:</label><input type="text" name="subject" class="input-text" size="30" required /><br />
	<label>CC:</label><input type="text" name="reply" class="input-text" size="30"  /><br />
	<label>CCO:</label><input type="text" name="reply" class="input-text" size="30"  /><br />
	<label>Responder a:</label><input type="text" name="reply" class="input-text" size="30"  /><br />
	<label>Formato:</label> <label>HTML <input type="radio" name="format" value="html" checked /><span></span></label> <label>PLAIN <input type="radio" name="format" value="plain" /><span></span></label><br /><br />
	<div id="file-container">
		<div id="file-1" style="margin-bottom:1em;">
			<div class="custom-input-file btn">
				<input type="file" class="input-file" name="file-1" />Adjuntar archivo
			</div><span></span>
		</div>
	</div>
	<label>Mensaje: </label><textarea rows="10" cols="50" class="input-text" name="msj"></textarea>
	<div class="center">
		<input type="submit" class="btn red" value="Enviar" id="btn-submit" />
	</div>
</form>
<iframe name="frame-ajax" style="display:none"></iframe>
<script type="text/javascript">
	function loadName () {
		var parent = this.parentNode.parentNode,
			data = parent.id.split("-"),
			info = $("span", parent)[0],
			addFile = (info.innerHTML == '');

		info.innerHTML = this.value+' <img src="'+root+'public/images/delete.png" style="cursor:pointer;" />';

		if (addFile) {
			var id = parseInt(data[1])+1,
				containerFile = $("#file-container"),
				cunstom = document.createElement("div"),
				container = cunstom.cloneNode(true),
				input = document.createElement("input"),
				span = document.createElement("span"),
				text = document.createTextNode("Adjuntar archivo");

			cunstom.className = "custom-input-file btn";
			container.id = "file-"+id;
			container.style.marginBottom = "1em";
			input.type = "file";
			input.className = "input-file";
			input.name = 'file-'+id;

			cunstom.appendChild(input);
			cunstom.appendChild(text);
			container.appendChild(cunstom);
			container.appendChild(span);
			containerFile.appendChild(container);
			$.evt.add (input, "change", loadName);
			$.evt.add ($("img", parent)[0], "click", deleteFile);
		}
		
	}

		function deleteFile () {
			var parent = this.parentNode.parentNode;
			$.evt.remove ($("input", parent)[0], "change", loadName);
			$.evt.remove (this, "click", deleteFile);
			parent.parentNode.removeChild(parent);
		}

	function submitForm () {
		var self = this,
			form = self.form,
			r = $("@frame-ajax")[0];
		form.submit();
		self.disabled = true;
		r.onload = function () {
			r = (r.contentWindow || r.contentDocument);
			if (r.document) {
				r = r.document.body.innerHTML;
			}
			//procesamiento de la respuesta
			alert (r);
			self.disabled = false;
		}
	}

	$ (function () {
		$.evt.add ($(".input-file"), "change", loadName);
		$.evt.add ($("#btn-submit"), "click", submitForm);
	});
</script>