/**
 * This class is intended as a mixin for classes that want to provide a "bind" config that
 * connects to a `ViewModel`.
 * @private
 * @since 5.0.0
 */
Ext.define('Ext.mixin.Bindable', {
    mixinId: 'bindable',

    config: {
        /**
         * @cfg {Object/String} [bind]
         * Setting this config option adds or removes data bindings for other configs.
         * For example, to bind the `title` config:
         *
         *      var panel = Ext.create({
         *          xtype: 'panel',
         *          bind: {
         *              title: 'Hello {user.name}'
         *          }
         *      });
         *
         * To dynamically add bindings:
         *
         *      panel.setBind({
         *          title: 'Greetings {user.name}!'
         *      });
         *
         * To remove bindings:
         *
         *      panel.setBind({
         *          title: null
         *      });
         *
         * The bind expressions are presented to `{@link Ext.app.ViewModel#bind}`. The
         * `ViewModel` instance is determined by `lookupViewModel`.
         *
         * **Note:** If  bind is passed as a string, it will use the
         * {@link Ext.Component#property-defaultBindProperty} for the binding.
         */
        bind: {
            $value: null,
            lazy: true
        },

        // @cmd-auto-dependency { aliasPrefix: 'controller.' }
        /**
         * @cfg {String/Object/Ext.app.ViewController} controller
         * A string alias, a configuration object or an instance of a `ViewController` for
         * this container. Sample usage:
         *
         *     Ext.define('MyApp.UserController', {
         *         alias: 'controller.user'
         *     });
         *
         *     Ext.define('UserContainer', {
         *         extend: 'Ext.container.container',
         *         controller: 'user'
         *     });
         *     // Or
         *     Ext.define('UserContainer', {
         *         extend: 'Ext.container.container',
         *         controller: {
         *             type: 'user',
         *             someConfig: true
         *         }
         *     });
         *
         *     // Can also instance at runtime
         *     var ctrl = new MyApp.UserController();
         *     var view = new UserContainer({
         *         controller: ctrl
         *     });
         *
         */
        controller: null,

        /**
         * @method getController
         * Returns the {@link Ext.app.ViewController} instance associated with this 
         * component via the {@link #controller} config or {@link #setController} method.
         * @return {Ext.app.ViewController} Returns this component's ViewController or 
         * null if one was not configured
         */

        /**
         * @cfg {Boolean} defaultListenerScope
         * If `true`, this component will be the default scope (this pointer) for events
         * specified with string names so that the scope can be dynamically resolved. The
         * component will automatically become the defaultListenerScope if a
         * {@link #controller} is specified.
         *
         * See the introductory docs for {@link Ext.container.Container} for some sample
         * usages.
         *
         * **NOTE**: This value can only be reliably set at construction time. Setting it
         * after that time may not correctly rewire all of the potentially effected
         * listeners.
         */
        defaultListenerScope: false,

        /**
         * @cfg {String/String[]/Object} publishes
         * One or more names of config properties that this component should publish 
         * to its ViewModel. Generally speaking, only properties defined in a class config
         * block (including ancestor config blocks and mixins) are eligible for publishing 
         * to the viewModel. Some components override this and publish their most useful 
         * configs by default. 
         * 
         * **Note:** We'll discuss publishing properties **not** found in the config block below. 
         * 
         * Values determined to be invalid by component (often form fields and model validations) 
         * will not be published to the ViewModel.
         *
         * This config uses the `{@link #cfg-reference}` to determine the name of the data
         * object to place in the `ViewModel`. If `reference` is not set then this config
         * is ignored.
         *
         * By using this config and `{@link #cfg-reference}` you can bind configs between
         * components. For example:
         *
         *      ...
         *          items: [{
         *              xtype: 'textfield',
         *              reference: 'somefield',  // component's name in the ViewModel
         *              publishes: 'value' // value is not published by default
         *          },{
         *              ...
         *          },{
         *              xtype: 'displayfield',
         *              bind: 'You have entered "{somefield.value}"'
         *          }]
         *      ...
         *
         * Classes must provide this config as an Object:
         *
         *      Ext.define('App.foo.Bar', {
         *          publishes: {
         *              foo: true,
         *              bar: true
         *          }
         *      });
         *
         * This is required for the config system to properly merge values from derived
         * classes.
         *
         * For instances this value can be specified as a value as show above or an array
         * or object as follows:
         *
         *      {
         *          xtype: 'textfield',
         *          reference: 'somefield',
         *          publishes: [
         *              'value',
         *              'rawValue',
         *              'dirty'
         *          ]
         *      }
         *
         *      // This achieves the same result as the above array form.
         *      {
         *          xtype: 'textfield',
         *          reference: 'somefield',
         *          publishes: {
         *              value: true,
         *              rawValue: true,
         *              dirty: true
         *          }
         *      }
         *
         * In some cases, users may want to publish a property to the viewModel that is not found
         * in a class  config block. In these situations, you may utilize {@link #publishState}
         * if the property has a  setter method. Let's use
         * {@link Ext.form.Labelable#setFieldLabel setFieldLabel} as an example:
         *
         *       setFieldLabel: function(fieldLabel) {
         *           this.callParent(arguments);
         *           this.publishState('fieldLabel', fieldLabel);
         *       }        
         * 
         * With the above chunk of code, fieldLabel may now be published to the viewModel.
         * 
         * @since 5.0.0
         */
        publishes: {
            $value: null,
            lazy: true,
            merge: function(newValue, oldValue) {
                return this.mergeSets(newValue, oldValue);
            }
        },

        // @cmd-auto-dependency { directRef: 'Ext.data.Session' }
        /**
         * @cfg {Boolean/Object/Ext.data.Session} [session=null]
         * If provided this creates a new `Session` instance for this component. If this
         * is a `Container`, this will then be inherited by all child components.
         *
         * To create a new session you can specify `true`:
         *
         *      Ext.create({
         *          xtype: 'viewport',
         *          session: true,
         *
         *          items: [{
         *              ...
         *          }]
         *      });
         *
         * Alternatively, a config object can be provided:
         *
         *      Ext.create({
         *          xtype: 'viewport',
         *          session: {
         *              ...
         *          },
         *
         *          items: [{
         *              ...
         *          }]
         *      });
         *
         */
        session: {
            $value: null,
            lazy: true
        },

        /**
         * @cfg {String/String[]/Object} twoWayBindable
         * This object holds a map of `config` properties that will update their binding
         * as they are modified. For example, `value` is a key added by form fields. The
         * form of this config is the same as `{@link #cfg!publishes}`.
         *
         * This config is defined so that updaters are not created and added for all
         * bound properties since most cannot be modified by the end-user and hence are
         * not appropriate for two-way binding.
         */
        twoWayBindable: {
            $value: null,
            lazy: true,
            merge: function(newValue, oldValue) {
                return this.mergeSets(newValue, oldValue);
            }
        },

        // @cmd-auto-dependency { aliasPrefix: 'viewmodel.', defaultType: 'default' }
        /**
         * @cfg {String/Object/Ext.app.ViewModel} viewModel
         * The `ViewModel` is a data provider for this component and its children. The
         * data contained in the `ViewModel` is typically used by adding `bind` configs
         * to the components that want present or edit this data.
         *
         * When set, the `ViewModel` is created and links to any inherited `viewModel`
         * instance from an ancestor container as the "parent". The `ViewModel` hierarchy,
         * once established, only supports creation or destruction of children. The
         * parent of a `ViewModel` cannot be changed on the fly.
         *
         * If this is a root-level `ViewModel`, the data model connection is made to this
         * component's associated `{@link Ext.data.Session Data Session}`. This is
         * determined by calling `getInheritedSession`.
         *
         */
        viewModel: {
            $value: null,
            lazy: true
        }
    },

    /**
     * @property {String} [defaultBindProperty]
     * This property is used to determine the property of a `bind` config that is just
     * the value. For example, if `defaultBindProperty="value"`, then this shorthand
     * `bind` config:
     *
     *      bind: '{name}'
     *
     * Is equivalent to this object form:
     *
     *      bind: {
     *          value: '{name}'
     *      }
     *
     * The `defaultBindProperty` is set to "value" for form fields and to "store" for
     * grids and trees.
     * @protected
     */
    defaultBindProperty: null,

    /**
     * @cfg {Boolean} nameable
     * Set to `true` for this component's `name` property to be tracked by its containing
     * `nameHolder`.
     */
    nameable: false,

    /**
     * @cfg {Boolean} shareableName
     * Set to `true` to allow this component's `name` to be shared by other items in the
     * same `nameHolder`. Such items will be returned in an array from `lookupName`.
     */
    shareableName: false,

    /**
     * @cfg {String} reference
     * Specifies a name for this component inside its component hierarchy. This name
     * must be unique within its {@link Ext.container.Container#referenceHolder view}
     * or its {@link Ext.app.ViewController ViewController}. See the documentation in
     * {@link Ext.container.Container} for more information about references.
     *
     * **Note**: Valid identifiers start with a letter or underscore and are followed
     * by zero or more additional letters, underscores or digits. References are case
     * sensitive.
     */
    reference: null,

    /**
     * @property {RegExp}
     * Regular expression used for validating `reference` values.
     * @private
     */
    validRefRe: /^[a-z_][a-z0-9_]*$/i,

    getReference: function() {
        // Maintained for compatibility with <7 where reference used the config system
        return this.reference;
    },

    /**
     * Called by `getInherited` to initialize the inheritedState the first time it is
     * requested.
     * @protected
     */
    initInheritedState: function(inheritedState) {
        var me = this,
            reference = me.reference,
            controller = me.getController(),
            // Don't instantiate the view model here, we only need to know that
            // it exists
            viewModel = me.getConfig('viewModel', true),
            session = me.getConfig('session', true),
            defaultListenerScope = me.getDefaultListenerScope();

        if (controller) {
            inheritedState.controller = controller;
        }

        if (defaultListenerScope) {
            inheritedState.defaultListenerScope = me;
        }
        else if (controller) {
            inheritedState.defaultListenerScope = controller;
        }

        if (viewModel) {
            // If we're not configured with an instance, just stamp the current component as
            // the thing that holds the view model. When we ask to get the inherited view model,
            // we will know that it's not an instance yet so we need to spin it up on this component
            // We need to initialize them from top-down, but we don't want to do it up front.
            if (!viewModel.isViewModel) {
                viewModel = me;
            }

            inheritedState.viewModel = viewModel;
        }

        // Same checks as the view model
        if (session) {
            if (!session.isSession) {
                session = me;
            }

            inheritedState.session = session;
        }

        if (reference) {
            me.referenceKey = (inheritedState.referencePath || '') + reference;
            me.viewModelKey = (inheritedState.viewModelPath || '') + reference;
        }
    },

    /**
     * Determines if the passed property name is bound to ViewModel data.
     * @param {String} [name] The property name to test. Defaults to the
     * {@link #defaultBindProperty}
     * @returns {Boolean} `true` if the passed property receives data from a ViewModel.
     * @since 6.5.0
     */
    isBound: function(name) {
        var bind = this.getBind();

        return !!(bind && (bind[name || this.defaultBindProperty]));
    },

    /**
     * Gets the controller that controls this view. May be a controller that belongs
     * to a view higher in the hierarchy.
     * 
     * @param {Boolean} [skipThis=false] `true` to not consider the controller directly attached
     * to this view (if it exists).
     * @return {Ext.app.ViewController} The controller. `null` if no controller is found.
     *
     * @since 5.0.1
     */
    lookupController: function(skipThis) {
        return this.getInheritedConfig('controller', skipThis) || null;
    },

    /**
     * Returns the `Ext.data.Session` for this instance. This property may come
     * from this instance's `{@link #session}` or be inherited from this object's parent.
     * @param {Boolean} [skipThis=false] Pass `true` to ignore a `session` configured on
     * this instance and only consider an inherited session.
     * @return {Ext.data.Session}
     * @since 5.0.0
     */
    lookupSession: function(skipThis) {
        // See lookupViewModel
        var ret = skipThis ? null : this.getSession(); // may be the initGetter!

        if (!ret) {
            ret = this.getInheritedConfig('session', skipThis);

            if (ret && !ret.isSession) {
                ret = ret.getInherited().session = ret.getSession();
            }
        }

        return ret || null;
    },

    /**
     * Returns the `Ext.app.ViewModel` for this instance. This property may come from this
     * this instance's `{@link #viewModel}` or be inherited from this object's parent.
     * @param {Boolean} [skipThis=false] Pass `true` to ignore a `viewModel` configured on
     * this instance and only consider an inherited view model.
     * @return {Ext.app.ViewModel}
     * @since 5.0.0
     */
    lookupViewModel: function(skipThis) {
        var ret = skipThis ? null : this.getViewModel(); // may be the initGetter!

        if (!ret) {
            ret = this.getInheritedConfig('viewModel', skipThis);

            // If what we get back is a component, it means the component was configured
            // with a view model, however the construction of it has been delayed until
            // we need it. As such, go and construct it and store it on the inherited state.
            if (ret && !ret.isViewModel) {
                ret = ret.getInherited().viewModel = ret.getViewModel();
            }
        }

        return ret || null;
    },

    /**
     * Publish this components state to the `ViewModel`. If no arguments are given (or if
     * this is the first call), the entire state is published. This state is determined by
     * the `publishes` property.
     *
     * This method is called only by component authors.
     *
     * @param {String} [property] The name of the property to update.
     * @param {Object} [value] The value of `property`. Only needed if `property` is given.
     * @protected
     * @since 5.0.0
     */
    publishState: function(property, value) {
        var me = this,
            state = me.publishedState,
            binds = me.getBind(),
            binding = binds && property && binds[property],
            count = 0,
            name, publishes, vm, path;

        //<debug>
        if (!(arguments.length === 0 || arguments.length === 2)) {
            Ext.raise('publishState must either be called with no args, or with both name ' +
                      'AND value passed');
        }
        //</debug>

        if (binding && !binding.syncing && !binding.isReadOnly()) {
            // If the binding has never fired & our value is either:
            // a) undefined
            // b) null
            // c) The value we were initially configured with
            // Then we don't want to publish it back to the view model. If we do, we'll be
            // overwriting whatever is in the viewmodel and it will never have a chance to fire.
            if (binding.calls || !(value == null || value === me.getInitialConfig()[property])) {
                binding.setValue(value);
            }
        }

        if (!(publishes = me.getPublishes())) {
            return;
        }

        if (!(vm = me.lookupViewModel())) {
            return;
        }

        // Important to access path after lookupViewModel, which will kick off
        // our inheritedState if we don't have one
        if (!(path = me.viewModelKey)) {
            return;
        }

        state = state || (me.publishedState = {});

        if (property) {
            if (!publishes[property]) {
                return;
            }

            // If we are setting an individual property and that is not a {} or a [] then
            // check to see if it is unchanged.
            if (!(value && value.constructor === Object) && !(value instanceof Array)) {
                if (state[property] === value) {
                    return;
                }
            }

            path += '.';
            path += property;
        }
        else {
            for (name in publishes) {
                ++count;
                state[name] = me.getConfig(name);
            }

            if (!count) { // if (no properties were put in "state")
                return;
            }

            value = state;
        }

        vm.set(path, value);
    },

    //=========================================================================

    privates: {
        /**
         * @param {String/Object} binds
         * @param {Object} currentBindings
         * @return {Object}
         * @private
         * @since 5.0.0
         */
        applyBind: function(binds, currentBindings) {
            if (!binds) {
                return currentBindings;
            }

            // eslint-disable-next-line vars-on-top
            var me = this,
                viewModel = me.lookupViewModel(),
                twoWayable = me.getTwoWayBindable(),
                getBindTemplateScope = me._getBindTemplateScope,
                b, watch, property, descriptor;

            //<debug>
            if (!viewModel) {
                Ext.raise('Cannot use bind config without a viewModel');
            }
            //</debug>

            if (typeof binds === 'string') {
                //<debug>
                if (!me.defaultBindProperty) {
                    Ext.raise(me.$className + ' has no defaultBindProperty - ' +
                                    'Please specify a bind object');
                }
                //</debug>

                b = binds;
                binds = {};
                binds[me.defaultBindProperty] = b;
            }

            for (property in binds) {
                descriptor = binds[property];
                b = currentBindings && currentBindings[property];

                if (b) {
                    b.destroy();
                    delete currentBindings[property];
                }

                if (descriptor) {
                    if (!b && twoWayable && twoWayable[property]) {
                        (watch || (watch = {}))[property] = '_onConfigPropChange';
                    }

                    b = viewModel.bind(descriptor, me.onBindNotify, me);
                    b._config = Ext.Config.get(property);
                    b.getTemplateScope = getBindTemplateScope;

                    //<debug>
                    if (!me[b._config.names.set]) {
                        Ext.raise('Cannot bind ' + property + ' on ' + me.$className +
                                        ' - missing a ' + b._config.names.set + ' method.');
                    }
                    //</debug>

                    (currentBindings || (currentBindings = {}))[property] = b;
                }
            }

            if (watch) {
                watch.scope = me;
                me.watchConfig(watch);
            }

            me.$bindings = currentBindings;

            return currentBindings;
        },

        applyController: function(controller) {
            if (controller) {
                controller = Ext.Factory.controller(controller);
                controller.setView(this);
            }

            // In classic, this is a no-op, in modern it will
            // save a local reference
            this.controller = controller;

            return controller;
        },

        updatePublishes: function(all) {
            var me = this,
                property, watch;

            if (all && me.lookupViewModel()) {
                for (property in all) {
                    if (all[property]) {
                        (watch || (watch = {}))[property] = '_onConfigPropChange';
                    }
                }

                if (watch) {
                    watch.scope = me;
                    me.watchConfig(watch);
                }
            }

            return all;
        },

        /**
         * Transforms a Session config to a proper instance.
         * @param {Object} session
         * @return {Ext.data.Session}
         * @private
         * @since 5.0.0
         */
        applySession: function(session) {
            var parentSession, config;

            if (!session) {
                return null;
            }

            if (!session.isSession) {
                parentSession = this.lookupSession(true); // skip this component
                config = (session === true) ? {} : session;

                if (parentSession) {
                    session = parentSession.spawn(config);
                }
                else {
                    // Mask this use of Session from Cmd - the dependency is not ours
                    // but the caller
                    session = new Ext.data['Session'](config); // eslint-disable-line dot-notation
                }
            }

            return session;
        },

        /**
         * Transforms a ViewModel config to a proper instance.
         * @param {String/Object/Ext.app.ViewModel} viewModel
         * @return {Ext.app.ViewModel}
         * @private
         * @since 5.0.0
         */
        applyViewModel: function(viewModel) {
            var me = this,
                config, session;

            if (!viewModel) {
                return null;
            }

            if (!viewModel.isViewModel) {
                config = {
                    parent: me.lookupViewModel(true), // skip this component

                    // Ensure that VM construction activity can reach the view (for
                    // example events on stores)
                    view: me
                };

                config.session = me.getSession();

                if (!session && !config.parent) {
                    config.session = me.lookupSession();
                }

                if (viewModel) {
                    if (viewModel.constructor === Object) {
                        Ext.apply(config, viewModel);
                    }
                    else if (typeof viewModel === 'string') {
                        config.type = viewModel;
                    }
                }

                viewModel = Ext.Factory.viewModel(config);
            }

            return viewModel;
        },

        _getBindTemplateScope: function() {
            // This method is called as a method on a Binding instance, so the "this" pointer
            // is that of the Binding. The "scope" of the Binding is the component owning it.
            return this.scope.resolveListenerScope();
        },

        destroyBindable: function() {
            var me = this,
                viewModel = me.getConfig('viewModel', true),
                session = me.getConfig('session', true),
                controller = me.getController();

            if (viewModel && viewModel.isViewModel) {
                viewModel.destroy();
                me.setViewModel(null);
            }

            if (session && session.isSession) {
                if (session.getAutoDestroy()) {
                    session.destroy();
                }

                me.setSession(null);
            }

            if (controller) {
                me.setController(null);
                controller.destroy();
            }
        },

        /**
         * This method triggers the lazy configs and must be called when it is time to
         * fully boot up. The configs that must be initialized are: `bind`, `publishes`,
         * `session`, `twoWayBindable` and `viewModel`.
         * @private
         * @since 5.0.0
         */
        initBindable: function() {
            var me = this,
                controller = me.controller;

            me.initBindable = Ext.emptyFn;
            me.getBind();
            me.getPublishes();

            // If we have binds, the applyBind method will call getTwoWayBindable. If we
            // have no binds then applyBind will not be called and we will ignore our
            // twoWayBindable config (which is fine).

            // If we have publishes or binds then the viewModel will be requested.
            if (!me.viewModel) {
                // Force VM creation now
                me.getViewModel();
            }

            if (controller) {
                controller.initBindings();
            }

            if (me.reference) {
                // If we have no "reference" config then we do not publish our state to the
                // viewmodel.
                me.publishState();
            }
        },

        /**
         * Checks if a particular binding is synchronizing the value.
         * @param {String} name The name of the property being bound to.
         * @return {Boolean} `true` if the binding is syncing.
         *
         * @private
         */
        isSyncing: function(name) {
            var bindings = this.getBind(),
                ret = false,
                binding;

            if (bindings) {
                binding = bindings[name];

                if (binding) {
                    ret = binding.syncing > 0;
                }
            }

            return ret;
        },

        notifyIf: function(skipThis) {
            var vm = this.lookupViewModel(skipThis);

            if (vm) {
                vm.notify();
            }
        },

        onBindNotify: function(value, oldValue, binding) {
            binding.syncing = (binding.syncing + 1) || 1;
            this[binding._config.names.set](value);
            --binding.syncing;
        },

        _onConfigPropChange: function(me, name, value) {
            me.publishState(name, value);
        },

        removeBindings: function() {
            var me = this,
                bindings = me.$bindings,
                b, key;

            if (bindings) {
                for (key in bindings) {
                    b = bindings[key];

                    if (b) {
                        b.destroy();
                        b._config = b.getTemplateScope = null;

                        bindings[key] = null;
                    }
                }
            }
        },

        /**
         * Updates the session config.
         * @param {Ext.data.Session} session
         * @private
         */
        updateSession: function(session) {
            var state = this.getInherited();

            if (session) {
                state.session = session;
            }
            else {
                delete state.session;
            }
        },

        /**
         * Updates the viewModel config.
         * @param {Ext.app.ViewModel} viewModel
         * @param {Ext.app.ViewModel} oldViewModel
         * @private
         */
        updateViewModel: function(viewModel, oldViewModel) {
            var me = this,
                state = me.getInherited(),
                controller = me.getController();

            if (viewModel) {
                me.hasVM = true;
                state.viewModel = viewModel;
                viewModel.setView(me);

                if (controller) {
                    controller.initViewModel(viewModel);
                }
            }
            else {
                delete state.viewModel;
            }

            // In classic, this is a no-op, in modern it will
            // save a local reference
            me.viewModel = viewModel;
        }
    } // private
});
