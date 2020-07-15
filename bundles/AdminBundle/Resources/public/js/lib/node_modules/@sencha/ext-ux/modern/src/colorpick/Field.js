/**
 * A field that can be clicked to bring up the color picker.
 * The selected color is configurable via {@link #value} and
 * The Format is configurable via {@link #format}.
 *
 *      @example
 *      Ext.create({
 *          xtype: 'colorfield',
 *          renderTo: Ext.getBody(),
 *
 *          value: '#993300',  // initial selected color
 *          format: 'hex6', // by default it's hex6
 *
 *          listeners : {
 *              change: function (field, color) {
 *                  console.log('New color: ' + color);
 *              }
 *          }
 *      });
 */
Ext.define('Ext.ux.colorpick.Field', {
    extend: 'Ext.field.Picker',
    xtype: 'colorfield',

    mixins: [
        'Ext.ux.colorpick.Selection'
    ],

    requires: [
        'Ext.window.Window',
        'Ext.ux.colorpick.Selector',
        'Ext.ux.colorpick.ColorUtils'
    ],

    editable: false,
    focusable: true,
    matchFieldWidth: false, // picker is usually wider than field

    // "Color Swatch" shown on the left of the field
    html: [
        '<div class="' + Ext.baseCSSPrefix + 'colorpicker-field-swatch">' +
            '<div class="' + Ext.baseCSSPrefix + 'colorpicker-field-swatch-inner"></div>' +
        '</div>'
    ],

    cls: Ext.baseCSSPrefix + 'colorpicker-field',

    config: {
        /**
         * @cfg {Object} popup
         * This object configures the popup window and colorselector component displayed
         * when this button is clicked. Applications should not need to configure this.
         * @private
         */
        popup: {
            lazy: true,
            $value: {
                xtype: 'window',
                closeAction: 'hide',
                modal: Ext.platformTags.phone ? true : false,
                referenceHolder: true,
                width: Ext.platformTags.phone ? '100%' : 'auto',
                layout: Ext.platformTags.phone ? 'hbox' : 'vbox',
                header: false,
                resizable: true,
                scrollable: true,
                items: {
                    xtype: 'colorselector',
                    reference: 'selector',
                    flex: '1 1 auto',
                    showPreviousColor: true,
                    showOkCancelButtons: true
                }
            }
        }
    },

    /**
     * @event change
     * Fires when a color is selected.
     * @param {Ext.ux.colorpick.Field} this
     * @param {String} color The value of the selected color as per specified {@link #format}.
     * @param {String} previousColor The previous color value.
     */

    afterRender: function() {
        this.callParent();
        this.updateValue(this.value);
    },

    // override as required by parent pickerfield
    createFloatedPicker: function() {
        var me = this,
            popup = me.getPopup(),
            picker;

        // the window will actually be shown and will house the picker
        me.colorPickerWindow = popup = Ext.create(popup);
        picker = me.colorPicker = popup.lookupReference('selector');

        picker.setColor(me.getColor());
        picker.setHexReadOnly(!me.editable);

        picker.on({
            ok: 'onColorPickerOK',
            cancel: 'onColorPickerCancel',
            close: 'onColorPickerCancel',
            scope: me
        });

        me.colorPicker.ownerCmp = me;

        return me.colorPickerWindow;
    },

    // override as required by parent pickerfield for mobile devices
    createEdgePicker: function() {
        var me = this,
            popup = me.getPopup(),
            picker;

        // the window will actually be shown and will house the picker
        me.colorPickerWindow = popup = Ext.create(popup);
        picker = me.colorPicker = popup.lookupReference('selector');

        me.pickerType = 'floated';
        picker.setColor(me.getColor());

        picker.on({
            ok: 'onColorPickerOK',
            cancel: 'onColorPickerCancel',
            close: 'onColorPickerCancel',
            scope: me
        });

        me.colorPicker.ownerCmp = me;

        return me.colorPickerWindow;
    },

    collapse: function() {
        var picker = this.getPicker();

        if (this.expanded) {
            picker.hide();
        }
    },

    showPicker: function() {
        var me = this,
            alignTarget = me[me.alignTarget],
            picker = me.getPicker(),
            color = this.getColor();

        // Setting up previous selected color
        if (this.colorPicker) {
            this.colorPicker.setColor(this.getColor());
            this.colorPicker.setPreviousColor(color);
        }

        // TODO: what if virtual keyboard is present
        if (me.getMatchFieldWidth()) {
            picker.setWidth(alignTarget.getWidth());
        }

        if (Ext.platformTags.phone) {
            picker.show();
        }
        else {
            picker.showBy(alignTarget, me.getFloatedPickerAlign(), {
                minHeight: 100
            });
        }

        // Collapse on touch outside this component tree.
        // Because touch platforms do not focus document.body on touch
        // so no focusleave would occur to trigger a collapse.
        me.touchListeners = Ext.getDoc().on({
            // Do not translate on non-touch platforms.
            // mousedown will blur the field.
            translate: false,
            touchstart: me.collapseIf,
            scope: me,
            delegated: false,
            destroyable: true
        });
    },

    onFocusLeave: function(e) {
        if (e.type !== 'focusenter') {
            this.callParent(arguments);
        }
    },

    // When the Ok button is clicked on color picker, preserve the previous value
    onColorPickerOK: function(colorPicker) {
        this.setColor(colorPicker.getColor());
        this.collapse();
    },

    onColorPickerCancel: function() {
        this.collapse();
    },

    onExpandTap: function() {
        var color = this.getColor();

        if (this.colorPicker) {
            this.colorPicker.setPreviousColor(color);
        }

        this.callParent(arguments);
    },

    // Expects value formatted as per "format" config
    setValue: function(color) {
        var me = this,
            c;

        if (Ext.ux.colorpick.ColorUtils.isValid(color)) {
            c = me.mixins.colorselection.applyValue.call(me, color);
            me.callParent([c]);
        }
    },

    // Sets this.format and color picker's setFormat()
    updateFormat: function(format) {
        var cp = this.colorPicker;

        if (cp) {
            cp.setFormat(format);
        }
    },

    updateValue: function(color) {
        var me = this,
            swatchEl = this.element.down('.x-colorpicker-field-swatch-inner'),
            c;

        // If the "value" is changed, update "color" as well. Since these are always
        // tracking each other, we guard against the case where we are being updated
        // *because* "color" is being set.
        if (!me.syncing) {
            me.syncing = true;
            me.setColor(color);
            me.syncing = false;
        }

        c = me.getColor();

        Ext.ux.colorpick.ColorUtils.setBackground(swatchEl, c);

        if (me.colorPicker) {
            me.colorPicker.setColor(c);
        }

        me.inputElement.dom.value = me.getValue();
    },

    validator: function(val) {
        if (!Ext.ux.colorpick.ColorUtils.isValid(val)) {
            return this.invalidText;
        }

        return true;
    },

    updateColor: function(color) {
        var me = this,
            cp = me.colorPicker,
            swatchEl = this.element.down('.x-colorpicker-field-swatch-inner');

        me.mixins.colorselection.updateColor.call(me, color);

        Ext.ux.colorpick.ColorUtils.setBackground(swatchEl, color);

        if (cp) {
            cp.setColor(color);
        }
    }
});
