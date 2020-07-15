/**
 * The counterpart to `Ext.Responsive` for `Ext.Widget`. This override is required by
 * `Ext.Responsive` but will only be included if `Ext.Widget` is also used.
 *
 * @since 6.7.0
 * @private
 */
Ext.define('Ext.ResponsiveWidget', {
    override: 'Ext.Widget',

    mixins: [
        'Ext.mixin.Responsive'
    ]
});
