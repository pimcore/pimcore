/**
 * @class Ext.sparkline.Base
 *
 * The base class for ExtJS SparkLines. SparkLines are small, inline graphs used to visually
 * display small amounts of data. For large datasets, use the
 * {@link Ext.chart.AbstractChart chart package}.
 *
 * The SparkLine subclasses accept an {@link #values array of values}, and present the data
 * in different visualizations.
 *
 *     @example
 *     new Ext.Panel({
 *         height: 300,
 *         width: 600,
 *         frame: true,
 *         title: 'Test Sparklines',
 *         renderTo:document.body,
 *         bodyPadding: 10,
 *
 *         // Named listeners will resolve to methods in this Panel
 *         defaultListenerScope: true,
 *
 *         // Named references will be collected, and can be access from this Panel
 *         referenceHolder: true,
 *
 *         items: [{
 *             reference: 'values',
 *             xtype: 'textfield',
 *             fieldLabel: 'Values',
 *             validator: function(v) {
 *                 var result = [];
 *
 *                 v = v.replace(/\s/g, '');
 *                 v = v.replace(/,$/, '');
 *                 v = v.split(',');
 *                 for (var i = 0; i < v.length; i++) {
 *                     if (!Ext.isNumeric(v[i])) {
 *                         return 'Value must be a comma separated array of numbers';
 *                     }
 *                     result.push(parseInt(v[i], 10));
 *                 }
 *                 this.values = result;
 *                 return true;
 *             },
 *             listeners: {
 *                 change: 'onTypeChange',
 *                 buffer: 500,
 *                 afterrender: {
 *                     fn: 'afterTypeRender',
 *                     single: true
 *                 }
 *             }
 *         }, {
 *             reference: 'type',
 *             xtype: 'combobox',
 *             fieldLabel: 'Type',
 *             store: [
 *                 ['sparklineline',     'Line'],
 *                 ['sparklinebox',      'Box'],
 *                 ['sparklinebullet',   'Bullet'],
 *                 ['sparklinediscrete', 'Discrete'],
 *                 ['sparklinepie',      'Pie'],
 *                 ['sparklinetristate', 'TriState']
 *             ],
 *             value: 'sparklineline',
 *             listeners: {
 *                 change: 'onTypeChange',
 *                 buffer: 500
 *             }
 *         }],
 *
 *         // Start with a line plot. 
 *         afterTypeRender: function(typeField) {
 *             typeField.setValue('6,10,4,-3,7,2');
 *         },
 *
 *         onTypeChange: function() {
 *             var me = this,
 *                 refs = me.getReferences(),
 *                 config;
 *
 *             if (me.sparkLine) {
 *                 me.remove(me.sparkLine, true);
 *             }
 *             config = {
 *                 xtype: refs.type.getValue(),
 *                 values: refs.values.values,
 *                 height: 25,
 *                 width: 100                    
 *             };
 *	           me.sparkLine = Ext.create(config);
 *             me.add(me.sparkLine);
 *
 *             // Put under fields
 *             me.sparkLine.el.dom.style.marginLeft = refs.type.labelEl.getWidth() + 'px';
 *         }
 *     });
 *
 */
Ext.define('Ext.sparkline.Base', {
    extend: 'Ext.Gadget',
    xtype: 'sparkline',
    requires: [
        'Ext.XTemplate',
        'Ext.sparkline.CanvasCanvas',
        'Ext.sparkline.VmlCanvas',
        'Ext.util.Color'
    ],

    cachedConfig: {
        /**
         * @cfg {String} lineColor
         * The hex value for line colors in graphs which
         * display lines ({@link Ext.sparkline.Box Box},
         * {@link Ext.sparkline.Discrete Discrete} and {@link Ext.sparkline.Line Line}).
         */
        lineColor: '#157fcc',

        defaultPixelsPerValue: 3,

        tagValuesAttribute: 'values',

        enableTagOptions: false,

        enableHighlight: true,

        /**
         * @cfg {String} [highlightColor=null]
         * The hex value for the highlight color to use when mouseing over a graph segment.
         */
        highlightColor: null,

        /**
         * @cfg {Number} [highlightLighten]
         * How much to lighten the highlight color by when mouseing over a graph segment.
         */
        highlightLighten: 0.1,

        /**
         * @cfg {Boolean} [tooltipSkipNull=true]
         * Null values will not have a tooltip displayed.
         */
        tooltipSkipNull: true,

        /**
         * @cfg {String} [tooltipPrefix]
         * A string to prepend to each field displayed in a tooltip.
         */
        tooltipPrefix: '',

        /**
         * @cfg {String} [tooltipSuffix]
         * A string to append to each field displayed in a tooltip.
         */
        tooltipSuffix: '',

        /**
         * @cfg {Boolean} [disableTooltips=false]
         * Set to `true` to disable mouseover tooltips.
         */
        disableTooltips: false,

        disableInteraction: false,

        /**
         * @cfg {String/Ext.XTemplate} [tipTpl]
         * An XTemplate used to display the value or values in a tooltip when hovering
         * over a Sparkline.
         *
         * The implemented subclases all define their own `tipTpl`, but it can be overridden.
         */
        tipTpl: null
    },

    config: {
        /**
         * @cfg {Number[]} values An array of numbers which define the chart.
         */
        values: null
    },

    baseCls: Ext.baseCSSPrefix + 'sparkline',

    element: {
        tag: 'canvas',
        reference: 'element',
        style: {
            display: 'inline-block',
            verticalAlign: 'top'
        },
        listeners: {
            mouseenter: 'onMouseEnter',
            mouseleave: 'onMouseLeave',
            mousemove: 'onMouseMove'
        },
        // Create canvas zero sized so that it does not affect the containing element's
        // initial layout
        // https://sencha.jira.com/browse/EXTJSIV-10145
        width: 0,
        height: 0
    },

    defaultBindProperty: 'values',

    // When any config is changed, the canvas needs to be redrawn.
    // This is done at the next animation frame when this queue is traversed.
    redrawQueue: {},

    inheritableStatics: {
        /**
         * @private
         * @static
         * @inheritable
         */
        onClassCreated: function(cls) {
            var configUpdater = cls.prototype.updateConfigChange,
                proto = cls.prototype,
                configs = cls.getConfigurator().configs,
                config,
                updaterName;

            // Set up an applier for all local configs which kicks off a request to redraw
            // on the next animation frame
            for (config in configs) {
                // tipTpl not included in this scheme
                if (config !== 'tipTpl') {
                    updaterName = Ext.Config.get(config).names.update;

                    if (proto[updaterName]) {
                        proto[updaterName] =
                            Ext.Function.createSequence(proto[updaterName], configUpdater);
                    }
                    else {
                        proto[updaterName] = configUpdater;
                    }
                }
            }
        }
    },

    constructor: function(config) {
        var me = this,
            ns = Ext.sparkline;

        // The canvas sets our element config
        me.canvas = Ext.supports.Canvas ? new ns.CanvasCanvas(me) : new ns.VmlCanvas(me);

        me.callParent([config]);
    },

    // determine if all values of an array match a value
    // returns true if the array is empty
    all: function(val, arr, ignoreNull) {
        var i;

        for (i = arr.length; i--;) {
            if (ignoreNull && arr[i] === null) {
                continue;
            }

            if (arr[i] !== val) {
                return false;
            }
        }

        return true;
    },

    // generic config value updater.
    // Redraws the graph, unless we are configured to buffer redraws
    // in wehich case it adds this to the queue to do a redraw on the next animation frame.
    updateConfigChange: function(newValue) {
        var me = this;

        // If we are buffering until animation frame, or we have not been sized, then
        // queue a redraw flush.
        if (me.bufferRedraw || !me.height || !me.width) {
            me.redrawQueue[me.getId()] = me;

            // Ensure that there is a single timer to handle all queued redraws.
            if (!me.redrawTimer) {
                Ext.sparkline.Base.prototype.redrawTimer =
                        Ext.raf(me.processRedrawQueue);
            }
        }
        else {
            me.redraw();
        }

        return newValue;
    },

    // Appliers convert an incoming config value.
    // Ensure the tipTpl is an XTemplate
    applyTipTpl: function(tipTpl) {
        if (tipTpl && !tipTpl.isTemplate) {
            tipTpl = new Ext.XTemplate(tipTpl);
        }

        return tipTpl;
    },

    normalizeValue: function(val) {
        var nf;

        switch (val) {
            case 'undefined':
                val = undefined;
                break;
            case 'null':
                val = null;
                break;
            case 'true':
                val = true;
                break;
            case 'false':
                val = false;
                break;

            default:
                nf = parseFloat(val);

                if (val == nf) { // eslint-disable-line eqeqeq
                    val = nf;
                }
        }

        return val;
    },

    normalizeValues: function(vals) {
        var i,
            result = [];

        for (i = vals.length; i--;) {
            result[i] = this.normalizeValue(vals[i]);
        }

        return result;
    },

    updateWidth: function(width, oldWidth) {
        var me = this,
            dom = me.element.dom,
            measurer = me.measurer;

        me.callParent([width, oldWidth]);
        me.canvas.setWidth(width);
        me.width = width;

        if (me.height == null && measurer) {
            me.setHeight(parseInt(measurer.getCachedStyle(dom.parentNode, 'line-height'), 10));
        }
    },

    updateHeight: function(height, oldHeight) {
        var me = this;

        me.callParent([height, oldHeight]);
        me.canvas.setHeight(height);
        me.height = height;
    },

    setValues: function(values) {
        var me = this,
            oldValues = me.getValues();

        // the values config is expected to be an array
        values = values == null ? [] : Ext.Array.from(values);
        me.values = values;
        me.callParent([values]);

        // If it's physically the same Array object, we need to invoke the updater
        // because though the array is the same, it may have been mutated,
        // and the config system setter will reject the change and not invoke the updater.
        // This is how the Stock Ticker example works. It shuffles values down
        // a static array.
        if (values === oldValues) {
            me.updateValues([values, oldValues]);
        }
    },

    redraw: function() {
        var me = this;

        if (!me.destroyed) {
            me.canvas.onOwnerUpdate();
            me.canvas.reset();

            if (me.getValues()) {
                me.onUpdate();
                me.renderGraph();
            }
        }
    },

    onUpdate: Ext.emptyFn,

    /*
     * Render the chart to the canvas
     */
    renderGraph: function() {
        var ret = true;

        if (this.disabled) {
            this.canvas.reset();
            ret = false;
        }

        return ret;
    },

    onMouseEnter: function(e) {
        this.onMouseMove(e);
    },

    onMouseMove: function(e) {
        var me = this;

        // In IE/Edge, the mousemove event fires before mouseenter
        // This is correct according to the spec
        // https://www.w3.org/TR/uievents/#events-mouseevent-event-order
        me.canvasRegion = me.canvasRegion || me.canvas.el.getRegion();
        me.currentPageXY = e.getPoint();
        me.redraw();
    },

    onMouseLeave: function() {
        var me = this;

        // mouseleave is guaranteed to fire last, so clear region here
        me.canvasRegion = me.currentPageXY = me.targetX = me.targetY = null;
        me.redraw();
        me.hideTip();
    },

    updateDisplay: function() {
        var me = this,
            values = me.getValues(),
            tipHtml, region;

        // To work out the position of currentPageXY within the canvas, we must account
        // for the fact that while document Y values as represented in the currentPageXY
        // are based from the top of the document, canvas Y values begin from the bottom
        // of the canvas element.
        if (values && values.length && me.currentPageXY &&
            me.canvasRegion.contains(me.currentPageXY)) {
            region = me.getRegion(me.currentPageXY[0] - me.canvasRegion.left,
                                  (me.canvasRegion.bottom - 1) - me.currentPageXY[1]);

            if (region != null && me.isValidRegion(region, values)) {
                if (!me.disableHighlight) {
                    me.renderHighlight(region);
                }

                if (!me.getDisableTooltips()) {
                    tipHtml = me.getRegionTooltip(region);
                }
            }

            if (me.hasListeners.sparklineregionchange) {
                me.fireEvent('sparklineregionchange', me);
            }

            if (tipHtml) {
                me.getSharedTooltip().setHtml(tipHtml);
                me.showTip();
            }
        }

        // No tip content; ensure it's hidden
        if (!tipHtml) {
            me.hideTip();
        }
    },

    /**
     * @method
     * Return a region id for a given x/y co-ordinate
     */
    getRegion: Ext.emptyFn,

    /**
     * Fetch the HTML to display as a tooltip
     */
    getRegionTooltip: function(region) {
        var me = this,
            entries = [],
            tipTpl = me.getTipTpl(),
            fields, showFields, showFieldsKey, newFields, fv,
            formatter, fieldlen, i, j;

        fields = me.getRegionFields(region);
        formatter = me.tooltipFormatter;

        if (formatter) {
            return formatter(me, me, fields);
        }

        if (!tipTpl) {
            return '';
        }

        if (!Ext.isArray(fields)) {
            fields = [fields];
        }

        showFields = me.tooltipFormatFieldlist;
        showFieldsKey = me.tooltipFormatFieldlistKey;

        if (showFields && showFieldsKey) {
            // user-selected ordering of fields
            newFields = [];

            for (i = fields.length; i--;) {
                fv = fields[i][showFieldsKey];

                if ((j = Ext.Array.indexOf(fv, showFields)) !== -1) {
                    newFields[j] = fields[i];
                }
            }

            fields = newFields;
        }

        fieldlen = fields.length;

        for (j = 0; j < fieldlen; j++) {
            if (!fields[j].isNull || !me.getTooltipSkipNull()) {
                Ext.apply(fields[j], {
                    prefix: me.getTooltipPrefix(),
                    suffix: me.getTooltipSuffix()
                });
                entries.push(tipTpl.apply(fields[j]));
            }
        }

        if (entries.length) {
            return entries.join('<br>');
        }

        return '';
    },

    getRegionFields: Ext.emptyFn,

    calcHighlightColor: function(color) {
        var me = this,
            highlightColor = me.getHighlightColor(),
            lighten = me.getHighlightLighten(),
            o;

        if (highlightColor) {
            return highlightColor;
        }

        if (lighten) {
            o = Ext.util.Color.fromString(color);

            if (o) {
                o.lighten(lighten);
                color = o.toHex();
            }
        }

        return color;
    },

    destroy: function() {
        delete this.redrawQueue[this.getId()];
        this.callParent();
    },

    privates: {
        hideTip: Ext.privateFn,

        isValidRegion: function(region, values) {
            return region < values.length;
        },

        showTip: Ext.privateFn
    }
}, function(SparklineBase) {
    var proto = SparklineBase.prototype;

    proto.getSharedTooltip = function() {
        var me = this,
            tooltip = me.tooltip;

        if (!tooltip) {
            proto.tooltip = tooltip = SparklineBase.constructTip();
        }

        return tooltip;
    };

    SparklineBase.onClassCreated(SparklineBase);

    proto.processRedrawQueue = function() {
        var redrawQueue = proto.redrawQueue,
            id;

        for (id in redrawQueue) {
            redrawQueue[id].redraw();
        }

        proto.redrawQueue = {};
        proto.redrawTimer = 0;
    };
});
