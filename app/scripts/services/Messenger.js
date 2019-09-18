export default class Messenger {
  show(msg, type) {
    Object.assign(this.component, { msg, type });
  }

  close() {
    this.component.type = 'not';
  }

  showError(msg) {
    this.show(msg, 'error');
  }

  showInfo(msg) {
    this.show(msg, 'info');
  }

  showAlert(msg) {
    this.show(msg, 'warning');
  }

  showSuccess(msg) {
    this.show(msg, 'success');
  }
}
