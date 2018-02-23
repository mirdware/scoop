import { Component } from 'scalar';

function close(e) {
  e.target.parentNode.className = 'not';
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
  }

  showInfo(msg) {
    this.msg = msg;
  }
}
