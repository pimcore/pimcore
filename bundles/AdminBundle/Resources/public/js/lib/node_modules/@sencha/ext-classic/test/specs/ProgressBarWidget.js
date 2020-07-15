topSuite("Ext.ProgressBarWidget", function() {
    var c;

    function makeProgress(config) {
        c = new Ext.ProgressBarWidget(Ext.apply({
            renderTo: Ext.getBody(),
            width: 100
        }, config));
    }

    afterEach(function() {
        c = Ext.destroy(c);
    });

    describe("setValue", function() {
        it("should cast undefined to 0", function() {
            makeProgress({
                value: 50
            });
            c.setValue(undefined);
            expect(c.getValue()).toBe(0);
        });

        it("should cast null to 0", function() {
            makeProgress({
                value: 50
            });
            c.setValue(null);
            expect(c.getValue()).toBe(0);
        });
    });
});
