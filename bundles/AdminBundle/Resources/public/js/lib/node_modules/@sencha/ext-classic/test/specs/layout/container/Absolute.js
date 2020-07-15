topSuite("Ext.layout.container.Absolute", 'Ext.Container', function() {
    var ct;

    afterEach(function() {
        ct = Ext.destroy(ct);
    });

    it("should layout an item with anchor that was initially hidden", function() {
        ct = new Ext.container.Container({
            renderTo: Ext.getBody(),
            width: 400,
            height: 400,
            layout: 'absolute',
            items: [{
                xtype: 'component',
                hidden: true,
                x: 200,
                y: 100,
                anchor: '-5 -50'
            }]
        });

        var c = ct.items.first();

        c.show();

        expect(c.getWidth()).toBe(195);
        expect(c.getHeight()).toBe(250);
        expect(c.getX()).toBe(200);
        expect(c.getY()).toBe(100);
    });
});
