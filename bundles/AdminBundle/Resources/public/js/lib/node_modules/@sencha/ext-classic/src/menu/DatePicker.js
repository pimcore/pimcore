/**
 * A menu containing an Ext.picker.Date Component.
 *
 * Notes:
 *
 * - Although not listed here, the **constructor** for this class accepts all of the
 *   configuration options of **{@link Ext.picker.Date}**.
 * - If subclassing DateMenu, any configuration options for the DatePicker must be applied
 *   to the **initialConfig** property of the DateMenu. Applying {@link Ext.picker.Date Date Picker}
 *   configuration settings to **this** will **not** affect the Date Picker's configuration.
 *
 * Example:
 *
 *     @example
 *     var dateMenu = Ext.create('Ext.menu.DatePicker', {
 *         handler: function(dp, date){
 *             Ext.Msg.alert('Date Selected', 'You selected ' + Ext.Date.format(date, 'M j, Y'));
 *         }
 *     });
 *
 *     Ext.create('Ext.menu.Menu', {
 *         items: [{
 *             text: 'Choose a date',
 *             menu: dateMenu
 *         },{
 *             iconCls: 'add16',
 *             text: 'Icon item'
 *         },{
 *             text: 'Regular item'
 *         }]
 *     }).showAt([5, 5]);
 */
Ext.define('Ext.menu.DatePicker', {
    extend: 'Ext.menu.Menu',

    alias: 'widget.datemenu',

    requires: [
        'Ext.picker.Date'
    ],

    ariaRole: 'dialog',

    /**
     * @cfg {String} ariaLabel
     * The ARIA label for the Date Picker menu.
     * @locale
     */
    ariaLabel: 'Date picker',

    /**
     * @cfg {Boolean} hideOnClick
     * False to continue showing the menu after a date is selected.
     */
    hideOnClick: true,

    /**
     * @cfg {String} pickerId
     * An id to assign to the underlying date picker.
     */
    pickerId: null,

    /**
     * @cfg {Object} [pickerCfg] Date picker configuration. This config
     * takes priority over {@link #pickerId}.
     */

    /**
     * @cfg {Number} maxHeight
     * @private
     */

    /**
     * @property {Ext.picker.Date} picker
     * The {@link Ext.picker.Date} instance for this DateMenu
     */

    // DatePicker menu is a special case; Date picker does all key handling
    // except the Esc key which is also handled unlike the ordinary menu
    focusableContainer: false,

    initComponent: function() {
        var me = this,
            cfg, pickerConfig;

        if (me.pickerCfg) {
            pickerConfig = Ext.apply({
                cls: Ext.baseCSSPrefix + 'menu-date-item',
                margin: 0,
                border: false,
                xtype: 'datepicker'
            }, me.pickerCfg);
        }
        else {
            // Need to keep this insanity for backwards compat :(
            cfg = Ext.apply({}, me.initialConfig);

            // Ensure we clear any listeners so they aren't duplicated
            delete cfg.listeners;

            pickerConfig = Ext.applyIf({
                cls: Ext.baseCSSPrefix + 'menu-date-item',
                margin: 0,
                border: false,
                xtype: 'datepicker'
            }, cfg);
        }

        if (me.pickerId != null && pickerConfig.id == null) {
            pickerConfig.id = me.pickerId;
        }

        // This is a Menu and it will have an ownerCmp pointing to its owning MenuItem.
        // This MUST not be propagated down into the picker. The picker's getRefOwner
        // must follow the ownerCt and find this Menu.
        delete pickerConfig.ownerCmp;

        Ext.apply(me, {
            showSeparator: false,
            plain: true,
            // remove the body padding from the datepicker menu item so it looks like 3.3
            bodyPadding: 0,
            items: [pickerConfig]
        });

        me.callParent();

        me.picker = me.down('datepicker');

        /**
         * @event select
         * @inheritdoc Ext.picker.Date#select
         */
        me.relayEvents(me.picker, ['select']);

        if (me.hideOnClick) {
            me.on('select', me.hidePickerOnSelect, me);
        }
    },

    onEscapeKey: function(e) {
        var me = this;

        // Unlike the other menus, DatePicker menu should not close completely on Esc key.
        // This is because ordinary menu items will allow using Left arrow key to return
        // to the parent menu; however in the Date picker left arrow is used to navigate
        // in the calendar. So we use Esc key to return to the parent menu instead.
        if (me.floating && me.ownerCmp && me.ownerCmp.focus) {
            me.ownerCmp.focus();
            me.hide();
        }
    },

    hidePickerOnSelect: function() {
        Ext.menu.Manager.hideAll();
    }
});
