/**
 * @private
 */
Ext.define('Ext.util.sizemonitor.Abstract', {

    mixins: ['Ext.mixin.Templatable'],

    requires: [
        'Ext.TaskQueue'
    ],

    config: {
        element: null,

        callback: Ext.emptyFn,

        scope: null,

        args: []
    },

    width: null,

    height: null,

    contentWidth: null,

    contentHeight: null,

    constructor: function(config) {
        var me = this;

        me.refresh = me.refresh.bind(me);

        me.info = {
            width: 0,
            height: 0,
            contentWidth: 0,
            contentHeight: 0,
            flag: 0
        };

        me.initElement();

        me.initConfig(config);

        me.bindListeners(true);
    },

    bindListeners: Ext.emptyFn,

    applyElement: function(element) {
        if (element) {
            return Ext.get(element);
        }
    },

    updateElement: function(element) {
        element.append(this.detectorsContainer, true);
        element.addCls(Ext.baseCSSPrefix + 'size-monitored');
    },

    applyArgs: function(args) {
        return args.concat([this.info]);
    },

    refreshMonitors: Ext.emptyFn,

    forceRefresh: function() {
        Ext.TaskQueue.requestRead('refresh', this);
    },

    getContentBounds: function() {
        return this.detectorsContainer.getBoundingClientRect();
    },

    getContentWidth: function() {
        return this.detectorsContainer.clientWidth;
    },

    getContentHeight: function() {
        return this.detectorsContainer.clientHeight;
    },

    refreshSize: function() {
        var element = this.getElement();

        if (!element || element.destroyed) {
            return false;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            size = element.measure(),
            width = size.width,
            height = size.height,
            contentWidth = me.getContentWidth(),
            contentHeight = me.getContentHeight(),
            currentContentWidth = me.contentWidth,
            currentContentHeight = me.contentHeight,
            info = me.info,
            resized = false,
            flag;

        me.width = width;
        me.height = height;
        me.contentWidth = contentWidth;
        me.contentHeight = contentHeight;

        flag = ((currentContentWidth !== contentWidth ? 1 : 0) +
                (currentContentHeight !== contentHeight ? 2 : 0));

        if (flag > 0) {
            info.width = width;
            info.height = height;
            info.contentWidth = contentWidth;
            info.contentHeight = contentHeight;
            info.flag = flag;

            resized = true;
            me.getCallback().apply(me.getScope(), me.getArgs());
        }

        return resized;
    },

    refresh: function() {
        if (this.destroying || this.destroyed) {
            return;
        }

        this.refreshSize();

        // We should always refresh the monitors regardless of whether or not refreshSize
        // resulted in a new size.  This avoids race conditions in situations such as
        // panel placeholder expand where we layout the panel in its expanded state momentarily
        // just so we can measure its animation destination, then immediately collapse it.
        // In such a scenario refreshSize() will be acting on the original size since it
        // is asynchronous, so it will not detect a size change, but we still need to
        // ensure that the monitoring elements are in sync, or else the next resize event
        // will not fire.
        Ext.TaskQueue.requestWrite('refreshMonitors', this);
    },

    destroy: function() {
        var me = this,
            element = me.getElement();

        me.bindListeners(false);

        if (element && !element.destroyed) {
            element.removeCls(Ext.baseCSSPrefix + 'size-monitored');
        }

        delete me._element;

        // This is a closure so Base destructor won't null it
        me.refresh = null;

        me.callParent();
    }
});
