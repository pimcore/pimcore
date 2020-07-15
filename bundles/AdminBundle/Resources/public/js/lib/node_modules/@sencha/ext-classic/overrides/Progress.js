/**
 * @class Ext.Progress
 *
 *     @example
 *     Ext.create({
 *         xtype: 'grid',
 *         title: 'Simpsons',
 *         store: {
 *             data: [
 *                 { name: 'Lisa', progress: .159 },
 *                 { name: 'Bart', progress: .216 },
 *                 { name: 'Homer', progress: .55 },
 *                 { name: 'Maggie', progress: .167 },
 *                 { name: 'Marge', progress: .145 }
 *             ]
 *         },
 *         columns: [
 *             { text: 'Name',  dataIndex: 'name' },
 *             {
 *                 text: 'Progress',
 *                 xtype: 'widgetcolumn',
 *                 width: 120,
 *                 dataIndex: 'progress',
 *                 widget: {
 *                     xtype: 'progress'
 *                 }
 *             }
 *         ],
 *         height: 200,
 *         width: 400,
 *         renderTo: Ext.getBody()
 *     });
 */

Ext.define('Ext.overrides.Progress', {
    override: 'Ext.Progress',

    config: {
        ui: 'default'
    },

    updateWidth: function(width, oldWidth) {
        var me = this;

        me.callParent([width, oldWidth]);
        width -= me.element.getBorderWidth('lr');
        me.backgroundEl.setWidth(width);
        me.textEl.setWidth(width);
    },

    privates: {
        startBarAnimation: function(o) {
            this.barEl.animate(o);
        },

        stopBarAnimation: function() {
            this.barEl.stopAnimation();
        }
    }
});
