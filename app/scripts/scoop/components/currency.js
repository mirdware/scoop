const numberFormat = new Intl.NumberFormat('de-DE', { style: 'decimal',  minimumFractionDigits: 2 });

String.prototype.currency = function () {
  return '$' + numberFormat.format(this);
}

Number.prototype.currency = function () {
  return '$' + numberFormat.format(this);
}

function checkFormat(e) {
  const character = String.fromCharCode(e.keyCode);
  if (!isNaN(character)) return true;
  if (character === '.' && e.target.value.indexOf('.') === -1) return true;
}

function mask($target) {
  if (!$target.value) return;
  $target.value = numberFormat.format($target.value);
}

function unmask($target) {
  if (!$target.value) return;
  $target.value = parseFloat($target.value.replace(/\./g, '').replace(',', '.'));
  $target.select();
}

export default ($) => ({
  mount: (e) => {
    const $target = e.target;
    $target.setAttribute('autocomplete', 'off');
    mask($target);
  },
  blur: (e) => mask(e.target),
  focus: (e) => unmask(e.target),
  mutate: (e) => mask(e.target),
  _keypress: (e) => checkFormat(e)
});
