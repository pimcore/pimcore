/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.helpers.colorpicker = {
    initOverrides: function () {
        Ext.define('pimcore.colorpick.Field', {
            extend: 'Ext.ux.colorpick.Field',

            constructor: function (config) {
                if (typeof config.isNull !== "undefined") {
                    this.isNull = config.isNull;
                }
                this.callParent([config]);
            },

            setIsNull: function (isNull) {
                var me = this;
                me.isNull = isNull;
            },

            getIsNull: function () {
                return this.isNull;
            },

            setValue: function (color) {
                var me = this;

                if (color != null) {
                    color = me.applyValue(color);
                }

                var c = color;

                if (c != null) {
                    me.callParent([c]);
                    me.updateValue(c);
                }
            },

            render: function (container, position) {
                this.callParent(container, position);
            },

            onColorPickerOK: function (colorPicker) {
                this.isNull = false;
                this.callParent([colorPicker]);
                this.updateValue(null, true);

            },

            updateValue: function (color, fromEvent) {
                var me = this,
                    c;

                if (!fromEvent) {
                    if (!me.syncing) {
                        me.syncing = true;
                        me.setColor(color);
                        me.syncing = false;
                    }
                }

                c = me.getColor();

                var inputEl = me.getEl() ? me.getEl().down('input') : null;

                if (inputEl) {
                    if (me.isNull) {
                        inputEl.hide();
                    } else {
                        inputEl.show();
                    }
                }

                if (me.swatchEl) {
                    var parent = me.swatchEl.parent();
                    parent.setVisible(!me.isNull);
                }
                if (!me.isNull) {
                    Ext.ux.colorpick.ColorUtils.setBackground(me.swatchEl, c);
                }

                if (me.colorPicker) {
                    me.colorPicker.setColor(c);
                }
            }
        });


        /**
         * see https://github.com/pimcore/pimcore/issues/2465 and https://github.com/pimcore/pimcore/issues/3384
         */
        Ext.override(Ext.ux.colorpick.ColorUtils, {
            hex2rgb: function (hex) {
                hex = hex.replace(/^#/, '');

                var parts = hex.match(/[a-f\d]{2}/ig);

                if (parts === null || parts.length !== 3) {
                    return null;
                }

                return {
                    r: parseInt(parts[0], 16),
                    g: parseInt(parts[1], 16),
                    b: parseInt(parts[2], 16)
                };
            }
        });


        /**
         * see https://github.com/pimcore/pimcore/issues/2465 and https://github.com/pimcore/pimcore/issues/3384
         */
        Ext.override(Ext.ux.colorpick.Selector, {
                constructor: function (config) {
                    var me = this,
                        childViewModel = Ext.Factory.viewModel('colorpick-selectormodel');

                    // Since this component needs to present its value as a thing to which users can
                    // bind, we create an internal VM for our purposes.
                    me.childViewModel = childViewModel;
                    me.items = [
                        me.getMapAndHexRGBFields(childViewModel),
                        me.getSliderAndHField(childViewModel),
                        me.getSliderAndSField(childViewModel),
                        me.getSliderAndVField(childViewModel),
                        me.getSliderAndAField(childViewModel),
                        me.getPreviewAndButtons(childViewModel, config)
                    ];

                    // Make the HEX field editable (see ext/ux/colorpick/Selector.js line 206)
                    // This is really smelly, but it seems like overkill to override the whole getMapAndHexRGBFields method
                    me.items[0].items[1].items[0].readOnly = false;


                    me.childViewModel.bind('{selectedColor}', function (color) {
                        me.setColor(color);
                    });

                    me.callSuper(arguments);
                }
            }
        );

        /**
         * see https://github.com/pimcore/pimcore/issues/2465 and https://github.com/pimcore/pimcore/issues/3384
         */
        Ext.override(Ext.ux.colorpick.SelectorModel, {

            changeRGB: function (rgb) {
                if (rgb) {
                    Ext.applyIf(rgb, this.data.selectedColor);

                    var hsv = Ext.ux.colorpick.ColorUtils.rgb2hsv(rgb.r, rgb.g, rgb.b);

                    rgb.h = hsv.h;
                    rgb.s = hsv.s;
                    rgb.v = hsv.v;

                    this.set('selectedColor', rgb);
                }
            }
        });
    }
}


;