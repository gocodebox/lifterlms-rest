/**
 * @description
 * HTTP code snippet generator for llms-api-node.
 */

'use strict'

var stringifyObject = require('stringify-object')
var CodeBuilder = require('httpsnippet/src/helpers/code-builder')

module.exports = function (source, options) {
	var opts = Object.assign({
		indent: '  '
	}, options)

	var code = new CodeBuilder(opts.indent)


	var reqOpts = {
		method: source.method,
		hostname: source.uriObj.hostname,
		port: source.uriObj.port,
		path: source.uriObj.path,
		headers: source.allHeaders
	}

	const apiOpts = {
		url: `${ source.uriObj.protocol }//${ source.uriObj.host }`,
	  consumerKey: 'ck_XXXXXXXXXXXXXXXXXXXXXX',
	  consumerSecret: 'cs_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
	};

	code
		.push( 'const llmsAPI = require( "llms-api-node" );' )
		.push( 'const llms = new llmsAPI( %s );', JSON.stringify( apiOpts, null, opts.indent ) )

	// console.log( source.method );
	code.blank();

	let opener = `llms.${ source.method.toLowerCase() }( '${ source.uriObj.path.replace( '/wp-json/llms/v1', '' ) }',`;
	if ( source.postData.jsonObj ) {
		code.push( 'const postData = %s;', JSON.stringify( source.postData.jsonObj, null, opts.indent ) );
		code.blank();
		opener += ` postData,`;
	}
	opener += ` function( err, data, res ) {`;


	code.push( opener );
	code.push( 1, `if ( err ) {` );
	code.push( 2, `throw new Error( 'Error!' );` );
	code.push( 1, `}`)
	code.push( 1, 'console.log( data );' );
	code.push( '} );')



			// .push('const options = %s;', JSON.stringify(reqOpts, null, opts.indent))
			// .blank()
			// .push('const req = http.request(options, function (res) {')
			// .push(1, 'const chunks = [];')
			// .blank()
			// .push(1, 'res.on("data", function (chunk) {')
			// .push(2, 'chunks.push(chunk);')
			// .push(1, '});')
			// .blank()
			// .push(1, 'res.on("end", function () {')
			// .push(2, 'const body = Buffer.concat(chunks);')
			// .push(2, 'console.log(body.toString());')
			// .push(1, '});')
			// .push('});')
			// .blank()

	// switch (source.postData.mimeType) {
	// 	case 'application/x-www-form-urlencoded':
	// 		if (source.postData.paramsObj) {
	// 			code.unshift('const qs = require("querystring");')
	// 			code.push('req.write(qs.stringify(%s));', stringifyObject(source.postData.paramsObj, {
	// 				indent: '  ',
	// 				inlineCharacterLimit: 80
	// 			}))
	// 		}
	// 		break

	// 	case 'application/json':
	// 		if (source.postData.jsonObj) {
	// 			code.push('req.write(JSON.stringify(%s));', stringifyObject(source.postData.jsonObj, {
	// 				indent: '  ',
	// 				inlineCharacterLimit: 80
	// 			}))
	// 		}
	// 		break

	// 	default:
	// 		if (source.postData.text) {
	// 			code.push('req.write(%s);', JSON.stringify(source.postData.text, null, opts.indent))
	// 		}
	// }

	// code.push('req.end();')

	return code.join()
}

module.exports.info = {
	key: 'llms',
	title: 'llms-api-node',
	link: 'http://nodejs.org/api/http.html#http_http_request_options_callback',
	description: 'LifterLMS Node API Interface'
}
