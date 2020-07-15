topSuite('Ext.chart.Markers', ['Ext.chart.*'], function() {
    describe('clearAll', function() {
        it('should clear all revisions and categories', function() {
            var MarkerHolder = Ext.define(null, {
                extend: 'Ext.draw.sprite.Rect',
                mixins: {
                    markerHolder: 'Ext.chart.MarkerHolder'
                },

                render: function() {
                    var me = this,
                        attr = me.attr;

                    me.putMarker('foo', {
                        translationX: attr.x,
                        translationY: attr.y
                    }, 0, true);
                    me.putMarker('foo', {
                        translationX: attr.x + attr.width,
                        translationY: attr.y
                    }, 1, true);
                    me.putMarker('foo', {
                        translationX: attr.x + attr.width,
                        translationY: attr.y + attr.height
                    }, 2, true);
                    me.putMarker('foo', {
                        translationX: attr.x,
                        translationY: attr.y + attr.height
                    }, 3, true);

                    this.callParent(arguments);
                }
            });

            var container = new Ext.draw.Container({
                    renderTo: document.body,
                    width: 300,
                    height: 300
                }),
                surface = container.getSurface(),
                markerHolder = new MarkerHolder({
                    x: 100,
                    y: 100,
                    width: 100,
                    height: 100,
                    fillStyle: 'yellow'
                }),
                markers = new Ext.chart.Markers({}),
                circleTpl = new Ext.draw.sprite.Circle({
                    fillStyle: 'red',
                    r: 5
                }),
                crossTpl = new Ext.draw.sprite.Cross({
                    fillStyle: 'red',
                    size: 5
                });

            markers.setTemplate(circleTpl);
            markerHolder.bindMarker('foo', markers);
            surface.add(markerHolder);
            surface.add(markers);

            expect(markers.getDirty()).toBe(true);
            expect(markerHolder.getMarker('foo').getTemplate().type).toBe('circle');
            surface.renderFrame();
            expect(markers.instances.length).toBe(4);

            markers.setTemplate(crossTpl);
            expect(markers.getDirty()).toBe(true);
            expect(markerHolder.getMarker('foo').getTemplate().type).toBe('path');
            surface.renderFrame();
            expect(markers.instances.length).toBe(4);

            container.destroy();
        });
    });
});
