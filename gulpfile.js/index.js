/**
 * Main Gulp File
 *
 * Requires all task files
 */

var gulp = require('gulp'),
	requireDir = require( 'require-dir' );

requireDir( './tasks' );
require( 'lifterlms-lib-tasks' )( gulp );
