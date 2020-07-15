topSuite("Ext.app.domain.Direct", ['Ext.direct.*'], function() {
    var ctrl, provFoo, provBar, handlerFoo, handlerBar;

    beforeEach(function() {
        spyOn(Ext.Ajax, 'request').andReturn();

        provFoo = new Ext.direct.RemotingProvider({
            id: 'foo',
            url: '/foo'
        });

        provBar = new Ext.direct.PollingProvider({
            id: 'bar',
            url: '/bar'
        });

        handlerFoo = jasmine.createSpy('event handler foo');
        handlerBar = jasmine.createSpy('event handler bar');

        ctrl = new Ext.app.Controller({ id: 'foo' });
    });

    it("should ignore case on event names", function() {
        ctrl.listen({
            direct: {
                '#foo': {
                    foo: handlerFoo
                }
            }
        });

        provFoo.fireEvent('FOO');

        expect(handlerFoo).toHaveBeenCalled();
    });

    it("listens to Providers' events by #id", function() {
        ctrl.listen({
            direct: {
                '#foo': {
                    foo: handlerFoo
                }
            }
        });

        provFoo.fireEvent('foo');

        expect(handlerFoo).toHaveBeenCalled();
    });

    it("doesn't listen to other Providers' events when selector doesn't match", function() {
        ctrl.listen({
            direct: {
                '#foo': {
                    bar: handlerFoo
                },
                '#bar': {
                    bar: handlerBar
                }
            }
        });

        provBar.fireEvent('bar');

        expect(handlerBar).toHaveBeenCalled();
        // AND
        expect(handlerFoo).not.toHaveBeenCalled();
    });

    it("listens to all Providers' events when selector is '*'", function() {
        ctrl.listen({
            direct: {
                '*': {
                    baz: handlerFoo
                }
            }
        });

        provFoo.fireEvent('baz');
        provBar.fireEvent('baz');

        expect(handlerFoo.callCount).toBe(2);
    });

    it("passes event arguments correctly", function() {
        var data = {
            responseText: Ext.encode([{ type: 'event', name: 'foo', data: 'bar' }])
        };

        ctrl.listen({
            direct: {
                '*': {
                    data: handlerFoo
                }
            }
        });

        provBar.onData({}, true, data);

        expect(handlerFoo).toHaveBeenCalledWith(
            provBar,
            new Ext.direct.Event({
                type: 'event',
                name: 'foo',
                data: 'bar'
            })
        );
    });
});
