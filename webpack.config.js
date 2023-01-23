const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

let webpackConfigs = [];

Encore
    // directory where compiled assets will be stored
    .setOutputPath('bundles/AdminBundle/public/build')
    .setOutputPath('bundles/AdminBundle/public/build/admin')
    // public path used by the web server to access the output path
    .setPublicPath('/bundles/pimcoreadmin/build/admin')


    .setManifestKeyPrefix('AdminBundle/build/admin')


    .addEntry('admin', './assets/admin.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    //.enableVersioning(Encore.isProduction())
;

let adminConfig = Encore.getWebpackConfig();
adminConfig.name = 'pimcoreAdmin';

webpackConfigs.push(adminConfig);

Encore.reset();

Encore

    // directory where compiled assets will be stored
    .setOutputPath('bundles/AdminBundle/public/build/image-editor')
    // public path used by the web server to access the output path
    .setPublicPath('/bundles/pimcoreadmin/build/image-editor')

    .setManifestKeyPrefix('AdminBundle/build/image-editor')


    .addEntry('image-editor', './assets/image-editor.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    //.enableVersioning(Encore.isProduction())
;

let imageEditorConfig = Encore.getWebpackConfig();
imageEditorConfig.name = 'pimcoreAdminImageEditor';

webpackConfigs.push(imageEditorConfig);


module.exports = webpackConfigs;