import { Component, Resource, IoC } from 'scalar';
import { Message } from './Message';

const document = window.document;
let blockSubmit = false;

// Custom form validation
function changeFormUI(form) {
  // Adding the new behaviour to the DOM
  function validate() {
    let invalid = form.querySelectorAll(':invalid');
    let allErrors = form.querySelectorAll('.error');

    // removing existing errors
    for (let i = 0, myError; myError = allErrors[i]; i++) {
      myError.classList.remove('error');
    }

    for(i = 0; myError = invalid[i]; i++) {
      // setting the custom behavior if element willValidate
      if (myError.willValidate) {
        let errorContainer = myError.parentNode;
        let errorIcon = errorContainer.getElementsByClassName('icon')[0];
        let validationMessage = myError.validationMessage;
        if (i === 0) {
          myError.focus();
        }
        errorContainer.className += ' error';
        myError.title = validationMessage;
        if (errorIcon) {
          errorIcon.title = validationMessage;
        }
      }
    }
  };

  /* The 'invalid' event is the one that triggers the
     errors. Here we are preventing those errors.*/
  form.addEventListener('invalid', function (evt) {
    evt.preventDefault();
    validate();
  }, true);

  /* Support Safari and Android browser—each of which do not prevent
     form submissions by default */
  form.addEventListener('submit', function (evt) {
    if (!this.checkValidity()) {
      evt.preventDefault();
      validate();
    }
  });
}

// adding the required attribute for multiple check boxes
function deleteRequiredAttr() {
  let thisCount = document.querySelectorAll('.options:checked').length;

  if (thisCount > 0) {
    for (let i = 0, myCheckBox; myCheckBox = allCheckBox[i]; i++) {
      myCheckBox.removeAttribute('required');
    }
  } else {
    for (i = 0; myCheckBox = allCheckBox[i]; i++) {
      myCheckBox.setAttribute('required', 'required');
    }
  }
}

if (document.querySelectorAll !== undefined) {
  let forms = document.querySelectorAll('.scoop-form');
  let allCheckBox = document.querySelectorAll('.options');

  for (let i = 0, myCheckBox; myCheckBox = allCheckBox[i]; i++) {
    myCheckBox.setAttribute('required', 'required');
    myCheckBox.addEventListener('change', deleteRequiredAttr);
  }

  for (let i = 0, form; form = forms[i]; i++) {
    if (form.checkValidity !== undefined) {
      changeFormUI(form);
    }
  }
}

function formatObject(form) {
  let obj = {},
    i=0, inp, name, type, index;

  for(; inp = form[i]; i++) {
    name = inp.name;
    if(name) {
      type = inp.type;
      if( (type == "radio" || type == "checkbox") && !inp.checked ) {
        continue;
      }
      if (name.indexOf("[]") != -1) {
        name += i;
      }
      obj[name] = inp.value;
      if(!obj[name] && type.indexOf("select") == 0 && (index = inp.selectedIndex) != -1) {
        obj[name] = inp.options[index].text;
      }
    }
  }
  return obj;
}

function submit(evt) {
  evt.preventDefault();
  let form = evt.target,
      data = formatObject(form),
      url = form.action,
      error,
      key;

  for (key in data) {
    error = $('#error-'+key.replace('_', '-'));
    if (error) {
      error.style.visibility = 'hidden';
    }
  }
  if (!blockSubmit) {
    blockSubmit = true;
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
      let resource = new this.src(url);
      resource.post(data).then(callback);
    }
  }

  function callback (r) {
    let res = false;
    let message = IoC.inject(Message);
    try { res = JSON.parse(r); } catch (ex) {}
    blockSubmit = false;

    if (res) {
      if (res.redirect) {
        window.location = res.redirect;
      }
      if (res.out) {
        message.showInfo(res.out);
        success && success(form, res);
        return;
      }
      if (res.error) {
        if (typeof res.error === 'string') {
            message.showError(res.error);
        } else {
          for (key in res.error) {
            var error = $('#error-'+key);
            error.title = res.error[key];
            error.style.visibility = 'visible';
          }
        }
        this.trouble(form, res);
        return;
      }
      r = res;
    }
    this.success(form, r);
  };
}

export class Form extends Component {
  constructor(selector = '.scoop-form') {
    super(selector);
    this.src = Resource;
  }

  listen() {
    return {
      'submit': submit
    }
  }

  trouble() {  }

  success() {  }
}
