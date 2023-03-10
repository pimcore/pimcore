const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

let webpackConfigs = [];

const par = {
    bundleFolderName: "EcommerceFrameworkBundle",
    name: 'ecommerceFramework',
    entryNames: ["flatpickr", "bootstrap", "voucher", "leaflet"],
    bundleName: "pimcoreecommerceframework",
    configName: "pimcoreEcommerceFramework",
    copyFiles: {
        from: "node_modules/leaflet/dist/images",
        to: "images/[path]/[name].[ext]"
    }
};

const publicPath = './src/Resources/public';

Encore.reset();

Encore

    // directory where compiled assets will be stored
    .setOutputPath(`${publicPath}/build`)
    .setOutputPath(`${publicPath}/build/${par.name}`)
    // public path used by the web server to access the output path
    .setPublicPath(`/bundles/${par.bundleName}/build/${par.name}`)

    .setManifestKeyPrefix(`${par.bundleFolderName}/build`)

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
;

par.entryNames.map((entryName) => {
    Encore.addEntry(entryName, `${publicPath}/assets/${entryName}.js`);
});

if (par.copyFiles) {
    Encore.copyFiles(par.copyFiles);
}

let encoreConfig = Encore.getWebpackConfig();
encoreConfig.name = par.configName;

webpackConfigs.push(encoreConfig);


module.exports = webpackConfigs;
