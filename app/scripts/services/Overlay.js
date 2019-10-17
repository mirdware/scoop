function generateOverlay() {
  const $overlay = document.createElement('div');
  const $loading = $overlay.cloneNode();
  $loading.className = 'loading';
  $overlay.id = 'scoop-overlay';
  $overlay.appendChild($loading);
  document.body.appendChild($overlay);
  return $overlay;
}

export default class Overlay {
  constructor() {
    this.$dom = document.getElementById('scoop-overlay') || generateOverlay();
  }

  open() {
    this.$dom.style.display = 'block';
  }

  isOpen() {
    return this.$dom.style.display === 'block';
  }

  close() {
    this.$dom.style.display = '';
  }
}
