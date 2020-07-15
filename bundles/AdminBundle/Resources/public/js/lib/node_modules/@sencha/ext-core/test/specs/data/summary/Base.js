topSuite("Ext.data.summary.Base", function() {
    it("should be able to pass a custom function", function() {
        var spy = jasmine.createSpy();

        var aggregator = Ext.data.summary.Base({
            calculate: spy
        });

        aggregator.calculate();
        expect(spy.callCount).toBe(1);
    });

    it("should be able to extend base with a custom function", function() {
        var spy = jasmine.createSpy();

        var T = Ext.define(null, {
            extend: 'Ext.data.summary.Base',
            calculate: spy
        });

        var aggregator = new T();

        aggregator.calculate();
        expect(spy.callCount).toBe(1);
    });
});
