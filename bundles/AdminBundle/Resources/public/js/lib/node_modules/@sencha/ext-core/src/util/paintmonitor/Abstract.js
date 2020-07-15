/**
 * @private
 */
Ext.define('Ext.util.paintmonitor.Abstract', {

    config: {
        element: null,

        callback: Ext.emptyFn,

        scope: null,

        args: []
    },

    eventName: '',

    monitorClass: '',

    constructor: function(config) {
        this.onElementPainted = this.onElementPainted.bind(this);

        this.initConfig(config);
    },

    bindListeners: function(bind) {
        // eslint-disable-next-line max-len
        this.monitorElement[bind ? 'addEventListener' : 'removeEventListener'](this.eventName, this.onElementPainted, true);
    },

    applyElement: function(element) {
        if (element) {
            return Ext.get(element);
        }
    },

    updateElement: function(element) {
        this.monitorElement = Ext.Element.create({
            classList: [Ext.baseCSSPrefix + 'paint-monitor', this.monitorClass]
        }, true);

        element.appendChild(this.monitorElement, true);
        element.addCls(Ext.baseCSSPrefix + 'paint-monitored');
        this.bindListeners(true);
    },

    onElementPainted: function() {},

    destroy: function() {
        var me = this,
            monitorElement = me.monitorElement,
            parentNode = monitorElement.parentNode,
            element = me.getElement();

        me.bindListeners(false);
        delete me.monitorElement;

        if (element && !element.destroyed) {
            element.removeCls(Ext.baseCSSPrefix + 'paint-monitored');
            delete me._element;
        }

        if (parentNode) {
            parentNode.removeChild(monitorElement);
        }

        me.callParent();
    }
});
