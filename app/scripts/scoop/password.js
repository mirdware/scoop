(function($) {
    var color = "rgb(173,255,47)",
        numbers = "01234567890",
        lcLetters = "abcdefghijklmnñopqrstuvwxyza",
        ucLetters = "ABCDEFGHIJKLMNÑOPQRSTUVWXYZA",
        iNumbers = "98765432109",
        iLcLetters = "zyxwvutsrrqpoñnmlkjihgfedcbaz",
        iUcLetters = "ZYXWVUTSRRQPOÑNMLKJIHGFEDCBAZ",
        core = {
            safe: function (e) {
                var clave = typeof e === "string"? e: this.value,
                    len = clave.length,
                    nivel = "Muy alto",
                    container = $("#control-pass"),
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
                    if (chars.indexOf(charc,0) == -1) {
                        chars += charc;
                    } else {
                        charRep++;
                    }
                    if (numbers.indexOf(charc,0) != -1) {
                        if (numbers.indexOf(prev,0) != -1) {
                            cnumChar++;
                        }
                        numChar++;
                    } else if (lcLetters.indexOf(charc,0) != -1) {
                        if ( lcLetters.indexOf(prev,0) != -1 ) {
                            clcChar++;
                        }
                        lcChar++;
                    } else if (ucLetters.indexOf(charc,0) != -1) {
                        if (ucLetters.indexOf(prev,0) != -1) {
                            cucChar++;
                        }
                        ucChar++;
                    } else {
                        spChar++;
                    }
                    prev = charc;
                }
                if ((lcChar+ucChar) == len || numChar == len) {
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
                    color = "rgb(255,69,0)";
                    nivel = "Muy bajo";
                } else if (total<=40) {
                    color = "rgb(255,165,0)";
                    nivel = "Bajo";
                } else if (total<=60) {
                    color = "rgb(255,255,0)";
                    nivel = "Medio";
                } else if (total<=80) {
                    color = "rgb(154,205,50)";
                    nivel = "Alto"
                } else if (total >100) {
                    total = 100;
                }
                $.css($("b", container)[0]).set({
                    backgroundColor: color,
                    width: total+"%"
                });
                $("span", container)[0].innerHTML = nivel;
            }
        };

    $.extend($, {password: core});
})(jetro);
