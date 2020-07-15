/**
 * A layout that arranges items vertically down a 
 * {@link Ext.container.Container Container}. This layout optionally divides available 
 * vertical space between child {@link Ext.container.Container#cfg-items items} 
 * containing a numeric {@link Ext.Component#cfg-flex flex} configuration.
 *
 * This layout may also be used to set the widths of child items by configuring it with 
 * the {@link #align} option.  The vertical position of the child items may be set using 
 * the {@link #pack} config.
 *
 *     @example
 *     Ext.create('Ext.Panel', {
 *         width: 500,
 *         height: 400,
 *         title: "VBoxLayout Panel",
 *         layout: {
 *             type: 'vbox',
 *         },
 *         renderTo: document.body,
 *         items: [{
 *             xtype: 'panel',
 *             title: 'Inner Panel One',
 *             width: 250,
 *             flex: 2
 *         },
 *         {
 *             xtype: 'panel',
 *             title: 'Inner Panel Two',
 *             width: 250,
 *             flex: 4
 *         },
 *         {
 *             xtype: 'panel',
 *             title: 'Inner Panel Three',
 *             width: '50%',
 *             flex: 4
 *         }]
 *     });
 * 
 * The following example may be used to view the outcomes when combining the `align` and 
 * `pack` configs:
 * 
 *     @example
 *     Ext.create({
 *         xtype: 'panel',
 *         renderTo: Ext.getBody(),
 *         height: 400,
 *         width: 520,
 *         defaultListenerScope: true,
 *         layout: 'vbox',
 *         defaultType: 'button',
 *         items: [{
 *             text: 'One'
 *         }, {
 *             text: 'Two'
 *         }, {
 *             text: 'Three'
 *         }],
 *         
 *         dockedItems: [{
 *             xtype: 'toolbar',
 *             dock: 'top',
 *             items: [{
 *                 xtype: 'buttongroup',
 *                 title: 'align',
 *                 layout: 'fit',
 *                 items: [{
 *                     xtype: 'segmentedbutton',
 *                     margin: 10,
 *                     allowDepress: true,
 *                     defaults: {
 *                         configType: 'align'  // custom config used in this example
 *                     },
 *                     items: [{
 *                         text: 'begin'
 *                     }, {
 *                         text: 'middle'
 *                     }, {
 *                         text: 'end'
 *                     }, {
 *                         text: 'stretch'
 *                     }, {
 *                         text: 'stretchmax'
 *                     }],
 *                     listeners: {
 *                         toggle: 'onToggle'
 *                     }
 *                 }]
 *             }, '->', {
 *                 xtype: 'buttongroup',
 *                 title: 'pack',
 *                 layout: 'fit',
 *                 items: [{
 *                     xtype: 'segmentedbutton',
 *                     margin: 10,
 *                     allowDepress: true,
 *                     defaults: {
 *                         configType: 'pack'  // custom config used in this example
 *                     },
 *                     items: [{
 *                         text: 'start'
 *                     }, {
 *                         text: 'center'
 *                     }, {
 *                         text: 'end'
 *                     }],
 *                     listeners: {
 *                         toggle: 'onToggle'
 *                     }
 *                 }]
 *             }]
 *         }],
 *         
 *         onToggle: function (group, button, isPressed) {
 *             var cfg = {};
 *             
 *             cfg[button.configType] = isPressed ? button.getText() : null;
 *             this.setLayout(cfg);
 *         }
 *     });
 */
Ext.define('Ext.layout.container.VBox', {
    extend: 'Ext.layout.container.Box',

    alias: 'layout.vbox',

    alternateClassName: 'Ext.layout.VBoxLayout',

    type: 'vbox',

    vertical: true
});
