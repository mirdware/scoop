import { Component } from 'scalar';

function close(e) {
  this.type = 'not';
}

export class Message extends Component {
  constructor() {
    super('#msg');
  }

  listen() {
    return {
      '.close': {
        'click': close
      }
    };
  }

  showError(msg) {
    this.msg = msg;
    this.type = 'error';
  }

  showInfo(msg) {
    this.msg = msg;
    this.type = 'info';
  }
}
