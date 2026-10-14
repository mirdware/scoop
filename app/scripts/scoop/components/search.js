import { inject } from 'scalar';
import Modal from './modal';
import ModalService from '../services/Modal';
import FormService from '../services/Form';

function submit(form, $) {
  const formData = $._form.toObject(form);
  $.done(formData);
}

@inject(ModalService, FormService)
export default class Search extends Modal {
  constructor(modal, form) {
    super(modal);
    this._form = form;
  }

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
