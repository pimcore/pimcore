topSuite("Ext.app.domain.Controller", ['Ext.app.Application'], function() {
    var ctrlFoo, ctrlBar, ctrlTest, handlerFoo, handlerBar;

    beforeEach(function() {
        Ext.define('spec.AliasController', {
            extend: 'Ext.app.Controller',
            alias: 'controller.test',
            'namespace': 'spec'
        });

        ctrlFoo = new Ext.app.Controller({ id: 'foo' });
        ctrlBar = new Ext.app.Controller({ id: 'bar' });
        ctrlTest = new spec.AliasController();

        handlerFoo = jasmine.createSpy('event handler foo');
        handlerBar = jasmine.createSpy('event handler bar');
    });

    afterEach(function() {
        Ext.undefine('spec.AliasController');
        ctrlTest = ctrlFoo = ctrlBar = handlerFoo = handlerBar = null;
    });

    it("should ignore case on event names", function() {
        ctrlFoo.listen({
            controller: {
                '#bar': {
                    foo: handlerFoo
                }
            }
        });

        ctrlBar.fireEvent('FOO');

        expect(handlerFoo).toHaveBeenCalled();
    });

    describe("id selector", function() {
        it("listens to other Controllers' events by #id", function() {
            ctrlFoo.listen({
                controller: {
                    '#bar': {
                        foo: handlerFoo
                    }
                }
            });

            ctrlBar.fireEvent('foo');

            expect(handlerFoo).toHaveBeenCalled();
        });

        it("doesn't listen to other Controllers' events when selector doesn't match", function() {
            ctrlFoo.listen({
                controller: {
                    '#foo': {
                        bar: handlerFoo
                    },
                    '#bar': {
                        bar: handlerBar
                    }
                }
            });

            ctrlFoo.fireEvent('bar');

            expect(handlerFoo).toHaveBeenCalled();
            // AND
            expect(handlerBar).not.toHaveBeenCalled();
        });
    });

    describe("alias selector", function() {
        it("should match based on alias", function() {
            ctrlFoo.listen({
                controller: {
                    'test': {
                        custom: handlerFoo
                    }
                }
            });
            ctrlTest.fireEvent('custom');
            expect(handlerFoo).toHaveBeenCalled();
        });

        it("should not listen when the alias does not match", function() {
            ctrlFoo.listen({
                controller: {
                    'other': {
                        custom: handlerFoo
                    }
                }
            });
            ctrlTest.fireEvent('custom');
            expect(handlerFoo).not.toHaveBeenCalled();
        });
    });

    describe("# selector", function() {
        var app;

        beforeEach(function() {
            app = new Ext.app.Application({
                name: 'ControllerDomainSpec'
            });
        });

        afterEach(function() {
            app.destroy();
            app = null;

            try {
                delete window.ControllerDomainSpec;
            }
            catch (e) {
                window.ControllerDomainSpec = undefined;
            }
        });

        it("should match an application", function() {
            ctrlFoo.listen({
                controller: {
                    '#': {
                        custom: handlerFoo
                    }
                }
            });
            app.fireEvent('custom');
            expect(handlerFoo).toHaveBeenCalled();
        });

        it("should not match a controller", function() {
            ctrlFoo.listen({
                controller: {
                    '#': {
                        custom: handlerFoo
                    }
                }
            });
            ctrlBar.fireEvent('custom');
            expect(handlerFoo).not.toHaveBeenCalled();
        });
    });

    describe("* selector", function() {
        it("listens to other Controllers' events when selector is '*'", function() {
            ctrlFoo.listen({
                controller: {
                    '*': {
                        baz: handlerFoo
                    }
                }
            });

            ctrlBar.fireEvent('baz');

            expect(handlerFoo).toHaveBeenCalled();
        });

        it("listens to its own events when selector is '*'", function() {
            ctrlFoo.listen({
                controller: {
                    '*': {
                        qux: handlerFoo
                    }
                }
            });

            ctrlFoo.fireEvent('qux');

            expect(handlerFoo).toHaveBeenCalled();
        });

        it("passes event arguments correctly", function() {
            ctrlFoo.listen({
                controller: {
                    '*': {
                        fred: handlerFoo
                    }
                }
            });

            ctrlBar.fireEvent('fred', 'foo', ['bar', 'baz']);

            expect(handlerFoo).toHaveBeenCalledWith('foo', ['bar', 'baz']);
        });
    });
});
