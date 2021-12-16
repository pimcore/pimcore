/**
 * @class Ext.ux.rating.Picker
 */
Ext.define('Ext.ux.overrides.rating.Picker', {
    override: 'Ext.ux.rating.Picker',

    //<debug>
    initConfig: function(config) {
        if (config && config.tooltip) {
            config.tip = config.tooltip;

            Ext.log.warn('[Ext.ux.rating.Picker] The "tooltip" config was replaced by "tip"');
        }

        this.callParent([ config ]);
    },
    //</debug>

    updateTooltipText: function(text) {
        var innerEl = this.innerEl,
            QuickTips = Ext.tip && Ext.tip.QuickTipManager,
            tip = QuickTips && QuickTips.tip,
            target;

        if (QuickTips) {
            innerEl.dom.setAttribute('data-qtip', text);
            this.trackerEl.dom.setAttribute('data-qtip', text);

            // If the QuickTipManager is active over our widget, we need to update
            // the tooltip text directly.
            target = tip && tip.activeTarget;
            target = target && target.el;

            if (target && innerEl.contains(target)) {
                tip.update(text);
            }
        }
    }
});
