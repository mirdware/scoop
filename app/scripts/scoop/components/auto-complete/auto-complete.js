import { Component, customElement } from 'scalar';
import styles from './auto-complete.css?raw';
import template from './auto-complete.html?raw';

function search(e, $) {
    const { value } = e.target;
    const { length } = value;
    const values = value ? $.data
    .map((data, index) =>  ({ value: getValue(data, $.label), index }))
    .filter((item) => item.value.substr(0, length).toUpperCase() === value.toUpperCase())
    .map((item) => Object.assign(item, {
        mask: `<b>${item.value.substr(0, length)}</b>${item.value.substr(length)}`
    })) : [];
    $._timeout && clearTimeout($._timeout);
    if (values.length > $.maxItems) {
        values.length = $.maxItems;
    }
    $._values = values;
    $._timeout = setTimeout(() => $.dispatchEvent(new CustomEvent('changed', {
        detail: value
    })), 200);
}

function selectItem(index, $) {
    const item = $._values[index];
    $.value = item.value;
    $._index = item.index;
    $._values = [];
    $.dispatchEvent(new CustomEvent('selected', {
        detail: JSON.parse(JSON.stringify($.data[$._index]))
    }));
}

function toUp($) {
    let currentFocus = $._currentFocus;
    const { length } = $._values;
    currentFocus++;
    if (currentFocus >= length) {
        currentFocus = 0;
    }
    $._currentFocus = currentFocus;
}

function toDown($) {
    let currentFocus = $._currentFocus;
    const { length } = $._values;
    currentFocus--;
    if (currentFocus < 0) {
        currentFocus = length - 1;
    }
    $._currentFocus = currentFocus;
}

function enter($) {
    const currentFocus = $._currentFocus;
    if (currentFocus > -1) {
        selectItem(currentFocus, $);
    }
}

function controlKey(e, $) {
    const { keyCode } = e;
    const inputs = { 40: toUp, 38: toDown, 13: enter };
    if (!inputs[keyCode]) return true;
    e.preventDefault();
    inputs[keyCode]($);
}

function close($) {
    const index = $._index;
    $._values = [];
    $._currentFocus = -1;
    if (index === -1) {
        $.value = '';
    } else {
        $.value = getValue($.data[index], $.label);
    }
}

function getValue(data, label) {
    if (label) {
        label.split('.').forEach((label) => {
            data = data[label];
        });
        return data;
    }
    return data;
}

@customElement({ template, styles })
export default class AutoComplete extends Component {
  constructor() {
    super();
    this._currentFocus = -1;
    this._index = -1;
    this.value = '';
    this.name = '';
    this.required = false;
    this.placeholder = '';
    this.data = [];
    this.maxItems = 5;
    this.label = '';
  }

  connectedCallback() {
    document.addEventListener("click", (e) => {
        if (e.target !== this) {
            close(this);
        }
    });
}

  listen = () => ({
    'input': {
        input: (e) => search(e, this),
        blur: () => {
            if (!this.value && this._index !== -1) {
                this._index = -1;
                this.dispatchEvent(new CustomEvent('selected'));
            }
            setTimeout(() => close(this), 150);
        },
        _keydown: (e) => controlKey(e, this)
    },
    '.autocomplete-items div': { click: (e) => selectItem(this.getIndex(e), this) }
  });
}
