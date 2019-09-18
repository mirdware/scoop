import { Resource, Component } from 'scalar';
import Messenger from '../services/Messenger';

let blockSubmit = false;

function validate(form) {
  const invalid = form.querySelectorAll(':invalid');
  for(let i = 0, myError; myError = invalid[i]; i++) {
    // setting the custom behavior if element willValidate
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

function callback ($, form, res) {
  blockSubmit = false;
  (res.code >= 400) ?
  $.trouble(res, form) :
  $.success(res, form);
}

// Custom form validation
function changeFormUI(form) {
  if (!form.checkValidity) return;
  form.addEventListener('invalid', (evt) => {
    evt.preventDefault();
    validate(form);
  }, true);
  /* Support Safari and Android browser—each of which do not prevent
     form submissions by default */
  form.addEventListener('submit', (evt) => {
    if (!form.checkValidity()) {
      evt.preventDefault();
      validate(form);
    }
  });
}

function formatObject(form) {
  let obj = {};
  for (let i = 0, inp; inp = form[i]; i++) {
    let name = inp.name;
    if(!name) return;
    let type = inp.type;
    if( (type == "radio" || type == "checkbox") && !inp.checked ) continue;
    let index = inp.selectedIndex;
    if (name.indexOf("[]") != -1) {
      name += i;
    }
    obj[name] = inp.value;
    if(!obj[name] && type.indexOf("select") == 0 && index != -1) {
      obj[name] = inp.options[index].text;
    }
  }
  return obj;
}

function getFrame() {
  let frame = document.getElementById('frame-scoop-ajax');
  if (frame) return frame;
  frame = document.createElement('iframe');
  frame.style.display = 'none';
  frame.name = 'frame-scoop-ajax';
  frame.id = 'frame-scoop-ajax';
  document.body.appendChild(frame);
}

function getContent(frame) {
  let content = (frame.contentWindow || frame.contentDocument);
  if (content.document) {
    content = content.document.body.innerHTML;
  }
  return content;
}

function removeErrors(form) {
  const allErrors = form.getElementsByClassName('error');
  for (let i = 0, error; error = allErrors[i]; i++) {
    error.classList.remove('error');
  }
}

function submit($, form) {
  $.inject(Messenger).close();
  removeErrors(form);
  if (blockSubmit) return;
  blockSubmit = true;
  if (form.enctype === 'multipart/form-data') {
    const frame = getFrame();
    form.target = 'frame-scoop-ajax';
    form.submit();
    frame.onload = () => callback($, form, getContent(frame));
    return;
  }
  const resource = new Resource(form.action);
  const data = formatObject(form);
  const method = form.method.toLowerCase();
  const send = resource[method](data);
  send
  .then((res) => callback($, form, res))
  .catch((res) => callback($, form, res));
}

export default class Form extends Component {
  listen() {
    return {
      mount: (e) => changeFormUI(e.target),
      submit: (e) => submit(this, e.target)
    }
  }

  trouble(res) {
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

  success(res) {
    if (res.redirect) window.location = res.redirect;
    if (res.out) this.inject(Messenger).showSuccess(res.out);
  }
}
