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
    if (icon) icon.dataset.tooltip = validationMessage;
  }
}

function removeErrors(form) {
  for (let i = 0, error; error = form.elements[i]; i++) {
    error.parentNode.classList.remove('error');
  }
}

function submit($, form) {
  $.loading = true;
  $.inject(Messenger).close();
  removeErrors(form);
  if (!form.checkValidity) validate(form);
  if (!$.resource) {
    $.method = (form.getAttribute('method') || 'get').toLowerCase();
    $.resource = new Resource(form.action);
  }
  if (form.enctype === 'multipart/form-data') {
    delete resource.headers['Content-Type'];
  }
  $.submit($.inject(FormService).toObject(form), form);
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
      '.input': {
        _invalid: (e) => validate(e.target.form)
      },
      reset: (e) => reset(e.target),
      _submit: (e) => submit(this, e.target)
    };
  }

  submit(data, form) {
    this.resource[this.method](data)
    .then((res) => {
      this.loading = false;
      this.done(res, form);
    }).catch((res) => {
      this.loading = false;
      this.fail(res, form);
    });
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
          if (icon) icon.dataset.tooltip = errors[key];
        }
      }
    } catch (ex) {
      this.inject(Messenger).showError(res.message);
      form.reset();
    }
  }

  done(res, form) {
    this.inject(Messenger).showSuccess(res);
    if (this.method === 'post') {
      return form.reset();
    }
    const passwords = form.querySelectorAll('input[type="password"]');
    for (let i = 0, password; password = passwords[i]; i++) {
      password.value = '';
    }
  }
}
