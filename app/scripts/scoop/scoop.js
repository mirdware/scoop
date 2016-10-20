import Message from './message.js';
import Placeholder from './placeholder.js';
import Password from './password.js';
import Form from './form.js';
import Validation from './vhtml5.js';

var beams = require('./beams.json'),
    name;

for (name in beams) {
    require('../..' + beams[name]);
}
