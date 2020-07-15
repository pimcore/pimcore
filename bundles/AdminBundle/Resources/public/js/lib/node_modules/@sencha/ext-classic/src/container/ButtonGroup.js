/**
 * Provides a container for arranging a group of related Buttons in a tabular manner.
 *
 *     @example
 *     Ext.create('Ext.panel.Panel', {
 *         title: 'Panel with ButtonGroup',
 *         width: 300,
 *         height:200,
 *         renderTo: document.body,
 *         bodyPadding: 10,
 *         html: 'HTML Panel Content',
 *         tbar: [{
 *             xtype: 'buttongroup',
 *             columns: 3,
 *             title: 'Clipboard',
 *             items: [{
 *                 text: 'Paste',
 *                 scale: 'large',
 *                 rowspan: 3,
 *                 iconCls: 'add',
 *                 iconAlign: 'top',
 *                 cls: 'btn-as-arrow'
 *             },{
 *                 xtype:'splitbutton',
 *                 text: 'Menu Button',
 *                 scale: 'large',
 *                 rowspan: 3,
 *                 iconCls: 'add',
 *                 iconAlign: 'top',
 *                 arrowAlign:'bottom',
 *                 menu: [{ text: 'Menu Item 1' }]
 *             },{
 *                 xtype:'splitbutton', text: 'Cut', iconCls: 'add16',
 *                 menu: [{ text: 'Cut Menu Item' }]
 *             },{
 *                 text: 'Copy', iconCls: 'add16'
 *             },{
 *                 text: 'Format', iconCls: 'add16'
 *             }]
 *         }]
 *     });
 *
 */
Ext.define('Ext.container.ButtonGroup', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.buttongroup',
    alternateClassName: 'Ext.ButtonGroup',

    requires: [
        'Ext.layout.container.Table'
    ],

    /**
     * @cfg {Number} columns
     * The `columns` configuration property passed to the {@link #layout configured layout manager}.
     * See {@link Ext.layout.container.Table#columns}.
     */

    /**
     * @cfg baseCls
     * @inheritdoc
     */
    baseCls: Ext.baseCSSPrefix + 'btn-group',

    /**
     * @cfg layout
     * @inheritdoc
     */
    layout: {
        type: 'table'
    },

    /**
     * @cfg defaultType
     * @inheritdoc
     */
    defaultType: 'button',

    /**
     * @cfg frame
     * @inheritdoc
     */
    frame: true,

    /**
     * @cfg {String} defaultButtonUI
     * A default {@link Ext.Component#ui ui} to use for {@link Ext.button.Button Button} items
     */

    /**
     * @cfg frameHeader
     * @inheritdoc
     */
    frameHeader: false,

    /**
     * @cfg {String} titleAlign
     * The alignment of the title text within the available space between the icon and the tools.
     */
    titleAlign: 'center',

    noTitleCls: 'notitle',

    bodyAriaRole: 'toolbar',

    /**
     * @property focusableContainerEl
     * @inheritdoc
     */
    focusableContainerEl: 'body',

    /**
     * @cfg focusableContainer
     * @inheritdoc
     */
    focusableContainer: true,

    initComponent: function() {
        // Copy the component's columns config to the layout if specified
        var me = this,
            cols = me.columns;

        if (cols) {
            me.layout = Ext.apply({ columns: cols }, me.layout);
        }

        if (!me.title) {
            me.addClsWithUI(me.noTitleCls);
        }

        me.callParent();
    },

    /**
     * @private
     */
    onBeforeAdd: function(component) {
        if (component.isButton) {
            if (this.defaultButtonUI && component.ui === 'default' &&
                !component.hasOwnProperty('ui')) {
                component.ui = this.defaultButtonUI;
            }
            else {
                component.ui = component.ui + '-toolbar';
            }
        }

        this.callParent(arguments);
    },

    beforeRender: function() {
        var me = this,
            ariaAttr;

        me.callParent();

        // If header is off we need to set aria-label
        if (me.afterHeaderInit && !me.header && me.title) {
            ariaAttr = me.bodyAriaRenderAttributes || (me.bodyAriaRenderAttributes = {});
            ariaAttr['aria-label'] = me.title;
        }
    },

    updateHeader: function(force) {
        var me = this,
            bodyEl = me.body,
            header, ariaAttr;

        me.callParent([force]);

        header = me.header;

        if (header) {
            if (bodyEl) {
                bodyEl.dom.setAttribute('aria-labelledby', header.id + '-title-textEl');
                bodyEl.dom.removeAttribute('aria-label');
            }
            else {
                ariaAttr = me.bodyAriaRenderAttributes || (me.bodyAriaRenderAttributes = {});
                ariaAttr['aria-labelledby'] = header.id + '-title-textEl';
                delete ariaAttr['aria-label'];
            }
        }
        else if (me.title) {
            if (bodyEl) {
                bodyEl.dom.setAttribute('aria-label', me.title);
                bodyEl.dom.removeAttribute('aria-labelledby');
            }
            else {
                ariaAttr = me.bodyAriaRenderAttributes || (me.bodyAriaRenderAttributes = {});
                ariaAttr['aria-label'] = me.title;
                delete ariaAttr['aria-labelledby'];
            }
        }
    },

    privates: {
        applyDefaults: function(c) {
            if (!Ext.isString(c)) {
                c = this.callParent(arguments);
            }

            return c;
        }
    }

    /**
     * @cfg {Array} tools
     * @private
     */
    /**
     * @cfg {Boolean} collapsible
     * @private
     */
    /**
     * @cfg {Boolean} collapseMode
     * @private
     */
    /**
     * @cfg {Boolean} animCollapse
     * @private
     */
    /**
     * @cfg {Boolean} closable
     * @private
     */
});
