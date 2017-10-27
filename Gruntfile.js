module.exports = function(grunt) {
	grunt.registerTask('default', ['uglify', 'sass']);

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		uglify: {
			options: {

			},
			build: {
				files: {
					'dist/js/app.js': 'assets/js/**/*.js'
				}
			}
		},
		sass: {
			options: {
                style: 'compressed'
            },
			build: {
	            files: {
	                'dist/css/app.css': 'assets/sass/**/*.scss'
	            }
			}
		},

		watch: {
			styles: {
				files: ['assets/sass/**/*.scss'],
				tasks: ['sass'],
	            options: {
	                spawn: false
	            }
			},
			scripts: {
				files: 'assets/js/**/*.js',
				tasks: ['uglify']
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
};
