/**
 * @private
 * Private Container class used by the {@link Ext.grid.RowEditor} to hold its buttons.
 */
Ext.define('Ext.grid.RowEditorButtons', {
    extend: 'Ext.container.Container',
    alias: 'widget.roweditorbuttons',

    frame: true,
    shrinkWrap: true,
    position: 'bottom',

    ariaRole: 'toolbar',

    constructor: function(config) {
        var me = this,
            rowEditor = config.rowEditor,
            cssPrefix = Ext.baseCSSPrefix,
            plugin = rowEditor.editingPlugin;

        config = Ext.apply({
            baseCls: cssPrefix + 'grid-row-editor-buttons',
            defaults: {
                xtype: 'button',
                ui: rowEditor.buttonUI,
                scope: plugin,
                flex: 1,
                minWidth: Ext.panel.Panel.prototype.minButtonWidth
            },
            items: [{
                cls: cssPrefix + 'row-editor-update-button',
                itemId: 'update',
                handler: plugin.completeEdit,
                text: rowEditor.saveBtnText,
                disabled: rowEditor.updateButtonDisabled,
                listeners: {
                    element: 'el',
                    keydown: me.onUpdateKeyDown,
                    scope: me
                }
            }, {
                cls: cssPrefix + 'row-editor-cancel-button',
                itemId: 'cancel',
                handler: plugin.cancelEdit,
                text: rowEditor.cancelBtnText,
                listeners: {
                    element: 'el',
                    keydown: me.onCancelKeyDown,
                    scope: me
                }
            }]
        }, config);

        me.callParent([config]);

        me.addClsWithUI(me.position);
    },

    // SHIFT+TAB off the update button loops back into the last field.
    onUpdateKeyDown: function(e) {
        if (e.shiftKey && e.getKey() === e.TAB) {
            e.stopEvent();
            // Must delay the focus, otherwise the imminent keyup will TAB off that field
            this.rowEditor.child(':focusable:not([isButton]):last').focus(false, true);
        }
    },

    // TAB off the cancel button loops back into the first field.
    onCancelKeyDown: function(e) {
        if (!e.shiftKey && e.getKey() === e.TAB) {
            e.stopEvent();
            // Must delay the focus, otherwise the imminent keyup will TAB off that field
            this.rowEditor.child(':focusable').focus(false, true);
        }
    },

    setButtonPosition: function(position) {
        var me = this,
            rowEditor = this.rowEditor,
            rowEditorHeight = rowEditor.getHeight(),
            rowEditorBody = rowEditor.body,
            bottom = '',
            top = '';

        me.removeClsWithUI(me.position);
        me.position = position;
        me.addClsWithUI(position);

        // we tried setting the top/bottom value in the stylesheet based on form field
        // height + row editor padding, but that approach does not work when there are
        // larger things inside the editor, e.g. textarea, so we have to measure
        // the row editor height and position the buttons accordingly (see EXTJSIV-9914).
        if (position === 'top') {
            bottom = (rowEditorHeight - rowEditorBody.getBorderWidth('t')) + 'px';
        }
        else {
            top = (rowEditorHeight - rowEditorBody.getBorderWidth('b')) + 'px';
        }

        me.el.setStyle({
            top: top,
            bottom: bottom
        });
    },

    privates: {
        getFramingInfoCls: function() {
            return this.baseCls + '-' + this.ui + '-' + this.position;
        },

        getFrameInfo: function() {
            var frameInfo = this.callParent();

            // Trick Renderable into rendering the top framing elements, even though they
            // are not needed in the default "bottom" position.  This allows us to flip the
            // buttons into "top" position without re-rendering.
            frameInfo.top = true;

            return frameInfo;
        }
    }
});
