/**
 * The Ext.chart package provides the capability to visualize data.
 * Each chart binds directly to a {@link Ext.data.Store store} enabling automatic
 * updates of the chart. A chart configuration object has some overall styling
 * options as well as an array of axes and series. A chart instance example could
 * look like this:
 *
 *     Ext.create('Ext.chart.CartesianChart', {
 *         width: 800,
 *         height: 600,
 *         animation: {
 *             easing: 'backOut',
 *             duration: 500
 *         },
 *         store: store1,
 *         legend: {
 *             position: 'right'
 *         },
 *         axes: [
 *             // ...some axes options...
 *         ],
 *         series: [
 *             // ...some series options...
 *         ]
 *     });
 *
 * In this example we set the `width` and `height` of a chart; We decide whether
 * our series are animated or not and we select a store to be bound to the chart;
 * We also set the legend to the right part of the chart.
 *
 * You can register certain interactions such as {@link Ext.chart.interactions.PanZoom}
 * on the chart by specifying an array of names or more specific config objects.
 * All the events will be wired automatically.
 *
 * You can also listen to series `itemXXX` events on both chart and series level.
 *
 * For example:
 *
 *     Ext.create('Ext.chart.CartesianChart', {
 *         plugins: {
 *             chartitemevents: {
 *                 moveEvents: true
 *             }
 *         },
 *         store: {
 *             fields: ['pet', 'households', 'total'],
 *             data: [
 *                 {pet: 'Cats', households: 38, total: 93},
 *                 {pet: 'Dogs', households: 45, total: 79},
 *                 {pet: 'Fish', households: 13, total: 171}
 *             ]
 *         },
 *         axes: [{
 *             type: 'numeric',
 *             position: 'left'
 *         }, {
 *             type: 'category',
 *             position: 'bottom'
 *         }],
 *         series: [{
 *             type: 'bar',
 *             xField: 'pet',
 *             yField: 'households',
 *             listeners: {
 *                 itemmousemove: function (series, item, event) {
 *                     console.log('itemmousemove', item.category, item.field);
 *                 }
 *             }
 *         }, {
 *             type: 'line',
 *             xField: 'pet',
 *             yField: 'total',
 *             marker: true
 *         }],
 *         listeners: { // Listen to itemclick events on all series.
 *             itemclick: function (chart, item, event) {
 *                 console.log('itemclick', item.category, item.field);
 *             }
 *         }
 *     });
 *
 * Important! It's generally a poor design choice to put interactive charts
 * inside scrollable views, in such cases it's not possible to tell
 * which component should respond to the interaction.
 * Since charts are typically interactive their default touch action config
 * looks as follows: {@link Ext.draw.Container#touchAction}.
 * If you do have a chart inside a scrollable view, even if it has no interactions,
 * you have to set its `touchAction` config to the following:
 *
 *     touchAction: {
 *         panX: true,
 *         panY: true
 *     }
 *
 * Otherwise, if a touch action started on a chart, a swipe will not scroll
 * the view.
 *
 * For more information about the axes and series configurations please check
 * the documentation of each series (Line, Bar, Pie, etc).
 *
 */
Ext.define('Ext.chart.AbstractChart', {

    extend: 'Ext.draw.Container',

    requires: [
        'Ext.chart.theme.Default',
        'Ext.chart.series.Series',
        'Ext.chart.interactions.Abstract',
        'Ext.chart.axis.Axis',
        'Ext.chart.Util',
        'Ext.data.StoreManager',
        'Ext.chart.legend.Legend',
        'Ext.chart.legend.SpriteLegend',
        'Ext.chart.Caption',
        'Ext.chart.legend.store.Store',
        'Ext.data.Store'
    ],

    isChart: true,

    defaultBindProperty: 'store',

    /**
     * @event beforerefresh
     * Fires before a refresh to the chart data is called.  If the `beforerefresh`
     * handler returns `false` the {@link #refresh} action will be canceled.
     * @param {Ext.chart.AbstractChart} this
     */

    /**
     * @event refresh
     * Fires after the chart data has been refreshed.
     * @param {Ext.chart.AbstractChart} this
     */

    /**
     * @event redraw
     * Fires after each {@link #event!redraw} call.
     * @param {Ext.chart.AbstractChart} this
     */

    /**
     * @private
     * @event layout
     * Fires after the final layout is done.
     * (Two layouts may be required to fully render a chart.
     * Typically for the initial render and every time thickness
     * of the chart's axes changes.)
     * @param {Ext.chart.AbstractChart} this
     */

    /**
     * @event itemhighlight
     * Fires when an item is highlighted.
     * @param {Ext.chart.AbstractChart} this
     * @param {Object} newItem The new highlight item.
     * @param {Object} oldItem The old highlight item.
     */

    /**
     * @event itemhighlightchange
     * Fires when an item's highlight changes.
     * @param this
     * @param {Object} newItem The new highlight item.
     * @param {Object} oldItem The old highlight item.
     */

    /**
     * @event itemmousemove
     * Fires when the mouse is moved on a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemmouseup
     * Fires when a mouseup event occurs on a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemmousedown
     * Fires when a mousedown event occurs on a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemmouseover
     * Fires when the mouse enters a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemmouseout
     * Fires when the mouse exits a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemclick
     * Fires when a click event occurs on a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemdblclick
     * Fires when a double click event occurs on a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemtap
     * Fires when a tap event occurs on a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event storechange
     * Fires when the store of the chart changes.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Ext.data.Store} newStore
     * @param {Ext.data.Store} oldStore
     */

    config: {

        /**
         * @cfg {Ext.data.Store/String/Object} store
         * The data source to which the chart is bound.
         * Acceptable values for this property are:
         *
         *   - **any {@link Ext.data.Store Store} class / subclass**
         *   - **an {@link Ext.data.Store#storeId ID of a store}**
         *   - **a {@link Ext.data.Store Store} config object**.  When passing a config you can
         *     specify the store type by alias.  Passing a config object with a store type will
         *     dynamically create a new store of that type when the chart is instantiated.
         *
         * For example:
         *
         *     Ext.define('MyApp.store.Customer', {
         *         extend: 'Ext.data.Store',
         *         alias: 'store.customerstore',
         *
         *         fields: ['name', 'value']
         *     });
         *
         *
         *     Ext.create({
         *         xtype: 'cartesian',
         *         renderTo: document.body,
         *         height: 400,
         *         width: 400,
         *         store: {
         *             type: 'customerstore',
         *             data: [{
         *                 name: 'metric one',
         *                 value: 10
         *             }]
         *         },
         *         axes: [{
         *             type: 'numeric',
         *             position: 'left',
         *             title: {
         *                 text: 'Sample Values',
         *                 fontSize: 15
         *             },
         *             fields: 'value'
         *         }, {
         *             type: 'category',
         *             position: 'bottom',
         *             title: {
         *                 text: 'Sample Values',
         *                 fontSize: 15
         *             },
         *             fields: 'name'
         *         }],
         *         series: {
         *             type: 'bar',
         *             xField: 'name',
         *             yField: 'value'
         *         }
         *     });
         */
        store: 'ext-empty-store',

        /**
         * @cfg {String} [theme="default"]
         * The name of the theme to be used. A theme defines the colors and styles
         * used by the series, axes, markers and other chart components.
         * Please see the documentation for the {@link Ext.chart.theme.Base} class
         * for more information.
         *
         * Possible theme values are:
         *   - 'green', 'sky', 'red', 'purple', 'blue', 'yellow'
         *   - 'category1' to 'category6'
         *   - and the above theme names with the '-gradients' suffix, e.g. 'green-gradients'
         *
         * IMPORTANT: You should require the themes you use; for example, to use:
         *
         *     theme: 'blue'
         *
         * the `Ext.chart.theme.Blue` class should be required:
         *
         *     requires: 'Ext.chart.theme.Blue'
         *
         * To require all chart themes:
         *
         *     requires: 'Ext.chart.theme.*'
         */
        theme: 'default',

        /**
         * Chart captions can be used to place titles, subtitles, credits and other captions
         * inside a chart. For example:
         *
         *     captions: {
         *         title: {
         *             text: 'Consumer Price Index'
         *         },
         *         subtitle: {
         *             text: 'from 2007 to 2017'
         *         },
         *         credits: {
         *             text: 'Source: 'bls.gov'
         *         }
         *     }
         *
         * One can use any names for properties in the `captions` config, but the `title`,
         * `subtitle` and `credits` ones have a special meaning - they are automatically
         * themeable. The `title` and `subtitle` are automatically docked to the top of
         * a chart and the `credits` to the bottom. The `title` uses the largest and
         * the heaviest font, while the `credits` - the smallest and the lightest.
         *
         * Other captions besides those three can be easily defined as well:
         *
         *     captions: {
         *         myFancyCaption: {
         *             docked: 'bottom',
         *             align: 'left',
         *             style: {
         *                 fontSize: 18,
         *                 fontWeight: 'bold',
         *                 fontFamily: 'Verdana'
         *             }
         *         }
         *     }
         *
         * If a caption config only specifies text, a shorthand syntax is also possible:
         *
         *     captions: {
         *         title: 'Consumer Price Index'
         *     }
         *
         * @cfg {Object} captions
         * @cfg {Ext.chart.Caption} captions.title
         * @cfg {Ext.chart.Caption} captions.subtitle
         * @cfg {Ext.chart.Caption} captions.credits
         */
        captions: null,

        /**
         * @cfg {Object} style
         * The style for the chart component.
         */
        style: null,

        /**
         * @cfg {Boolean/Object} [animation=true]
         * Defaults to `easeInOut` easing with a 500ms duration.
         * See {@link Ext.draw.modifier.Animation} for possible configuration options.
         */
        animation: !Ext.isIE8,

        /**
         * @cfg {Ext.chart.series.Series/Array} series
         * Array of {@link Ext.chart.series.Series Series} instances or config objects.
         * For example:
         *
         *     series: [{
         *         type: 'column',
         *         axis: 'left',
         *         listeners: {
         *             'afterrender': function() {
         *                 console.log('afterrender');
         *             }
         *         },
         *         xField: 'category',
         *         yField: 'data1'
         *     }]
         */
        series: [],

        /**
         * @cfg {Ext.chart.axis.Axis/Array/Object} axes
         * Array of {@link Ext.chart.axis.Axis Axis} instances or config objects.
         * For example:
         *
         *     axes: [{
         *         type: 'numeric',
         *         position: 'left',
         *         title: 'Number of Hits',
         *         minimum: 0
         *     }, {
         *         type: 'category',
         *         position: 'bottom',
         *         title: 'Month of the Year'
         *     }]
         */
        axes: [],

        /**
         * @cfg {Ext.chart.legend.Legend/Ext.chart.legend.SpriteLegend/Boolean} legend
         * The legend config for the chart. If specified, a legend block will be shown
         * next to the chart.
         * Each legend item displays the {@link Ext.chart.series.Series#title title}
         * of the series, the color of the series and allows to toggle the visibility
         * of the series (at least one series should remain visible).
         *
         * Sencha Charts support two types of legends: sprite based and DOM based.
         *
         * The sprite based legend can be shown in chart {@link Ext.draw.Container#preview preview}
         * and is a part of the downloaded {@link Ext.draw.Container#download chart image}.
         * The sprite based legend is always displayed in full and takes as much space as necessary,
         * the legend items are split into columns to use the available space efficiently.
         * The sprite based legend is styled via a {@link Ext.chart.theme.Base chart theme}.
         *
         * The DOM based legend supports RTL.
         * It occupies a fixed width or height and scrolls when the content overflows.
         * The DOM based legend is styled via CSS rules.
         *
         * By default the sprite legend is used. The type can be explicitly specified:
         *
         *     legend: {
         *         type: 'dom',  // 'sprite' is another possible value
         *         docked: 'top'
         *     }
         *
         * If the legend config is set to `true`, the sprite legend will be used
         * docked to the bottom.
         */
        legend: null,

        /**
         * @cfg {Array} colors
         * Array of colors/gradients to override the color of items and legends.
         */
        colors: null,

        /**
         * @cfg {Object/Number/String} insetPadding
         * The amount of inset padding in pixels for the chart.
         * Inset padding is the padding from the boundary of the chart to any
         * of its contents.
         */
        insetPadding: {
            top: 10,
            left: 10,
            right: 10,
            bottom: 10
        },

        /**
         * @cfg {Object} background Set the chart background.
         * This can be a gradient object, image, or color.
         *
         * For example, if `background` were to be a color we could set the object as
         *
         *     background: '#ccc'
         *
         * You can specify an image by using:
         *
         *     background: {
         *         type: 'image',
         *         src: 'http://path.to.image/'
         *     }
         *
         * Also you can specify a gradient by using the gradient object syntax:
         *
         *     background: {
         *         type: 'linear',
         *         degrees: 0,
         *         stops: [
         *             {
         *                 offset: 0,
         *                 color: 'white'
         *             },
         *             {
         *                 offset: 1,
         *                 color: 'blue'
         *             }
         *         ]
         *     }
         */
        background: null,

        /**
         * @cfg {Array} interactions
         * Interactions are optional modules that can be plugged in to a chart
         * to allow the user to interact with the chart and its data in special ways.
         * The `interactions` config takes an Array of Object configurations,
         * each one corresponding to a particular interaction class identified
         * by a `type` property:
         *
         *     new Ext.chart.AbstractChart({
         *         renderTo: Ext.getBody(),
         *         width: 800,
         *         height: 600,
         *         store: store1,
         *         axes: [
         *             // ...some axes options...
         *         ],
         *         series: [
         *             // ...some series options...
         *         ],
         *         interactions: [{
         *             type: 'interactiontype'
         *             // ...additional configs for the interaction...
         *         }]
         *     });
         *
         * When adding an interaction which uses only its default configuration
         * (no extra properties other than `type`), you can alternately specify
         * only the type as a String rather than the full Object:
         *
         *     interactions: ['reset', 'rotate']
         *
         * The current supported interaction types include:
         *
         * - {@link Ext.chart.interactions.PanZoom panzoom} - allows pan and zoom of axes
         * - {@link Ext.chart.interactions.ItemHighlight itemhighlight} - allows highlighting
         * of series data points
         * - {@link Ext.chart.interactions.ItemInfo iteminfo} - allows displaying details of
         * a data point in a popup panel
         * - {@link Ext.chart.interactions.Rotate rotate} - allows rotation of pie and radar series
         *
         * See the documentation for each of those interaction classes to see how they
         * can be configured.
         *
         * Additional custom interactions can be registered using `'interactions.'` alias prefix.
         */
        interactions: [],

        /**
         * @private
         * The main area of the chart where grid and series are drawn.
         */
        mainRect: null,

        /**
         * @private
         * Override value.
         */
        resizeHandler: null,

        /**
         * @cfg {Object} highlightItem
         * The current highlight item in the chart.
         * The object must be the one that you get from item events.
         *
         * Note that series can also own highlight items.
         * This notion is separate from this one and should not be used at the same time.
         */
        highlightItem: null,

        /* eslint-disable indent */
        surfaceZIndexes: {
            background: 0, // Contains the backround 'rect' sprite.
            main: 1,       // Contains grid lines and CrossZoom overlay 'rect' sprite.
            grid: 2,       // Reserved.
            series: 3,     // Contains series sprites.
            axis: 4,       // No actual `axis` surface is created, but this zIndex is used
                           // for all axis surfaces (one surface is created per axis).
            chart: 5,      // Covers whole chart, minus the legend area.
                           // Contains sprites defined in the `sprites` config,
                           // title, subtitle and credits.
            caption: 6,    // Contains title, subtitle and credits sprites.
            overlay: 7,    // This surface will typically contain chart labels
                           // and interaction sprites like crosshair lines.
                           // With cartesian charts, equivalent in size to the `series` surface.
                           // With polar charts, equivalent in size to the `chart` surface.
            legend: 8      // `SpriteLegend` surface.
        }
        /* eslint-enable indent */
    },

    /**
     * @private
     */
    legendStore: null,

    /**
     * When this is non-zero, changes to sprite attributes apply instantly.
     * See {@link #getAnimation}.
     * @private
     */
    animationSuspendCount: 0,

    /**
     * @private
     */
    chartLayoutSuspendCount: 0,

    /**
     * @private
     */
    chartLayoutCount: 0,

    /**
     * @private
     */
    scheduledLayoutId: null,

    /**
     * @private
     */
    axisThicknessSuspendCount: 0,

    /**
     * @private
     * Indicates that thickness of one or more axes has changed,
     * at the time of {@link #performLayout} call. I.e. 'performLayout'
     * should be called again when current layout is done.
     */
    isThicknessChanged: false,

    constructor: function(config) {
        var me = this;

        me.itemListeners = {};
        me.surfaceMap = {};
        me.chartComponents = {};

        me.isInitializing = true;

        me.suspendChartLayout();
        me.animationSuspendCount++;

        me.callParent(arguments);

        me.isInitializing = false;

        me.getSurface('main');
        me.getSurface('chart').setFlipRtlText(me.getInherited().rtl);
        me.getSurface('overlay').waitFor(me.getSurface('series'));

        me.animationSuspendCount--;
        me.resumeChartLayout();
    },

    applyAnimation: function(animation, oldAnimation) {
        return Ext.chart.Util.applyAnimation(animation, oldAnimation);
    },

    updateAnimation: function() {
        if (this.isConfiguring) {
            return;
        }

        // eslint-disable-next-line vars-on-top
        var seriesList = this.getSeries(),
            ln = seriesList.length,
            i, series;

        this.isSettingSeriesAnimation = true;

        for (i = 0; i < ln; i++) {
            series = seriesList[i];

            // Don't update the series animation config, if it was set by
            // a user, unless 'suspendAnimation' was called.
            if (!series.isUserAnimation || this.animationSuspendCount) {
                series.setAnimation(series.getAnimation());
            }
        }

        this.isSettingSeriesAnimation = false;
    },

    getAnimation: function() {
        var result;

        if (this.animationSuspendCount) {
            result = {
                duration: 0
            };
        }
        else {
            result = this.callParent();
        }

        return result;
    },

    suspendAnimation: function() {
        this.animationSuspendCount++;

        if (this.animationSuspendCount === 1) {
            this.updateAnimation();
        }
    },

    resumeAnimation: function() {
        this.animationSuspendCount--;

        if (this.animationSuspendCount === 0) {
            this.updateAnimation();
        }
    },

    applyInsetPadding: function(padding, oldPadding) {
        var result;

        if (!Ext.isObject(padding)) {
            result = Ext.util.Format.parseBox(padding);
        }
        else if (!oldPadding) {
            result = padding;
        }
        else {
            result = Ext.apply(oldPadding, padding);
        }

        return result;
    },

    /**
     * Suspends chart's layout.
     */
    suspendChartLayout: function() {
        var me = this;

        me.chartLayoutSuspendCount++;

        if (me.chartLayoutSuspendCount === 1) {
            if (me.scheduledLayoutId) {
                me.layoutInSuspension = true;
                me.cancelChartLayout();
            }
            else {
                me.layoutInSuspension = false;
            }
        }
    },

    /**
     * Decrements chart's layout suspend count.
     * When the suspend count is decremented to zero,
     * a layout is scheduled.
     */
    resumeChartLayout: function() {
        var me = this;

        me.chartLayoutSuspendCount--;

        if (me.chartLayoutSuspendCount === 0) {
            if (me.layoutInSuspension) {
                me.scheduleLayout();
            }
        }
    },

    /**
     * Cancel a scheduled layout.
     */
    cancelChartLayout: function() {
        if (this.scheduledLayoutId) {
            Ext.draw.Animator.cancel(this.scheduledLayoutId);
            this.scheduledLayoutId = null;
            this.checkLayoutEnd();
        }
    },

    /**
     * Schedule a layout at next frame.
     */
    scheduleLayout: function() {
        var me = this;

        if (me.allowSchedule() && !me.scheduledLayoutId) {
            me.scheduledLayoutId = Ext.draw.Animator.schedule('doScheduleLayout', me);
        }
    },

    allowSchedule: function() {
        return true;
    },

    doScheduleLayout: function() {
        var me = this;

        me.scheduledLayoutId = null;

        if (me.chartLayoutSuspendCount) {
            me.layoutInSuspension = true;
        }
        else {
            me.performLayout();
        }
    },

    /**
     * Prevent axes from triggering chart layout when their thickness changes.
     * E.g. during an interaction that makes changes to the axes,
     * or when chart layout was triggered by something else,
     * for example a chart resize event.
     */
    suspendThicknessChanged: function() {
        this.axisThicknessSuspendCount++;
    },

    /**
     * Decrements axis thickness suspend count.
     * When axis thickness suspend count is decremented to zero,
     * chart layout is performed.
    */
    resumeThicknessChanged: function() {
        if (this.axisThicknessSuspendCount > 0) {
            this.axisThicknessSuspendCount--;

            if (this.axisThicknessSuspendCount === 0 && this.isThicknessChanged) {
                this.onThicknessChanged();
            }
        }
    },

    onThicknessChanged: function() {
        if (this.axisThicknessSuspendCount === 0) {
            this.isThicknessChanged = false;
            this.performLayout();
        }
        else {
            this.isThicknessChanged = true;
        }
    },

    applySprites: function(sprites) {
        var surface = this.getSurface('chart');

        sprites = Ext.Array.from(sprites);
        surface.removeAll(true);
        surface.add(sprites);

        return sprites;
    },

    initItems: function() {
        var items = this.items,
            i, ln, item;

        if (items && !items.isMixedCollection) {
            this.items = [];
            items = Ext.Array.from(items);

            for (i = 0, ln = items.length; i < ln; i++) {
                item = items[i];

                if (item.type) {
                    Ext.raise("To add custom sprites to the chart use the 'sprites' config.");
                }
                else {
                    this.items.push(item);
                }
            }
        }

        // @noOptimize.callParent
        this.callParent();
        // noOptimize is needed because in the ext build we have a parent method to call,
        // but in touch we do not so we need to suppress the cmd warning during optimized build
    },

    applyBackground: function(newBackground, oldBackground) {
        var surface = this.getSurface('background');

        return this.refreshBackground(surface, newBackground, oldBackground);
    },

    /**
     * @private
     * The background updater. Used by both the chart and the sprite legend.
     * @param surface The surface to put the background in.
     * @param newBackground
     * @param oldBackground
     * @return {Ext.draw.sprite.Rect/Ext.draw.sprite.Sprite}
     */
    refreshBackground: function(surface, newBackground, oldBackground) {
        var width, height, isUpdateOld;

        if (newBackground) {
            if (oldBackground) {
                width = oldBackground.attr.width;
                height = oldBackground.attr.height;
                isUpdateOld = oldBackground.type === (newBackground.type || 'rect');
            }

            if (newBackground.isSprite) {
                oldBackground = newBackground;
            }
            else if (newBackground.type === 'image' && Ext.isString(newBackground.src)) {
                if (isUpdateOld) {
                    oldBackground.setAttributes({
                        src: newBackground.src
                    });
                }
                else {
                    surface.remove(oldBackground, true);
                    oldBackground = surface.add(newBackground);
                }
            }
            else {
                if (isUpdateOld) {
                    oldBackground.setAttributes({
                        fillStyle: newBackground
                    });
                }
                else {
                    surface.remove(oldBackground, true);
                    oldBackground = surface.add({
                        type: 'rect',
                        fillStyle: newBackground,
                        animation: {
                            customDurations: {
                                x: 0,
                                y: 0,
                                width: 0,
                                height: 0
                            }
                        }
                    });
                }
            }
        }

        if (width && height) {
            oldBackground.setAttributes({
                width: width,
                height: height
            });
        }

        oldBackground.setAnimation(this.getAnimation());

        return oldBackground;
    },

    defaultResizeHandler: function(size) {
        this.scheduleLayout();

        return false;
    },

    applyMainRect: function(newRect, rect) {
        if (!rect) {
            return newRect;
        }

        this.getSeries();
        this.getAxes();

        if (newRect[0] === rect[0] &&
            newRect[1] === rect[1] &&
            newRect[2] === rect[2] &&
            newRect[3] === rect[3]) {
            return rect;
        }
        else {
            return newRect;
        }
    },

    register: function(component) {
        var map = this.chartComponents,
            id = component.getId();

        //<debug>
        if (id === undefined) {
            Ext.raise('Chart component id is undefined. ' +
                'Please ensure the component has an id.');
        }

        if (id in map) {
            Ext.raise('Registering duplicate chart component id "' + id + '"');
        }
        //</debug>

        map[id] = component;
    },

    unregister: function(component) {
        var map = this.chartComponents,
            id = component.getId();

        delete map[id];
    },

    get: function(id) {
        return this.chartComponents[id];
    },

    /**
     * @method getAxis Returns an axis instance based on the type of data passed.
     * @param {String/Number/Ext.chart.axis.Axis} axis You may request an axis by passing
     * an id, the number of the array key returned by {@link #getAxes}, or an axis instance.
     * @return {Ext.chart.axis.Axis} The axis requested.
     */
    getAxis: function(axis) {
        if (axis instanceof Ext.chart.axis.Axis) {
            return axis;
        }
        else if (Ext.isNumber(axis)) {
            return this.getAxes()[axis];
        }
        else if (Ext.isString(axis)) {
            return this.get(axis);
        }
    },

    getSurface: function(id, type) {
        var me = this,
            map = me.surfaceMap,
            surface;

        id = id || 'main';
        type = type || id;

        surface = this.callParent([id, type]);

        if (!map[type]) {
            map[type] = [];
        }

        if (Ext.Array.indexOf(map[type], surface) < 0) {
            surface.type = type;
            map[type].push(surface);
            surface.on('destroy', me.forgetSurface, me);
        }

        return surface;
    },

    forgetSurface: function(surface) {
        var map = this.surfaceMap,
            group, index;

        if (!map || this.destroying) {
            return;
        }

        group = map[surface.type];
        index = group ? Ext.Array.indexOf(group, surface) : -1;

        if (index >= 0) {
            group.splice(index, 1);
        }
    },

    applyAxes: function(newAxes, oldAxes) {
        var me = this,
            positions = { left: 'right', right: 'left' },
            result = [],
            axis, oldAxis, linkedTo, id, oldMap, series,
            i, j, ln;

        me.animationSuspendCount++;

        me.getStore();

        if (!oldAxes) {
            oldAxes = [];
            oldAxes.map = {};
        }

        oldMap = oldAxes.map;
        result.map = {};

        newAxes = Ext.Array.from(newAxes, true);

        for (i = 0, ln = newAxes.length; i < ln; i++) {
            axis = newAxes[i];

            if (!axis) {
                continue;
            }

            if (axis instanceof Ext.chart.axis.Axis) {
                oldAxis = oldMap[axis.getId()];
                axis.setChart(me);
            }
            else {
                axis = Ext.Object.chain(axis);
                linkedTo = axis.linkedTo;
                id = axis.id;

                if (Ext.isNumber(linkedTo)) {
                    axis = Ext.merge({}, newAxes[linkedTo], axis);
                }
                else if (Ext.isString(linkedTo)) {
                    Ext.Array.each(newAxes, function(item) {
                        if (item.id === axis.linkedTo) {
                            axis = Ext.merge({}, item, axis);

                            return false;
                        }
                    });
                }

                axis.id = id;
                axis.chart = me;

                if (me.getInherited().rtl) {
                    axis.position = positions[axis.position] || axis.position;
                }

                id = axis.getId && axis.getId() || axis.id;
                axis = Ext.factory(axis, null, oldAxis = oldMap[id], 'axis');
            }

            if (axis) {
                result.push(axis);
                result.map[axis.getId()] = axis;
            }
        }

        me.axesChangeSeries = {};

        for (i in oldMap) {
            if (!result.map[i]) {
                oldAxis = oldMap[i];

                if (oldAxis && !oldAxis.destroyed) {
                    // At this point the series still have their `xAxis` and `yAxis` configs
                    // set to old axes. We need to update such series with new matching axes
                    // by calling their `onAxesChange` method.
                    for (j = 0, ln = oldAxis.boundSeries.length; j < ln; j++) {
                        series = oldAxis.boundSeries[j];
                        me.axesChangeSeries[series.getId()] = series;

                    }

                    oldAxis.destroy();
                }
            }
        }

        me.animationSuspendCount--;

        return result;
    },

    updateAxes: function(axes) {
        var me = this,
            seriesMap = me.axesChangeSeries,
            series, id, i, ln, axis;

        for (id in seriesMap) {
            series = seriesMap[id];
            // `true` to force set series' axes, even if they are already set
            // (in this case to old axes that were just destroyed in the `axes` applier).
            series.onAxesChange(me, true);
        }

        // If changes to the `axes` config are made post chart creation, without making any
        // changes to the series afterwards, we need to figure out the new axes' `boundSeries`
        // manually, as the 'serieschange' event won't be fired in this case.
        for (i = 0, ln = axes.length; i < ln; i++) {
            axis = axes[i];
            axis.onSeriesChange(me);
        }

        if (!me.isConfiguring && !me.destroying) {
            me.scheduleLayout();
        }
    },

    circularCopyArray: function(inArray, startIndex, count) {
        var outArray = [],
            i,
            len = inArray && inArray.length;

        if (len) {
            for (i = 0; i < count; i++) {
                outArray.push(inArray[(startIndex + i) % len]);
            }
        }

        return outArray;
    },

    circularCopyObject: function(inObject, startIndex, count) {
        var me = this,
            name, value,
            outObject = {};

        if (count) {
            for (name in inObject) {
                if (inObject.hasOwnProperty(name)) {
                    value = inObject[name];

                    if (Ext.isArray(value)) {
                        outObject[name] = me.circularCopyArray(value, startIndex, count);
                    }
                    else {
                        outObject[name] = value;
                    }
                }
            }
        }

        return outObject;
    },

    getColors: function() {
        var me = this,
            configColors = me.config.colors,
            theme = me.getTheme();

        if (Ext.isArray(configColors) && configColors.length > 0) {
            configColors = me.applyColors(configColors);
        }

        return configColors || (theme && theme.getColors());
    },

    applyColors: function(newColors) {
        newColors = Ext.Array.map(newColors, function(color) {
            if (Ext.isString(color)) {
                return color;
            }
            else {
                return color.toString();
            }
        });

        return newColors;
    },

    updateColors: function(newColors) {
        var me = this,
            theme = me.getTheme(),
            colors = newColors || (theme && theme.getColors()),
            colorIndex = 0,
            series = me.getSeries(),
            seriesCount = series && series.length,
            i, seriesItem, seriesColors, seriesColorCount;

        if (colors.length) {
            for (i = 0; i < seriesCount; i++) {
                seriesItem = series[i];
                seriesColorCount = seriesItem.themeColorCount();
                seriesColors = me.circularCopyArray(colors, colorIndex, seriesColorCount);
                colorIndex += seriesColorCount;
                seriesItem.updateChartColors(seriesColors);
            }
        }

        if (!me.isConfiguring) {
            me.refreshLegendStore();
        }
    },

    applyTheme: function(theme) {
        if (theme && theme.isTheme) {
            return theme;
        }

        return Ext.Factory.chartTheme(theme);
    },

    updateGradients: function(gradients) {
        if (!Ext.isEmpty(gradients)) {
            this.updateTheme(this.getTheme());
        }
    },

    updateTheme: function(theme, oldTheme) {
        var me = this,
            axes = me.getAxes(),
            series = me.getSeries(),
            colors = me.getColors(),
            i;

        if (!series) {
            return;
        }

        me.updateChartTheme(theme);

        for (i = 0; i < axes.length; i++) {
            axes[i].updateTheme(theme);
        }

        for (i = 0; i < series.length; i++) {
            series[i].setTheme(theme);
        }

        me.updateSpriteTheme(theme);

        me.updateColors(colors);

        // It may be necessary to perform a layout here.
        // But instead of the 'chart.scheduleLayout' call, we can call
        // 'chart.redraw'. If after the redraw call the thickness
        // of any axis changes, this will automatically trigger
        // chart layout (see Ext.chart.axis.sprite.Axis.doThicknessChanged).
        // Otherwise, no layout is necessary.
        me.redraw();
        me.fireEvent('themechange', me, theme, oldTheme);
    },

    themeOnlyIfConfigured: {
        captions: true
    },

    updateChartTheme: function(theme) {
        var me = this,
            chartTheme = theme.getChart(),
            initialConfig = me.getInitialConfig(),
            defaultConfig = me.defaultConfig,
            configs = me.self.getConfigurator().configs,
            genericChartTheme = chartTheme.defaults,
            specificChartTheme = chartTheme[me.xtype],
            themeOnlyIfConfigured = me.themeOnlyIfConfigured,
            key, value, isObjValue, isUnusedConfig, initialValue, cfg;

        chartTheme = Ext.merge({}, genericChartTheme, specificChartTheme);

        for (key in chartTheme) {
            value = chartTheme[key];
            cfg = configs[key];

            if (value !== null && value !== undefined && cfg) {
                initialValue = initialConfig[key];
                isObjValue = Ext.isObject(value);
                isUnusedConfig = initialValue === defaultConfig[key];

                if (isObjValue) {
                    if (isUnusedConfig && themeOnlyIfConfigured[key]) {
                        continue;
                    }

                    value = Ext.merge({}, value, initialValue);
                }

                if (isUnusedConfig || isObjValue) {
                    me[cfg.names.set](value);
                }
            }
        }
    },

    updateSpriteTheme: function(theme) {
        var me = this,
            chartSurface,
            sprites, styles, sprite, style, key, attr, isText, i, ln;

        me.getSprites();

        chartSurface = me.getSurface('chart');
        sprites = chartSurface.getItems();
        styles = theme.getSprites();

        for (i = 0, ln = sprites.length; i < ln; i++) {
            sprite = sprites[i];
            style = styles[sprite.type];

            if (style) {
                attr = {};
                isText = sprite.type === 'text';

                for (key in style) {
                    if (!(key in sprite.config)) {
                        // Setting individual font attributes will take over the 'font' shorthand
                        // attribute, but this behavior is undesireable for theming.
                        if (!(isText && key.indexOf('font') === 0 && sprite.config.font)) {
                            attr[key] = style[key];
                        }
                    }
                }

                sprite.setAttributes(attr);
            }
        }
    },

    /**
     * Adds a {@link Ext.chart.series.Series Series} to this chart.
     *
     * The Series (or array) passed will be added to the existing series. If an `id` is specified
     * in a new Series, any existing Series of that `id` will be updated.
     *
     * The chart will be redrawn in response to the change.
     *
     * @param {Object/Object[]/Ext.chart.series.Series/Ext.chart.series.Series[]} newSeries A
     * config object describing the Series to add, or an instantiated Series object. Or an array
     * of these.
     */
    addSeries: function(newSeries) {
        var series = this.getSeries();

        series = series.concat(Ext.Array.from(newSeries));
        this.setSeries(series);
    },

    /**
     * Remove a {@link Ext.chart.series.Series Series} from this chart.
     * The Series (or array) passed will be removed from the existing series.
     *
     * The chart will be redrawn in response to the change.
     *
     * @param {Ext.chart.series.Series/String} series The Series or the `id` of the Series
     * to remove. May be an array.
     */
    removeSeries: function(series) {
        var existingSeries = this.getSeries(),
            newSeries = [],
            removeMap = {},
            i, len, s;

        series = Ext.Array.from(series);

        // Build a map of the Series IDs that are to be removed
        for (i = 0, len = series.length; i < len; i++) {
            s = series[i];

            // If they passed a Series Object
            if (typeof s !== 'string') {
                s = s.getId();
            }

            removeMap[s] = true;
        }

        // Build a new Series array that excludes those Series scheduled for removal
        for (i = 0, len = existingSeries.length; i < len; i++) {
            if (!removeMap[existingSeries[i].getId()]) {
                newSeries.push(existingSeries[i]);
            }
        }

        this.setSeries(newSeries);
    },

    applySeries: function(newSeries, oldSeries) {
        var me = this,
            result = [],
            oldMap, oldSeriesItem,
            i, ln, series;

        me.animationSuspendCount++;

        me.getAxes();

        if (oldSeries) {
            oldMap = oldSeries.map;
        }
        else {
            oldSeries = [];
            oldMap = oldSeries.map = {};
        }

        result.map = {};
        newSeries = Ext.Array.from(newSeries, true);

        for (i = 0, ln = newSeries.length; i < ln; i++) {
            series = newSeries[i];

            if (!series) {
                continue;
            }

            oldSeriesItem = oldMap[series.getId && series.getId() || series.id];

            // New Series instance passed in
            if (series instanceof Ext.chart.series.Series) {
                // Replacing
                if (oldSeriesItem && oldSeriesItem !== series) {
                    oldSeriesItem.destroy();
                }

                series.setChart(me);
            }
            // Series config object passed in
            else if (Ext.isObject(series)) {

                // Config object matched an existing Series item by id;
                // update its configuration
                if (oldSeriesItem) {
                    oldSeriesItem.setConfig(series);
                    series = oldSeriesItem;
                }
                // Create a new Series
                else {
                    if (Ext.isString(series)) {
                        series = {
                            type: series
                        };
                    }

                    series.chart = me;
                    series = Ext.create(series.xclass || ('series.' + series.type), series);
                }
            }

            result.push(series);
            result.map[series.getId()] = series;
        }

        for (i in oldMap) {
            if (!result.map[oldMap[i].id]) {
                oldMap[i].destroy();
            }
        }

        me.animationSuspendCount--;

        return result;
    },

    updateSeries: function(newSeries, oldSeries) {
        var me = this;

        if (me.destroying) {
            return;
        }

        me.animationSuspendCount++;

        me.fireEvent('serieschange', me, newSeries, oldSeries);

        if (!Ext.isEmpty(newSeries)) {
            me.updateTheme(me.getTheme());
        }

        me.refreshLegendStore();

        if (!me.isConfiguring && !me.destroying) {
            me.scheduleLayout();
        }

        me.animationSuspendCount--;
    },

    defaultLegendType: 'sprite',

    applyLegend: function(legend, oldLegend) {
        var me = this,
            result = null,
            alias;

        if (oldLegend && !(oldLegend.destroyed || oldLegend.destroying)) {
            if (me.legendStoreListeners) {
                me.legendStoreListeners.destroy();
            }

            if (me.legendStore) {
                me.legendStore.destroy();
            }

            oldLegend.destroy();
        }

        if (legend) {
            if (Ext.isBoolean(legend)) {
                result = Ext.create('legend.' + me.defaultLegendType, {
                    docked: 'bottom',
                    chart: me
                });
            }
            else {
                legend.docked = legend.docked || 'bottom';
                legend.chart = me;
                alias = 'legend.' + (legend.type || me.defaultLegendType);
                result = Ext.create(alias, legend);
            }
        }

        return result;
    },

    updateLegend: function(legend) {
        var me = this;

        // Probably has been already destroyed with the old legend,
        // but making sure.
        me.destroyLegendStore();

        if (legend) {
            me.getItems();
            legend.setStore(me.refreshLegendStore());
        }

        if (!me.isConfiguring) {
            me.scheduleLayout();
        }
    },

    captionApplier: function(caption, oldCaption) {
        var me = this,
            result;

        if (oldCaption && !(oldCaption.destroyed || oldCaption.destroying)) {
            oldCaption.destroy();
        }

        if (caption) {
            caption.chart = me;
            result = new Ext.chart.Caption(caption);
        }

        return result;
    },

    applyCaptions: function(captions, oldCaptions) {
        var map = {},
            caption, oldCaption,
            name, any;

        for (name in captions) {
            caption = captions[name];

            if (caption && !caption.length && !(caption.text && caption.text.length)) {
                caption = null;
            }
            else if (typeof caption === 'string') {
                caption = {
                    text: caption
                };
                // Initial config is used for proper theming (see `updateChartTheme`)
                // and config merging, however, mergin won't work as expected, if
                // the initial config value remains a string, so we modify it here.
                this.getInitialConfig().captions[name] = caption;
            }

            oldCaption = oldCaptions && oldCaptions[name];
            caption = this.captionApplier(caption, oldCaption);

            if (caption) {
                any = true;
                map[name] = caption;
            }
        }

        return any && map;
    },

    updateCaptions: function() {
        var me = this;

        if (!me.isConfiguring) {
            me.scheduleLayout();
        }
    },

    /**
     * Return the legend store that contains all the legend information.
     * This information is collected from all the series.
     * @return {Ext.chart.legend.store.Store}
     */
    getLegendStore: function() {
        var me = this,
            store = me.legendStore;

        if (!store) {
            store = me.legendStore = new Ext.chart.legend.store.Store({ chart: me });
            me.legendStoreListeners = store.on({
                scope: me,
                update: 'onLegendStoreUpdate',
                destroyable: true
            });
        }

        return store;
    },

    destroyLegendStore: function() {
        var store = this.legendStore;

        if (store && !(store.destroyed || store.destroying)) {
            store.destroy();
        }

        this.legendStore = null;
    },

    refreshLegendStore: function() {
        var me = this,
            legendStore = me.getLegendStore(),
            series, seriesList, legendData, i, ln;

        if (legendStore) {
            seriesList = me.getSeries();
            legendData = [];

            for (i = 0, ln = seriesList.length; i < ln; i++) {
                series = seriesList[i];

                if (series.getShowInLegend()) {
                    series.provideLegendInfo(legendData);
                }
            }

            legendStore.setData(legendData);
        }

        return legendStore;
    },

    onLegendStoreUpdate: function(store, record) {
        var me = this,
            series;

        if (record) {
            series = this.getSeries().map[record.get('series')];

            if (series) {
                series.setHiddenByIndex(record.get('index'), record.get('disabled'));
                me.redraw();
            }
        }
    },

    applyInteractions: function(interactions, oldInteractions) {
        var me = this,
            result = [],
            oldMap, interaction, i, ln;

        interactions = Ext.Array.from(interactions, true);

        if (!oldInteractions) {
            oldInteractions = [];
            oldInteractions.map = {};
        }

        oldMap = oldInteractions.map;
        result.map = {};

        for (i = 0, ln = interactions.length; i < ln; i++) {
            interaction = interactions[i];

            if (!interaction) {
                continue;
            }

            // eslint-disable-next-line max-len
            interaction = Ext.factory(interaction, null, oldMap[interaction.getId && interaction.getId() || interaction.id], 'interaction');

            if (interaction) {
                interaction.setChart(me);
                result.push(interaction);
                result.map[interaction.getId()] = interaction;
            }
        }

        for (i in oldMap) {
            if (!result.map[i]) {
                oldMap[i].destroy();
            }
        }

        return result;
    },

    /**
     * Get an interaction by type.
     * @param {String} type The type of the interaction.
     * @return {Ext.chart.interactions.Abstract} The interaction. `null`
     * if not found.
     */
    getInteraction: function(type) {
        var interactions = this.getInteractions(),
            len = interactions && interactions.length,
            out = null,
            interaction, i;

        if (len) {
            for (i = 0; i < len; ++i) {
                interaction = interactions[i];

                if (interaction.type === type) {
                    out = interaction;
                    break;
                }
            }
        }

        return out;
    },

    applyStore: function(store) {
        return store && Ext.StoreManager.lookup(store);
    },

    updateStore: function(newStore, oldStore) {
        var me = this;

        if (oldStore && !oldStore.destroyed) {
            oldStore.un({
                datachanged: 'onDataChanged',
                update: 'onDataChanged',
                scope: me,
                order: 'after'
            });

            if (oldStore.autoDestroy) {
                oldStore.destroy();
            }
        }

        if (newStore) {
            newStore.on({
                datachanged: 'onDataChanged',
                update: 'onDataChanged',
                scope: me,
                order: 'after'
            });
        }

        me.fireEvent('storechange', me, newStore, oldStore);
        me.onDataChanged();
    },

    /**
     * Redraw the chart. If animations are set this will animate the chart too.
     * Note: the actual redraw is performed in a subclass.
     */
    redraw: function() {
        this.fireEvent('redraw', this);
    },

    /**
     * @private
     * Lays out chart components and triggers a {@link #event!redraw}.
     * Note: the actual layout is performed in a subclass.
     * A subclass should not perform a layout, if this parent method
     * returns `false`.
     * @return {Boolean}
     */
    performLayout: function() {
        if (this.destroying || this.destroyed) {
            //<debug>
            Ext.raise('Attempting to lay out a dead chart: ' + this.getId());

            //</debug>
            return false; // Cancel subclass layout.
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            legend = me.getLegend(),
            chartRect = me.getChartRect(true),
            background = me.getBackground(),
            result = true,
            legendRect;

        me.cancelChartLayout();

        //<debug>
        // Unlike the 'layout' event that is called after all chart layouts are done
        // and none are pending, this event fires before the start of each layout.
        me.fireEvent('beforelayout', me);
        //</debug>

        if (background) {
            me.getSurface('background').setRect(chartRect.slice());
            background.setAttributes({
                width: chartRect[2],
                height: chartRect[3]
            });
        }

        // The top docked legend is a special case and should be laid out after captions.

        if (legend && legend.isSpriteLegend && !legend.isTop) {
            legendRect = legend.computeRect(chartRect);
        }

        me.layoutCaptions(chartRect);

        if (legend && legend.isSpriteLegend && legend.isTop) {
            legendRect = legend.computeRect(chartRect);
        }

        if (legendRect) {
            me.getSurface('legend').setRect(legendRect);
            result = legend.performLayout();
        }

        me.getSurface('chart').setRect(chartRect);

        if (result) {
            me.hasFirstLayout = true;
        }

        return result;
    },

    layoutCaptions: function(chartRect) {
        var captions = this.getCaptions(),
            shrinkRect = {
                left: 0,
                top: 0,
                right: chartRect[2],
                bottom: chartRect[3]
            },
            caption, captionName, captionList,
            i, ln;

        if (captions) {
            captionList = [];

            for (captionName in captions) {
                captionList.push(captions[captionName]);
            }

            captionList.sort(function(a, b) {
                return a.getWeight() - b.getWeight();
            });

            for (i = 0, ln = captionList.length; i < ln; i++) {
                caption = captionList[i];

                if (!i) {
                    this.getSurface(caption.surfaceName).setRect(chartRect.slice());
                }

                caption.computeRect(chartRect, shrinkRect);
            }

            this.captionList = captionList;
        }
    },

    /**
     * @private
     */
    checkLayoutEnd: function() {
        //        not running                not pending
        if (!this.chartLayoutCount && !this.scheduledLayoutId) {
            this.onLayoutEnd();
        }
    },

    /**
     * @private
     */
    onLayoutEnd: function() {
        var me = this;

        me.fireEvent('layout', me);
    },

    /**
     * @private
     * The area of the chart minus the legend, title, subtitle and credits.
     * Cache chart rect as element.getSize() results in
     * a relatively expensive call to the getComputedStyle().
     */
    getChartRect: function(isRecompute) {
        var me = this,
            chartRect, bodySize;

        if (isRecompute) {
            me.chartRect = null;
        }

        if (me.chartRect) {
            chartRect = me.chartRect;
        }
        else {
            bodySize = me.bodyElement.getSize();
            chartRect = me.chartRect = [0, 0, bodySize.width, bodySize.height];
        }

        return chartRect;
    },

    /**
     * @private
     * Converts page coordinates into chart's 'series' surface coordinates.
     */
    getEventXY: function(e) {
        return this.getSurface('series').getEventXY(e);
    },

    /**
     * Given an x/y point relative to the chart, find and return the first series item that
     * matches that point.
     * @param {Number} x
     * @param {Number} y
     * @return {Object} An object with `series` and `item` properties, or `false` if no item found.
     */
    getItemForPoint: function(x, y) {
        var me = this,
            seriesList = me.getSeries(),
            rect = me.getMainRect(),
            ln = seriesList.length,
            minDistance = Infinity,
            result = null,
            i, item;

        // The x,y here are already converted to the 'main' surface coordinates.
        // Series surface rect matches the main surface rect.
        if (!(me.hasFirstLayout && rect && x >= 0 && x <= rect[2] && y >= 0 && y <= rect[3])) {
            return null;
        }

        // Iterate in reverse order so that the series that render later (on top)
        // get hit tested first.
        for (i = ln - 1; i >= 0; i--) {
            item = seriesList[i].getItemForPoint(x, y);

            if (item) {
                // Imagine a chart with multiple series, e.g. 'line', 'scatter' and 'bar'.
                // For 'line' and 'scatter' series, the method will look for the nearest
                // marker, but for 'bar' series, it will look for the first bar that
                // contains the given point. For such series, the 'distance' information
                // is absent and meaningless.
                if (!item.distance) {
                    result = item;
                    break;
                }

                if (item.distance < minDistance) {
                    minDistance = item.distance;
                    result = item;
                }
            }
        }

        return result;
    },

    /**
     * @private
     * Given an x/y point relative to the chart, find and return all series items that match
     * that point.
     * @param {Number} x
     * @param {Number} y
     * @return {Array} An array of objects with `series` and `item` properties.
     * @deprecated 6.5.2 This method is deprecated
     */
    getItemsForPoint: function(x, y) {
        var me = this,
            seriesList = me.getSeries(),
            ln = seriesList.length,
            // If we haven't drawn yet, don't attempt to find any items.
            i = me.hasFirstLayout ? ln - 1 : -1,
            items = [],
            series, item;

        // Iterate from the end so that the series that are drawn later get hit tested first.
        for (; i >= 0; i--) {
            series = seriesList[i];
            item = series.getItemForPoint(x, y);

            if (item && (item.category === 'items' || item.category === 'markers')) {
                items.push(item);
            }
        }

        return items;
    },

    /**
     * @private
     */
    onDataChanged: function() {
        var me = this,
            rect, store, series, axes;

        if (me.isInitializing) {
            return;
        }

        rect = me.getMainRect();
        store = me.getStore();
        series = me.getSeries();
        axes = me.getAxes();

        if (!store || !axes || !series) {
            return;
        }

        if (!rect) { // The chart hasn't been rendered yet.
            me.on({
                redraw: me.onDataChanged,
                scope: me,
                single: true
            });

            return;
        }

        me.processData();
        me.redraw();
    },

    /**
     * @private
     * The number of records in the chart's store last time the data was changed.
     */
    recordCount: 0,

    /**
     * @private
     */
    processData: function() {
        var me = this,
            recordCount = me.getStore().getCount(),
            seriesList = me.getSeries(),
            ln = seriesList.length,
            isNeedUpdateColors = false,
            i = 0,
            series;

        for (; i < ln; i++) {
            series = seriesList[i];
            series.processData();

            if (!isNeedUpdateColors && series.isStoreDependantColorCount) {
                isNeedUpdateColors = true;
            }
        }

        if (isNeedUpdateColors && recordCount > me.recordCount) {
            me.updateColors(me.getColors());
            me.recordCount = recordCount;
        }

        // 'refreshLegendStore' will attemp to grab the 'series',
        // which are still configuring at this point.
        // The legend store will be refreshed inside the chart.series
        // updater anyway.
        if (!me.isConfiguring) {
            me.refreshLegendStore();
        }
    },

    /**
     * Changes the data store bound to this chart and refreshes it.
     * @param {Ext.data.Store} store The store to bind to this chart.
     */
    bindStore: function(store) {
        this.setStore(store);
    },

    applyHighlightItem: function(newHighlightItem, oldHighlightItem) {
        var i1, i2, s1, s2;

        if (newHighlightItem === oldHighlightItem) {
            return;
        }

        if (Ext.isObject(newHighlightItem) && Ext.isObject(oldHighlightItem)) {
            i1 = newHighlightItem;
            i2 = oldHighlightItem;
            s1 = i1.sprite && (i1.sprite[0] || i1.sprite);
            s2 = i2.sprite && (i2.sprite[0] || i2.sprite);

            if (s1 === s2 && i1.index === i2.index) {
                return;
            }
        }

        return newHighlightItem;
    },

    updateHighlightItem: function(newHighlightItem, oldHighlightItem) {
        var newHighlight, oldHighlight;

        if (oldHighlightItem) {
            oldHighlight = oldHighlightItem.series.getHighlight();

            if (oldHighlight) {
                oldHighlightItem.series.setAttributesForItem(oldHighlightItem,
                                                             { highlighted: false });
            }
        }

        if (newHighlightItem) {
            newHighlight = newHighlightItem.series.getHighlight();

            if (newHighlight) {
                newHighlightItem.series.setAttributesForItem(newHighlightItem,
                                                             { highlighted: true });
            }
        }

        if (oldHighlight || newHighlight) {
            this.fireEvent('itemhighlight', this, newHighlightItem, oldHighlightItem);
        }
    },

    destroyChart: function() {
        var me = this;

        // The order is important here.
        me.setInteractions(null);
        me.setAxes(null);
        me.setSeries(null);
        me.setLegend(null);
        me.setStore(null);

        me.cancelChartLayout();
    },

    /* ---------------------------------
     Methods needed for ComponentQuery
     ---------------------------------- */

    /**
     * @private
     * @param {Boolean} deep
     * @return {Array}
     */
    getRefItems: function(deep) {
        var me = this,
            series = me.getSeries(),
            axes = me.getAxes(),
            interaction = me.getInteractions(),
            legend = me.getLegend(),
            ans = [],
            i, ln;

        for (i = 0, ln = series.length; i < ln; i++) {
            ans.push(series[i]);

            if (series[i].getRefItems) {
                ans.push.apply(ans, series[i].getRefItems(deep));
            }
        }

        for (i = 0, ln = axes.length; i < ln; i++) {
            ans.push(axes[i]);

            if (axes[i].getRefItems) {
                ans.push.apply(ans, axes[i].getRefItems(deep));
            }
        }

        for (i = 0, ln = interaction.length; i < ln; i++) {
            ans.push(interaction[i]);

            if (interaction[i].getRefItems) {
                ans.push.apply(ans, interaction[i].getRefItems(deep));
            }
        }

        if (legend) {
            ans.push(legend);
        }

        return ans;
    }
});
