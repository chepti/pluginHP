const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const { getWebpackEntryPoints } = require( '@wordpress/scripts/utils/config' );

module.exports = {
	...defaultConfig,
	entry: () => {
		const defaults = getWebpackEntryPoints( 'script' )();
		return {
			...defaults,
			'frontend/viewer': './src/frontend/viewer.js',
		};
	},
};
