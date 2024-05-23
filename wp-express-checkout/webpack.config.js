const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
// const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');

// const wcDepMap = {
//     '@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
//     '@woocommerce/settings'       : ['wc', 'wcSettings']
// };
//
// const wcHandleMap = {
//     '@woocommerce/blocks-registry': 'wc-blocks-registry',
//     '@woocommerce/settings'       : 'wc-settings'
// };
//
// const requestToExternal = (request) => {
//     if (wcDepMap[request]) {
//         return wcDepMap[request];
//     }
// };
//
// const requestToHandle = (request) => {
//     if (wcHandleMap[request]) {
//         return wcHandleMap[request];
//     }
// };

// Export configuration.
module.exports = {
    ...defaultConfig,
    entry: {
        'index': '/src/blocks/wpec-payment-gateway-integration/index.js',
    },
    output: {
    	path: path.resolve( __dirname, 'includes/integrations/woocommerce/block-integration' ),
    	filename: '[name].js',
    },
    // plugins: [
    // 	...defaultConfig.plugins.filter(
    // 		(plugin) =>
    // 			plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
    // 	),
    // 	new WooCommerceDependencyExtractionWebpackPlugin({
    // 		requestToExternal,
    // 		requestToHandle
    // 	})
    // ]
};
