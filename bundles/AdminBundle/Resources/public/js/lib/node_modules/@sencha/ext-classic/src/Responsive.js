/**
 * This is an override, not a class. To use `Ext.Responsive` you simply require it:
 *
 *      Ext.application({
 *          requires: [
 *              'Ext.Responsive'
 *          ],
 *
 *          // ...
 *      });
 *
 * Once required, this override mixes in {@link Ext.mixin.Responsive} into `Ext.Component`
 * and `Ext.Widget` so that these classes both gain the
 * {@link Ext.mixin.Responsive#cfg!responsiveConfig responsiveConfig} and
 * {@link Ext.mixin.Responsive#cfg!responsiveFormulas responsiveFormulas} configs.
 * @since 6.7.0
 */
Ext.define('Ext.Responsive', {
    override: 'Ext.Component',

    mixins: [
        'Ext.mixin.Responsive'
    ],

    requires: [
        // Also an override, so it will drop out if Ext.Widget isn't used
        'Ext.ResponsiveWidget'
    ]
});
