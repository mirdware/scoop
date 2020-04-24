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

  getQueryParams(query) {
    if  (!query) return;
    const params = {};
    query.substring(1, query.length).split('&').forEach((string) => {
      string = string.split('=');
      params[string[0]] = string[1];
    });
    return params;
  }

  toObject(form) {
    let obj = {};
    for (let i = 0, inp; inp = form.elements[i]; i++) {
      let name = inp.name;
      if (!name) continue;
      const type = inp.type;
      if ((type == "radio" || type == "checkbox") && !inp.checked) continue;
      const index = inp.selectedIndex;
      const lastChars = name.length - 2;
      if (name.indexOf("[]") === lastChars) {
        name = name.substr(0, lastChars);
        if (!obj[name]) {
          obj[name] = [];
        }
        obj[name].push(inp.value);
      } else if (type === 'file') {
        obj[name] = inp.files;
      } else if (!obj[name] && type.indexOf("select") == 0 && index != -1) {
        obj[name] = inp.options[index].value;
      } else {
        obj[name] = inp.value;
      }
    }
    return obj;
  }
}