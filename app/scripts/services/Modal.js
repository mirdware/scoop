import { Resource } from 'scalar';
import Overlay from './Overlay';

const location = window.location;
const cache = window.sessionStorage;

function destructuring() {
  const $dom = document.getElementById('scoop-modal');
  if ($dom) {
    return {
      $dom,
      $body: $dom.querySelector('.body'),
      $title: $dom.querySelector('.header h2')
    };
  }
}

function createBody(href, $dom, $body) {
  const url = href.split('#');
  if (url[1] && url[0] === location.href) {
    let content = document.getElementById(url[1]);
    if (content) {
      content = content.outerHTML;
      cache.setItem(href, content);
      show($dom, $body, content);
    }
  } else {
    new Resource(href).get().then((data) => {
      cache.setItem(href, data);
      show($dom, $body, data);
    });
  }
}

function generateModal() {
  const $dom = document.createElement('div');
  const $title = document.createElement('h3');
  const $body = $dom.cloneNode();
  const $header = $dom.cloneNode();
  const $close = $dom.cloneNode();
  $header.className = 'header';
  $body.className = 'body';
  $dom.id = 'scoop-modal';
  $header.appendChild($title);
  $header.appendChild($close);
  $dom.appendChild($header);
  $dom.appendChild($body);
  document.body.appendChild($dom);
  return { $dom, $body, $title };
}

function show($dom, $body, content) {
  $body.innerHTML = content;
  $dom.style.display = 'block';
  $dom.style.width = $dom.clientWidth + 'px';
  $dom.style.marginLeft = ($dom.clientWidth / 2 * -1) + 'px';
  $dom.style.marginTop = ($dom.clientHeight / 2 * -1) + 'px';
}

export default class Modal {
  constructor(inject) {
    this.overlay = inject(Overlay);
    this.modal = destructuring()|| generateModal();
  }

  open(href, title) {
    if (this.overlay.isOpen()) return false;
    const modal = this.modal;
    const $dom = modal.$dom;
    const $body = modal.$body;
    const content = cache.getItem(href);
    this.overlay.open();
    modal.$title.innerText = title;
    if (content) {
      show($dom, $body, content);
    } else {
      createBody(href, $dom, $body);
    }
    return true;
  }

  close() {
    this.modal.$dom.style.display = '';
    this.overlay.close();
  }
}
