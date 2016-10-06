(function(window, undefined) {
    var document = window.document;

    // Custom form validation
    function changeFormUI(form) {
        // Adding the new behaviour to the DOM
        var validate = function () {
            var i,
                myError,
                invalid = form.querySelectorAll(":invalid"),
                allErrors = form.querySelectorAll(".error");

            // removing existing errors
            for (i = 0; myError = allErrors[i]; i++) {
                myError.classList.remove("error");
            }

            for(i = 0; myError = invalid[i]; i++) {
                // setting the custom behavior if element willValidate
                if (myError.willValidate) {
                    var errorContainer = myError.parentNode;
                    if (i === 0) {
                        myError.focus();
                    }
                    errorContainer.className += " error";
                    myError.title = myError.validationMessage;
                }
            }
        };

        /* The "invalid" event is the one that triggers the
           errors. Here we are preventing those errors.*/
        form.addEventListener("invalid", function (evt) {
            evt.preventDefault();
            validate();
        }, true);

        /* Support Safari and Android browser—each of which do not prevent
           form submissions by default */
        form.addEventListener("submit", function (evt) {
            if (!this.checkValidity()) {
                evt.preventDefault();
                validate();
            }
        });
    }

    // adding the required attribute for multiple check boxes
    function deleteRequiredAttr() {
        var i,
            myCheckBox,
            thisCount = document.querySelectorAll(".options:checked").length;

        if (thisCount > 0) {
            for (i = 0; myCheckBox = allCheckBox[i]; i++) {
                myCheckBox.removeAttribute("required");
            }
        } else {
            for (i = 0; myCheckBox = allCheckBox[i]; i++) {
                myCheckBox.setAttribute("required", "required");
            }
        }
    }

    if (document.querySelectorAll === undefined) {
        return;
    }

    var i,
        form,
        myCheckBox,
        forms = document.querySelectorAll(".scoop-form"),
        allCheckBox = document.querySelectorAll(".options");

    for (i = 0; myCheckBox = allCheckBox[i]; i++) {
        myCheckBox.setAttribute("required", "required");
        myCheckBox.addEventListener("change", deleteRequiredAttr);
    }

    for (i = 0; form = forms[i]; i++) {
        if (form.checkValidity === undefined) {
            return;
        }
        changeFormUI(form);
    }
})(window);
