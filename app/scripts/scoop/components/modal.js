import { Component, inject } from 'scalar';
import ModalService from '../services/Modal';

function close($) {
  $.dismiss ? $.dismiss() : $._modal.close();
}

@inject(ModalService)
export default class Modal extends Component {
  constructor(modal) {
    super();
    this._modal = modal;
  }

  listen() {
    return {
      mount: () => {
        this.compose(this._modal.overlay.$dom, () => ({
          click: () => close(this)
        }));
      },
      '.header div': {
        click: () => close(this)
      }
    };
  }

  send(data = {}) {
    Object.assign(this, data);
    return new Promise((resolve, reject) => {
      this.dismiss = (reason) => {
        this._modal.close();
        reject(reason);
      };
      this.done = (value) => {
        this._modal.close();
        resolve(value);
      }
    });
  }
}
