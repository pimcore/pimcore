topSuite("Ext.app.domain.Store", function() {
    var ctrl, storeFoo, storeBar, handlerFoo, handlerBar;

    beforeEach(function() {
        storeFoo = new Ext.data.Store({
            storeId: 'foo',

            fields: ['foo', 'bar']
        });

        storeBar = new Ext.data.Store({
            storeId: 'bar',

            fields: ['baz', 'qux']
        });

        handlerFoo = jasmine.createSpy('event handler foo');
        handlerBar = jasmine.createSpy('event handler bar');

        Ext.define('spec.CustomStore', {
            extend: 'Ext.data.Store',
            alias: 'store.customstore'
        });

        ctrl = new Ext.app.Controller({ id: 'foo' });
    });

    afterEach(function() {
        Ext.destroy(storeFoo, storeBar);
        ctrl.destroy();
        Ext.undefine('spec.CustomStore');
        ctrl = storeFoo = storeBar = null;
    });

    it("should ignore case on event names", function() {
        ctrl.listen({
            store: {
                '#foo': {
                    foo: handlerFoo
                }
            }
        });

        storeFoo.fireEvent('FOO');

        expect(handlerFoo).toHaveBeenCalled();
    });

    it("listens to Stores' events by #id", function() {
        ctrl.listen({
            store: {
                '#foo': {
                    foo: handlerFoo
                }
            }
        });

        storeFoo.fireEvent('foo');

        expect(handlerFoo).toHaveBeenCalled();
    });

    it("doesn't listen to other Stores' events when selector doesn't match", function() {
        ctrl.listen({
            store: {
                '#foo': {
                    bar: handlerFoo
                },
                '#bar': {
                    bar: handlerBar
                }
            }
        });

        storeBar.fireEvent('bar');

        expect(handlerBar).toHaveBeenCalled();
        // AND
        expect(handlerFoo).not.toHaveBeenCalled();
    });

    it("listens to all Stores' events when selector is '*'", function() {
        ctrl.listen({
            store: {
                '*': {
                    baz: handlerFoo
                }
            }
        });

        storeFoo.fireEvent('baz');
        storeBar.fireEvent('baz');

        expect(handlerFoo.callCount).toBe(2);
    });

    it("should listen by alias", function() {
        // Silence console warning about CustomStore being created with no model
        spyOn(Ext.log, 'warn');

        var s = new spec.CustomStore();

        ctrl.listen({
            store: {
                'customstore': {
                    baz: handlerFoo
                }
            }
        });

        s.fireEvent('baz');
        expect(handlerFoo).toHaveBeenCalled();
        s.destroy();
    });

    it("passes event arguments correctly", function() {
        var data = [{ foo: 1, bar: 2 }, { foo: 3, bar: 4 }];

        ctrl.listen({
            store: {
                '*': {
                    datachanged: handlerFoo
                }
            }
        });

        storeFoo.loadData(data);

        expect(handlerFoo).toHaveBeenCalledWith(storeFoo);
    });
});
