import Jetro from 'jetro';

(function ($, window) {
    var FALSE = false,
        TRUE = true,
        blockSubmit = FALSE,
        document = window.document;

    function submit(evt, opt) { //opt = {success: function(){}, trouble: function() {}, url: String}
        opt || (opt = {});
        evt.preventDefault();
        var form = evt.target,
            data = $.ajax.form(form),
            url = opt.url || form.action,
            container = $('#msg-error') || $('#msg-out') || $('#msg-warning'),
            success = opt.success,
            trouble = opt.trouble,
            error,
            key;

        if (container) {
            container.id= 'msg-not';
        }
        for (key in data) {
            error = $('#error-'+key.replace('_', '-'));
            if (error) {
                error.style.visibility = 'hidden';
            }
        }
        if (!blockSubmit) {
            blockSubmit = TRUE;
            if (form.enctype === 'multipart/form-data') {
                var frame = $('#frame-scoop-ajax');
                if (!frame) {
                    frame = document.createElement('iframe');
                    frame.style.display = 'none';
                    frame.name = 'frame-scoop-ajax';
                    frame.id = 'frame-scoop-ajax';
                    document.body.appendChild(frame);
                }
                form.target = 'frame-scoop-ajax';
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

            if (res) {
                if (res.redirect) {
                    window.location = res.redirect;
                }
                if (res.out) {
                    message('out', res.out);
                    success && success(form, res);
                    return;
                }
                if (res.error) {
                    if (typeof res.error === 'string') {
                        message('error', res.error);
                    } else {
                        for (key in res.error) {
                            var error = $('#error-'+key);
                            error.title = res.error[key];
                            error.style.visibility = 'visible';
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

    $.extend($.ajax, {submit: submit});
})(Jetro, window);
