topSuite("Ext.chart.interactions.PanZoom",
    ['Ext.Panel', 'Ext.toolbar.Toolbar', 'Ext.chart.*', 'Ext.data.ArrayStore',
     'Ext.Button'],
function() {
    describe('modeToggleButton', function() {
        it('should have its value set based on the value of zoomOnPanGesture config', function() {
            var panel, toolbar, isAfterRender;

            runs(function() {
                toolbar = Ext.create({
                    xtype: 'toolbar',
                    renderTo: document.body,
                    width: 400,
                    height: 50
                });
                panel = Ext.create({
                    xtype: 'panel',
                    renderTo: document.body,
                    items: {
                        xtype: 'cartesian',
                        width: 400,
                        height: 400,
                        store: {
                            data: [
                                { x: 0, y: 0 },
                                { x: 1, y: 2 },
                                { x: 2, y: 1 },
                                { x: 3, y: 3 },
                                { x: 4, y: 1 }
                            ]
                        },
                        interactions: {
                            type: 'panzoom',
                            zoomOnPanGesture: true
                        },
                        series: {
                            type: 'line',
                            xField: 'x',
                            yField: 'y'
                        },
                        axes: [
                            {
                                type: 'numeric',
                                position: 'bottom'
                            },
                            {
                                type: 'numeric',
                                position: 'left'
                            }
                        ]
                    },
                    listeners: {
                        afterrender: function() {
                            isAfterRender = true;
                        }
                    }
                });
            });

            waitsFor(function() {
                return isAfterRender;
            });

            runs(function() {
                var chart = Ext.first('cartesian');

                var panzoom = chart.getInteractions()[0];

                var button = panzoom.getModeToggleButton();

                toolbar.add(button);

                expect(button.getValue()).toBe('zoom');

                panzoom.setZoomOnPanGesture(false);
                expect(button.getValue()).toBe('pan');

                Ext.destroy(toolbar, panel);
            });
        });
    });
});
