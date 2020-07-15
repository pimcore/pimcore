topSuite("Ext.app.Util", function() {
    it("has namespaces property", function() {
        expect(Ext.app.namespaces).toBeDefined();
    });

    it("adds single namespace to known list", function() {
        Ext.app.addNamespaces('Foo');

        expect(Ext.app.namespaces['Foo']).toBeTruthy();
    });

    it("adds multiple namespaces to known list", function() {
        Ext.app.addNamespaces([
            'Foo.bar',
            'Foo.bar.baz',
            'Qux'
        ]);

        var ns = Ext.app.namespaces;

        expect(ns['Foo.bar']).toBeTruthy();
        // AND
        expect(ns['Foo.bar.baz']).toBeTruthy();
        // AND
        expect(ns['Qux']).toBeTruthy();
    });

    it("resolves namespace for a class", function() {
        var ns = Ext.app.getNamespace('Foo.Bar');

        expect(ns).toBe('Foo');
    });

    it("resolves nested namespace for a class", function() {
        var ns = Ext.app.getNamespace('Foo.bar.Baz');

        expect(ns).toBe('Foo.bar');
    });

    it("resolves even deeper nested namespace for a class", function() {
        var ns = Ext.app.getNamespace('Foo.bar.baz.Qux');

        expect(ns).toBe('Foo.bar.baz');
    });

    it("returns undefined when class belongs to unknown namespace", function() {
        var ns = Ext.app.getNamespace('foo.bar.baz.Qux');

        expect(ns).toBeUndefined();
    });

    it("clears namespaces", function() {
        Ext.app.clearNamespaces();

        var keys = Ext.Object.getKeys(Ext.app.namespaces);

        expect(keys).toEqual([]);
    });
});
