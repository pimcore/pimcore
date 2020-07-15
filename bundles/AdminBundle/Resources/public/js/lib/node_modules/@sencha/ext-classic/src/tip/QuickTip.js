/**
 * A specialized tooltip class for tooltips that can be specified in markup and automatically
 * managed by the global {@link Ext.tip.QuickTipManager} instance. See the QuickTipManager
 * documentation for additional usage details and examples.
 *
 *      @example     
 *      Ext.tip.QuickTipManager.init(); // Instantiate the QuickTipManager 
 *
 *      Ext.create('Ext.Button', {
 *
 *          renderTo: Ext.getBody(),
 *          text: 'My Button',
 *          listeners: {
 *
 *              afterrender: function(me) {
 *
 *                  // Register the new tip with an element's ID
 *                  Ext.tip.QuickTipManager.register({
 *                      target: me.getId(), // Target button's ID
 *                      title : 'My Tooltip',  // QuickTip Header
 *                      text  : 'My Button has a QuickTip' // Tip content  
 *                  });
 *
 *              },
 *              destroy: function(me) {
 *                  Ext.tip.QuickTipManager.unregister(me.getId());
 *              }
 *          }
 *      });
 *
 */
Ext.define('Ext.tip.QuickTip', {
    extend: 'Ext.tip.ToolTip',
    alias: 'widget.quicktip',
    alternateClassName: 'Ext.QuickTip',

    /**
     * @cfg {String/HTMLElement/Ext.dom.Element} target
     * The target HTMLElement, {@link Ext.dom.Element} or id to associate with this Quicktip.
     *
     * Defaults to the document.
     */

    /**
     * @cfg {Boolean} interceptTitles
     * `true` to automatically use the element's DOM title value if available.
     */
    interceptTitles: false,

    /**
     * @cfg {String/Ext.panel.Title} title
     * The title text to be used to display in the Tip header.  May be a string 
     * (including HTML tags) or an {@link Ext.panel.Title} config object.
     */
    title: '&#160;',

    /**
     * @private
     */
    tagConfig: {
        namespace: 'data-',
        attribute: 'qtip',
        width: 'qwidth',
        target: 'target',
        title: 'qtitle',
        hide: 'hide',
        cls: 'qclass',
        align: 'qalign',
        anchor: 'anchor',
        showDelay: 'qshowDelay',
        hideAction: 'hideAction',
        anchorTarget: 'anchorTarget'
    },

    isQuickTip: true,

    /**
     * @cfg shrinkWrapDock
     * @inheritdoc
     */
    shrinkWrapDock: true,

    initComponent: function() {
        var me = this;

        // delegate selector is a function which detects presence
        // of attributes which provide QuickTip text.
        me.delegate = me.delegate.bind(me);

        me.target = me.target || Ext.getDoc();
        me.targets = me.targets || {};

        me.header = me.header || {};
        me.header.focusableContainer = false;

        me.callParent();
    },

    setTagConfig: function(cfg) {
        this.tagConfig = Ext.apply({}, cfg);

        // Let attr get recomputed
        delete this.tagConfig.attr;
    },

    /**
     * @cfg text
     * @inheritdoc Ext.tip.ToolTip#cfg-html
     */
    text: null,

    /**
     * @cfg html
     * @hide
     * -- hidden for Ext.tip.QuickTip - see #cfg-text
     */

    /**
     * Configures a new quick tip instance and assigns it to a target element.
     *
     * For example usage, see the {@link Ext.tip.QuickTipManager} class header.
     *
     * @param {Object} config The config object with the following properties:
     * @param config.target (required) The target HTMLElement, {@link Ext.dom.Element} or 
     * id to associate with this Quicktip.  See {@link Ext.tip.QuickTip#target}.
     * @param config.text Tip body content.  See {@link Ext.tip.QuickTip#text}.
     * @param config.title Tip header.  See {@link Ext.tip.QuickTip#title}.
     * @param config.autoHide False to prevent the tip from automatically hiding on 
     * mouseleave.  See {@link Ext.tip.QuickTip#autoHide}.
     * @param config.cls An optional extra CSS class that will be added to the tip.  See 
     * {@link Ext.tip.QuickTip#cls}.
     * @param config.dismissDelay Delay in milliseconds before the tooltip automatically 
     * hides (overrides singleton value).  See {@link Ext.tip.QuickTip#dismissDelay}.
     * @param config.width Tip width in pixels.  See {@link Ext.tip.QuickTip#width}.
     */
    register: function(config) {
        var configs = Ext.isArray(config) ? config : arguments,
            i = 0,
            len = configs.length,
            target, j, targetLen;

        for (; i < len; i++) {
            config = configs[i];
            target = config.target;

            if (target) {
                if (Ext.isArray(target)) {
                    for (j = 0, targetLen = target.length; j < targetLen; j++) {
                        this.targets[Ext.id(target[j])] = config;
                    }
                }
                else {
                    this.targets[Ext.id(target)] = config;
                }
            }
        }
    },

    /**
     * Removes this quick tip from its element and destroys it.
     * @param {String/HTMLElement/Ext.dom.Element} el The element from which the quick tip
     * is to be removed or ID of the element.
     */
    unregister: function(el) {
        delete this.targets[Ext.id(el)];
    },

    /**
     * Hides a visible tip or cancels an impending show for a particular element.
     * @param {String/HTMLElement/Ext.dom.Element} el The element that is the target of
     * the tip or ID of the element.
     */
    cancelShow: function(el) {
        var me = this,
            currentTarget = me.currentTarget;

        el = Ext.getDom(el);

        if (me.isVisible()) {
            if (currentTarget.dom === el) {
                me.hide();
            }
        }
        else if (currentTarget.dom === el) {
            me.clearTimer('show');
        }
    },

    delegate: function(target) {
        var me = this,
            cfg = me.tagConfig,
            attr = cfg.attr || (cfg.attr = cfg.namespace + cfg.attribute),
            text;

        // We can now only activate on elements which have the required attributes
        text = target.getAttribute(attr) || (me.interceptTitles && target.title);

        return !!text;
    },

    /**
     * @private
     * Reads the tip text from the target.
     */
    getTipText: function(target) {
        var titleText = target.title,
            cfg = this.tagConfig,
            attr = cfg.attr || (cfg.attr = cfg.namespace + cfg.attribute);

        if (this.interceptTitles && titleText) {
            target.setAttribute(attr, titleText);
            target.removeAttribute('title');

            return titleText;
        }
        else {
            return target.getAttribute(attr);
        }
    },

    onTargetOver: function(event) {
        var me = this,
            currentTarget = me.currentTarget,
            target = event.target,
            targets, registeredTarget, key;

        // If the over target is not an HTMLElement, or is the <html> or the <body>, then return
        if (!target || target.nodeType !== 1 || target === document.documentElement ||
            target === document.body) {
            return;
        }

        me.pointerEvent = event;
        targets = me.targets;

        // Loop through registered targets seeing if we are over one.
        for (key in targets) {
            if (targets.hasOwnProperty(key)) {
                registeredTarget = targets[key];

                target = Ext.getDom(registeredTarget.target);

                // If we moved over a registered target from outside of it, activate it.
                if (target && Ext.fly(target).contains(event.target) &&
                    !Ext.fly(target).contains(event.relatedTarget)) {
                    currentTarget.attach(target);
                    me.activeTarget = registeredTarget;
                    registeredTarget.el = currentTarget;
                    me.anchor = registeredTarget.anchor;
                    me.activateTarget();

                    return;
                }
            }
        }

        // We found no registered targets, now continue as a regular ToolTip, and
        // see if we are over any of our delegated targets.
        me.callParent([event]);
    },

    handleTargetOver: function(target, event) {
        var me = this,
            currentTarget = me.currentTarget,
            cfg = me.tagConfig,
            ns = cfg.namespace,
            tipText = me.getTipText(target, event),
            autoHide;

        if (tipText) {

            autoHide = currentTarget.getAttribute(ns + cfg.hide);

            me.activeTarget = {
                el: currentTarget,
                text: tipText,
                width: +currentTarget.getAttribute(ns + cfg.width) || null,
                autoHide: autoHide !== "user" && autoHide !== 'false',
                title: currentTarget.getAttribute(ns + cfg.title),
                cls: currentTarget.getAttribute(ns + cfg.cls),
                align: currentTarget.getAttribute(ns + cfg.align),
                showDelay: currentTarget.getAttribute(ns + cfg.showDelay),
                hideAction: currentTarget.getAttribute(ns + cfg.hideAction),
                alignTarget: currentTarget.getAttribute(ns + cfg.anchorTarget)
            };

            // If we were not configured with an anchor,
            // allow it to be set by the target's properties
            if (!me.initialConfig.hasOwnProperty('anchor')) {
                me.anchor = currentTarget.getAttribute(ns + cfg.anchor);
            }

            // If we are anchored, and not configured with an anchorTarget,
            // anchor to the target element, or whatever its 'data-anchortarget' points to
            if (me.anchor && !me.initialConfig.hasOwnProperty('anchorTarget')) {
                me.alignTarget = me.activeTarget.alignTarget || target;
            }

            me.activateTarget();
        }
    },

    activateTarget: function() {
        var me = this,
            activeTarget = me.activeTarget,
            delay = activeTarget.showDelay,
            hideAction = activeTarget.hideAction;

        // If moved from target to target rapidly, the hide delay will not
        // have fired, so just update content and alignment.
        if (me.isVisible()) {
            me.updateContent();
            me.realignToTarget();
        }
        else {
            if (activeTarget.showDelay) {
                delay = me.showDelay;
                me.showDelay = parseInt(activeTarget.showDelay, 10);
            }

            me.delayShow();

            if (activeTarget.showDelay) {
                me.showDelay = delay;
            }

            if (!(hideAction = activeTarget.hideAction)) {
                delete me.hideAction;
            }
            else {
                me.hideAction = hideAction;
            }
        }
    },

    getAnchorAlign: function() {
        var active = this.activeTarget;

        return (active && active.align) || this.callParent();
    },

    getAlignRegion: function() {
        var me = this,
            activeTarget = me.activeTarget,
            currentTargetDom = me.currentTarget.dom,
            result;

        // If we are anchored, and not configured with an anchorTarget,
        // align to the target element, or whatever its 'data-anchortarget' points to
        if (activeTarget && activeTarget.alignTarget && me.anchor &&
            !me.initialConfig.hasOwnProperty('anchorTarget')) {
            me.currentTarget.attach(Ext.getDom(activeTarget.alignTarget));
        }

        // Anchor to the target when have an align config or an anchor config
        me.anchorToTarget = !!(activeTarget.align || me.anchor);
        result = me.callParent();

        // Return currentTarget to correctness for pointer event processing
        me.currentTarget.attach(currentTargetDom);

        return result;
    },

    /**
     * @private
     */
    handleTargetOut: function(e) {
        var me = this,
            active = me.activeTarget,
            autoHide = me.autoHide,
            hideDelay = me.hideDelay;

        if (active && autoHide !== false) {
            me.autoHide = true;

            if (active.hideDelay) {
                me.hideDelay = parseInt(active.hideDelay, 10);
            }

            me.callParent([e]);
            me.autoHide = autoHide;
            me.hideDelay = hideDelay;
        }
    },

    targetTextEmpty: function() {
        var me = this,
            target = me.activeTarget,
            cfg = me.tagConfig,
            el, text;

        if (target) {
            el = target.el;

            if (el) {
                text = el.getAttribute(cfg.namespace + cfg.attribute);

                // Note that the quicktip could also have been registered with the QuickTipManager.
                // If this was the case, then we don't want to veto showing it.
                // Simply do a lookup in the registered targets collection.
                if (!text && !me.targets[Ext.id(target.el.dom)]) {
                    return true;
                }
            }
        }

        return false;
    },

    show: function() {
        var me = this,
            fromDelay = me.fromDelayShow;

        // We're coming from a delayed show, so check whether
        // the attribute has been removed before we show it
        if (fromDelay && me.targetTextEmpty()) {
            me.activeTarget = null;
            me.currentTarget.detach();

            return;
        }

        me.callParent(arguments);
    },

    /**
     * @method beforeShow
     * @inheritdoc Ext.tip.Tip#method-beforeShow
     */
    beforeShow: function() {
        this.updateContent();
        this.callParent(arguments);
    },

    /**
     * @private
     */
    updateContent: function() {
        var me = this,
            target = me.activeTarget,
            header = me.header,
            dismiss, cls;

        if (target) {
            me.suspendLayouts();

            if (target.title) {
                me.setTitle(target.title);
                header.show();
            }
            else if (header) {
                header.hide();
            }

            me.update(target.text);
            me.autoHide = target.autoHide;
            dismiss = target.dismissDelay;

            me.dismissDelay = Ext.isNumber(dismiss) ? dismiss : me.dismissDelay;

            cls = me.lastCls;

            if (cls) {
                me.removeCls(cls);
                delete me.lastCls;
            }

            cls = target.cls;

            if (cls) {
                me.addCls(cls);
                me.lastCls = cls;
            }

            me.setWidth(target.width);

            me.align = target.align;
            me.resumeLayouts(true);
        }
    },

    /**
     * @method hide
     * @inheritdoc
     */
    hide: function() {
        this.activeTarget = null;
        this.callParent();
    }
});
