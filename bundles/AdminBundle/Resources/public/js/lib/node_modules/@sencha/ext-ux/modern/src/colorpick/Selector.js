/**
 * Sencha Pro Services presents xtype "colorselector".
 * API has been kept as close to the regular colorpicker as possible. The Selector can be
 * rendered to any container.
 *
 * The defaul selected color is configurable via {@link #value} config
 * and The Format is configurable via {@link #format}. Usually used in
 * forms via {@link Ext.ux.colorpick.Button} or {@link Ext.ux.colorpick.Field}.
 *
 * Typically you will need to listen for the change event to be notified when the user
 * chooses a color. Alternatively, you can bind to the "value" config
 *
 *     @example
 *     Ext.create('Ext.ux.colorpick.Selector', {
 *         value     : '993300',  // initial selected color
 *         format   : 'hex6', // by default it's hex6
 *         renderTo  : Ext.getBody(),
 *
 *         listeners: {
 *             change: function (colorselector, color) {
 *                 console.log('New color: ' + color);
 *             }
 *         }
 *     });
 */
Ext.define('Ext.ux.colorpick.Selector', {
    extend: 'Ext.panel.Panel',
    xtype: 'colorselector',

    mixins: [
        'Ext.ux.colorpick.Selection'
    ],

    controller: 'colorpick-selectorcontroller',

    requires: [
        'Ext.field.Text',
        'Ext.field.Number',
        'Ext.ux.colorpick.ColorMap',
        'Ext.ux.colorpick.SelectorModel',
        'Ext.ux.colorpick.SelectorController',
        'Ext.ux.colorpick.ColorPreview',
        'Ext.ux.colorpick.Slider',
        'Ext.ux.colorpick.SliderAlpha',
        'Ext.ux.colorpick.SliderSaturation',
        'Ext.ux.colorpick.SliderValue',
        'Ext.ux.colorpick.SliderHue'
    ],

    config: {
        hexReadOnly: false
    },

    /**
     * default width and height gives 255x255 color map in Crisp
     */
    width: Ext.platformTags.phone ? 'auto' : 580,
    height: 337,

    cls: Ext.baseCSSPrefix + 'colorpicker',
    padding: 10,

    layout: {
        type: Ext.platformTags.phone ? 'vbox' : 'hbox',
        align: 'stretch'
    },

    defaultBindProperty: 'value',
    twoWayBindable: [
        'value',
        'hidden'
    ],

    /**
     * @cfg fieldWidth {Number} Width of the text fields on the container (excluding HEX);
     * since the width of the slider containers is the same as the text field under it
     * (it's the same vbox column), changing this value will also affect the spacing between
     * the sliders.
     */
    fieldWidth: 50,

    /**
     * @cfg fieldPad {Number} padding between the sliders and HEX/R/G/B fields.
     */
    fieldPad: 5,

    /**
     * @cfg {Boolean} [showPreviousColor]
     * Whether "previous color" region (in upper right, below the selected color preview) should 
     * be shown;
     * these are relied upon by the {@link Ext.ux.colorpick.Button} and the 
     * {@link Ext.ux.colorpick.Field}.
     */
    showPreviousColor: false,

    /**
     * @cfg {String} [okButtonText]
     * Text value for "Ok" button;
     * these are relied upon by the {@link Ext.ux.colorpick.Button} and the 
     * {@link Ext.ux.colorpick.Field}.
     */
    okButtonText: 'OK',

    /**
     * @cfg {String} [cancelButtonText]
     * Text value for "Cancel" button;
     * these are relied upon by the {@link Ext.ux.colorpick.Button} and the 
     * {@link Ext.ux.colorpick.Field}.
     */
    cancelButtonText: 'Cancel',

    /**
     * @cfg {Boolean} [showOkCancelButtons]
     * Whether Ok and Cancel buttons (in upper right, below the selected color preview) should 
     * be shown;
     * these are relied upon by the {@link Ext.ux.colorpick.Button} and the 
     * {@link Ext.ux.colorpick.Field}.
     */
    showOkCancelButtons: false,

    /**
     * @event change
     * Fires when a color is selected. Simply dragging sliders around will trigger this.
     * @param {Ext.ux.colorpick.Selector} this
     * @param {String} color The value of the selected color as per specified {@link #format}.
     * @param {String} previousColor The previous color value.
     */

    /**
     * @event ok
     * Fires when OK button is clicked (see {@link #showOkCancelButtons}).
     * @param {Ext.ux.colorpick.Selector} this
     * @param {String} color The value of the selected color as per specified {@link #format}.
     */

    /**
     * @event cancel
     * Fires when Cancel button is clicked (see {@link #showOkCancelButtons}).
     * @param {Ext.ux.colorpick.Selector} this
     */

    listeners: {
        resize: 'onResize',
        show: 'onResize'
    },

    initConfig: function(config) {
        var me = this,
            childViewModel = Ext.Factory.viewModel('colorpick-selectormodel');

        // Since this component needs to present its value as a thing to which users can
        // bind, we create an internal VM for our purposes.
        me.childViewModel = childViewModel;

        if (Ext.platformTags.phone && !(Ext.Viewport.getOrientation() === "landscape")) {
            me.fieldWidth = 35;
        }

        if (Ext.platformTags.phone) {
            config.items = [
                me.getPreviewForMobile(childViewModel, config),
                {
                    xtype: 'container',
                    padding: '4px 0 0 0',
                    layout: {
                        type: 'hbox',
                        align: 'stretch'
                    },
                    flex: 1,
                    items: [
                        me.getMapAndHexRGBFields(childViewModel),
                        me.getSliderAndHField(childViewModel),
                        me.getSliderAndSField(childViewModel),
                        me.getSliderAndVField(childViewModel),
                        me.getSliderAndAField(childViewModel)
                    ]
                },
                me.getButtonForMobile(childViewModel, config)
            ];
        }
        else {
            config.items = [
                me.getMapAndHexRGBFields(childViewModel),
                me.getSliderAndHField(childViewModel),
                me.getSliderAndSField(childViewModel),
                me.getSliderAndVField(childViewModel),
                me.getSliderAndAField(childViewModel),
                me.getPreviewAndButtons(childViewModel, config)
            ];
        }

        me.childViewModel.bind('{selectedColor}', function(color) {
            me.setColor(color);
        });

        this.callParent(arguments);
    },

    updateColor: function(color) {
        var me = this;

        me.mixins.colorselection.updateColor.call(me, color);

        me.childViewModel.set('selectedColor', color);
    },

    updatePreviousColor: function(color) {
        this.childViewModel.set('previousColor', color);
    },

    // Splits up view declaration for readability
    // "Map" and HEX/R/G/B fields
    getMapAndHexRGBFields: function(childViewModel) {
        var me = this,
            fieldMargin = '0 ' + me.fieldPad + ' 0 0',
            fieldWidth = me.fieldWidth;

        return {
            xtype: 'container',
            viewModel: childViewModel,
            cls: Ext.baseCSSPrefix + 'colorpicker-escape-overflow',
            flex: 1,
            autoSize: false,
            layout: {
                type: 'vbox',
                constrainAlign: true
            },
            margin: '0 10 0 0',
            items: [
                // "MAP"
                {
                    xtype: 'colorpickercolormap',
                    reference: 'colorMap',
                    flex: 1,
                    bind: {
                        position: {
                            bindTo: '{selectedColor}',
                            deep: true
                        },
                        hue: '{selectedColor.h}'
                    },
                    listeners: {
                        handledrag: 'onColorMapHandleDrag'
                    }
                },
                // HEX/R/G/B FIELDS
                {
                    xtype: 'container',
                    layout: 'hbox',
                    autoSize: null,

                    defaults: {
                        labelAlign: 'top',
                        allowBlank: false
                    },

                    items: [{
                        xtype: 'textfield',
                        label: 'HEX',
                        flex: 1,
                        bind: '{hex}',
                        clearable: Ext.platformTags.phone ? false : true,
                        margin: fieldMargin,
                        validators: /^#[0-9a-f]{6}$/i,
                        readOnly: me.getHexReadOnly(),
                        required: true
                    }, {
                        xtype: 'numberfield',
                        clearable: false,
                        label: 'R',
                        bind: '{red}',
                        width: fieldWidth,
                        hideTrigger: true,
                        validators: /^(0|[1-9]\d*)$/i,
                        maxValue: 255,
                        minValue: 0,
                        margin: fieldMargin,
                        required: true
                    }, {
                        xtype: 'numberfield',
                        clearable: false,
                        label: 'G',
                        bind: '{green}',
                        width: fieldWidth,
                        hideTrigger: true,
                        validators: /^(0|[1-9]\d*)$/i,
                        maxValue: 255,
                        minValue: 0,
                        margin: fieldMargin,
                        required: true
                    }, {
                        xtype: 'numberfield',
                        clearable: false,
                        label: 'B',
                        bind: '{blue}',
                        width: fieldWidth,
                        hideTrigger: true,
                        validators: /^(0|[1-9]\d*)$/i,
                        maxValue: 255,
                        minValue: 0,
                        margin: 0,
                        required: true
                    }]
                }
            ]
        };
    },

    // Splits up view declaration for readability
    // Slider and H field 
    getSliderAndHField: function(childViewModel) {
        var me = this,
            fieldWidth = me.fieldWidth;

        return {
            xtype: 'container',
            viewModel: childViewModel,
            cls: Ext.baseCSSPrefix + 'colorpicker-escape-overflow',
            width: fieldWidth,
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            items: [
                {
                    xtype: 'colorpickersliderhue',
                    reference: 'hueSlider',
                    flex: 1,
                    bind: {
                        hue: '{selectedColor.h}'
                    },
                    width: fieldWidth,
                    listeners: {
                        handledrag: 'onHueSliderHandleDrag'
                    }
                },
                {
                    xtype: 'numberfield',
                    reference: 'hnumberfield',
                    clearable: false,
                    label: 'H',
                    labelAlign: 'top',
                    bind: '{hue}',
                    hideTrigger: true,
                    maxValue: 360,
                    minValue: 0,
                    allowBlank: false,
                    margin: 0,
                    required: true
                }
            ]
        };
    },

    // Splits up view declaration for readability
    // Slider and S field 
    getSliderAndSField: function(childViewModel) {
        var me = this,
            fieldWidth = me.fieldWidth,
            fieldPad = me.fieldPad;

        return {
            xtype: 'container',
            viewModel: childViewModel,
            cls: [
                Ext.baseCSSPrefix + 'colorpicker-escape-overflow',
                Ext.baseCSSPrefix + 'colorpicker-column-sslider'
            ],
            width: fieldWidth,
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            margin: '0 ' + fieldPad + ' 0 ' + fieldPad,
            items: [
                {
                    xtype: 'colorpickerslidersaturation',
                    reference: 'satSlider',
                    flex: 1,
                    bind: {
                        saturation: '{saturation}',
                        hue: '{selectedColor.h}'
                    },
                    width: fieldWidth,
                    listeners: {
                        handledrag: 'onSaturationSliderHandleDrag'
                    }
                },
                {
                    xtype: 'numberfield',
                    reference: 'snumberfield',
                    clearable: false,
                    label: 'S',
                    labelAlign: 'top',
                    bind: '{saturation}',
                    hideTrigger: true,
                    maxValue: 100,
                    minValue: 0,
                    allowBlank: false,
                    margin: 0,
                    required: true
                }
            ]
        };
    },

    // Splits up view declaration for readability
    // Slider and V field 
    getSliderAndVField: function(childViewModel) {
        var me = this,
            fieldWidth = me.fieldWidth;

        return {
            xtype: 'container',
            viewModel: childViewModel,
            cls: [
                Ext.baseCSSPrefix + 'colorpicker-escape-overflow',
                Ext.baseCSSPrefix + 'colorpicker-column-vslider'
            ],
            width: fieldWidth,
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            items: [
                {
                    xtype: 'colorpickerslidervalue',
                    reference: 'valueSlider',
                    flex: 1,
                    bind: {
                        value: '{value}',
                        hue: '{selectedColor.h}'
                    },
                    width: fieldWidth,
                    listeners: {
                        handledrag: 'onValueSliderHandleDrag'
                    }
                },
                {
                    xtype: 'numberfield',
                    reference: 'vnumberfield',
                    clearable: false,
                    label: 'V',
                    labelAlign: 'top',
                    bind: '{value}',
                    hideTrigger: true,
                    maxValue: 100,
                    minValue: 0,
                    allowBlank: false,
                    margin: 0,
                    required: true
                }
            ]
        };
    },

    // Splits up view declaration for readability
    // Slider and A field 
    getSliderAndAField: function(childViewModel) {
        var me = this,
            fieldWidth = me.fieldWidth;

        return {
            xtype: 'container',
            viewModel: childViewModel,
            cls: Ext.baseCSSPrefix + 'colorpicker-escape-overflow',
            width: fieldWidth,
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            margin: '0 0 0 ' + me.fieldPad,
            items: [
                {
                    xtype: 'colorpickerslideralpha',
                    reference: 'alphaSlider',
                    flex: 1,
                    bind: {
                        alpha: '{alpha}',
                        color: {
                            bindTo: '{selectedColor}',
                            deep: true
                        }
                    },
                    width: fieldWidth,
                    listeners: {
                        handledrag: 'onAlphaSliderHandleDrag'
                    }
                },
                {
                    xtype: 'numberfield',
                    reference: 'anumberfield',
                    clearable: false,
                    label: 'A',
                    labelAlign: 'top',
                    bind: '{alpha}',
                    hideTrigger: true,
                    maxValue: 100,
                    minValue: 0,
                    allowBlank: false,
                    margin: 0,
                    required: true
                }
            ]
        };
    },

    // Splits up view declaration for readability
    // Preview current/previous color squares and OK and Cancel buttons
    getPreviewAndButtons: function(childViewModel, config) {
        // selected color preview is always shown
        var items = [{
            xtype: 'colorpickercolorpreview',
            flex: 1,
            bind: {
                color: {
                    bindTo: '{selectedColor}',
                    deep: true
                }
            }
        }];

        // previous color preview is optional
        if (config.showPreviousColor) {
            items.push({
                xtype: 'colorpickercolorpreview',
                flex: 1,
                bind: {
                    color: {
                        bindTo: '{previousColor}',
                        deep: true
                    }
                },
                listeners: {
                    click: 'onPreviousColorSelected'
                }
            });
        }

        // Ok/Cancel buttons are optional
        if (config.showOkCancelButtons) {
            items.push(
                {
                    xtype: 'button',
                    text: this.okButtonText,
                    margin: '10 0 0 0',
                    handler: 'onOK'
                },
                {
                    xtype: 'button',
                    text: this.cancelButtonText,
                    margin: '10 0 0 0',
                    handler: 'onCancel'
                }
            );
        }

        return {
            xtype: 'container',
            viewModel: childViewModel,
            cls: Ext.baseCSSPrefix + 'colorpicker-column-preview',
            width: 70,
            margin: '0 0 0 10',
            items: items,
            layout: {
                type: 'vbox',
                align: 'stretch'
            }
        };
    },
    getPreviewForMobile: function(childViewModel, config) {
        // selected color preview is always shown
        var items = [{
            xtype: 'colorpickercolorpreview',
            flex: 1,
            bind: {
                color: {
                    bindTo: '{selectedColor}',
                    deep: true
                }
            }
        }];

        // previous color preview is optional
        if (config.showPreviousColor) {
            items.push({
                xtype: 'colorpickercolorpreview',
                flex: 1,
                bind: {
                    color: {
                        bindTo: '{previousColor}',
                        deep: true
                    }
                },
                listeners: {
                    click: 'onPreviousColorSelected'
                }
            });
        }

        return {
            xtype: 'container',
            viewModel: childViewModel,
            cls: Ext.baseCSSPrefix + 'colorpicker-column-mobile-preview',
            // width: '100%',
            height: 40,
            margin: '10 0 10 0',
            items: items,
            layout: {
                type: 'hbox',
                align: 'stretch'
            }
        };
    },
    getButtonForMobile: function(childViewModel, config) {
        // selected color preview is always shown
        var items = [];

        // Ok/Cancel buttons are optional
        if (config.showOkCancelButtons) {
            items.push(
                {
                    xtype: 'container',
                    flex: 1
                },
                {
                    xtype: 'button',
                    text: this.cancelButtonText,
                    minWidth: 70,
                    margin: '5 5 0 5',
                    handler: 'onCancel'
                },
                {
                    xtype: 'button',
                    text: this.okButtonText,
                    margin: '5 5 0 5',
                    minWidth: 50,
                    handler: 'onOK'
                }
            );

            return {
                xtype: 'container',
                viewModel: childViewModel,
                cls: Ext.baseCSSPrefix + 'colorpicker-column-mobile-button',
                width: '100%',
                height: 40,
                margin: '0',
                align: 'right',
                items: items,
                layout: {
                    type: 'hbox',
                    align: 'stretch'
                }
            };
        }

        return {};
    }
});
