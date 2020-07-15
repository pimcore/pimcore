topSuite("Ext.layout.container.Table", 'Ext.Panel', function() {

    describe("fixed/auto sizing", function() {

        // See EXTJSIV-7667
        it("should be able to auto-size tables correctly", function() {
            var ct = new Ext.container.Container({
                width: 400,
                height: 200,
                renderTo: Ext.getBody(),
                items: {
                    xtype: 'panel',
                    layout: {
                        type: 'table',
                        columns: 1
                    },
                    items: [{
                        border: false,
                        itemId: 'item',
                        xtype: 'panel',
                        title: 'Lots of Spanning',
                        html: '<div style="width: 100px;"></div>'
                    }]
                }
            });

            // Tolerate 100-104 range due to browser diffs
            expect(ct.down('#item').getWidth()).toBeGreaterThan(99);
            expect(ct.down('#item').getWidth()).toBeLessThan(105);
            ct.destroy();
       });
    });
});
