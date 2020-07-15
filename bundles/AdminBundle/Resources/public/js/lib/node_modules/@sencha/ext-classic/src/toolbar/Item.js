/**
 * The base class that other non-interacting Toolbar Item classes should extend in order to
 * get some basic common toolbar item functionality.
 */
Ext.define('Ext.toolbar.Item', {
    extend: 'Ext.Component',
    alias: 'widget.tbitem',
    alternateClassName: 'Ext.Toolbar.Item',

    // Toolbar required here because we'll try to decorate it's alternateClassName
    // with this class' alternate name
    requires: ['Ext.toolbar.Toolbar'],

    /**
     * @cfg {String} overflowText
     * Text to be used for the menu if the item is overflowed.
     */

    enable: Ext.emptyFn,
    disable: Ext.emptyFn,
    focus: Ext.emptyFn
});
