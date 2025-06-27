import { Component, customElement } from 'scalar';
import styles from './multi-select.css?raw';
import template from './multi-select.html?raw';

function loadOptions($) {
  const $option = $.querySelector('option');
  if ($option) {
    $.$select.appendChild($option);
    $.checkList.push($option);
  }
}

function toogleItem(index, $) {
  const item = $.checkList[index];
  item.selected = !item.selected;
  if ($.selectAll) {
    if (parseInt(index)) {
      calculateAllSelector($);
    } else {
      $._data.forEach((i) => i.selected = item.selected);
    }
  }
}

function dispatchEvent($) {
  $.$select.dispatchEvent(new Event('change'));
  $.dispatchEvent(new CustomEvent('changed', {
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

function removeBadge(e, $) {
  const badge = $.badgeList[$.getIndex(e)];
  badge.selected = false;
  refresh($);
  e.stopPropagation();
}

function search(e, $) {
  const txtSearch = e.target.value.toUpperCase();
  $.checkList = $._data.filter((check) => check.all || check.text.toUpperCase().includes(txtSearch));
}

function show($) {
  $.show ='block';
  $.$search.focus();
  $.$search.select();
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
    toogleItem(currentFocus, $);
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
  $.show = 'none';
  refresh($);
}

@customElement({ template, styles })
export default class MultiSelect extends Component {
  constructor() {
    super();
    this._data = [];
    this._currentFocus = -1;
    this._txtAll ='All';
    this._txtRemove = 'Remove';
    this._txtSearch ='Search';
    this.placeholder ='select a item';
    this.maxItems = 5;
    this.selectAll = false;
    this.search = false;
    this.hideClose = false;
    this.name = '';
    this.required = false;
    this.value = [];
  }

  connectedCallback() {
    this.$select = this.shadowRoot.querySelector('select');
    this.$search = this.shadowRoot.querySelector('.search');
    if (this.selectAll) {
      const allSelector = { text: this._txtAll, all: true };
      this.checkList.unshift(allSelector);
    }
    setTimeout(() => {
      this._data = this.checkList;
      refresh(this);
    }, 0);
    document.addEventListener('click', (e) => {
      if (e.target !== this) {
        close(this);
      }
    });
  }

  listen = () => ({
    slotchange: () => loadOptions(this),
    '.dropdown': { click: () => show(this), keydown: (e) => e.keyCode === 13 && show(this) },
    '.list-wrapper': { _keydown: (e) => controlKey(e, this) },
    '.list label': { change: (e) => toogleItem(this.getIndex(e), this) },
    '.optext span': { _click: (e) => removeBadge(e, this) },
    '.search': { input: (e) => search(e, this) }
  });
}
