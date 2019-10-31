export default class Messenger {
  show(msg, type) {
    if (this.component) Object.assign(this.component, { msg, type });
  }

  close() {
    this.show('', 'not');
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
