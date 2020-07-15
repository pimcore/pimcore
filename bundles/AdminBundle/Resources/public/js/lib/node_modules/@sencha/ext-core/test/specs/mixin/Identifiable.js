topSuite("Ext.mixin.Identifiable", function() {
    var TestClass;

    beforeEach(function() {
        TestClass = new Ext.Class({
            mixins: [Ext.mixin.Identifiable]
        });
    });

    describe("getId()", function() {
        it("should return a unique id and cache it", function() {
            var foo = new TestClass(),
                id1 = foo.getId(),
                id2 = foo.getId();

            expect(id1).toBe(id2);
        });

        it("should return unique ids", function() {
            var foo = new TestClass(),
                bar = new TestClass(),
                id1 = foo.getId(),
                id2 = bar.getId();

            expect(id1).not.toEqual(id2);
        });
    });
});
