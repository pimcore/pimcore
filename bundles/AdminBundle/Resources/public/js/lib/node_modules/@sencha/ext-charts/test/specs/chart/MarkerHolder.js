topSuite("Ext.chart.MarkerHolder", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    describe('bindMarker', function() {
        it("should release the bound marker when the marker is destroyed", function() {
            var surface = new Ext.draw.Surface({}),
                markerHolder = new Ext.chart.series.sprite.PieSlice({}),
                markers = new Ext.chart.Markers({}),
                template = new Ext.draw.sprite.Text({});

            markers.setTemplate(template);
            markerHolder.bindMarker('labels', markers);
            expect(markerHolder.getMarker('labels')).toBe(markers);
            surface.add(markerHolder);
            markers.destroy();

            expect(markerHolder.getMarker('labels')).toBe(null);

            surface.destroy();

            expect(markerHolder.destroyed).toBe(true);
            expect(template.destroyed).toBe(true);
        });
    });
});
