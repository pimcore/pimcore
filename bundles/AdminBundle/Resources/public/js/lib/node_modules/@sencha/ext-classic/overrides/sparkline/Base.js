/**
 * @class Ext.sparkline.Base
 */
Ext.define('Ext.override.sparkline.Base', {
    override: 'Ext.sparkline.Base',

    statics: {
        constructTip: function() {
            // eslint-disable-next-line dot-notation
            return new Ext.tip['ToolTip']({
                id: 'sparklines-tooltip',
                showDelay: 0,
                dismissDelay: 0,
                hideDelay: 400
            });
        }
    },

    onMouseMove: function(e) {
        this.getSharedTooltip().triggerEvent = e;
        this.callParent([e]);
    },

    onMouseLeave: function(e) {
        this.callParent([e]);
        this.getSharedTooltip().target = null;
    },

    privates: {
        hideTip: function() {
            var tip = this.getSharedTooltip();

            tip.target = null;
            tip.hide();
        },

        showTip: function() {
            var tip = this.getSharedTooltip();

            tip.target = this.el;
            tip.onTargetOver(tip.triggerEvent);
        }
    }
}, function(Cls) {
    // If we are on a VML platform (IE8 - TODO: remove this when that retires)...
    if (!Ext.supports.Canvas) {
        Cls.prototype.element = {
            tag: 'span',
            reference: 'element',
            listeners: {
                mouseenter: 'onMouseEnter',
                mouseleave: 'onMouseLeave',
                mousemove: 'onMouseMove'
            },
            style: {
                display: 'inline-block',
                position: 'relative',
                overflow: 'hidden',
                margin: '0px',
                padding: '0px',
                verticalAlign: 'top',
                cursor: 'default'
            },
            children: [{
                tag: 'svml:group',
                reference: 'groupEl',
                coordorigin: '0 0',
                coordsize: '0 0',
                style: 'position:absolute;width:0;height:0;pointer-events:none'
            }]
        };
    }
});
