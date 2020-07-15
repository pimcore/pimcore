/**
 * @class Ext.app.Application
 */

Ext.define('Ext.overrides.app.Application', {
    override: 'Ext.app.Application',
    uses: [
        'Ext.tip.QuickTipManager'
    ],

    // @cmd-auto-dependency {aliasPrefix: "view.", mvc: true, requires: ["Ext.plugin.Viewport"]}
    /**
     * @cfg {Boolean/String} [autoCreateViewport=false]
     * @deprecated 5.1 Instead use {@link #mainView}
     * @member Ext.app.Application
     */
    autoCreateViewport: false,

    config: {
        /**
         * @cfg {Boolean} enableQuickTips
         * @deprecated 6.2.0 Use {@link #quickTips}.
         */
        enableQuickTips: null
    },

    /**
     * @cfg {Boolean} quickTips
     * True to automatically set up Ext.tip.QuickTip support.
     *
     * @since 6.2.0
     */
    quickTips: true,

    updateEnableQuickTips: function(enableQuickTips) {
        this.setQuickTips(enableQuickTips);
    },

    applyMainView: function(mainView) {
        var view, proto, config, protoPlugins, configPlugins;

        if (typeof mainView === 'string') {
            view = this.getView(mainView);
            config = {};
        }
        else {
            config = mainView;
            view = Ext.ClassManager.getByConfig(mainView);
        }

        proto = view.prototype;

        if (!proto.isViewport) {
            // Need to copy over any plugins defined on the prototype and on the config.
            protoPlugins = Ext.Array.from(proto.plugins);
            configPlugins = Ext.Array.from(config.plugins);
            config = Ext.apply({}, config);
            config.plugins = ['viewport'].concat(protoPlugins, configPlugins);
        }

        return view.create(config);
    },

    getDependencies: function(cls, data, requires) {
        var Controller = Ext.app.Controller,
            proto = cls.prototype,
            namespace = data.$namespace,
            viewportClass = data.autoCreateViewport;

        if (viewportClass) {
            //<debug>
            if (!namespace) {
                Ext.raise("[Ext.app.Application] Can't resolve namespace for " +
                    data.$className + ", did you forget to specify 'name' property?");
            }
            //</debug>

            if (viewportClass === true) {
                viewportClass = 'Viewport';
            }
            else {
                requires.push('Ext.plugin.Viewport');
            }

            Controller.processDependencies(proto, requires, namespace, 'view', viewportClass);
        }
    },

    onBeforeLaunch: function() {
        var me = this,
            autoCreateViewport = me.autoCreateViewport;

        if (me.getQuickTips()) {
            me.initQuickTips();
        }

        if (autoCreateViewport) {
            me.initViewport();
        }

        this.callParent(arguments);
    },

    getViewportName: function() {
        var name = null,
            autoCreate = this.autoCreateViewport;

        if (autoCreate) {
            name = (autoCreate === true) ? 'Viewport' : autoCreate;
        }

        return name;
    },

    initViewport: function() {
        this.setMainView(this.getViewportName());
    },

    initQuickTips: function() {
        Ext.tip.QuickTipManager.init();
    }
});
