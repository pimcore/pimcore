Ext.define('Ext.ux.gauge.needle.Diamond', {
    extend: 'Ext.ux.gauge.needle.Abstract',
    alias: 'gauge.needle.diamond',

    config: {
        path: function(ir, or) {
            return or - ir > 10
                ? 'M0,' + ir + ' L-4,' + (ir + 5) + ' L0,' + or + ' L4,' + (ir + 5) + ' Z'
                : '';
        }
    }
});
