import Modal from './modal';
import FormService from '../services/Form';

function submit(form, $) {
  const formData = $.inject(FormService).toObject(form);
  $.done(formData);
}

export default class Search extends Modal {
  listen() {
    const parent = super.listen();
    const mount = parent.mount;
    return Object.assign(parent, {
      mount: (e) => {
        const autofocus = e.target.querySelector('[autofocus]');
        if (autofocus) {
          autofocus.focus();
        }
        mount(e);
      },
      'form': {
        _submit: (e) => submit(e.target, this)
      }
    });
  }
}
