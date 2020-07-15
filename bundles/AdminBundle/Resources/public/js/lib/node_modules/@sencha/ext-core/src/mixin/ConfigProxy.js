/**
 * This mixin allows a class to easily forward (or proxy) configs to other objects. Once
 * mixed in, the using class (and its derived classes) can add a `proxyConfig` object
 * property to their class body that specifies the accessor and configs to manage.
 *
 * For example:
 *
 *      Ext.define('ParentThing', {
 *          mixins: [
 *              'Ext.mixin.ConfigProxy'
 *          ],
 *
 *          config: {
 *              childThing: {
 *                  xtype: 'panel'
 *              }
 *          },
 *
 *          proxyConfig: {
 *              // The keys of this object are themselves configs. Their getters
 *              // are used to identify the target to which the listed configs are
 *              // proxied.
 *
 *              childThing: [
 *                  // This list of config names will be proxied to the object
 *                  // returned by the getter (getChildThing in this case). In
 *                  // addition, each of these will be defined as configs on this
 *                  // class but with a special getter and setter.
 *                  //
 *                  // These configs cannot be previously defined nor can their
 *                  // be getters or setters already present.
 *
 *                  'title'
 *              ]
 *          }
 *      });
 *
 * If the getter for a proxy target returns `null`, the setter for the proxied config
 * will simply discard the value. It is expected that the target will generally always
 * exist.
 *
 * To proxy methods, the array of config names is replaced by an object:
 *
 *      Ext.define('ParentThing', {
 *          mixins: [
 *              'Ext.mixin.ConfigProxy'
 *          ],
 *
 *          config: {
 *              childThing: {
 *                  xtype: 'panel'
 *              }
 *          },
 *
 *          proxyConfig: {
 *              // The keys of this object are themselves configs. Their getters
 *              // are used to identify the target to which the listed configs are
 *              // proxied.
 *
 *              childThing: {
 *                  configs: [
 *                      // same as when "childThing" was just this array...
 *                  ],
 *
 *                  methods: [
 *                      // A list of methods to proxy to the childThing.
 *                      'doStuff'
 *                  ]
 *              ]
 *          }
 *      });
 *
 * @private
 * @since 6.5.0
 */
Ext.define('Ext.mixin.ConfigProxy', function(ConfigProxy) { return { // eslint-disable-line brace-style, max-len
    extend: 'Ext.Mixin',

    mixinConfig: {
        id: 'configproxy',

        extended: function(baseClass, derivedClass, classBody) {
            var proxyConfig = classBody.proxyConfig;

            derivedClass.$configProxies = Ext.apply(
                {}, derivedClass.superclass.self.$configProxies
            );

            if (proxyConfig) {
                delete classBody.proxyConfig;
                ConfigProxy.processClass(derivedClass, proxyConfig);
            }
        }
    },

    onClassMixedIn: function(targetClass) {
        var prototype = targetClass.prototype,
            proxyConfig = prototype.proxyConfig,
            initConfig = prototype.initConfig;

        prototype.$proxiedConfigs = null;  // constant shape
        targetClass.$configProxies = {
            // contents are basically the same as the proxyConfig object.
        };

        prototype.initConfig = function(config) {
            initConfig.apply(this, arguments);

            // ensure future setter calls will pass through to the target:
            this.$proxiedConfigs = null;

            return this;
        };

        if (proxyConfig) {
            delete prototype.proxyConfig;
            ConfigProxy.processClass(targetClass, proxyConfig);
        }
    },

    /**
     * This method returns an object of all proxied config values for a given target. This
     * is only useful during the class initialization phase to avoid passing in "wrong"
     * initial config values for a child object and then proxying down all the configs
     * from the parent.
     *
     * This method is not typically called directly but rather `mergeProxiedConfigs` is
     * more likely.
     * @param {String} name The proxy target config name (in the class example, this would
     * be "childThing").
     * @return {Object}
     * @private
     * @since 6.5.0
     */
    getProxiedConfigs: function(name) {
        var me = this,
            configs = me.config,  // the merged config set
            configProxies = me.self.$configProxies[name],
            i = configProxies && configProxies.length,
            cfg, proxiedConfigs, ret, s, v;

        if (i && me.isConfiguring) {
            // Lazily create the $proxiedConfigs map to track the config properties
            // we are "stealing" away.
            proxiedConfigs = me.$proxiedConfigs || (me.$proxiedConfigs = {});

            while (i-- > 0) {
                cfg = configProxies[i];
                proxiedConfigs[s = cfg.name] = cfg;

                if ((v = configs[s]) !== undefined) {
                    (ret || (ret = {}))[s] = v;
                }
            }
        }

        return ret;
    },

    /**
     * This method accepts the normal config object (`itemConfig`) for the child object
     * (`name`) and merges any proxied configs into a new config object. This is useful
     * during the class initialization phase to avoid passing in "wrong" initial config
     * values for a child object and then proxying down the rest of the configs.
     *
     * This method is typically called during an "applier" method for a proxy target. If
     * called at any other time this method simply returns the given `itemConfig`. This
     * makes it safe to code such appliers as follows:
     *
     *      applyChildThing: function(config) {
     *          config = this.mergeProxiedConfigs('childThing', config);
     *
     *          return new ChildThing(config);
     *      }
     *
     * @param {String} name The proxy target config name (in the class example, this would
     * be "childThing").
     * @param {Mixed} itemConfig The default configuration for the child item.
     * @param {Boolean} [alwaysClone] Pass `true` to ensure a new object is returned.
     * @return {Object}
     * @private
     * @since 6.5.0
     */
    mergeProxiedConfigs: function(name, itemConfig, alwaysClone) {
        var me = this,
            ret = itemConfig,
            proxied = me.getProxiedConfigs(name),
            configurator;

        if (proxied) {
            if (!itemConfig) {
                ret = proxied;
            }
            else if (itemConfig.constructor === Object) {
                configurator = me.self.getConfigurator();

                // First clone() so don't mutate the config:
                ret = configurator.merge(me, Ext.clone(itemConfig), proxied);
            }
        }

        if (alwaysClone && ret === itemConfig) {
            ret = Ext.clone(ret);
        }

        return ret;
    },

    statics: {
        processClass: function(targetClass, proxyConfig) {
            var ExtConfig = Ext.Config,
                targetProto = targetClass.prototype,
                add = {},
                proxies = targetClass.$configProxies,
                cfg, configs, itemGetter, i, item, methods, n, name, proxiedConfigs, s;

            for (item in proxyConfig) {
                itemGetter = ExtConfig.get(item).names.get;
                configs = proxyConfig[item];

                if (Ext.isArray(configs)) {
                    methods = null;
                }
                else {
                    methods = configs.methods;
                    configs = configs.configs;
                }

                if (!(proxiedConfigs = proxies[item])) {
                    proxies[item] = proxiedConfigs = [];
                }
                else {
                    // this array comes from the superclass so slice it for this class:
                    proxies[item] = proxiedConfigs = proxiedConfigs.slice();
                }

                for (i = 0, n = methods && methods.length; i < n; ++i) {
                    if (!targetProto[name = methods[i]]) {
                        targetProto[name] = ConfigProxy.wrapFn(itemGetter, name);
                    }
                    //<debug>
                    else {
                        Ext.raise('Cannot proxy method "' + name + '"');
                    }
                    //</debug>
                }

                for (i = 0, n = configs && configs.length; i < n; ++i) {
                    cfg = ExtConfig.get(s = configs[i]);

                    //<debug>
                    if (s in add) {
                        Ext.raise('Duplicate proxy config definitions for "' + s + '"');
                    }

                    if (s in targetProto.config) {
                        Ext.raise('Config "' + s + '" already defined for class ' +
                                  targetProto.$className);
                    }
                    //</debug>

                    add[s] = undefined; // sentinel initial value to avoid smashing
                    proxiedConfigs.push(cfg);

                    if (!targetProto[name = cfg.names.get]) {
                        targetProto[name] = ConfigProxy.wrapGet(itemGetter, name);
                    }
                    //<debug>
                    else {
                        Ext.raise('Cannot proxy "' + s + '" config getter');
                    }
                    //</debug>

                    if (!targetProto[name = cfg.names.set]) {
                        targetProto[name] = ConfigProxy.wrapSet(itemGetter, name, s);
                    }
                    //<debug>
                    else {
                        Ext.raise('Cannot proxy "' + s + '" config setter');
                    }
                    //</debug>
                }
            }

            targetClass.addConfig(add);
        },

        wrapFn: function(itemGetter, name) {
            return function() {
                var item = this[itemGetter]();

                return item && item[name].apply(item, arguments);
            };
        },

        wrapGet: function(itemGetter, configGetter) {
            return function() {
                var item = this[itemGetter]();

                return item && item[configGetter]();
            };
        },

        wrapSet: function(itemGetter, configSetter, itemName) {
            return function(value) {
                var me = this,
                    item, proxiedConfigs;

                // We define the proxied configs with "undefined" value so that we can
                // detect this and not smash them by default.
                if (!me.isConfiguring || value !== undefined) {
                    // If the item's applier called mergeProxiedConfigs or getProxiedConfigs
                    // then each config is marked as processed in proxiedConfigs (only during
                    // initialization).
                    item = me[itemGetter]();

                    proxiedConfigs = me.$proxiedConfigs; // lazy created by itemGetter

                    if (proxiedConfigs && proxiedConfigs[itemName]) {
                        delete proxiedConfigs[itemName]; // drop only the first set call
                        item = null;
                    }

                    if (item) {
                        item[configSetter](value);
                    }
                }

                return me;
            };
        }
    }
};
});
