topSuite("Ext.chart.legend.sprite.Item", ['Ext.draw.Container'], function() {
    beforeEach(function() {
        // Silence warnings about Sencha download server
        spyOn(Ext.log, 'warn');
    });

    describe("layoutUpdater", function() {
        it("should have top-left of the bounding box at (0,0)", function() {
            // Should place children sprites so that the composite's bounding box
            // has (0,0) as its top-left corner.
            var sprite, surface, container;

            container = new Ext.draw.Container({
                width: 200,
                height: 200,
                renderTo: document.body
            });
            surface = new Ext.draw.Surface();
            sprite = new Ext.chart.legend.sprite.Item({
                text: 'Hello',
                enabled: true,
                marker: {
                    type: 'rect',
                    fillStyle: 'red',
                    strokeStyle: 'black'
                },
                label: {
                    text: 'Hello',
                    fontSize: 40
                },
                series: null,
                record: null
            });
            surface.add(sprite);
            container.add(surface);

            var bbox = sprite.getBBox();

            expect(bbox.x).toBe(0);
            expect(bbox.y).toBe(0);

            Ext.destroy(sprite, surface, container);
        });

        it("should have a properly sized marker", function() {
            // Should have one dimension of the marker's bounding box
            // equal to the value of the marker's 'size' config,
            // and the other dimension equal or less than the value of
            // the 'size' config".
            var size = 20,
                precision = 8,
                sprite, surface, container;

            container = new Ext.draw.Container({
                width: 200,
                height: 200,
                renderTo: document.body
            });
            surface = new Ext.draw.Surface();
            sprite = new Ext.chart.legend.sprite.Item({
                text: 'Hello',
                enabled: true,
                marker: {
                    type: 'path',
                    size: size,
                    path: 'M20.375,12.833h-2.209V10c0,0,0,0,0-0.001c0-2.389,1.945-4.333,4.334-4.333c2.391,0,4.335,1.944,4.335,4.333c0,0,0,0,0,0v2.834h2V9.999h-0.001c-0.001-3.498-2.836-6.333-6.334-6.333S16.166,6.502,16.166,10v2.833H3.125V25h17.25V12.833z',
                    fillStyle: 'red',
                    strokeStyle: 'black'
                },
                label: {
                    text: 'Hello'
                },
                series: null,
                record: null
            });
            surface.add(sprite);
            container.add(surface);

            var marker = sprite.getMarker();

            var bbox = marker.getBBox();

            expect(bbox.width <= size).toBeTruthy();
            expect(bbox.height <= size).toBeTruthy();
            expect(Math.max(bbox.width, bbox.height)).toBeCloseTo(size, precision);

            Ext.destroy(sprite, surface, container);
        });

        it("should have properly centered label & marker", function() {
            // Should have marker and label bounding boxes centered vertically
            // against each other.
            var size = 17,
                precision = 8,
                sprite, surface, container;

            container = new Ext.draw.Container({
                width: 200,
                height: 200,
                renderTo: document.body
            });
            surface = new Ext.draw.Surface();
            sprite = new Ext.chart.legend.sprite.Item({
                text: 'Hello',
                enabled: true,
                marker: {
                    type: 'circle',
                    size: size,
                    fillStyle: 'red',
                    strokeStyle: 'black'
                },
                label: {
                    text: 'Hello'
                },
                series: null,
                record: null
            });
            surface.add(sprite);
            container.add(surface);

            var marker = sprite.getMarker();

            var label = sprite.getLabel();

            var mbb = marker.getBBox();

            var lbb = label.getBBox();

            expect(mbb.y - lbb.y).toBeCloseTo((lbb.y + lbb.height) - (mbb.y + mbb.height), precision);

            Ext.destroy(sprite, surface, container);
        });

        it("should have a proper gap between label & marker", function() {
            // Should have a gap between marker and label bounding boxes
            // equal to the value of the 'markerLabelGap' attribute.
            var size = 15,
                precision = 8,
                sprite, surface, container;

            container = new Ext.draw.Container({
                width: 200,
                height: 200,
                renderTo: document.body
            });
            surface = new Ext.draw.Surface();
            sprite = new Ext.chart.legend.sprite.Item({
                text: 'Hello',
                enabled: true,
                marker: {
                    type: 'circle',
                    size: size,
                    fillStyle: 'red',
                    strokeStyle: 'black'
                },
                label: {
                    text: 'Hello'
                },
                series: null,
                record: null
            });
            surface.add(sprite);
            container.add(surface);

            var marker = sprite.getMarker();

            var label = sprite.getLabel();

            var mbb = marker.getBBox();

            var lbb = label.getBBox();

            expect(lbb.x - (mbb.x + mbb.width)).toBeCloseTo(sprite.attr.markerLabelGap, precision);

            Ext.destroy(sprite, surface, container);
        });
    });
});
