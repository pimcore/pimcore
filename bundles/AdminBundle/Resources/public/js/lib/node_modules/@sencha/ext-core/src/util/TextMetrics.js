/**
 * Provides precise pixel measurements for blocks of text so that you can determine 
 * the exact pixel height and width of a block of text. 
 * 
 * **Note:** The TextMetrics tool should only be utilized to measure plain text. Attempting to 
 * measure text that includes HTML may return inaccurate results.
 *
 * This measurement works by copying the relevant font-related CSS styles from the element  
 * param to the TextMetrics' cached measurement element.  This returns the dimensions of the cached
 * element wrapping the text.  By default, the wrapping element is auto-sized.  
 * You must provide a **fixed width** if the passed text is multi-lined.
 *
 * When multiple measurements are being done with the same element styling, you should 
 * create a single, reusable TextMetrics instance.  This is more efficient than using the 
 * static {@link #measure} method.  The element styles are copied to the cached 
 * TextMetrics element once during instantiation versus repeated copying using 
 * _measure()_.
 *
 * The following example demonstrates the recommended use of TextMetrics where the custom 
 * textfield class sets up a reusable TextMetrics instance used to measure the label 
 * width. This example assumes that all instances of _mytextfield_ have the same 
 * {@link Ext.form.Labelable#labelClsExtra labelClsExtra} and 
 * {@link Ext.form.Labelable#labelStyle labelStyle} configs.
 *
 *     Ext.define('MyApp.view.MyTextField', {
 *         extend: 'Ext.form.field.Text',
 *         xtype: 'mytextfield',
 *     
 *         initComponent: function () {
 *             var me = this,
 *                 tm = me.getTextMetrics();
 *      
 *             me.labelWidth = tm.getWidth(me.fieldLabel + me.labelSeparator);
 *             me.callParent();
 *         },
 *     
 *         getTextMetrics: function () {
 *             var me = this,
 *                 // Using me.self allows labelCls etc. to vary by derived
 *                 // class, but not by instance.
 *                 cls = me.self,
 *                 tm = cls.measurer,
 *                 el;
 *     
 *             if (!tm) {
 *                 el = Ext.getBody().createChild();
 *                 el.addCls(me.labelCls + ' ' + me.labelClsExtra).
 *                     applyStyles(me.labelStyle);
 *     
 *                 cls.measurer = tm = new Ext.util.TextMetrics(el);
 *             }
 *     
 *             return tm;
 *         }
 *     });
 *
 *     Ext.create('Ext.form.Panel', {
 *         title: 'Contact Info',
 *         width: 600,
 *         bodyPadding: 10,
 *         renderTo: Ext.getBody(),
 *         items: [{
 *             xtype: 'mytextfield',
 *             fieldLabel: 'Name',
 *             labelStyle: 'font-size: 10px;'
 *         }, {
 *             xtype: 'mytextfield',
 *             fieldLabel: 'Email Address',
 *             labelStyle: 'font-size: 10px;'
 *         }]
 *     });
 *
 * While less efficient than the preceding example, this example allows each instance of 
 * _mytextfield2_ to have unique labelClsExtra and labelStyle configs.  Each custom 
 * textfield instance uses the static TextMetrics measure method which will copy the 
 * label styles repeatedly, thus being less efficient but more versatile.
 *
 *     Ext.define('MyApp.view.MyTextField2', {
 *         extend: 'Ext.form.field.Text',
 *         xtype: 'mytextfield2',
 *     
 *         initComponent: function () {
 *             var me = this,
 *                 el = me.getMeasurementEl(),
 *                 tm = Ext.util.TextMetrics;
 *     
 *             me.labelWidth = tm.measure(el, me.fieldLabel + me.labelSeparator).width;
 *             me.callParent();
 *         },
 *        
 *         getMeasurementEl: function () {
 *             var me = this,
 *                 cls = MyApp.view.MyTextField2,
 *                 el = cls.measureEl;
 *     
 *             if (!el) {
 *                 cls.measureEl = el = Ext.getBody().createChild();
 *             }
 *     
 *             el.dom.removeAttribute('style');
 *             el.removeCls(el.dom.className).
 *                 addCls(me.labelCls + ' ' + me.labelClsExtra).
 *                 applyStyles(me.labelStyle);
 *     
 *             return el;
 *         }
 *     });
 *     
 *     Ext.create('Ext.form.Panel', {
 *         title: 'Contact Info',
 *         width: 600,
 *         bodyPadding: 10,
 *         renderTo: Ext.getBody(),
 *         items: [{
 *             xtype: 'mytextfield2',
 *             fieldLabel: 'Name',
 *             labelStyle: 'font-size: 14px;font-weight: bold;',
 *             labelClsExtra: 'nameLabel'
 *         }, {
 *             xtype: 'mytextfield2',
 *             fieldLabel: 'Email Address',
 *             labelStyle: 'font-size: 10px;',
 *             labelClsExtra: 'emailLabel'
 *         }]
 *     });
 */
Ext.define('Ext.util.TextMetrics', {
    requires: [
        'Ext.dom.Element'
    ],

    statics: {
        shared: null,

        /**
         * Measures the size of the specified text
         * @param {String/HTMLElement} el The element, dom node or id from which to copy existing
         * CSS styles that can affect the size of the rendered text
         * @param {String} text The text to measure
         * @param {Number} [fixedWidth] If the text will be multiline, you have to set a fixed width
         * in order to accurately measure the text height
         * @return {Object} An object containing the text's size
         * `{width: (width), height: (height)}`
         * @static
         */
        measure: function(el, text, fixedWidth) {
            var me = this,
                shared = me.shared || (me.shared = new me(el, fixedWidth));

            shared.bind(el);
            shared.setFixedWidth(fixedWidth || 'auto');

            return shared.getSize(text);
        },

        /**
         * Destroy the TextMetrics instance created by {@link #measure}.
         * @static
         */
        destroy: function() {
            this.shared = Ext.destroy(this.shared);
        }
    },

    /**
     * Creates new TextMetrics.
     * @param {String/HTMLElement/Ext.dom.Element} bindTo The element or its ID to bind to.
     * @param {Number} [fixedWidth] A fixed width to apply to the measuring element.
     */
    constructor: function(bindTo, fixedWidth) {
        var me = this,
            measure = me.measure = Ext.getBody().createChild({
                //<debug>
                // tell the spec runner to ignore this element when checking if the dom is clean 
                'data-sticky': true,
                //</debug>
                role: 'presentation',
                cls: Ext.baseCSSPrefix + 'textmetrics',
                style: {
                    position: 'absolute',
                    left: '-1000px',
                    top: '-1000px',
                    visibility: 'hidden'
                }
            });

        if (bindTo) {
            me.bind(bindTo);
        }

        if (fixedWidth) {
            measure.setWidth(fixedWidth);
        }
    },

    /**
     * Returns the size of the specified text based on the internal element's style and width
     * properties
     * @param {String} text The text to measure
     * @return {Object} An object containing the text's size `{width: (width), height: (height)}`
     */
    getSize: function(text) {
        var measure = this.measure,
            size;

        measure.setHtml(text);
        size = measure.getSize();
        measure.setHtml('');

        return size;
    },

    /**
     * Binds this TextMetrics instance to a new element
     * @param {String/HTMLElement/Ext.dom.Element} el The element or its ID.
     */
    bind: function(el) {
        this.measure.setStyle(
            // Create an Ext.dom.Fly instance on our prototype unless we've already been through
            // here. Attach it to the passed HTMLElement/Ext.Element
            (this.el || (this.self.prototype.el = new Ext.dom.Fly())).attach(el).getStyle([
                'font-size',
                'font-size-adjust',
                'font-style',
                'font-weight',
                'font-family',
                'font-kerning',
                'font-stretch',
                'line-height',
                'text-transform',
                'text-decoration',
                'letter-spacing',
                'word-break'
            ])
        );
    },

    /**
     * Sets a fixed width on the internal measurement element.  If the text will be multiline,
     * you have to set a fixed width in order to accurately measure the text height.
     * @param {Number} width The width to set on the element
     */
    setFixedWidth: function(width) {
        this.measure.setWidth(width);
    },

    /**
      * Returns the measured width of the specified text
      * @param {String} text The text to measure
      * @return {Number} width The width in pixels
      */
    getWidth: function(text) {
        this.measure.dom.style.width = 'auto';

        return this.getSize(text).width;
    },

    /**
      * Returns the measured height of the specified text
      * @param {String} text The text to measure
      * @return {Number} height The height in pixels
      */
    getHeight: function(text) {
        return this.getSize(text).height;
    },

    /**
      * Destroy this instance
      */
    destroy: function() {
        var me = this;

        me.el = me.measure = Ext.destroy(me.measure);
        me.callParent();
    }
}, function() {
    Ext.Element.override({
        /**
         * Returns the width in pixels of the passed text, or the width of the text in this Element.
         * @param {String} text The text to measure. Defaults to the innerHTML of the element.
         * @param {Number} [min] The minumum value to return.
         * @param {Number} [max] The maximum value to return.
         * @return {Number} The text width in pixels.
         * @member Ext.dom.Element
         */
        getTextWidth: function(text, min, max) {
            return Ext.Number.constrain(
                Ext.util.TextMetrics.measure(
                    this.dom, Ext.valueFrom(text, this.dom.innerHTML, true)
                ).width,
                min || 0,
                max || 1000000
            );
        }
    });
});
