/**
 * @class Ext.draw.ContainerBase
 * @private
 */
Ext.define('Ext.draw.ContainerBase', {
    extend: 'Ext.Container',

    constructor: function(config) {
        this.callParent([config]);
        this.initAnimator();
    },

    onResize: function(width, height, oldWidth, oldHeight) {
        this.handleResize({
            width: width,
            height: height
        }, true);
    },

    addElementListener: function() {
        var el = this.element;

        el.on.apply(el, arguments);
    },

    removeElementListener: function() {
        var el = this.element;

        el.un.apply(el, arguments);
    },

    preview: function(image) {
        var item;

        image = image || this.getImage();

        if (image.type === 'svg-markup') {
            item = {
                xtype: 'container',
                html: image.data
            };
        }
        else {
            item = {
                xtype: 'image',
                mode: 'img',
                imageCls: '',
                cls: Ext.baseCSSPrefix + 'chart-preview',
                src: image.data
            };
        }

        Ext.Viewport.add({
            xtype: 'panel',
            layout: 'fit',
            modal: true,
            border: 1,
            shadow: true,
            width: '90%',
            height: '90%',
            hideOnMaskTap: true,
            centered: true,
            floated: true,
            scrollable: false,
            closable: true,
            // Use 'hide' so that hiding via close button/mask tap go through
            // the same code path
            closeAction: 'hide',
            items: [item],
            listeners: {
                hide: function() {
                    this.destroy();
                }
            }
        }).show();
    }
});
