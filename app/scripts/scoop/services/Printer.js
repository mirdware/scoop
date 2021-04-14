function getFrame() {
  let frame = document.getElementById('frame-scoop-ajax');
  if (frame) return frame;
  frame = document.createElement('iframe');
  frame.style.display = 'none';
  frame.name = 'frame-scoop-ajax';
  frame.id = 'frame-scoop-ajax';
  document.body.appendChild(frame);
  return frame;
}

export default class {
  print(url) {
    const frame = getFrame();
    frame.src = url;
    return new Promise ((resolve) => frame.onload = () => {
      const content = frame.contentWindow || frame.contentDocument;
      content.print();
      content.onafterprint = (e) => resolve(e);
    });
  }
}
