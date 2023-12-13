export default class Form {
  setQueryString(params) {
    let query = '';
    for (const name in params) {
      if (params[name]) {
        query += name + '=' + encodeURI(params[name]) + '&';
      }
    }
    window.history.pushState(null, '', query ?
    `${location.pathname}?${query.substring(0, query.length - 1)}` :
    location.pathname);
  }

  getQueryParams(query) {
    if (!query) return;
    const params = {};
    query.substring(1, query.length).split('&').forEach((string) => {
      string = string.split('=');
      params[string[0]] = decodeURI(string[1]);
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
        obj[name] = type === "select-multiple" ?
        Array.from(inp.selectedOptions).map(({ value }) => value) :
        inp.options[index].value;
      } else {
        obj[name] = inp.value;
      }
    }
    return obj;
  }
}