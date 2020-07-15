/**
 * A layout that arranges items horizontally across a 
 * {@link Ext.container.Container Container}. This layout optionally divides available 
 * horizontal space between child {@link Ext.container.Container#cfg-items items} 
 * containing a numeric {@link Ext.Component#cfg-flex flex} configuration.
 * 
 * This layout may be used to set the heights or vertical position of child items 
 * by configuring it with the {@link #align} option.  The horizontal position of the 
 * child items may be set using the {@link #pack} config.
 * 
 *     @example
 *     Ext.create('Ext.Panel', {
 *         width: 500,
 *         height: 300,
 *         title: "HBoxLayout Panel",
 *         layout: {
 *             type: 'hbox',
 *             align: 'stretch'
 *         },
 *         renderTo: document.body,
 *         items: [{
 *             xtype: 'panel',
 *             title: 'Inner Panel One',
 *             flex: 2
 *         },{
 *             xtype: 'panel',
 *             title: 'Inner Panel Two',
 *             flex: 1
 *         },{
 *             xtype: 'panel',
 *             title: 'Inner Panel Three',
 *             flex: 1
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
 *         layout: 'hbox',
 *         defaultType: 'button',
 *         items: [{
 *             text: 'One'
 *         }, {
 *             text: 'Two'
 *         }, {
 *             text: 'Three'
 *         }],
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
 *     			   layout: 'fit',
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
Ext.define('Ext.layout.container.HBox', {
    extend: 'Ext.layout.container.Box',

    alias: 'layout.hbox',

    alternateClassName: 'Ext.layout.HBoxLayout',

    type: 'hbox',

    vertical: false
});
