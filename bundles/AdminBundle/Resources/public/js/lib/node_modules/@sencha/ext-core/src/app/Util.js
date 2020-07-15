/**
 * @private 
 * @class Ext.app.Util
 */
Ext.define('Ext.app.Util', {
}, function() {
    Ext.apply(Ext.app, {
        namespaces: {
            Ext: {}
        },

        /**
        * Adds namespace(s) to known list.
        * @private
        *
        * @param {String/String[]} namespace
        */
        addNamespaces: function(namespace) {
            var namespaces = Ext.app.namespaces,
                i, l;

            if (!Ext.isArray(namespace)) {
                namespace = [namespace];
            }

            for (i = 0, l = namespace.length; i < l; i++) {
                namespaces[namespace[i]] = true;
            }
        },

        /**
        * Clear all namespaces from known list.
        * @private
        */
        clearNamespaces: function() {
            Ext.app.namespaces = {};
        },

        /**
        * Get namespace prefix for a class name.
        * @private
        * @param {String} className
        *
        * @return {String} Namespace prefix if it's known, otherwise undefined
        */
        getNamespace: function(className) {
            var namespaces = Ext.apply({}, Ext.ClassManager.paths, Ext.app.namespaces),
                deepestPrefix = '',
                prefix;

            for (prefix in namespaces) {
                if (namespaces.hasOwnProperty(prefix) &&
                    prefix.length > deepestPrefix.length &&
                    (prefix + '.' === className.substring(0, prefix.length + 1))) {
                    deepestPrefix = prefix;
                }
            }

            return deepestPrefix === '' ? undefined : deepestPrefix;
        },

        /**
         * Sets up paths based on the `appFolder` and `paths` configs.
         * @param {String} appName The application name (root namespace).
         * @param {String} appFolder The folder for app sources ("app" by default).
         * @param {Object} paths A set of namespace to path mappings.
         * @private
         * @since 6.0.0
         */
        setupPaths: function(appName, appFolder, paths) {
            var manifestPaths = Ext.manifest,
                ns;

            // Ignore appFolder:null
            if (appName && appFolder !== null) {
                manifestPaths = manifestPaths && manifestPaths.paths;

                // If the manifest has paths, only honor appFolder if defined. If the
                // manifest has no paths (old school mode), then we want to default an
                // unspecified appFolder value to "app". Sencha Cmd will pass in paths
                // to configure the loader via the "paths" property of the manifest so
                // we don't want to try and be "helpful" in that case.
                if (!manifestPaths || appFolder !== undefined) {
                    Ext.Loader.setPath(appName, (appFolder === undefined) ? 'app' : appFolder);
                }
            }

            if (paths) {
                for (ns in paths) {
                    if (paths.hasOwnProperty(ns)) {
                        Ext.Loader.setPath(ns, paths[ns]);
                    }
                }
            }
        }
    });

    /**
     * @method getNamespace
     * @member Ext
     * @param {String} className
     *
     * @return {String} Namespace prefix if it's known, otherwise undefined
     */
    Ext.getNamespace = Ext.app.getNamespace;

});
