export default class Form {
  setQueryString(data) {
    let params = '';
    for (const name in data) {
      if (data[name]) {
        params += name + '=' + encodeURI(data[name]) + '&';
      }
    }
    window.history.pushState(null, '', params ?
    `${location.pathname}?${params.substr(0, params.length-1)}` :
    location.pathname);
  }
  
  toObject(form) {
    let obj = {};
    for (let i = 0, inp; inp = form.elements[i]; i++) {
      let name = inp.name;
      if (!name) continue;
      const type = inp.type;
      if ((type == "radio" || type == "checkbox") && !inp.checked) continue;
      const index = inp.selectedIndex;
      if (name.indexOf("[]") != -1) {
        name += i;
      }
      obj[name] = inp.value;
      if (type === 'file') {
        obj[name] = inp.files;
      } else if (!obj[name] && type.indexOf("select") == 0 && index != -1) {
        obj[name] = inp.options[index].text;
      }
    }
    return obj;
  }
}