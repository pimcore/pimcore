topSuite("Ext.app.EventDomain", function() {
    it("should register all EventDomain singletons", function() {
        var instances = Ext.app.EventDomain.instances;

        expect(instances).toEqual({
            global: Ext.app.domain.Global,
            component: Ext.app.domain.Component,
            controller: Ext.app.domain.Controller,
            store: Ext.app.domain.Store,
            direct: Ext.app.domain.Direct
        });
    });

    describe("should monitor default base classes for domains:", function() {
        it("Ext.Component for component domain", function() {
            expect(Ext.app.domain.Component.monitoredClasses).toEqual([Ext.Widget, Ext.Component]);
        });
    });
});
