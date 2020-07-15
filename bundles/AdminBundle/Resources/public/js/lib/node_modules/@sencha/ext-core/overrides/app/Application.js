// This is an override because it must be loaded very early, possibly before Ext.app.Application
// in dev mode so that Ext.application() can be called.
// Being an override also ensures that it is only included in a built app if Ext.app.Application
// is present.
//
// @override Ext.app.Application

/**
 * @method application
 * @member Ext
 * Loads Ext.app.Application class and starts it up with given configuration after the
 * page is ready.
 *
 * See `Ext.app.Application` for details.
 *
 * @param {Object/String} config Application config object or name of a class derived
 * from Ext.app.Application.
 */
Ext.application = function(config) {
    var createApp = function(App) {
        // This won't be called until App class has been created.
        Ext.onReady(function() {
            var Viewport = Ext.viewport;

            // eslint-disable-next-line dot-notation
            Viewport = Viewport && Viewport['Viewport'];

            if (Viewport && Viewport.setup) {
                Viewport.setup(App.prototype.config.viewport);
            }

            Ext.app.Application.instance = new App();
        });
    };

    if (typeof config === "string") {
        Ext.require(config, function() {
            createApp(Ext.ClassManager.get(config));
        });
    }
    else {
        config = Ext.apply({
            extend: 'Ext.app.Application' // can be replaced by config!
        }, config);

        // We have to process "paths" before creating Application class,
        // or `requires` won't work.
        Ext.app.setupPaths(config.name, config.appFolder, config.paths);

        config['paths processed'] = true;

        // Let Ext.define do the hard work but don't assign a class name.
        Ext.define(config.name + ".$application", config,
                   function() {
                       createApp(this);
                   });
    }
};
