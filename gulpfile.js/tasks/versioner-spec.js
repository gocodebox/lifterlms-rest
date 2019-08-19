/**
 * -----------------------------------------------------------
 * Spec Versioner
 * -----------------------------------------------------------
 *
 * Replace version number in the spec with a new version.
 *
 * Usage: gulp versioner-spec -V {version}
 */

var   gulp    = require( 'gulp' )
	, replace = require( 'gulp-replace' )
	, argv    = require( 'yargs' ).argv
	, gutil   = require( 'gulp-util' )
	, getVersion = require( process.cwd() + '/node_modules/lifterlms-lib-tasks/lib/getVersion' )
	, pkg = require( process.cwd() + '/package.json' )
;

gulp.task( 'versioner-spec', function() {

    let the_version = argv.V;

    the_version = getVersion( the_version, pkg.version );

    gutil.log( gutil.colors.blue( 'Updating spec versions to `' + the_version + '`' ) );

	return gulp.src( [ './spec/openapi.yaml'  ], { base: './' } )
		.pipe( replace( /  version: (\d+\.\d+\.\d+)(\-\D+\.\d+)?/g, function( match, p1, p2, string ) {
	        // if there's a prerelease suffix (eg -beta.1) remove it entirely
	        if ( p2 ) {
	          match = match.replace( p2, '' );
	        }
			return match.replace( p1, the_version );
		} ) )
		.pipe( gulp.dest( './' ) );


} );
