/**
 * A Profile represents a range of devices that fall under a common category. For the vast majority
 * of apps that use device profiles, the app defines a Phone profile and a Tablet profile. Doing
 * this enables you to easily customize the experience for the different sized screens offered by
 * those device types.
 *
 * Only one Profile can be active at a time, and each Profile defines a simple {@link #isActive}
 * function that should return either true or false. The first Profile to return true from its
 * isActive function is set as your Application's
 * {@link Ext.app.Application#currentProfile current profile}.
 *
 * A Profile can define any number of {@link #models}, {@link #views}, {@link #controllers} and
 * {@link #stores} which will be loaded if the Profile is activated. It can also define
 * a {@link #launch} function that will be called after all of its dependencies have been loaded,
 * just before the {@link Ext.app.Application#launch application launch} function is called.
 *
 * ## Sample Usage
 *
 * First you need to tell your Application about your Profile(s):
 *
 *     Ext.application({
 *         name: 'MyApp',
 *         profiles: ['Phone', 'Tablet']
 *     });
 *
 * This will load app/profile/Phone.js and app/profile/Tablet.js. Here's how we might define the
 * Phone profile:
 *
 *     Ext.define('MyApp.profile.Phone', {
 *         extend: 'Ext.app.Profile',
 *
 *         views: ['Main'],
 *
 *         isActive: function() {
 *             return Ext.os.is('Phone');
 *         }
 *     });
 *
 * The isActive function returns true if we detect that we are running on a phone device. If that
 * is the case the Application will set this Profile active and load the 'Main' view specified
 * in the Profile's {@link #views} config.
 *
 * ## Class Specializations
 *
 * Because Profiles are specializations of an application, all of the models, views, controllers
 * and stores defined in a Profile are expected to be namespaced under the name of the Profile.
 * Here's an expanded form of the example above:
 *
 *     Ext.define('MyApp.profile.Phone', {
 *         extend: 'Ext.app.Profile',
 *
 *         views: ['Main'],
 *         controllers: ['Signup'],
 *         models: ['MyApp.model.Group'],
 *
 *         isActive: function() {
 *             return Ext.os.is('Phone');
 *         }
 *     });
 *
 * In this case, the Profile is going to load *app/view/phone/Main.js*,
 * *app/controller/phone/Signup.js* and *app/model/Group.js*. Notice that in each of the first
 * two cases the name of the profile ('phone' in this case) was injected into the class names.
 * In the third case we specified the full Model name (for Group) so the Profile name was not
 * injected.
 *
 * For a fuller understanding of the ideas behind Profiles and how best to use them in your app,
 * we suggest you read the [device profiles guide](/touch/2.4/core_concepts/device_profiles.html).
 * 
 */
Ext.define('Ext.app.Profile', {
    mixins: [
        'Ext.mixin.Observable'
    ],

    requires: [
        'Ext.app.Controller'
    ],

    /**
     * @property {Boolean}
     * `true` to identify an object as an instance of `Ext.app.Profile`
     */
    isProfile: true,

    /**
     * @cfg {String} [namespace]
     * The namespace that this Profile's classes can be found in. Defaults to the lowercase
     * Profile {@link #name}, for example a Profile called MyApp.profile.Phone will by default
     * have a 'phone' namespace, which means that this Profile's additional models, stores, views
     * and controllers will be loaded from the MyApp.model.phone.*, MyApp.store.phone.*,
     * MyApp.view.phone.* and MyApp.controller.phone.* namespaces respectively.
     * @accessor
     */

    /**
     * @cfg {String} [name]
     * The name of this Profile. Defaults to the last section of the class name (e.g. a profile
     * called MyApp.profile.Phone will default the name to 'Phone').
     * @accessor
     */

    config: {
        /**
         * @cfg {String} mainView
         */
        mainView: {
            $value: null,
            lazy: true
        },

        /**
         * @cfg {Ext.app.Application} application
         * The {@link Ext.app.Application Application} instance to which this Profile is
         * bound. This is set automatically.
         * @accessor
         * @readonly
         */
        application: null,

        // @cmd-auto-dependency {aliasPrefix: "controller.", profile: true, blame: "all"}
        /**
         * @cfg {String[]} controllers
         * Any additional {@link Ext.app.Controller Controllers} to load for this profile.
         * Note that each item here will be prepended with the Profile namespace when loaded.
         *
         * Example usage:
         *
         *     controllers: [
         *         'Users',
         *         'MyApp.controller.Products'
         *     ]
         *
         * This will load *MyApp.controller.tablet.Users* and *MyApp.controller.Products*.
         * @accessor
         */
        controllers: [],

        // @cmd-auto-dependency {aliasPrefix : "model.", profile: true, blame: "all"}
        /**
         * @cfg {String[]} models
         * Any additional {@link Ext.app.Application#models Models} to load for this profile.
         * Note that each item here will be prepended with the Profile namespace when loaded.
         *
         * Example usage:
         *
         *     models: [
         *         'Group',
         *         'MyApp.model.User'
         *     ]
         *
         * This will load *MyApp.model.tablet.Group* and *MyApp.model.User*.
         * @accessor
         */
        models: [],

        /* eslint-disable-next-line max-len */
        // @cmd-auto-dependency { aliasPrefix: "view.", profile: true, isKeyedObject:true, blame: "all" }
        /**
         * @cfg {Object/String[]} views
         * This config allows the active profile to define a set of `xtypes` and map them
         * to desired classes and default configurations. Normally an `xtype` is statically
         * declared by a {@link Ext.Component component} in its class definition. This
         * mechanism allows the active profile to control a set of these types.
         *
         * Example:
         *
         *      views: {
         *          // The "main" xtype maps to MyApp.view.tablet.Main
         *          //
         *          main: 'MyApp.view.tablet.Main',
         *
         *          // The "inbox" xtype maps to a subclass of MyApp.view.Inbox (created
         *          // by this mechanism) that sets the "mode" config to "compact".
         *          //
         *          inbox: {
         *              xclass: 'MyApp.view.Inbox',
         *              mode: 'compact'
         *          }
         *      }
         *
         * Note that class names used in this form must be full class names, unlike the
         * historical usage of `views`. Further, these views cannot be accessed using the
         * `getView` method but rather via their assigned `xtype`.
         *
         * The historical usage of this config is enabled when an array is passed. In this
         * case, these are simply additional {@link Ext.app.Application#views views} to
         * load for this profile. Note that each item here will be prepended with the
         * Profile namespace when loaded.
         *
         * Example usage:
         *
         *     views: [
         *         'Main',
         *         'MyApp.view.Login'
         *     ]
         *
         * This will load *MyApp.view.tablet.Main* and *MyApp.view.Login*. While supported,
         * this usage is discouraged in favor of `xtype` mapping.
         * @accessor
         */
        views: [],

        // @cmd-auto-dependency {aliasPrefix: "store.", profile: true, blame: "all"}
        /**
         * @cfg {String[]} stores
         * Any additional {@link Ext.app.Application#stores Stores} to load for this profile.
         * Note that each item here will be prepended with the Profile namespace when loaded.
         *
         * Example usage:
         *
         *     stores: [
         *         'Users',
         *         'MyApp.store.Products'
         *     ]
         *
         * This will load *MyApp.store.tablet.Users* and *MyApp.store.Products*.
         * @accessor
         */
        stores: []
    },

    /**
     * Creates a new Profile instance
     */
    constructor: function(config) {
        this.initConfig(config);

        this.mixins.observable.constructor.apply(this, arguments);
    },

    /**
     * Determines whether or not this Profile is active on the device isActive is executed on.
     * Should return true if this profile is meant to be active on this device, false otherwise.
     * Each Profile should implement this function (the default implementation just returns false).
     * @return {Boolean} True if this Profile should be activated on the device it is running on,
     * false otherwise
     */
    isActive: function() {
        return false;
    },

    /**
     * This method is called once the profile is determined to be the active profile. This
     * initialization is performed before controllers are initialized and therefore also
     * before launch.
     * @protected
     * @since 6.0.1
     */
    init: function() {
        var views = this.getViews(),
            xtype;

        if (views && !(views instanceof Array)) {
            for (xtype in views) {
                Ext.ClassManager.setXType(views[xtype], xtype);
            }
        }
    },

    /**
     * @method
     * The launch function is called by the {@link Ext.app.Application Application} if this
     * Profile's {@link #isActive} function returned true. This is typically the best place to run
     * any profile-specific app launch code. Example usage:
     *
     *     launch: function() {
     *         Ext.create('MyApp.view.tablet.Main');
     *     }
     */
    launch: Ext.emptyFn,

    onClassExtended: function(cls, data, hooks) {
        var onBeforeClassCreated = hooks.onBeforeCreated;

        hooks.onBeforeCreated = function(cls, data) {
            var Controller = Ext.app.Controller,
                className = cls.$className,
                requires = [],
                proto = cls.prototype,
                views = data.views,
                name, namespace;

            // Process name and namespace configs here since we need to use the namespace
            // in the dependency calculation
            name = data.name;

            if (name) {
                delete data.name;
            }
            else {
                name = className.split('.');
                name = name[name.length - 1];
            }

            cls._name = name;

            cls._namespace = name = (data.namespace || name).toLowerCase();
            delete data.namespace;

            namespace = Controller.resolveNamespace(cls, data);

            Controller.processDependencies(proto, requires, namespace, 'model', data.models, name);
            Controller.processDependencies(proto, requires, namespace, 'store', data.stores, name);
            Controller.processDependencies(proto, requires, namespace, 'controller',
                                           data.controllers, name);

            if (views) {
                if (views instanceof Array) {
                    Controller.processDependencies(proto, requires, namespace, 'view', views, name);
                }
                else {
                    Ext.app.Profile.processViews(className, views, requires);
                }
            }

            Ext.require(requires, Ext.Function.pass(onBeforeClassCreated, arguments, this));
        };
    },

    getName: function() {
        // This used to be a Config but is now processed in onClassExtended so we provide
        // the getter for compat.
        return this.self._name;
    },

    getNamespace: function() {
        // This used to be a Config but is now processed in onClassExtended so we provide
        // the getter for compat.
        return this.self._namespace;
    },

    privates: {
        statics: {
            processViews: function(className, views, requires) {
                var body, cls, s, xtype;

                for (xtype in views) {
                    cls = views[xtype];

                    if (typeof cls !== 'string') {
                        s = cls.xclass;

                        //<debug>
                        if (!s) {
                            Ext.raise('Views must specify an xclass');
                        }
                        //</debug>

                        body = Ext.apply({
                            extend: s
                        }, cls);

                        delete body.xclass;

                        // Class names will be App.profile.Tablet$inbox for example
                        Ext.define(views[xtype] = className + '$' + xtype, body);
                        cls = s;
                    }

                    requires.push(cls);
                }
            }
        }
    }
});
