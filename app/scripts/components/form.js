import { Resource, Component } from 'scalar';
import Messenger from '../services/Messenger';

function validate(form) {
  const invalid = form.querySelectorAll(':invalid');
  let focused = false;
  removeErrors(form);
  for(let i = 0, input; input = invalid[i]; i++) {
    if (!input.willValidate) continue;
    const errorContainer = input.parentNode;
    const icon = errorContainer.getElementsByClassName('icon')[0];
    const validationMessage = input.validationMessage;
    if (!focused) {
      input.focus();
      focused = true;
    }
    errorContainer.classList.add('error');
    input.title = validationMessage;
    if (icon) icon.title = validationMessage;
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
  if ($.blockSubmit) return;
  $.blockSubmit = true;
  $.inject(Messenger).close();
  removeErrors(form);
  if (!form.checkValidity) validate(form);
  $.submit(form);
}

function reset(form) {
  const autofocusField = form.querySelector('[autofocus]');
  if (autofocusField instanceof HTMLInputElement) {
    autofocusField.focus();
  }
  return true;
}

export default class Form extends Component {
  listen() {
    return {
      invalid: (e) => {
        const form = e.target.form;
        removeErrors(form);
        validate(form);
      },
      reset: (e) => reset(e.target),
      submit: (e) => submit(this, e.target)
    }
  }

  submit(form) {
    const resource = new Resource(form.action);
    let data;
    if (form.enctype === 'multipart/form-data') {
      data = new FormData(form);
      delete resource.headers['Content-Type'];
    } else {
      data = formatObject(form);
    }
    resource[form.method.toLowerCase()](data)
    .then((res) => this.done(res, form))
    .catch((res) => this.fail(res, form))
    .then(() => this.blockSubmit = false);
  }

  fail(res, form) {
    try {
      const errors = JSON.parse(res.message);
      let focused = false;
      for (const key in errors) {
        const input = document.getElementById(key);
        const container = input.parentNode;
        const icon = container.getElementsByClassName('icon')[0];
        if (!focused) {
          input.focus();
          focused = true;
        }
        container.classList.add('error');
        if (icon) icon.title = errors[key];
      }
    } catch (ex) {
      this.inject(Messenger).showError(res.message);
      form.reset();
      this.reset();
    }
  }

  done(res, form) {
    if (res.redirect) return window.location = res.redirect;
    this.inject(Messenger).showSuccess(res);
    form.reset();
    this.reset();
  }
}
