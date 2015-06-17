module.exports = function(grunt) {
    var srcJS = "app/resources/javascript/",
            srcCSS = "app/resources/styles/",
            jsFiles = grunt.file.readJSON(srcJS+"compress.json");

    for (var i=0, file; file = jsFiles[i]; i++) {
        jsFiles[i] = srcJS+file;
    }

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        pathJS: "public/js/",
        pathCSS: "public/css/",
        srcCSS: srcCSS,
        srcJS: srcJS,

        uglify: {
            minify: {
                options: {
                    sourceMap: true,
                    sourceMapName: "<%= pathJS %>sourcemap.map"
                },
                files: {
                    "<%= pathJS %><%= pkg.name %>.min.js": jsFiles
                }
            }
        },

        stylus: {
            build: {
                options: {
                    linenos: true,
                    compress: false
                },
                files: [{
                    expand: true,
                    cwd: srcCSS,
                    src: ["**/compress.styl"],
                    dest: srcCSS,
                    ext: ".cp.css"
                }]
            }
        },

        cssmin: {
            build: {
                files: {
                    "<%= pathCSS %><%= pkg.name %>.min.css": ["<%= srcCSS %>{,*/}compress.cp.css"]
                }
            }
        },

        clean: {
            css: ["<%= srcCSS %>/**/*.cp.css"]
        },

        watch: {
            scripts: {
                files: ["<%= srcJS %>{,*/}compress.json", "gruntfile.js"].concat(jsFiles),
                tasks: ["uglify"],
                options: {
                     livereload: 35729,
                     reload: true
                }
            },
            stylesheets: {
                files: ["<%= srcCSS %>{,*/}*.styl", "<%= srcCSS %>{,*/}*.css", "!<%= srcCSS %>{,*/}*.cp.css"],
                tasks: ["css"],
                options: {
                     livereload: 35729
                }
            }
        }

    });

    grunt.loadNpmTasks("grunt-contrib-uglify");
    grunt.loadNpmTasks("grunt-contrib-watch");
    grunt.loadNpmTasks("grunt-contrib-stylus");
    grunt.loadNpmTasks("grunt-contrib-cssmin");
    grunt.loadNpmTasks("grunt-contrib-clean");

    grunt.registerTask("css", ["stylus", "cssmin", "clean"]);
    grunt.registerTask("default", ["uglify", "css", "watch"]);
};