/**
 * This mixin provides support for a `plugins` config and related API's.
 *
 * If this mixin is used for non-Components, the statements regarding the host being a
 * Component can be translated accordingly. The only requirement on the user of this class
 * is that the plugins actually used be appropriate for their host.
 *
 * While `Ext.Component` in the Classic Toolkit supports `plugins`, it does not use this
 * class to provide that support. This is due to backwards compatibility in regard to
 * timing changes this implementation would present.
 *
 * **Important:** To ensure plugins are destroyed, call `setPlugins(null)`.
 * @protected
 * @since 6.2.0
 */
Ext.define('Ext.mixin.Pluggable', function(Pluggable) { return { // eslint-disable-line brace-style
    requires: [
        'Ext.plugin.Abstract'
    ],

    mixinId: 'pluggable',

    config: {
        /**
         * @cfg {Array/Ext.enums.Plugin/Object/Ext.plugin.Abstract} plugins
         * This config describes one or more plugin config objects used to create plugin
         * instances for this component.
         *
         * Plugins are a way to bundle and reuse custom functionality. Plugins should extend
         * `Ext.plugin.Abstract` but technically the only requirement for a valid plugin
         * is that it contain an `init` method that accepts a reference to its owner. Once
         * a plugin is created, the owner will call the `init` method, passing a reference
         * to itself. Each plugin can then call methods or respond to events on its owner
         * as needed to provide its functionality.
         *
         * This config's value can take several different forms.
         *
         * The value can be a single string with the plugin's {@link Ext.enums.Plugin alias}:
         *
         *      var list = Ext.create({
         *          xtype: 'list',
         *          itemTpl: '<div class="item">{title}</div>',
         *          store: 'Items',
         *
         *          plugins: 'listpaging'
         *      });
         *
         * In the above examples, the string "listpaging" is the type alias for
         * `Ext.dataview.plugin.ListPaging`. The full alias includes the "plugin." prefix
         * (i.e., 'plugin.listpaging').
         *
         * The preferred form for multiple plugins or to configure plugins is the
         * keyed-object form (new in version 6.5):
         *
         *      var list = Ext.create({
         *          xtype: 'list',
         *          itemTpl: '<div class="item">{title}</div>',
         *          store: 'Items',
         *
         *          plugins: {
         *              pullrefresh: true,
         *              listpaging: {
         *                  autoPaging: true,
         *                  weight: 10
         *              }
         *          }
         *      });
         *
         * The object keys are the `id`'s as well as the default type alias. This form
         * allows the value of the `plugins` to be merged from base class to derived class
         * and finally with the instance configuration. This allows classes to define a
         * set of plugins that derived classes or instantiators can further configure or
         * disable. This merge behavior is a feature of the
         * {@link Ext.Class#cfg!config config system}.
         *
         * The `plugins` config can also be an array of plugin aliases (arrays are not
         * merged so this form does not respect plugins defined by the class author):
         *
         *      var list = Ext.create({
         *          xtype: 'list',
         *          itemTpl: '<div class="item">{title}</div>',
         *          store: 'Items',
         *
         *          plugins: ['listpaging', 'pullrefresh']
         *      });
         *
         * An array can also contain elements that are config objects with a `type`
         * property holding the type alias:
         *
         *      var list = Ext.create({
         *          xtype: 'list',
         *          itemTpl: '<div class="item">{title}</div>',
         *          store: 'Items',
         *
         *          plugins: ['pullrefresh', {
         *              type: 'listpaging',
         *              autoPaging: true
         *          }]
         *      });
         */
        plugins: null
    },

    /**
     * Adds a plugin. For example:
     *
     *      list.addPlugin('pullrefresh');
     *
     * Or:
     *
     *      list.addPlugin({
     *          type: 'pullrefresh',
     *          pullRefreshText: 'Pull to refresh...'
     *      });
     *
     * @param {Object/String/Ext.plugin.Abstract} plugin The plugin or config object or
     * alias to add.
     * @since 6.2.0
     */
    addPlugin: function(plugin) {
        var me = this,
            plugins = me.getPlugins();

        if (plugins) {
            plugin = me.createPlugin(plugin);
            plugin.init(me);
            plugins.push(plugin);
        }
        else {
            me.setPlugins(plugin);
            plugin = me.getPlugins()[0];
        }

        return plugin;
    },

    /**
     * Removes and destroys a plugin.
     *
     * **Note:** Not all plugins are designed to be removable. Consult the documentation
     * for the specific plugin in question to be sure.
     * @param {String/Ext.plugin.Abstract} plugin The plugin or its `id` to remove.
     * @return {Ext.plugin.Abstract} plugin instance or `null` if not found.
     * @since 6.2.0
     */
    destroyPlugin: function(plugin) {
        return this.removePlugin(plugin, true);
    },

    /**
     * Retrieves plugin by its `type` alias. For example:
     *
     *      var list = Ext.create({
     *          xtype: 'list',
     *          itemTpl: '<div class="item">{title}</div>',
     *          store: 'Items',
     *
     *          plugins: ['listpaging', 'pullrefresh']
     *      });
     *
     *      list.findPlugin('pullrefresh').setPullRefreshText('Pull to refresh...');
     *
     * **Note:** See also {@link #getPlugin}.
     *
     * @param {String} type The Plugin's `type` as specified by the class's
     * {@link Ext.Class#cfg-alias alias} configuration.
     * @return {Ext.plugin.Abstract} plugin instance or `null` if not found.
     * @since 6.2.0
     */
    findPlugin: function(type) {
        var plugins = this.getPlugins(),
            n = plugins && plugins.length,
            i, plugin, ret;

        for (i = 0; i < n && !ret; i++) {
            plugin = plugins[i];

            // Classic used ptype forever, so support it too but Core/Modern just use
            // type.
            if (plugin.type === type || plugin.ptype === type) {
                ret = plugin;
            }
        }

        return ret || null;
    },

    /**
     * Retrieves a plugin by its `id`.
     *
     *      var list = Ext.create({
     *          xtype: 'list',
     *          itemTpl: '<div class="item">{title}</div>',
     *          store: 'Items',
     *
     *          plugins: [{
     *              type: 'pullrefresh',
     *              id: 'foo'
     *          }]
     *      });
     *
     *      list.getPlugin('foo').setPullRefreshText('Pull to refresh...');
     *
     * **Note:** See also {@link #findPlugin}.
     *
     * @param {String} id The `id` of the plugin.
     * @return {Ext.plugin.Abstract} plugin instance or `null` if not found.
     * @since 6.2.0
     */
    getPlugin: function(id) {
        var plugins = this.getPlugins(),
            n = plugins && plugins.length,
            i, plugin, ret;

        for (i = 0; i < n && !ret; i++) {
            plugin = plugins[i];

            // Classic used pluginId, so support it too but Core/Modern just use id.
            if (plugin.id === id || plugin.pluginId === id) {
                ret = plugin;
            }
        }

        return ret || null;
    },

    /**
     * Removes and (optionally) destroys a plugin.
     *
     * **Note:** Not all plugins are designed to be removable. Consult the documentation
     * for the specific plugin in question to be sure.
     * @param {String/Ext.plugin.Abstract} plugin The plugin or its `id` to remove.
     * @param {Boolean} [destroy] Pass `true` to not call `destroy()` on the plugin.
     * @return {Ext.plugin.Abstract} plugin instance or `null` if not found.
     * @since 6.2.0
     */
    removePlugin: function(plugin, destroy) {
        var plugins = this.getPlugins(),
            i = plugins && plugins.length || 0,
            p;

        while (i-- > 0) {
            p = plugins[i];

            if (p === plugin || p.id === plugin) {
                plugins.splice(i, 1);

                if (destroy) {
                    if (p.destroy) {
                        p.destroy();
                    }
                }
                else if (p.detachCmp) {
                    p.detachCmp();

                    if (p.setCmp) {
                        p.setCmp(null);
                    }
                }

                break;
            }

            p = null;
        }

        return p;
    },

    privates: {
        statics: {
            idSeed: 0
        },

        /**
         * Creates a particular plugin type if defined in the `plugins` configuration.
         * @param {String} type The `type` of the plugin.
         * @return {Ext.plugin.Abstract} The plugin that was created.
         * @private
         * @since 6.2.0
         */
        activatePlugin: function(type) {
            var me = this,
                config = me.initialConfig,
                plugins = config && config.plugins,
                ret = null,
                i, include, p;

            if (plugins) {
                include = me.config.plugins;
                include = (include && typeof include === 'object') ? include : null;

                plugins = Ext.plugin.Abstract.decode(plugins, 'type', include);

                for (i = plugins.length; i-- > 0;) {
                    p = plugins[i];

                    if (p === type || p.type === type) {
                        me.initialConfig = config = Ext.apply({}, config);
                        config.plugins = plugins; // switch over to our copy

                        // Put the instance in the plugins array so it will be included in
                        // the applyPlugins loop for normal processing of plugins.
                        plugins[i] = ret = me.createPlugin(p);

                        break;
                    }
                }
            }

            return ret;
        },

        /**
         * Applier for the `plugins` config property.
         * @param {String[]/Object[]/Ext.plugin.Abstract[]} plugins The new plugins to use.
         * @param {Ext.plugin.Abstract[]} oldPlugins The existing plugins in use.
         * @private
         */
        applyPlugins: function(plugins, oldPlugins) {
            var me = this,
                oldCount = oldPlugins && oldPlugins.length || 0,
                count, i, plugin;

            // Ensure we have an array if we got a single thing or a copy of the array
            // if we got an array.
            if (plugins) {
                plugins = Ext.plugin.Abstract.decode(plugins, 'type');
            }

            // We need to destroy() old plugins that aren't being brought forward in
            // the new array...
            //
            for (i = 0; i < oldCount; ++i) {
                oldPlugins[i].$dead = true; // so paint the old ones
            }

            // Pass #1 (For historical reasons): Create all of the plugins. Prior versions
            // did this pass first then called init() so we preserve the timings and do
            // the same.
            //
            count = plugins && plugins.length || 0;

            for (i = 0; i < count; ++i) {
                plugins[i] = me.createPlugin(plugins[i]); // ensure we have an instance
            }

            // Pass #2: Initialize the plugins that have not been and clear $dead for
            // any returning for the next round.
            //
            for (i = 0; i < count; ++i) {
                plugin = plugins[i];

                if (plugin.$dead) { // if (it was in oldPlugins)
                    delete plugin.$dead;  // unpaint it (it's a keeper)
                }
                else {
                    plugin.init(me);  // this one is new to the party
                }
            }

            // Now we can teardown any plugins that aren't coming back.
            //
            for (i = 0; i < oldCount; ++i) {
                if ((plugin = oldPlugins[i]).$dead) {
                    delete plugin.$dead;
                    Ext.destroy(plugin);
                }
            }

            return plugins;
        },

        /**
         * Converts the provided type or config object into a plugin instance.
         * @param {String/Object/Ext.plugin.Abstract} config The plugin type, config
         * object or instance.
         * @return {Ext.plugin.Abstract}
         * @private
         */
        createPlugin: function(config) {
            var ret;

            if (typeof config === 'string') {
                config = {
                    type: config
                };
            }

            ret = config;

            if (!config.isInstance) {
                // The owner may be needed by plugin's initConfig so provide it:
                config.cmp = this;

                ret = Ext.factory(config, null, null, 'plugin');

                // Cleanup the user's config object:
                delete config.cmp;
            }

            if (!ret.id) {
                ret.id = ++Pluggable.idSeed;
            }

            if (ret.setCmp) {
                ret.setCmp(this);
            }

            return ret;
        }
    }
};
});
