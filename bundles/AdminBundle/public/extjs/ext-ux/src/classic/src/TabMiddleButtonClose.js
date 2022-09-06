/**
 * Plugin for adding a close context menu to tabs. Note that the menu respects
 * the closable configuration on the tab. As such, commands like remove others
 * and remove all will not remove items that are not closable.
 */
Ext.define('Ext.ux.TabMiddleButtonClose', {
    extend: 'Ext.plugin.Abstract',

    alias: 'plugin.tabmiddlebuttonclose',

    mixins: {
        observable: 'Ext.util.Observable'
    },

    //public
    constructor: function (config) {
        this.callParent([config]);
        this.mixins.observable.constructor.call(this, config);
    },

    init : function(tabpanel){
        this.tabPanel = tabpanel;
        this.tabBar = tabpanel.down("tabbar");

        this.mon(this.tabPanel, {
            scope: this,
            afterlayout: this.onAfterLayout,
            single: true
        });
    },

    onAfterLayout: function() {
        this.mon(this.tabBar.el, {
            scope: this,
            mousedown: this.onMiddleClick,
            delegate: '.x-tab'
        });
    },

    /**
     * @private
     */
    onMiddleClick : function(event, target){
        event.preventDefault();

        if( target &&  event.browserEvent.button==1  ){
            var item = this.tabBar.getComponent(target.id);
            item.onCloseClick();
        }
    }
});
