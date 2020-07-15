topSuite("Ext.app.domain.Global", function() {
    var ctrl, panel, spy;

    beforeEach(function() {
        spy = jasmine.createSpy();
        ctrl = new Ext.app.Controller({ id: 'foo' });
    });

    afterEach(function() {
        spy = ctrl = panel = Ext.destroy(panel, ctrl);
    });

    it("should ignore case on event names", function() {
        ctrl.listen({
            global: {
                foo: spy
            }
        });

        Ext.GlobalEvents.fireEvent('FOO');

        expect(spy).toHaveBeenCalled();
    });

    it("should pass the event data & default the scope to the controller", function() {
        var data = [{ foo: 1, bar: 2 }, { foo: 3, bar: 4 }];

        ctrl.listen({
            global: {
                bar: spy
            }
        });

        Ext.GlobalEvents.fireEvent('bar', data);

        expect(spy.mostRecentCall.args[0]).toBe(data);
        expect(spy.mostRecentCall.object).toBe(ctrl);
    });

    it("should be able to listen over multiple listen calls", function() {
        var other = jasmine.createSpy();

        ctrl.listen({
            global: {
                foo: spy
            }
        });

        ctrl.listen({
            global: {
                bar: other
            }
        });

        Ext.GlobalEvents.fireEvent('foo');
        expect(spy.callCount).toBe(1);
        expect(other).not.toHaveBeenCalled();
        spy.reset();
        other.reset();
        Ext.GlobalEvents.fireEvent('bar');
        expect(other.callCount).toBe(1);
        expect(spy).not.toHaveBeenCalled();
    });

    it("should remove all listeners when the controller is destroyed", function() {
        ctrl.listen({
            global: {
                foo: spy
            }
        });

        ctrl.listen({
            global: {
                bar: spy
            }
        });

        ctrl.destroy();

        Ext.GlobalEvents.fireEvent('foo');
        Ext.GlobalEvents.fireEvent('bar');
        expect(spy).not.toHaveBeenCalled();
    });

    it("should only remove listeners for the controller on unlisten", function() {
        var ctrl2 = new Ext.app.Controller({ id: 'other' }),
            other = jasmine.createSpy();

        ctrl.listen({
            global: {
                foo: spy
            }
        });

        ctrl2.listen({
            global: {
                bar: other
            }
        });
        Ext.GlobalEvents.fireEvent('foo');
        Ext.GlobalEvents.fireEvent('bar');
        expect(spy.callCount).toBe(1);
        expect(other.callCount).toBe(1);
        ctrl2.destroy();
        spy.reset();
        other.reset();
        Ext.GlobalEvents.fireEvent('foo');
        Ext.GlobalEvents.fireEvent('bar');
        expect(spy.callCount).toBe(1);
        expect(other).not.toHaveBeenCalled();
    });
});
