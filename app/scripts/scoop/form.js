import { Component, Resource, IoC } from 'scalar';

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

function callback (self, form, r) {
  let res = false;
  try {
    res = JSON.parse(r);
  } catch (ex) {}
  blockSubmit = false;
  if (!res) return self.trouble(form, r);
  if (res.redirect) {
    window.location = res.redirect;
  }
  if (res.out) {
    message.showInfo(res.out);
    return self.success(form, res);
  }
  if (res.error) {
    if (typeof res.error === 'string') {
        message.showError(res.error);
        return self.trouble(form, res);
    }
    for (key in res.error) {
      const error = document.querySelector('#error-'+key);('#error-'+key);
      error.title = res.error[key];
      error.style.visibility = 'visible';
    }
    return self.trouble(form, res);
  }
  self.success(form, res);
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
  for (let i = 0, input; inp = form[i]; i++) {
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
  let frame = document.querySelector('#frame-scoop-ajax');
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

export class Form extends Component {
  constructor(selector = '.scoop-form') {
    super(selector);
    this.perform((form) => changeFormUI(form));
  }

  submit(evt) {
    evt.preventDefault();
    const form = evt.target;
    const errors = form.querySelectorAll('.error');
    for (let i = 0, error; error = errors[i]; i++) {
      error.classList.remove('error');
    }
    if (blockSubmit) return;
    blockSubmit = true;
    if (form.enctype === 'multipart/form-data') {
      const frame = getFrame();
      form.target = 'frame-scoop-ajax';
      form.submit();
      frame.onload = () => callback(this, form, getContent(frame));
      return;
    }
    const resource = new Resource(form.action);
    const data = formatObject(form);
    const method = form.method.toUpperCase();
    const send = method === 'POST' ? resource.post(data) : resource.get(data);
    send.then((res) => callback(this, form, res));
  }

  trouble() {  }

  success() {  }
}
