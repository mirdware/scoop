(function ($, window) {
    var duration = 400,
        timer,
        divMsg,
        cssMsg,
        msgTop,
        msgHeight;

    function hide() {
        $.sfx.anim(divMsg, {
            opacity: 0,
            height: "0px",
            paddingTop: "0px",
            paddingBottom: "0px"
        }, {
            duration: duration,
            onComplete: function () {
                divMsg.className = "not";
            }
        });
    }

    function onScroll(e) {
        if (divMsg.className !== "not") {
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
        divMsg = $("#msg");
        if (divMsg) {
            cssMsg = $.css(divMsg);
            msgHeight = divMsg.offsetHeight-parseInt(cssMsg.get("paddingTop"))*2;

            cssMsg.set({
                opacity: 1,
                height: msgHeight+"px"
            });
        }
        if (!divMsg) return;
        $.evt.add(window, "scroll", onScroll);
        $.evt.add($("i", divMsg)[0], "click", hide);
    });

    $.extend($, {
        message: function (type, msg) {
            if (type != "error" && type != "success" && type != "warning" && type != "info") {
                throw new Error(type+" no es un tipo de mensaje valido");
            }
            divMsg.className = type;
            $("span", divMsg)[0].innerHTML = msg;
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
        }
    });
})(jetro, window);
