/**
 * A simple progress bar widget.
 *
 * You are responsible for showing, updating (via {@link #setValue}) and clearing the
 * progress bar as needed from your own code. This method is most appropriate when you
 * want to show progress throughout an operation that has predictable points of interest
 * at which you can update the control.
 */
Ext.define('Ext.Progress', {
    extend: 'Ext.Gadget',
    xtype: ['progress', 'progressbarwidget'],
    alternateClassName: 'Ext.ProgressBarWidget',

    mixins: [
        'Ext.ProgressBase'
    ],

    config: {
        /**
         * @cfg {String} [text]
         * The background text
         */
        text: null,

        /**
         * @cfg {Boolean} [animate=false]
         * Specify as `true` to have this progress bar animate to new extent when updated.
         */
        animate: false
    },

    cachedConfig: {
        textCls: Ext.baseCSSPrefix + 'progress-text',

        cls: null
    },

    baseCls: Ext.baseCSSPrefix + 'progress',

    template: [{
        reference: 'backgroundEl'
    }, {
        reference: 'barEl',
        cls: Ext.baseCSSPrefix + 'progress-bar',
        children: [{
            reference: 'textEl'
        }]
    }],

    defaultBindProperty: 'value',

    updateCls: function(cls, oldCls) {
        var el = this.element;

        if (oldCls) {
            el.removeCls(oldCls);
        }

        if (cls) {
            el.addCls(cls);
        }
    },

    updateUi: function(ui, oldUi) {
        var element = this.element,
            barEl = this.barEl,
            baseCls = this.baseCls + '-';

        this.callParent([ui, oldUi]);

        if (oldUi) {
            element.removeCls(baseCls + oldUi);
            barEl.removeCls(baseCls + 'bar-' + oldUi);
        }

        element.addCls(baseCls + ui);
        barEl.addCls(baseCls + 'bar-' + ui);
    },

    updateTextCls: function(textCls) {
        this.backgroundEl.addCls(textCls + ' ' + textCls + '-back');
        this.textEl.addCls(textCls);
    },

    updateValue: function(value, oldValue) {
        var me = this,
            textTpl = me.getTextTpl();

        if (textTpl) {
            me.setText(textTpl.apply({
                value: value,
                percent: Math.round(value * 100)
            }));
        }

        if (!me.isConfiguring && me.getAnimate()) {
            me.stopBarAnimation();
            me.startBarAnimation(Ext.apply({
                from: {
                    width: (oldValue * 100) + '%'
                },
                to: {
                    width: (value * 100) + '%'
                }
            }, me.animate));
        }
        else {
            me.barEl.setStyle('width', (value * 100) + '%');
        }
    },

    updateText: function(text) {
        this.backgroundEl.setHtml(text);
        this.textEl.setHtml(text);
    },

    doDestroy: function() {
        this.stopBarAnimation();
        this.callParent();
    },

    privates: {
        startBarAnimation: Ext.privateFn,
        stopBarAnimation: Ext.privateFn
    }
});
