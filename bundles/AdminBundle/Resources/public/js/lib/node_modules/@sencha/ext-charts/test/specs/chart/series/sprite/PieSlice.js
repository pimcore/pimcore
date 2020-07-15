topSuite("Ext.chart.series.sprite.PieSlice", ['Ext.draw.Surface'], function() {

    describe('destroy', function() {
        it("should remove itself from the surface", function() {
            var surface = new Ext.draw.Surface({}),
                // PieSlice uses the MarkerHolder mixin, if a MarkerHolder
                // calls callParent in its 'destroy' method,
                // this alters the destruction sequence and this
                // test will fail.
                sprite = new Ext.chart.series.sprite.PieSlice({}),
                id = sprite.getId();

            surface.add(sprite);
            sprite.destroy();

            expect(surface.getItems().length).toBe(0);
            expect(surface.get(id)).toBe(undefined);

            surface.destroy();
        });
    });

});
