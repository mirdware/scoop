var beams = require("./beams.json"),
    name;
require("./message.js");
require("./placeholder.js");
require("./password.js");
require("./form.js");
require("./vhtml5.js");
for (name in beams) {
    require("../.." + beams[name]);
}