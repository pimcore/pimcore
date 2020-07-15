topSuite("Ext.app.EventBus", function() {
    var eventbus = Ext.app.EventBus;

    it("should be a singleton", function() {
        expect(Ext.app.EventBus.isInstance).toBeTruthy();
    });

    describe("register/unregister", function() {
        var cmpDomain = Ext.app.domain.Component,
            ctrlDomain = Ext.app.domain.Controller,
            ctrl = new Ext.app.Controller({ id: 'ctrl' }),
            handler = jasmine.createSpy('handler');

        it("should register controllers with control()", function() {
            eventbus.control({
                componentBar: {
                    eventBaz: handler
                }
            }, ctrl);

            expect(cmpDomain.bus.eventbaz.componentBar.ctrl).toBeDefined();
        });

        it("should register controllers with listen()", function() {
            eventbus.listen({
                controller: {
                    '#controllerQux': {
                        eventFred: handler
                    }
                }
            }, ctrl);

            expect(ctrlDomain.bus.eventfred['#controllerQux'].ctrl).toBeDefined();
        });

        it("should unregister controllers with unlisten()", function() {
            eventbus.unlisten('ctrl');

            expect(cmpDomain.bus.eventbaz.componentBar.ctrl).toBeUndefined();
            // AND
            expect(ctrlDomain.bus.eventfred['#controllerQux'].ctrl).toBeUndefined();
        });
    });
});
