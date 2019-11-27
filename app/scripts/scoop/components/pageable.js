import { Resource } from 'scalar';
import ModalService from '../services/Modal';
import FormService from '../services/Form';
import Modal from './modal';

const location = window.location;

function sendRequest($) {
  new Resource(location.href).get()
  .then((data) => {
    const { page } = data;
    const disabledNext = (page + 1) * data.size >= data.total;
    const disabledPrev = page == 0;
    $.data = data.result;
    $.next = {
      disabled: disabledNext,
      href: disabledNext ? '' : getHref(page, page + 1)
    };
    $.prev = {
      disabled: disabledPrev,
      href: disabledPrev ? '' : getHref(page, page - 1)
    };
  });
}

function getPage() {
  const page = location.search.match(/page=(\d+)/);
  return page ? parseInt(page[1]) : 0;
}

function getHref(page, nextPage) {
  let { href } = location;
  if (nextPage > 0) {
    if (page) {
      href = href.replace('page=' + page, 'page=' + nextPage);
    } else {
      href += (href.indexOf('?') !== -1 ? '&' : '?') + 'page=' + nextPage;
    }
  } else {
    href = href
    .replace('?page=' + page, '')
    .replace('&page=' + page, '');
  }
  return href;
}

function addPage($, pagePlus) {
  const page = getPage();
  const nextPage = page + pagePlus;
  if (page > nextPage && $.prev.disabled) return;
  if (page < nextPage && $.next.disabled) return;
  const href = getHref(page, nextPage);
  window.history.pushState(null, '', href);
  sendRequest($);
}

function setPage($, $element) {
  window.history.pushState(null, '', $element.href);
  sendRequest($);
}

function openModal(e, $) {
  const target = e.currentTarget;
  const _modal = $.inject(ModalService);
  _modal.open(target.form.action + 'search/', target.title);
  $.compose(_modal.modal.$dom, Modal);
}

function init($) {
  const { prev, next } = $;
  const { href } = location;
  window.addEventListener('popstate', () => sendRequest($));
  prev.disabled = prev.disabled || href === prev.href;
  next.disabled = next.disabled || href === next.href;
}

function search(form, $) {
  const _form = $.inject(FormService);
  const data = _form.toObject(form);
  _form.setQueryString(data);
  sendRequest($);
}

export default ($) => ({
  mount: () => init($),
  'form': {_submit: (e) => search(e.target, $)},
  '.prev': {_click: (e) => addPage($, -1)},
  '.next': {_click: (e) => addPage($, 1)},
  '.modal': {_click: (e) => openModal(e, $)},
  'input[type="search"]': {_search: (e) => search(e.target.form, $)},
  '.num-page': {_click: (e) => setPage($, e.target)}
});
