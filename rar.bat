cd views/js/
copy /Y /B modernizr.js +std.js +modal.std.js +slider.std.js +fun.js scripts.js
cd ../images/css/
copy /Y /B stylestd.css +styles.modal.css +styles.slider.css +styles.app styles.css
cd ../../../
java -jar yuicompressor.jar views\js\scripts.js -o views\js\scripts.js --charset utf-8
java -jar yuicompressor.jar views\images\css\styles.css -o views\images\css\styles.css --charset utf-8