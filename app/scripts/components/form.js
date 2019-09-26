import { Resource, Component } from 'scalar';
import Messenger from '../services/Messenger';

function validate(form) {
  const invalid = form.querySelectorAll(':invalid');
  removeErrors(form);
  for(let i = 0, myError; myError = invalid[i]; i++) {
    if (!myError.willValidate) continue;
    const errorContainer = myError.parentNode;
    const errorIcon = errorContainer.getElementsByClassName('icon')[0];
    const validationMessage = myError.validationMessage;
    if (i === 0) myError.focus();
    errorContainer.classList.add('error');
    myError.title = validationMessage;
    if (errorIcon) errorIcon.title = validationMessage;
  }
}

function formatObject(form) {
  let obj = {};
  for (let i = 0, inp; inp = form.elements[i]; i++) {
    let name = inp.name;
    if (!name) continue;
    let type = inp.type;
    if ( (type == "radio" || type == "checkbox") && !inp.checked ) continue;
    let index = inp.selectedIndex;
    if (name.indexOf("[]") != -1) {
      name += i;
    }
    obj[name] = inp.value;
    if (!obj[name] && type.indexOf("select") == 0 && index != -1) {
      obj[name] = inp.options[index].text;
    }
  }
  return obj;
}

function removeErrors(form) {
  for (let i = 0, error; error = form.elements[i]; i++) {
    error.parentNode.classList.remove('error');
    error.removeAttribute('title');
  }
}

function submit($, form) {
  let data;
  if ($.blockSubmit) return;
  $.blockSubmit = true;
  $.inject(Messenger).close();
  removeErrors(form);
  if (!form.checkValidity) validate(form);
  const resource = new Resource(form.action);
  if (form.enctype === 'multipart/form-data') {
    data = new FormData(form);
    delete resource.headers['Content-Type'];
  } else {
    data = formatObject(form);
  }
  resource[form.method.toLowerCase()](data)
  .then((res) => $.done(res, form))
  .catch((res) => $.fail(res, form))
  .then(() => $.blockSubmit = false);
}

export default class Form extends Component {
  listen() {
    return {
      invalid: (e) => {
        const form = e.target.form;
        removeErrors(form);
        validate(form);
      },
      submit: (e) => submit(this, e.target)
    }
  }

  fail(res) {
    if (res.code !== 400) return;
    try {
      const errors = JSON.parse(res.message);
      for (const key in errors) {
        const input = document.getElementById(key);
        const container = input.parentNode;
        const error = container.getElementsByClassName('icon')[0];
        container.classList.add('error');
        error.title = errors[key];
      }
    } catch (ex) {
      return this.inject(Messenger).showError(res.message);
    }
  }

  done(res, form) {
    if (res.redirect) window.location = res.redirect;
    if (res.out) {
      this.inject(Messenger).showSuccess(res.out);
      form.reset();
    }
  }
}
