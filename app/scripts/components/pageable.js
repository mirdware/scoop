import { Resource } from 'scalar';

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
    href = href.replace('page=' + page, '');
    const indexQ = href.indexOf('?');
    if (indexQ === href.length - 1) {
      href = href.substr(0, indexQ);
    }
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

function init($) {
  const { prev, next } = $;
  const { href } = location;
  window.addEventListener('popstate', () => sendRequest($));
  prev.disabled = prev.disabled || href === prev.href;
  next.disabled = next.disabled || href === next.href;
}

export default ($) => ({
  mount: () => init($),
  '.prev': {click: () => addPage($, -1)},
  '.next': {click: () => addPage($, 1)},
  '.num-page': {click: (e) => setPage($, e.target)}
});
