cd views/js/
copy /Y /B std.js +scoop.std.js +modal.std.js +slider.std.js +fun.js scripts.js
cd ../images/css/
copy /Y /B stylescoop.css +styles.modal.css +styles.slider.css +styles.app.css styles.css
cd ../../../
java -jar yuicompressor.jar views\js\scripts.js -o views\js\scripts.js --charset utf-8
java -jar yuicompressor.jar views\images\css\styles.css -o views\images\css\styles.css --charset utf-8