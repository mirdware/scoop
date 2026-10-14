import { customElement } from 'scalar';
import styles from './styles.css?inline';
import template from './template.html?raw';

function loadOptions($) {
  const options = Array.from($.shadowRoot.host.querySelectorAll('option'));
  if (!options.length) return;
  options.forEach(($option) => {
    $.$select.appendChild($option);
    $.checkList.push($option);
  });
  refresh($);
}

function toogleItem(item, $) {
  item.selected = !item.selected;
  if ($.selectAll) {
    if (item.text === 'All') {
      $._data.forEach((i) => i.selected = item.selected);
    } else {
      calculateAllSelector($);
    }
  }
}

function dispatchEvent($) {
  $.$select.dispatchEvent(new Event('change'));
  $.shadowRoot.host.dispatchEvent(new CustomEvent('changed', {
    detail: JSON.parse(JSON.stringify($.value))
  }));
}

function calculateAllSelector($) {
  const allSelector = $.checkList[0];
  const selecteds = $._data.filter((check) => check.selected && !check.all).length;
  allSelector.selected = $._data.length - 1 === selecteds;
}

function refresh($) {
  const $selectedOptions = $._data.filter((check) => check.selected && !check.all);
  $.numSelected = $selectedOptions.length;
  $.showItems = $.numSelected <= $.maxItems;
  $.badgeList = $.showItems ? $selectedOptions : [];
  $.selectAll && calculateAllSelector($);
  dispatchEvent($);
}

function removeBadge(e, badge, $) {
  badge.selected = false;
  refresh($);
  e.stopPropagation();
}

function search(e, $) {
  const txtSearch = e.target.value.toUpperCase();
  $.checkList = $._data.filter((check) => check.all || check.text.toUpperCase().includes(txtSearch));
}

function show($) {
  setTimeout(() => {
    $._txtSearch = '';
    $._currentFocus = -1;
    $.show = 'block';
    $.$search.focus();
    $.$search.select();
  }, 1);
}

function toUp($) {
  let currentFocus = $._currentFocus;
  const { length } = $.checkList;
  currentFocus++;
  if (currentFocus >= length) {
    currentFocus = 0;
  }
  $._currentFocus = currentFocus;
}

function toDown($) {
  let currentFocus = $._currentFocus;
  const { length } = $.checkList;
  currentFocus--;
  if (currentFocus < 0) {
    currentFocus = length - 1;
  }
  $._currentFocus = currentFocus;
}

function enter($) {
  const currentFocus = $._currentFocus;
  if (currentFocus > -1) {
    toogleItem($.checkList[currentFocus], $);
  }
}

function controlKey(e, $) {
  const { keyCode } = e;
  const inputs = { 40: toUp, 38: toDown, 13: enter, 27: close };
  if (!inputs[keyCode]) return true;
  e.preventDefault();
  inputs[keyCode]($);
}

function close($) {
  if ($.show === 'none') return;
  $.show = 'none';
  refresh($);
}

@customElement({ template, styles })
export default class MultiSelect {
  #closeController = new AbortController();

  constructor() {
    this._data = [];
    this._currentFocus = -1;
    this._txtAll ='All';
    this._txtRemove = 'Remove';
    this._txtSearch = 'Search';
    this.show = 'none';
    this.placeholder ='select a item';
    this.maxItems = 5;
    this.selectAll = false;
    this.search = false;
    this.hideClose = false;
    this.name = '';
    this.required = false;
    this.value = [];
  }

  onInit() {
    this.$select = this.shadowRoot.querySelector('select');
    this.$search = this.shadowRoot.querySelector('.search');
    this._data = this.checkList;
    document.addEventListener("click", (e) => {
      if (e.target !== this.shadowRoot.host) {
        close(this);
      }
    }, {
      passive: true,
      signal: this.#closeController.signal
    });
  }

  connectedCallback() {
    if (this.selectAll) {
      this.checkList.unshift({ text: this._txtAll, all: true });
    }
  }

  listen = () => ({
    mount: () => this.onInit(),
    unmount: () => this.#closeController.abort(),
    slotchange: () => loadOptions(this),
    '.dropdown': { click: () => show(this) },
    '.list-wrapper': { _keydown: (e) => controlKey(e, this) },
    '.list label': { change: (_, item) => toogleItem(item, this) },
    '.optext span': { _click: (e, badge) => removeBadge(e, badge, this) },
    '.search': { input: (e) => search(e, this), keydown: (e) => e.keyCode === 13 && search(e, this) }
  });
}
