import { Component, Resource, IoC } from 'scalar';
import { Message } from './Message';

let blockSubmit = false;

function validate(form) {
  const invalid = form.querySelectorAll(':invalid');
  const allErrors = form.querySelectorAll('.error');

  // removing existing errors
  for (let i = 0, myError; myError = allErrors[i]; i++) {
    myError.classList.remove('error');
  }

  for(let i = 0, myError; myError = invalid[i]; i++) {
    // setting the custom behavior if element willValidate
    if (!myError.willValidate) continue;
    const errorContainer = myError.parentNode;
    const errorIcon = errorContainer.getElementsByClassName('icon')[0];
    const validationMessage = myError.validationMessage;
    if (i === 0) myError.focus();
    errorContainer.className += ' error';
    myError.title = validationMessage;
    if (errorIcon) errorIcon.title = validationMessage;
  }
}

function callback (r) {
  let res = false;
  try { res = JSON.parse(r); } catch (ex) {}
  blockSubmit = false;
  if (!res) {
    return this.trouble(form, r);
  }
  if (res.redirect) {
    window.location = res.redirect;
  }
  const message = IoC.inject(Message);
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
        const error = document.querySelector('#error-'+key);('#error-'+key);
        error.title = res.error[key];
        error.style.visibility = 'visible';
      }
    }
    return this.trouble(form, res);
  }
  r = res;
  this.success(form, r);
}

// Custom form validation
function changeFormUI(form) {
  form.addEventListener('invalid', (evt) => {
    evt.preventDefault();
    validate(form);
  }, true);

  /* Support Safari and Android browser—each of which do not prevent
     form submissions by default */
  form.addEventListener('submit', (evt) => {
    if (!this.checkValidity()) {
      evt.preventDefault();
      validate(form);
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
    i = 0, inp, name, type, index;

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
  let form = evt.target;
  IoC.inject(Message).showInfo('Prueba desde JS');
  const errors = form.querySelectorAll('.error');
  for (let i = 0, error; error = errors[i]; i++) {
    error.classList.remove('error');
  }
  if (!blockSubmit) {
    blockSubmit = true;
    if (form.enctype === 'multipart/form-data') {
      const frame = document.querySelector('#frame-scoop-ajax');
      if (!frame) {
        frame = document.createElement('iframe');
        frame.style.display = 'none';
        frame.name = 'frame-scoop-ajax';
        frame.id = 'frame-scoop-ajax';
        document.body.appendChild(frame);
      }
      form.target = 'frame-scoop-ajax';
      form.submit();
      frame.onload = () => {
        frame = (frame.contentWindow || frame.contentDocument);
        if (frame.document) {
          frame = frame.document.body.innerHTML;
        }
        callback(frame);
      };
    } else {
      let resource = new this.src(form.action);
      resource.post(formatObject(form)).then(callback);
    }
  }
}

export class Form extends Component {
  constructor(selector = '.scoop-form') {
    super(selector);
    this.perform((form) => changeFormUI(form));
    this.src = Resource;
  }

  listen() {
    return {submit: submit};
  }

  trouble() {  }

  success() {  }
}
