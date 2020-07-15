topSuite("Ext.dom.Fly", function() {
    describe("attach", function() {
        var el, fly;

        beforeEach(function() {
            fly = new Ext.dom.Fly();
            el = Ext.getBody().createChild();
        });

        afterEach(function() {
            el = fly = Ext.destroy(el);
        });

        it("should attach to an id", function() {
            fly.attach(el.id);
            expect(fly.dom).toBe(el.dom);
        });

        it("should attach to a dom element", function() {
            fly.attach(el.dom);
            expect(fly.dom).toBe(el.dom);
        });

        it("should attach to an Ext element", function() {
            fly.attach(el);
            expect(fly.dom).toBe(el.dom);
        });
    });
});
