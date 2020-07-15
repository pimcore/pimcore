/**
 * This plugin can be applied to any `Component` (although almost always to a `Container`)
 * to make it fill the browser viewport. This plugin is used internally by the more familiar
 * `Ext.container.Viewport` class.
 *
 * The `Viewport` container is commonly used but it can be an issue if you need to fill the
 * viewport with a container that derives from another class (e.g., `Ext.tab.Panel`). Prior
 * to this plugin, you would have to do this:
 *
 *      Ext.create('Ext.container.Viewport', {
 *          layout: 'fit', // full the viewport with the tab panel
 *
 *          items: [{
 *              xtype: 'tabpanel',
 *              items: [{
 *                  ...
 *              }]
 *          }]
 *      });
 *
 * With this plugin you can create the `tabpanel` as the viewport:
 *
 *      Ext.create('Ext.tab.Panel', {
 *          plugins: {
 *              viewport: true
 *          },
 *
 *          items: [{
 *              ...
 *          }]
 *      });
 *
 * More importantly perhaps is that as a plugin, the view class can be reused in other
 * contexts such as the content of a `{@link Ext.window.Window window}`.
 *
 * The Viewport renders itself to the document body, and automatically sizes itself to the size of
 * the browser viewport and manages window resizing. There may only be one Viewport created
 * in a page.
 *
 * ## Responsive Design
 *
 * This plugin enables {@link Ext.mixin.Responsive#responsiveConfig} for the components
 * by requiring `Ext.Responsive`.
 *
 * @since 5.0.0
 */
Ext.define('Ext.plugin.Viewport', {
    extend: 'Ext.plugin.Abstract',

    requires: [
        'Ext.Responsive'
    ],

    alias: 'plugin.viewport',

    setCmp: function(cmp) {
        this.cmp = cmp;

        if (cmp && !cmp.isViewport) {
            this.decorate(cmp);

            if (cmp.renderConfigs) {
                cmp.flushRenderConfigs();
            }

            cmp.setupViewport();
        }
    },

    destroy: function() {
        var el = this.cmp.el;

        this.callParent();

        // Remove the injected overrides so that the bodyEl singleton
        // can be reused by subsequent code (eg, unit tests)
        if (el) {
            delete el.setHeight;
            delete el.setWidth;
        }
    },

    statics: {
        decorate: function(target) {
            Ext.applyIf(target.prototype || target, {
                ariaRole: 'application',

                viewportCls: Ext.baseCSSPrefix + 'viewport'
            });

            Ext.override(target, {
                isViewport: true,

                preserveElOnDestroy: true,

                initComponent: function() {
                    this.callParent();
                    this.setupViewport();
                },

                // Because we don't stamp the size until onRender, our size model
                // won't return correctly. As we're always going to be configured,
                // just return the value here
                getSizeModel: function() {
                    var configured = Ext.layout.SizeModel.configured;

                    return configured.pairsByHeightOrdinal[configured.ordinal];
                },

                handleViewportResize: function() {
                    var me = this,
                        Element = Ext.dom.Element,
                        width = Element.getViewportWidth(),
                        height = Element.getViewportHeight();

                    if (width !== me.width || height !== me.height) {
                        me.setSize(width, height);
                    }
                },

                setupViewport: function() {
                    var me = this,
                        el = document.body;

                    if (!me.$responsiveId) {
                        me.setResponsiveConfig(true);
                        Ext.mixin.Responsive.register(me);
                        me.setupResponsiveContext();
                    }

                    // Here in the (perhaps unlikely) case that the body dom el doesn't yet have
                    // an id, we want to give it the same id as the viewport component so getCmp
                    // lookups will be able to find the owner component.
                    //
                    // Note that nothing says that components that use configured elements
                    // have to have matching ids (they probably won't), but this is at least making
                    // the attempt so that getCmp *may* be able to find the component.
                    // However, in these cases, it's really better to use Component#from
                    // to find the owner component.
                    if (!el.id) {
                        el.id = me.id;
                    }

                    // In addition, stamp on the data-componentid so lookups using Component's
                    // from will work.
                    el.setAttribute('data-componentid', me.id);

                    if (!me.ariaStaticRoles[me.ariaRole]) {
                        el.setAttribute('role', me.ariaRole);
                    }

                    el = me.el = Ext.getBody();

                    Ext.fly(document.documentElement).addCls(me.viewportCls);
                    el.setHeight = el.setWidth = Ext.emptyFn;
                    el.dom.scroll = 'no';
                    me.allowDomMove = false;
                    me.renderTo = el;

                    if (Ext.supports.Touch) {
                        me.addMeta('apple-mobile-web-app-capable', 'yes');
                    }

                    // Get the DOM disruption over with before the Viewport renders
                    // and begins a layout
                    Ext.scrollbar.size();

                    // Clear any dimensions, we will size later on in onRender
                    me.width = me.height = undefined;

                    // ... but take the measurements now because doing that in onRender
                    // will cause a costly reflow which we just forced with getScrollbarSize()
                    me.initialViewportHeight = Ext.Element.getViewportHeight();
                    me.initialViewportWidth = Ext.Element.getViewportWidth();
                },

                afterLayout: function(layout) {
                    if (Ext.supports.Touch) {
                        document.body.scrollTop = 0;
                    }

                    this.callParent([layout]);
                },

                onRender: function() {
                    var me = this,
                        overflowEl = me.getOverflowEl(), // eslint-disable-line no-unused-vars
                        body = Ext.getBody();

                    me.callParent(arguments);

                    // The global scroller is our scroller.
                    // We must provide a non-scrolling one if we are not configured to scroll,
                    // otherwise the deferred ready listener in Scroller will create
                    // one with scroll: true
                    Ext.setViewportScroller(me.getScrollable() || {
                        x: false,
                        y: false,
                        element: body
                    });

                    // If we are not scrolling the body, the body has to be overflow:hidden
                    if (me.getOverflowEl() !== body) {
                        body.setStyle('overflow', 'hidden');
                    }

                    // Important to start life as the proper size (to avoid extra layouts)
                    // But after render so that the size is not stamped into the body,
                    // although measurement has to take place before render to avoid
                    // causing a reflow.
                    me.width = me.initialViewportWidth;
                    me.height = me.initialViewportHeight;

                    me.initialViewportWidth = me.initialViewportHeight = null;
                },

                initInheritedState: function(inheritedState, inheritedStateInner) {
                    var me = this,
                        root = Ext.rootInheritedState;

                    if (inheritedState !== root) {
                        // We need to go at this again but with the rootInheritedState object. Let
                        // any derived class poke on the proper object!
                        me.initInheritedState(
                            me.inheritedState = root,
                            me.inheritedStateInner = Ext.Object.chain(root)
                        );
                    }
                    else {
                        me.callParent([inheritedState, inheritedStateInner]);
                    }
                },

                doDestroy: function() {
                    var me = this,
                        root = Ext.rootInheritedState,
                        scroller = me.scrollable,
                        key;

                    // We set the global body scroller aboce in onRender.
                    // Just relinquish it here and allow it to live on.
                    if (scroller) {
                        // Return the body scroller to default; X and Y scrolling
                        scroller.setConfig({
                            x: true,
                            y: true
                        });
                        me.scrollable = null;
                    }

                    // Clear any properties from the inheritedState so we don't pollute the
                    // global namespace. If we have a rtl flag set, leave it alone because it's
                    // likely we didn't write it
                    for (key in root) {
                        if (key !== 'rtl') {
                            delete root[key];
                        }
                    }

                    delete me.el.setHeight;
                    delete me.el.setWidth;
                    me.removeUIFromElement();
                    me.el.removeCls(me.baseCls);
                    Ext.fly(document.body.parentNode).removeCls(me.viewportCls);

                    me.callParent();
                },

                addMeta: function(name, content) {
                    var meta = document.createElement('meta');

                    meta.setAttribute('name', name);
                    meta.setAttribute('content', content);
                    Ext.getHead().appendChild(meta, true);
                },

                privates: {
                    // override here to prevent an extraneous warning
                    applyTargetCls: function(targetCls) {
                        var el = this.el;

                        if (el === this.getTargetEl()) {
                            this.el.addCls(targetCls);
                        }
                        else {
                            this.callParent([targetCls]);
                        }
                    },

                    // Override here to prevent tabIndex set/reset on the body
                    disableTabbing: function() {
                        var el = this.el;

                        if (el) {
                            el.saveTabbableState({
                                skipSelf: true
                            });
                        }
                    },

                    enableTabbing: function() {
                        var el = this.el;

                        if (el) {
                            el.restoreTabbableState({ skipSelf: true });
                        }
                    },

                    updateResponsiveState: function() {
                        // By providing this method we are in sync with the layout suspend/resume as
                        // well as other changes to configs that need to happen during this pulse of
                        // size change.

                        // Since we are not using the Viewport plugin beyond applying its methods on
                        // to our prototype, we need to be Responsive ourselves and call this here:
                        this.handleViewportResize();
                        this.callParent();
                    }

                }
            });
        }
    }
}, function(Viewport) {
    Viewport.prototype.decorate = Viewport.decorate;
});
