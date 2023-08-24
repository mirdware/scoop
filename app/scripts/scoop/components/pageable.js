import { Component } from 'scalar';
import Resource from '@spawm/resource';
import ModalService from '../services/Modal';
import FormService from '../services/Form';
import Search from './search';

const location = window.location;
const queryParams = {};

async function sendRequest($) {
  let url = $.options.url ? $.options.url + location.search : location.href;
  const resource = new Resource(url, {
    redirect: false 
  });
  if (url.indexOf('http') !== 0) {
    url = location.protocol + url;
  }
  $.loading = true;
  const data = await resource.get();
  $.refresh(data);
  Object.assign($, getQueryParams($), data);
  $.loading = false;
}

function getPage(name, search) {
  const regex = new RegExp(name + '=(\\d+)');
  const page = search.match(regex);
  return page ? parseInt(page[1]) : 0;
}

function getHref(page, nextPage, name) {
  let { href } = location;
  if (nextPage > 0) {
    if (page) {
      href = href.replace(name + '=' + page, name + '=' + nextPage);
    } else {
      href += (href.indexOf('?') !== -1 ? '&' : '?') + name + '=' + nextPage;
    }
  } else {
    href = href.replace('?' + name + '=' + page, '');
    if (href.indexOf('?') === -1) {
      href = href.replace('&', '?');
    }
    href = href.replace('&' + name + '=' + page, '');
  }
  return href;
}

function addPage($, pagePlus) {
  if ($.loading) return;
  const name = $.options.page;
  const page = getPage(name, location.search);
  const nextPage = page + pagePlus;
  if (page > nextPage && $[$.options.prev].disabled) return;
  if (page < nextPage && $[$.options.next].disabled) return;
  const href = getHref(page, nextPage, name);
  window.history.pushState(null, '', href);
  sendRequest($);
}

function setPage($, $element) {
  window.history.pushState(null, '', $element.href);
  sendRequest($);
}

async function openModal(e, $) {
  const target = e.currentTarget;
  const _modal = $.inject(ModalService);
  const $dom = await _modal.open(target.form.dataset.modal, target.title);
  const res = await $.compose($dom, Search).send(getQueryParams($));
  $.inject(FormService).setQueryString(res);
  sendRequest($);
}

function init($, options) {
  options = Object.assign({
    prev: 'prev',
    next: 'next',
    data: 'data',
    page: 'page'
  }, options ? JSON.parse(options) : {});
  const prev = $[options.prev];
  const next = $[options.next];
  const currentPage = getPage(options.page, location.search);
  $.options = options;
  window.addEventListener('popstate', () => sendRequest($));
  prev.disabled = prev.disabled || currentPage === getPage(options.page, prev.href);
  next.disabled = next.disabled || currentPage === getPage(options.page, next.href);
  getQueryParams($);
}

function search(form, $) {
  if (!$.loading) {
    const _form = $.inject(FormService);
    const data = _form.toObject(form);
    _form.setQueryString(data);
    sendRequest($);
  }
}

function getQueryParams($) {
  const res = $.inject(FormService).getQueryParams(location.search);
  for (const key in queryParams) {
    queryParams[key] = '';
  }
  return Object.assign(queryParams, res);
}

export default class Pageable extends Component {
  listen() {
    return {
      mount: (e) => init(this, e.target.dataset.options),
      'form': { _submit: (e) => search(e.target, this) },
      '.prev': { _click: () => addPage(this, -1) },
      '.next': { _click: () => addPage(this, 1) },
      '.modal': { _click: (e) => openModal(e, this) },
      'input[type="search"]': { _search: (e) => search(e.target.form, this) },
      '.num-page': { _click: (e) => setPage(this, e.target) }
    };
  }

  refresh(data) {
    this[this.options.data] = data.result;
    this.disable(data.page, data.size, data.total);
  }

  disable(page, size, total) {
    const name = this.options.page;
    const disabledNext = (page + 1) * size >= total;
    const disabledPrev = page == 0;
    this[this.options.next] = {
      disabled: disabledNext,
      href: disabledNext ? '' : getHref(page, page + 1, name)
    };
    this[this.options.prev] = {
      disabled: disabledPrev,
      href: disabledPrev ? '' : getHref(page, page - 1, name)
    };
  }
};
