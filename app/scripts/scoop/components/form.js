import { Resource, Component } from 'scalar';
import Messenger from '../services/Messenger';
import FormService from '../services/Form';

function validate(form) {
  const invalid = form.querySelectorAll(':invalid');
  let focused = false;
  removeErrors(form);
  for (let i = 0, input; input = invalid[i]; i++) {
    if (!input.willValidate) continue;
    const errorContainer = input.parentNode;
    const validationMessage = input.validationMessage;
    if (!focused) {
      input.focus();
      focused = true;
    }
    errorContainer.classList.add('error');
    errorContainer.dataset.tooltip = validationMessage;
  }
}

function removeErrors(form) {
  for (let i = 0, error; error = form.elements[i]; i++) {
    const parent = error.parentNode;
    parent.classList.remove('error');
    parent.removeAttribute('data-tooltip');
  }
}

function submit($, form) {
  $.loading = true;
  $.inject(Messenger).close();
  removeErrors(form);
  if (!form.checkValidity) validate(form);
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
    if (!form.resource) {
      form.resource = new Resource(form.action);
      if (form.enctype !== 'multipart/form-data') {
        form.resource.headers['Content-Type'] = 'application/json';
      }
    }
    form.resource[(form.getAttribute('method') || 'get').toLowerCase()](data)
    .then((res) => {
      this.loading = false;
      this.done(res, form);
    }).catch((res) => {
      this.loading = false;
      this.fail(res, form);
    });
  }

  fail(res, form) {
    console.log(res);
    try {
      const errors = JSON.parse(res.message);
      let focused = false;
      for (const key in errors) {
        const input = document.getElementById(key.replace(/_/g, '-'));
        if (input) {
          const container = input.parentNode;
          if (!focused) {
            input.focus();
            focused = true;
          }
          container.classList.add('error');
          container.dataset.tooltip = errors[key];
        }
      }
    } catch (ex) {
      this.inject(Messenger).showError(res.message);
    }
  }

  done(res, form) {
    this.inject(Messenger).showSuccess(res);
    if (form.method.toLowerCase() === 'post') {
      form.reset();
      return reset(form);
    }
    const passwords = form.querySelectorAll('input[type="password"]');
    for (let i = 0, password; password = passwords[i]; i++) {
      password.value = '';
    }
  }
}
