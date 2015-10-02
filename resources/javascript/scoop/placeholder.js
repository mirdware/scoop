(function ($, undefined) {
    var sph = 0,
        originInput = {},
        core = {
            add: function(input) {
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
            reset: function() {
                originInput = {};
            }
        };

    function clear () {
        var id = getData(this, "sph");
        if (originInput[id] === undefined) {
            originInput[id] = this.value;
            this.value = "";
        } else if (this.value == originInput[id]) {
            this.value = "";
        }
    }

    function revert() {
        if (this.value == "") {
            this.value = originInput[getData(this, "sph")];
        }
    }

    function getData(el, id) {
        return el.getAttribute("data-"+id);
    }

    function setData(el, id, value) {
        el.setAttribute("data-"+id, value);
    }

    $.extend($, {placeholder: core})    
})(jetro);
