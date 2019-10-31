import { Component } from 'scalar';
import ModalService from '../services/Modal';

function close($) {
  $.dismiss ? $.dismiss() : $._modal.close();
}

export default class Modal extends Component {

  listen() {
    return {
      mount: () => {
        const _modal = this.inject(ModalService);
        this._modal = _modal;
        this.compose(_modal.overlay.$dom, () => ({
          click: () => close(this)
        }));
      },
      '.header div': {
        click: () => close(this)
      }
    }
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
