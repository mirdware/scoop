import { Resource, Component } from 'scalar';
import Messenger from '../services/Messenger';
import FormService from '../services/Form';

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
    errorContainer.title = validationMessage;
    if (icon) icon.title = validationMessage;
  }
}

function removeErrors(form) {
  for (let i = 0, error; error = form.elements[i]; i++) {
    error.parentNode.classList.remove('error');
    error.removeAttribute('title');
  }
}

function submit($, form) {
  $.loading = true;
  $.inject(Messenger).close();
  removeErrors(form);
  if (!form.checkValidity) validate(form);
  const data = $.inject(FormService).toObject(form);
  if (form.enctype === 'multipart/form-data') {
    delete resource.headers['Content-Type'];
  }
  $.resource[$.method](data)
  .then((res) => {
    $.loading = false;
    $.done(res, form);
  }).catch((res) => {
    $.loading = false;
    $.fail(res, form);
  });
}

function reset(form) {
  const autofocusField = form.querySelector('[autofocus]');
  if (autofocusField instanceof HTMLInputElement) {
    autofocusField.focus();
  }
}

export default class Form extends Component {
  listen() {
    return {
      mount: (e) => {
        const form = e.target;
        this.method = (form.getAttribute('method') || 'get').toLowerCase();
        this.resource = new Resource(form.action);
      },
      '.input': {
        _invalid: (e) => validate(e.target.form)
      },
      reset: (e) => reset(e.target),
      _submit: (e) => submit(this, e.target)
    };
  }

  fail(res, form) {
    try {
      const errors = JSON.parse(res.message);
      let focused = false;
      for (const key in errors) {
        const input = document.getElementById(key.replace(/_/g, '-'));
        if (input) {
          const container = input.parentNode;
          const icon = container.getElementsByClassName('icon')[0];
          if (!focused) {
            input.focus();
            focused = true;
          }
          container.classList.add('error');
          if (icon) icon.title = errors[key];
        }
      }
    } catch (ex) {
      this.inject(Messenger).showError(res.message);
      form.reset();
      this.reset();
    }
  }

  done(res, form) {
    this.inject(Messenger).showSuccess(res);
    if (this.method === 'post') {
      form.reset();
      return this.reset();
    }
    const passwords = form.querySelectorAll('input[type="password"]');
    for (let i = 0, password; password = passwords[i]; i++) {
      password.value = '';
    }
  }
}
