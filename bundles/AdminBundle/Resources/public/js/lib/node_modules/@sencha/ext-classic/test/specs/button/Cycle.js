topSuite("Ext.button.Cycle", ['Ext.app.ViewController'], function() {
    var button;

    function clickIt(event) {
        jasmine.fireMouseEvent(button.el.dom, event || 'click');
    }

    function makeButton(config) {
        // ARIA errors and warnings are expected
        spyOn(Ext.log, 'error');
        spyOn(Ext.log, 'warn');

        button = new Ext.button.Cycle(Ext.apply({
            text: 'Button',
            menu: {
                items: [{
                    text: 'Foo',
                    iconCls: 'iconFoo',
                    glyph: '100@FooFont'
                }, {
                    text: 'Bar',
                    iconCls: 'iconBar',
                    glyph: '200@BarFont'
                }, {
                    text: 'Baz',
                    iconCls: 'iconBaz',
                    glyph: '300@BazFont'
                }]
            }
        }, config));
    }

    afterEach(function() {
        Ext.destroy(button);
        button = null;
    });

    describe("alternate class name", function() {
        it("should have Ext.CycleButton as the alternate class name", function() {
            expect(Ext.button.Cycle.prototype.alternateClassName).toEqual("Ext.CycleButton");
        });

        it("should allow the use of Ext.CycleButton", function() {
            expect(Ext.CycleButton).toBeDefined();
        });
    });

    describe("event/handler", function() {
        var eventSpy, handlerSpy;

        beforeEach(function() {
            eventSpy = jasmine.createSpy();
            handlerSpy = jasmine.createSpy();
        });

        afterEach(function() {
            eventSpy = handlerSpy = null;
        });

        describe("during construction", function() {
            it("should not fire the event/handler with no activeItem", function() {
                makeButton({
                    listeners: {
                        change: eventSpy
                    },
                    changeHandler: handlerSpy
                });
                expect(eventSpy).not.toHaveBeenCalled();
                expect(handlerSpy).not.toHaveBeenCalled();
            });

            it("should not fire the event/handler with an activeItem", function() {
                makeButton({
                    activeItem: 1,
                    listeners: {
                        change: eventSpy
                    },
                    changeHandler: handlerSpy
                });
                expect(eventSpy).not.toHaveBeenCalled();
                expect(handlerSpy).not.toHaveBeenCalled();
            });
        });

        describe("arguments", function() {
            it("should pass the button and the active item", function() {
                makeButton({
                    listeners: {
                        change: eventSpy
                    },
                    changeHandler: handlerSpy
                });
                button.setActiveItem(1);
                expect(eventSpy.callCount).toBe(1);
                expect(eventSpy.mostRecentCall.args[0]).toBe(button);
                expect(eventSpy.mostRecentCall.args[1]).toBe(button.getMenu().items.getAt(1));

                expect(handlerSpy.callCount).toBe(1);
                expect(handlerSpy.mostRecentCall.args[0]).toBe(button);
                expect(handlerSpy.mostRecentCall.args[1]).toBe(button.getMenu().items.getAt(1));
            });
        });

        describe("suppressEvents", function() {
            it("should not fire if suppressEvents is passed", function() {
                makeButton({
                    listeners: {
                        change: eventSpy
                    },
                    changeHandler: handlerSpy
                });
                button.setActiveItem(1, true);
                expect(eventSpy).not.toHaveBeenCalled();
                expect(handlerSpy).not.toHaveBeenCalled();
            });
        });

        describe("scope", function() {
            it("should default the scope to the button", function() {
                makeButton({
                    changeHandler: handlerSpy
                });
                button.setActiveItem(1);
                expect(handlerSpy.mostRecentCall.object).toBe(button);
            });

            it("should use a passed scope", function() {
                var scope = {};

                makeButton({
                    changeHandler: handlerSpy,
                    scope: scope
                });
                button.setActiveItem(1);
                expect(handlerSpy.mostRecentCall.object).toBe(scope);
            });
        });

        it("should be able to resolve to a view controller", function() {
            var ctrl = new Ext.app.ViewController();

            ctrl.doSomething = jasmine.createSpy();
            makeButton({
                changeHandler: 'doSomething'
            });

            var ct = new Ext.container.Container({
                controller: ctrl,
                items: button
            });

            button = ct.items.first();
            button.setActiveItem(2);
            ct.destroy();
        });
    });

    describe("showText", function() {
        describe("with showText: false", function() {
            it("should show the button text", function() {
                makeButton({
                    showText: false
                });
                expect(button.getText()).toBe('Button');
                button.setActiveItem(1);
                expect(button.getText()).toBe('Button');
                button.setActiveItem(2);
                expect(button.getText()).toBe('Button');
            });

            it("should not prepend the prependText", function() {
                makeButton({
                    showText: false,
                    prependText: '!'
                });
                expect(button.getText()).toBe('Button');
            });
        });

        describe("with showText: true", function() {
            it("should show the active item text", function() {
                makeButton({
                    showText: true
                });
                expect(button.getText()).toBe('Foo');
                button.setActiveItem(1);
                expect(button.getText()).toBe('Bar');
                button.setActiveItem(2);
                expect(button.getText()).toBe('Baz');
            });

            it("should prepend the prependText", function() {
                makeButton({
                    showText: true,
                    prependText: '!'
                });
                expect(button.getText()).toBe('!Foo');
                button.setActiveItem(1);
                expect(button.getText()).toBe('!Bar');
                button.setActiveItem(2);
                expect(button.getText()).toBe('!Baz');
            });
        });
    });

    describe("forceIcon", function() {
        it("should show the active item iconCls by default", function() {
            makeButton();
            expect(button.iconCls).toBe('iconFoo');
        });

        it("should update the icon when the active item changes", function() {
            makeButton();
            button.setActiveItem(1);
            expect(button.iconCls).toBe('iconBar');
        });

        it("should use the forceIcon if specified", function() {
            makeButton({
                forceIcon: 'iconForce'
            });
            expect(button.iconCls).toBe('iconForce');
            button.setActiveItem(1);
            expect(button.iconCls).toBe('iconForce');
        });
    });

    describe("forceGlyph", function() {
        it("should show the active item glyph by default", function() {
            makeButton();
            expect(button.glyph.isEqual(Ext.Glyph.fly('100@FooFont'))).toBe(true);
        });

        it("should update the glyph when the active item changes", function() {
            makeButton();
            button.setActiveItem(1);
            expect(button.glyph.isEqual(Ext.Glyph.fly('200@BarFont'))).toBe(true);
        });

        it("should use the forceIcon if specified", function() {
            makeButton({
                forceGlyph: '400@ForceFont'
            });
            expect(button.glyph.isEqual(Ext.Glyph.fly('400@ForceFont'))).toBe(true);
            button.setActiveItem(1);
            expect(button.glyph.isEqual(Ext.Glyph.fly('400@ForceFont'))).toBe(true);
        });
    });
});
