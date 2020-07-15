/**
 * @protected
 * @class Ext.app.BaseController
 * Base class for Controllers.
 * 
 */
Ext.define('Ext.app.BaseController', {
    requires: [
        'Ext.app.EventBus',
        'Ext.app.domain.Global'
    ],

    uses: [
        'Ext.app.domain.Controller'
    ],

    mixins: [
        'Ext.mixin.Observable',
        'Ext.route.Mixin'
    ],

    isController: true,

    config: {
        /**
         * @cfg {String} id The id of this controller. You can use this id when dispatching.
         * 
         * For an example of dispatching, see the examples under the 
         * {@link Ext.app.Controller#cfg-listen listen} config.
         *
         * If an id is not explicitly set, it will default to the controller's full classname.
         * 
         * @accessor
         */
        id: undefined,

        /**
         * @cfg {Object} control
         * @accessor
         *
         * Adds listeners to components selected via {@link Ext.ComponentQuery}. Accepts an
         * object containing component paths mapped to a hash of listener functions.  
         * The function value may also be a string matching the name of a method on the 
         * controller.
         *
         * In the following example the `updateUser` function is mapped to to the `click`
         * event on a button component, which is a child of the `useredit` component.
         *
         *      Ext.define('MyApp.controller.Users', {
         *          extend: 'Ext.app.Controller',
         *
         *          control: {
         *              'useredit button[action=save]': {
         *                  click: 'updateUser'
         *              }
         *          },
         *
         *          updateUser: function(button) {
         *              console.log('clicked the Save button');
         *          }
         *      });
         *
         * The method you pass to the listener will automatically be resolved on the controller.
         * In this case, the `updateUser` method that will get executed on the `button` `click`
         * event will resolve to the `updateUser` method on the controller,
         *
         * See {@link Ext.ComponentQuery} for more information on component selectors.
         */

        control: null,

        /**
         * @cfg {Object} listen
         * @accessor
         *
         * Adds listeners to different event sources (also called "event domains"). The
         * primary event domain is that of components, but there are also other event domains:
         * {@link Ext.app.domain.Global Global} domain that intercepts events fired from
         * {@link Ext.GlobalEvents} Observable instance, 
         * {@link Ext.app.domain.Controller Controller} domain can be used to listen to events 
         * fired by other Controllers, {@link Ext.app.domain.Store Store} domain gives access to 
         * Store events, and {@link Ext.app.domain.Direct Direct} domain can be used with 
         * Ext Direct Providers to listen to their events.
         *
         * To listen to "bar" events fired by a controller with id="foo":
         *
         *      Ext.define('AM.controller.Users', {
         *          extend: 'Ext.app.Controller',
         *
         *          listen: {
         *              controller: {
         *                  '#foo': {
         *                      bar: 'onFooBar'
         *                  }
         *              }
         *          }
         *      });
         *
         * To listen to "bar" events fired by any controller, and "baz" events
         * fired by Store with storeId="baz":
         *
         *      Ext.define('AM.controller.Users', {
         *          extend: 'Ext.app.Controller',
         *
         *          listen: {
         *              controller: {
         *                  '*': {
         *                      bar: 'onAnyControllerBar'
         *                  }
         *              },
         *              store: {
         *                  '#baz': {
         *                      baz: 'onStoreBaz'
         *                  }
         *              }
         *          }
         *      });
         *
         * To listen to "idle" events fired by {@link Ext.GlobalEvents} when other event
         * processing is complete and Ext JS is about to return control to the browser:
         *
         *      Ext.define('AM.controller.Users', {
         *          extend: 'Ext.app.Controller',
         *
         *          listen: {
         *              global: {            // Global events are always fired
         *                  idle: 'onIdle'   // from the same object, so there
         *              }                    // are no selectors
         *          }
         *      });
         *
         * As this relates to components, the following example:
         *
         *      Ext.define('AM.controller.Users', {
         *          extend: 'Ext.app.Controller',
         *
         *          listen: {
         *              component: {
         *                  'useredit button[action=save]': {
         *                      click: 'updateUser'
         *                  }
         *              }
         *          }
         *      });
         *
         * Is equivalent to:
         *
         *      Ext.define('AM.controller.Users', {
         *          extend: 'Ext.app.Controller',
         *
         *          control: {
         *              'useredit button[action=save]': {
         *                  click: 'updateUser'
         *              }
         *          }
         *      });
         *
         * Of course, these can all be combined in a single call and used instead of
         * `control`, like so:
         *
         *      Ext.define('AM.controller.Users', {
         *          extend: 'Ext.app.Controller',
         *
         *          listen: {
         *              global: {
         *                  idle: 'onIdle'
         *              },
         *              controller: {
         *                  '*': {
         *                      foobar: 'onAnyFooBar'
         *                  },
         *                  '#foo': {
         *                      bar: 'onFooBar'
         *                  }
         *              },
         *              component: {
         *                  'useredit button[action=save]': {
         *                      click: 'updateUser'
         *                  }
         *              },
         *              store: {
         *                  '#qux': {
         *                      load: 'onQuxLoad'
         *                  }
         *              }
         *          }
         *      });
         */
        listen: null
    },

    /**
     * Creates new Controller.
     *
     * @param {Object} [config] Configuration object.
     */
    constructor: function(config) {
        var me = this;

        // In versions prior to 5.1, this constructor used to call the Ext.util.Observable
        // constructor (which applied the config properties directly to the instance)
        // AND it used to call initConfig as well.  Since the constructor of
        // Ext.mixin.Observable calls initConfig, but does not apply the properties to
        // the instance, we do that here for backward compatibility.
        Ext.apply(me, config);
        // The control and listen properties are also methods so we need to delete them
        // from the instance after applying the config object.
        delete me.control;
        delete me.listen;

        me.eventbus = Ext.app.EventBus;

        // need to have eventbus property set before we initialize the config
        me.mixins.observable.constructor.call(me, config);
    },

    updateId: function(id) {
        this.id = id;
    },

    applyListen: function(listen) {
        if (Ext.isObject(listen)) {
            listen = Ext.clone(listen);
        }

        return listen;
    },

    applyControl: function(control) {
        if (Ext.isObject(control)) {
            control = Ext.clone(control);
        }

        return control;
    },

    /**
     * @param {Object} control The object to pass to the {@link #method-control} method
     * @private
     */
    updateControl: function(control) {
        this.getId();

        if (control) {
            this.control(control);
        }
    },

    /**
     * @param {Object} listen The object to pass to the {@link #method-listen} method
     * @private
     */
    updateListen: function(listen) {
        this.getId();

        if (listen) {
            this.listen(listen);
        }
    },

    isActive: function() {
        return true;
    },

    /**
     * Adds listeners to components selected via {@link Ext.ComponentQuery}. Accepts an
     * object containing component paths mapped to a hash of listener functions.
     *
     * In the following example the `updateUser` function is mapped to to the `click`
     * event on a button component, which is a child of the `useredit` component.
     *
     *      Ext.define('AM.controller.Users', {
     *          init: function() {
     *              this.control({
     *                  'useredit button[action=save]': {
     *                      click: this.updateUser
     *                  }
     *              });
     *          },
     *          
     *          updateUser: function(button) {
     *              console.log('clicked the Save button');
     *          }
     *      });
     *
     * Or alternatively one call `control` with two arguments:
     *
     *      this.control('useredit button[action=save]', {
     *          click: this.updateUser
     *      });
     *
     * See {@link Ext.ComponentQuery} for more information on component selectors.
     *
     * @param {String/Object} selectors If a String, the second argument is used as the
     * listeners, otherwise an object of selectors -> listeners is assumed
     * @param {Object} [listeners] Config for listeners.
     * @param {Ext.app.BaseController} [controller] (private)
     */
    control: function(selectors, listeners, controller) {
        var me = this,
            ctrl = controller,
            obj;

        if (Ext.isString(selectors)) {
            obj = {};
            obj[selectors] = listeners;
        }
        else {
            obj = selectors;
            ctrl = listeners;
        }

        me.eventbus.control(obj, ctrl || me);
    },

    /**
     * Adds listeners to different event sources (also called "event domains"). The
     * primary event domain is that of components, but there are also other event domains:
     * {@link Ext.app.domain.Global Global} domain that intercepts events fired from
     * {@link Ext.GlobalEvents} Observable instance, {@link Ext.app.domain.Controller Controller}
     * domain can be used to listen to events fired by other Controllers,
     * {@link Ext.app.domain.Store Store} domain gives access to Store events, and
     * {@link Ext.app.domain.Direct Direct} domain can be used with Ext Direct Providers
     * to listen to their events.
     * 
     * To listen to "bar" events fired by a controller with id="foo":
     *
     *      Ext.define('AM.controller.Users', {
     *          init: function() {
     *              this.listen({
     *                  controller: {
     *                      '#foo': {
     *                         bar: this.onFooBar
     *                      }
     *                  }
     *              });
     *          },
     *          ...
     *      });
     * 
     * To listen to "bar" events fired by any controller, and "baz" events
     * fired by Store with storeId="baz":
     *
     *      Ext.define('AM.controller.Users', {
     *          init: function() {
     *              this.listen({
     *                  controller: {
     *                      '*': {
     *                         bar: this.onAnyControllerBar
     *                      }
     *                  },
     *                  store: {
     *                      '#baz': {
     *                          baz: this.onStoreBaz
     *                      }
     *                  }
     *              });
     *          },
     *          ...
     *      });
     *
     * To listen to "idle" events fired by {@link Ext.GlobalEvents} when other event
     * processing is complete and Ext JS is about to return control to the browser:
     *
     *      Ext.define('AM.controller.Users', {
     *          init: function() {
     *              this.listen({
     *                  global: {               // Global events are always fired
     *                      idle: this.onIdle   // from the same object, so there
     *                  }                       // are no selectors
     *              });
     *          }
     *      });
     * 
     * As this relates to components, the following example:
     *
     *      Ext.define('AM.controller.Users', {
     *          init: function() {
     *              this.listen({
     *                  component: {
     *                      'useredit button[action=save]': {
     *                         click: this.updateUser
     *                      }
     *                  }
     *              });
     *          },
     *          ...
     *      });
     * 
     * Is equivalent to:
     *
     *      Ext.define('AM.controller.Users', {
     *          init: function() {
     *              this.control({
     *                  'useredit button[action=save]': {
     *                     click: this.updateUser
     *                  }
     *              });
     *          },
     *          ...
     *      });
     *
     * Of course, these can all be combined in a single call and used instead of
     * `control`, like so:
     *
     *      Ext.define('AM.controller.Users', {
     *          init: function() {
     *              this.listen({
     *                  global: {
     *                      idle: this.onIdle
     *                  },
     *                  controller: {
     *                      '*': {
     *                         foobar: this.onAnyFooBar
     *                      },
     *                      '#foo': {
     *                         bar: this.onFooBar
     *                      }
     *                  },
     *                  component: {
     *                      'useredit button[action=save]': {
     *                         click: this.updateUser
     *                      }
     *                  },
     *                  store: {
     *                      '#qux': {
     *                          load: this.onQuxLoad
     *                      }
     *                  }
     *              });
     *          },
     *          ...
     *      });
     *
     * @param {Object} to Config object containing domains, selectors and listeners.
     * @param {Ext.app.Controller} [controller] The controller to add the listeners to. Defaults
     * to the current controller.
     */
    listen: function(to, controller) {
        this.eventbus.listen(to, controller || this);
    },

    destroy: function() {
        var me = this,
            bus = me.eventbus;

        if (bus) {
            bus.unlisten(me);
            me.eventbus = null;
        }

        me.callParent();
    }
});
