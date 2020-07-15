topSuite("Ext.app.domain.Component", ['Ext.menu.Menu'], function() {
    var panel, ctrl;

    beforeEach(function() {
        panel = new Ext.panel.Panel({
            renderTo: Ext.getBody(),

            width: 100,
            height: 100
        });

        ctrl = new Ext.app.Controller({
            id: 'foo'
        });
    });

    afterEach(function() {
        Ext.destroy(panel);
    });

    it("should ignore case on event names", function() {
        var handler = jasmine.createSpy('foo handler');

        ctrl.control({
            panel: {
                foo: handler
            }
        });

        panel.fireEvent('FOO');

        expect(handler).toHaveBeenCalled();
    });

    it("controls Component events with control() method", function() {
        var handler = jasmine.createSpy('foo handler');

        ctrl.control({
            panel: {
                foo: handler
            }
        });

        panel.fireEvent('foo');

        expect(handler).toHaveBeenCalled();
    });

    it("listens to Component events with listen() method", function() {
        var handler = jasmine.createSpy('bar handler');

        ctrl.listen({
            component: {
                panel: {
                    bar: handler
                }
            }
        });

        panel.fireEvent('bar');

        expect(handler).toHaveBeenCalled();
    });

    describe('looking up a menu as the direct child of a menu item', function() {
        var handler, menu;

        beforeEach(function() {
            handler = jasmine.createSpy('foo handler');

            menu = new Ext.menu.Menu({
                width: 100,
                items: [{
                    itemId: 'foobar',
                    menu: new Ext.menu.Menu({
                        id: 'childMenu',
                        items: [{
                            text: 'A'
                        }]
                    })
                }],
                renderTo: Ext.getBody()
            });
        });

        afterEach(function() {
            Ext.destroy(menu);
            handler = menu = null;
        });

        it('should find the owner of the menu as a descendant of the menu item', function() {
            ctrl.control({
                '#foobar menu': {
                    foo: handler
                }
            });

            Ext.getCmp('childMenu').fireEvent('foo');

            expect(handler).toHaveBeenCalled();
        });

        it('should find the owner of the menu as a direct child of the menu item', function() {
            ctrl.control({
                '#foobar > menu': {
                    foo: handler
                }
            });

            Ext.getCmp('childMenu').fireEvent('foo');

            expect(handler).toHaveBeenCalled();
        });
    });
});
