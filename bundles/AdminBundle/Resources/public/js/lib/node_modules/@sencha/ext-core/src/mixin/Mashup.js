// @cmd.optimizer.requires.async
/**
 * This mixin allows users to easily require external scripts in their classes. This load
 * process delays application launch (`Ext.onReady`) until all such scripts are loaded
 * ensuring that your class will have access to its required scripts from the start.
 *
 * For example:
 *
 *      Ext.define('Feed', {
 *          mixins: ['Ext.mixin.Mashup'],
 *
 *          requiredScripts: [
 *              '//www.foo.com/code/bar.js' // preserve http/s
 *          ],
 *
 *          // The code in "bar.js" will be available at application launch
 *      });
 *
 * If your required script needs to be dynamic, for example it contains a key for an API, you can
 * use the {@link Ext.Manifest#mashup mashup} object.
 *
 * @since 5.0.0
 */
Ext.define('Ext.mixin.Mashup', function(Mashup) { return { // eslint-disable-line brace-style
    extend: 'Ext.Mixin',

    mixinConfig: {
        id: 'mashup',

        extended: function(baseClass, derivedClass) {
            Mashup.process(derivedClass);
        }
    },

    /**
     * @cfg {String/String[]} requiredScripts
     * Scripts that are required to be loaded in order for your Mashup to work.
     * These scripts can also use {@link Ext.Template template} syntax if there is data in the
     * {@link Ext.Manifest#mashup mashup} object for the class `xtype`.
     *
     * For example, `Ext.ux.google.Map` needs an API key and so its `requiredScripts` looks
     * like this:
     *
     *      requiredScripts: {
     *          '//maps.googleapis.com/maps/api/js?key={key}'
     *      }
     *
     * The `key` property is provided in `app.json` or directly in `Ext.manifest`:
     *
     *      "mashup": {
     *          "map": {  // the xtype
     *              "key": "[GOOGLE_MAPS_KEY]"
     *          }
     *      }
     *
     * For non-components (classes that have no `xtype`), the `mashupConfig` can be used
     * to supply the configuration key:
     *
     *      mashupConfig: {
     *          key: 'map'
     *      }
     */

    /**
     * @cfg {Object} mashup
     * An object that allows developers to pass values into templates used to populate
     * {@link Ext.Mashup#requiredScripts requiredScripts}.
     *
     * Here is an example of passing a key along to `Ext.ux.google.Map`.
     *
     *      "mashup": {
     *          "map": {  // the xtype
     *              "key": "[GOOGLE_MAPS_KEY]"
     *          }
     *      }
     *
     * @member Ext.Manifest
     */

    statics: {
        process: function(targetClass) {
            var body = targetClass.prototype,
                requiredScripts = body.requiredScripts,
                hooks = targetClass._classHooks,
                onCreated = hooks.onCreated,
                xtypes = targetClass.prototype.xtypes,
                mashup = Ext.manifest.mashup || {},
                options = body.mashupConfig,
                i, script;

            if (requiredScripts) {
                delete body.requiredScripts;

                hooks.onCreated = function() {
                    var me = this,
                        scripts = [],
                        args = Ext.Array.slice(arguments),
                        redirect = mashup.redirect || {};

                    requiredScripts = scripts.concat(requiredScripts);

                    options = options && mashup[options.key];

                    if (xtypes) {
                        for (i = 0; !options && i < xtypes.length; ++i) {
                            options = mashup[xtypes[i]];
                        }
                    }

                    for (i = 0; i < requiredScripts.length; i++) {
                        script = requiredScripts[i];

                        if (redirect[script] === false) {
                            continue;
                        }

                        script = redirect[script] || script;

                        if (script.indexOf('{') > -1) {
                            if (options) {
                                script = new Ext.Template(script).apply(options);
                            }
                            //<debug>
                            else {
                                Ext.log.error('Missing mashup options for ' +
                                    body.$className + ' script "' + script + '"');
                            }
                            //</debug>
                        }

                        scripts.push(script);
                    }

                    if (!scripts.length) {
                        hooks.onCreated = onCreated;
                        hooks.onCreated.call(me, args);

                        return;
                    }

                    Ext.Loader.loadScripts({
                        url: scripts,
                        cache: true, // no cache busting
                        onError: function(opts, error) {
                            targetClass.scriptError = targetClass.prototype.scriptError = error;
                            hooks.onCreated = onCreated;
                            hooks.onCreated.call(me, args);
                        },
                        onLoad: function() {
                            hooks.onCreated = onCreated;
                            hooks.onCreated.call(me, args);
                        }
                    });
                };
            }
        }
    },

    onClassMixedIn: function(targetClass) {
        Mashup.process(targetClass);
    }
};
});
