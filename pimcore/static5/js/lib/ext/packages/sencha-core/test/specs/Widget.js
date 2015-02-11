describe("Ext.Widget", function() {
    var widget;

    function defineWidget(first, config) {
        Ext.define('spec.Widget', Ext.apply({
            extend: 'Ext.Widget'
        }, config));

        if (!first) {
            // The spec wants to run in "not first" mode - this means we need to create
            // an instance and throw it away, so that the spec operates on the second
            // instance of the Widget ever created.
            new spec.Widget();
        }
    }

    afterEach(function() {
        if (widget) {
            widget.destroy();
        }
        Ext.undefine('spec.Widget');
    });

    function makeSuite(first) {
        // most specs defined here need to run twice - once as the first instance of a
        // Widget that gets created, and once as the second instance. This is needed because
        // the first and second instances of a Ext.Widget go down significantly different
        // code paths.  The first instance creates the Element and caches it as a template
        // element that is cloned by successive instances.
        describe(first ? "first instance" : "second instance", function() {
            it("should have an element when 'element' reference is defined on the main element", function() {
                defineWidget(first);
                widget = new spec.Widget();
                expect(widget.element instanceof Ext.dom.Element).toBe(true);
                expect(widget.el).toBe(widget.element);
            });

            if (first) {
                // error thrown on first instance - no need to run these spec for the second instance
                it("should throw an error when no 'element' reference is defined", function() {
                    defineWidget(first, {
                        element: {}
                    });

                    function makeWidget() {
                        new spec.Widget();
                    }

                    expect(makeWidget).toThrow("No 'element' reference found in 'spec.Widget' template.");
                });

                it("should throw an error if multiple 'element' references are defined", function() {
                    defineWidget(first, {
                        element: {
                            reference: 'element',
                            children: [{
                                reference: 'element'
                            }]
                        }
                    });

                    function makeWidget() {
                        new spec.Widget();
                    }

                    expect(makeWidget).toThrow("Duplicate 'element' reference detected in 'spec.Widget' template.");
                });
            }

            it("should allow the 'element' reference to be a descendant of the main template element", function() {
                defineWidget(first, {
                    element: {
                        children: [{
                            cls: 'foo',
                            reference: 'element'
                        }]
                    }
                });

                widget = new spec.Widget();

                expect(widget.element.dom.className).toBe('foo');
            });

            it("should resolve element references, and remove the 'reference' attributes from the dom", function() {
                defineWidget(first, {
                    element: {
                        reference: 'element',
                        children: [{
                            cls: 'foo',
                            reference: 'foo',
                            children: [{
                                cls: 'baz',
                                reference: 'baz'
                            }]
                        }, {
                            cls: 'bar',
                            reference: 'bar'
                        }]
                    }
                });

                widget = new spec.Widget();

                expect(widget.foo instanceof Ext.dom.Element).toBe(true);
                expect(widget.foo.dom.className).toBe('foo');
                expect(widget.foo.dom.getAttribute('reference')).toBeNull();

                expect(widget.bar instanceof Ext.dom.Element).toBe(true);
                expect(widget.bar.dom.className).toBe('bar');
                expect(widget.bar.dom.getAttribute('reference')).toBeNull();

                expect(widget.baz instanceof Ext.dom.Element).toBe(true);
                expect(widget.baz.dom.className).toBe('baz');
                expect(widget.baz.dom.getAttribute('reference')).toBeNull();

                expect(widget.element.dom.getAttribute('reference')).toBeNull();
            });

            it("should set skipGarbageCollection on element references", function() {
                 defineWidget(first, {
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'foo'
                        }]
                    }
                });

                widget = new spec.Widget();

                expect(widget.element.skipGarbageCollection).toBe(true);
                expect(widget.foo.skipGarbageCollection).toBe(true);
            });

            it("should generate an id if not configured", function() {
                defineWidget(first);
                widget = new spec.Widget();

                expect(widget.id).toBeDefined();
                expect(widget.element.id).toBe(widget.id);
            });

            it("should use configured id", function() {
                var id = 'my-widget';

                defineWidget(first);
                widget = new spec.Widget({
                    id: id
                });

                expect(widget.id).toBe(id);
                expect(widget.element.id).toBe(id);
            });

            it("should add a listener to the main element", function() {
                var onClick = jasmine.createSpy();

                defineWidget(first, {
                    element: {
                        reference: 'element',
                        listeners: {
                            click: 'onClick'
                        }
                    },
                    onClick: onClick
                });

                widget = new spec.Widget();
                // must be in the document to receive events
                Ext.getBody().appendChild(widget.element);

                jasmine.fireMouseEvent(widget.element, 'click');

                expect(onClick).toHaveBeenCalled();
                expect(onClick.mostRecentCall.object).toBe(widget);

                widget.destroy();
            });

            it("should add listeners to child elements", function() {
                var fooScope, barScope, bazScope, jazzScope,
                    fooClick = jasmine.createSpy(),
                    barClick = jasmine.createSpy(),
                    bazClick = jasmine.createSpy(),
                    jazzClick = jasmine.createSpy();

                defineWidget(first, {
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'foo',
                            listeners: {
                                click: 'fooClick'
                            }
                        }, {
                            cls: 'bar',
                            reference: 'bar',
                            listeners: {
                                // Make sure scope is set correctly for object form
                                click: {
                                    fn: 'barClick'
                                }
                            },
                            children: [{
                                reference: 'baz',
                                listeners: {
                                    click: 'bazClick'
                                },
                                scope: {} // make sure this scope is ignored
                            }, {
                                reference: 'jazz',
                                listeners: {
                                    click: {
                                        fn: 'jazzClick',
                                        scope: {} // ignored - scope is always "this"
                                    }
                                }
                            }]
                        }]
                    },

                    fooClick: fooClick,

                    barClick: barClick,

                    bazClick: bazClick,

                    jazzClick: jazzClick
                });

                widget = new spec.Widget();
                // must be in the document to receive events
                Ext.getBody().appendChild(widget.element);

                jasmine.fireMouseEvent(widget.foo, 'click');
                expect(fooClick).toHaveBeenCalled();
                expect(fooClick.mostRecentCall.object).toBe(widget);

                jasmine.fireMouseEvent(widget.bar, 'click');
                expect(barClick).toHaveBeenCalled();
                expect(barClick.mostRecentCall.object).toBe(widget);

                jasmine.fireMouseEvent(widget.baz, 'click');
                expect(bazClick).toHaveBeenCalled();
                expect(bazClick.mostRecentCall.object).toBe(widget);

                jasmine.fireMouseEvent(widget.jazz, 'click');
                expect(jazzClick).toHaveBeenCalled();
                expect(jazzClick.mostRecentCall.object).toBe(widget);

                widget.destroy();
            });
        });
    }

    makeSuite(true);
    makeSuite(false);

    describe("listener scope resolution", function() {
        var spies, scopes, Widget, widget, Parent, parent, Grandparent, grandparent,
            Controller, ParentController, GrandparentController;

        function defineParent(cfg) {
            Parent = Ext.define(null, Ext.apply({
                extend: 'Ext.Container',
                onFoo: spies.parent
            }, cfg));
        }

        function defineGrandparent(cfg) {
            Grandparent = Ext.define(null, Ext.apply({
                extend: 'Ext.Container',
                onFoo: spies.grandparent
            }, cfg));
        }

        function expectScope(scope) {
            var scopes = {
                    widget: widget,
                    controller: widget.getController(),
                    parent: parent,
                    parentController: parent && parent.getController(),
                    grandparent: grandparent,
                    grandparentController: grandparent && grandparent.getController()
                },
                name, spy;

            for (name in spies) {
                spy = spies[name];

                if (name === scope) {
                    expect(spy).toHaveBeenCalled();
                    expect(spy.mostRecentCall.object).toBe(scopes[name]);
                } else {
                    expect(spy).not.toHaveBeenCalled();
                }
            }
        }

        beforeEach(function() {
            spies = {
                widget: jasmine.createSpy(),
                controller: jasmine.createSpy(),
                parent: jasmine.createSpy(),
                parentController: jasmine.createSpy(),
                grandparent: jasmine.createSpy(),
                grandparentController: jasmine.createSpy()
            };

            Controller = Ext.define(null, {
                extend: 'Ext.app.ViewController',
                onFoo: spies.controller
            });

            ParentController = Ext.define(null, {
                extend: 'Ext.app.ViewController',
                onFoo: spies.parentController
            });

            GrandparentController = Ext.define(null, {
                extend: 'Ext.app.ViewController',
                onFoo: spies.grandparentController
            });
        });

        afterEach(function() {
            if (widget) {
                widget.destroy();
            }
            if (parent) {
                parent.destroy();
            }
            if (grandparent) {
                grandparent.destroy();
            }
        });

        describe("listener declared on class body", function() {
            function defineWidget(cfg) {
                Widget = Ext.define(null, Ext.merge({
                    extend: 'Ext.Widget',
                    listeners: {
                        foo: 'onFoo'
                    },
                    onFoo: spies.widget
                }, cfg));
            }

            it("should resolve to the widget with unspecified scope", function() {
                defineWidget();
                widget = new Widget();
                widget.fireEvent('foo');
                expectScope('widget');
            });

            it("should fail with scope:'controller'", function() {
                defineWidget({
                    listeners: {
                        scope: 'controller'
                    }
                });
                widget = new Widget();
                expect(function() {
                    widget.fireEvent('foo');
                }).toThrow();
            });

            it("should resolve to the widget with scope:'this'", function() {
                defineWidget({
                    listeners: {
                        scope: 'this'
                    }
                });
                widget = new Widget();
                widget.fireEvent('foo');
                expectScope('widget');
            });

            describe("with view controller", function() {
                it("should resolve to the view controller with unspecified scope", function() {
                    defineWidget({
                        controller: new Controller()
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the view controller with scope:'controller'", function() {
                    defineWidget({
                        controller: new Controller(),
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        controller: new Controller(),
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with defaultListenerScope", function() {
                it("should resolve to the widget with unspecified scope", function() {
                    defineWidget({
                        defaultListenerScope: true
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expectScope('widget');
                });

                it("should fail with scope:'controller'", function() {
                    defineWidget({
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller and defaultListenerScope", function() {
                it("should resolve to the widget with unspecified scope", function() {
                    defineWidget({
                        controller: new Controller(),
                        defaultListenerScope: true
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expectScope('widget');
                });

                it("should resolve to the view controller with scope:'controller'", function() {
                    defineWidget({
                        controller: new Controller(),
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        controller: new Controller(),
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the parent with unspecified scope", function() {
                    defineWidget();
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parent');
                });

                it("should fail with scope:'controller'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });
                });

                it("should resolve to the parent view controller with unspecified scope", function() {
                    defineWidget();
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller and defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController(),
                        defaultListenerScope: true
                    })
                });

                it("should resolve to the parent with unspecified scope", function() {
                    defineWidget();
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parent');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with defaultListenerScope on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the grandparent with unspecified scope", function() {
                    defineWidget();
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparent');
                });

                it("should fail with scope:'controller'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController()
                    });
                });

                it("should resolve to the grandparent view controller with unspecified scope", function() {
                    defineWidget();
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller and defaultListenerScope on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController(),
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the grandparent with unspecified scope", function() {
                    defineWidget();
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparent');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on child and view controller on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });
                });

                it("should resolve to the child view controller with unspecified scope", function() {
                    defineWidget({
                        controller: new Controller()
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the child view controller with scope:'controller'", function() {
                    defineWidget({
                        controller: new Controller(),
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        controller: new Controller(),
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on child and view controller on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController()
                    });
                });

                it("should resolve to the child view controller with unspecified scope", function() {
                    defineWidget({
                        controller: new Controller()
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the child view controller with scope:'controller'", function() {
                    defineWidget({
                        controller: new Controller(),
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        controller: new Controller(),
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on child and defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the child view controller with unspecified scope", function() {
                    defineWidget({
                        controller: new Controller()
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the child view controller with scope:'controller'", function() {
                    defineWidget({
                        controller: new Controller(),
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        controller: new Controller(),
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on parent and defaultListenerScope on child", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });
                });

                it("should resolve to the widget with unspecified scope", function() {
                    defineWidget({
                        defaultListenerScope: true
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    defineWidget({
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on child and defaultListenerScope on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the child view controller with unspecified scope", function() {
                    defineWidget({
                        controller: new Controller()
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the child view controller with scope:'controller'", function() {
                    defineWidget({
                        controller: new Controller(),
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        controller: new Controller(),
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on grandparent and defaultListenerScope on child", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController()
                    });
                });

                it("should resolve to the widget with unspecified scope", function() {
                    defineWidget({
                        defaultListenerScope: true
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    defineWidget({
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    defineWidget({
                        defaultListenerScope: true,
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with scope declared on inner object", function() {
                it("should resolve to controller with unspecified outer scope", function() {
                    defineWidget({
                        defaultListenerScope: true,
                        controller: new Controller(),
                        listeners: {
                            foo: {
                                fn: 'onFoo',
                                scope: 'controller'
                            }
                        }
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expectScope('controller');
                });

                it("should resolve to controller with outer scope of controller", function() {
                    defineWidget({
                        defaultListenerScope: true,
                        controller: new Controller(),
                        listeners: {
                            scope: 'controller',
                            foo: {
                                fn: 'onFoo',
                                scope: 'controller'
                            }
                        }
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expectScope('controller');
                });
            });

            describe("with handler declared as a function reference", function() {
                var handler, scope;

                function defineWidget(cfg) {
                    Widget = Ext.define(null, Ext.merge({
                        extend: 'Ext.Widget',
                        listeners: {
                            foo: handler
                        }
                    }, cfg))
                }

                beforeEach(function() {
                    handler = jasmine.createSpy();
                    handler.andCallFake(function() {
                        scope = this;
                    });
                });

                afterEach(function() {
                    scope = null;
                });

                it("should use the widget as the default scope", function() {
                    defineWidget();
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expect(handler).toHaveBeenCalled();
                    expect(handler.mostRecentCall.object).toBe(widget);
                });

                it("should use an arbitrary object as the scope", function() {
                    var obj = {};

                    defineWidget({
                        listeners: {
                            scope: obj
                        }
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expect(scope).toBe(scope);
                });

                it("should use the widget with scope:'this'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'this'
                        }
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expect(scope).toBe(widget);
                });

                it("should fail with scope:'controller'", function() {
                    defineWidget({
                        listeners: {
                            scope: 'controller'
                        }
                    });
                    widget = new Widget();
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                it("should use the widget with scope:'this' specified on an inner object", function() {
                    defineWidget({
                        listeners: {
                            foo: {
                                fn: handler,
                                scope: 'this'
                            }
                        }
                    });
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expect(scope).toBe(widget);
                });

                it("should fail with scope:'controller' specified on an inner object", function() {
                    defineWidget({
                        listeners: {
                            foo: {
                                fn: handler,
                                scope: 'controller'
                            }
                        }
                    });
                    widget = new Widget();
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                describe("with view controller", function() {
                    it("should resolve to the widget with unspecified scope", function() {
                        defineWidget({
                            controller: new Controller()
                        });
                        widget = new Widget();
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });

                    it("should resolve to the view controller with scope:'controller'", function() {
                        defineWidget({
                            controller: new Controller(),
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        widget = new Widget();
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget.getController());
                    });

                    it("should resolve to the widget with scope:'this'", function() {
                        defineWidget({
                            controller: new Controller(),
                            listeners: {
                                scope: 'this'
                            }
                        });
                        widget = new Widget();
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });
                });

                describe("with defaultListenerScope", function() {
                    it("should resolve to the widget with unspecified scope", function() {
                        defineWidget({
                            defaultListenerScope: true
                        });
                        widget = new Widget();
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });

                    it("should fail with scope:'controller'", function() {
                        defineWidget({
                            defaultListenerScope: true,
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        widget = new Widget();
                        expect(function() {
                            widget.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the widget with scope:'this'", function() {
                        defineWidget({
                            defaultListenerScope: true,
                            listeners: {
                                scope: 'this'
                            }
                        });
                        widget = new Widget();
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });
                });

                describe("with view controller and defaultListenerScope", function() {
                    it("should resolve to the widget with unspecified scope", function() {
                        defineWidget({
                            controller: new Controller(),
                            defaultListenerScope: true
                        });
                        widget = new Widget();
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });

                    it("should resolve to the view controller with scope:'controller'", function() {
                        defineWidget({
                            controller: new Controller(),
                            defaultListenerScope: true,
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        widget = new Widget();
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget.getController());
                    });

                    it("should resolve to the widget with scope:'this'", function() {
                        defineWidget({
                            controller: new Controller(),
                            defaultListenerScope: true,
                            listeners: {
                                scope: 'this'
                            }
                        });
                        widget = new Widget();
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });
                });

                describe("with defaultListenerScope on parent", function() {
                    beforeEach(function() {
                        defineParent({
                            defaultListenerScope: true
                        });
                    });

                    it("should resolve to the widget with unspecified scope", function() {
                        defineWidget();
                        widget = new Widget();
                        parent = new Parent({
                            items: widget
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });

                    it("should fail with scope:'controller'", function() {
                        defineWidget({
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        widget = new Widget();
                        parent = new Parent({
                            items: widget
                        });
                        expect(function() {
                            widget.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the widget with scope:'this'", function() {
                        defineWidget({
                            listeners: {
                                scope: 'this'
                            }
                        });
                        widget = new Widget();
                        parent = new Parent({
                            items: widget
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });
                });

                describe("with view controller on parent", function() {
                    beforeEach(function() {
                        defineParent({
                            controller: new ParentController()
                        });
                    });

                    it("should resolve to the widget with unspecified scope", function() {
                        defineWidget();
                        widget = new Widget();
                        parent = new Parent({
                            items: widget
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });

                    it("should resolve to the parent view controller with scope:'controller'", function() {
                        defineWidget({
                            listeners: {
                                scope: 'controller'
                            }
                        });
                        widget = new Widget();
                        parent = new Parent({
                            items: widget
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(parent.getController());
                    });

                    it("should resolve to the widget with scope:'this'", function() {
                        defineWidget({
                            listeners: {
                                scope: 'this'
                            }
                        });
                        widget = new Widget();
                        parent = new Parent({
                            items: widget
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });
                });
            });
        });

        describe("listener declared on instance config", function() {
            function defineWidget(cfg) {
                Widget = Ext.define(null, Ext.merge({
                    extend: 'Ext.Widget',
                    onFoo: spies.widget
                }, cfg));
            }

            it("should resolve to the widget with unspecified scope", function() {
                defineWidget();
                widget = new Widget({
                    listeners: {
                        foo: 'onFoo'
                    }
                });
                widget.fireEvent('foo');
                expectScope('widget');
            });

            it("should fail with scope:'controller'", function() {
                defineWidget();
                widget = new Widget({
                    listeners: {
                        foo: 'onFoo',
                        scope: 'controller'
                    }
                });
                expect(function() {
                    widget.fireEvent('foo');
                }).toThrow();
            });

            it("should resolve to the widget with scope:'this'", function() {
                defineWidget();
                widget = new Widget({
                    listeners: {
                        foo: 'onFoo',
                        scope: 'this'
                    }
                });
                widget.fireEvent('foo');
                expectScope('widget');
            });

            describe("with view controller", function() {
                beforeEach(function() {
                    defineWidget({
                        controller: new Controller()
                    });
                });

                it("should resolve to the widget with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });

                it("should fail with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with defaultListenerScope", function() {
                beforeEach(function() {
                    defineWidget({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the widget with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });

                it("should fail with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller and defaultListenerScope", function() {
                beforeEach(function() {
                    defineWidget({
                        controller: new Controller(),
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the widget with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });

                it("should fail with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        defaultListenerScope: true
                    });
                    defineWidget();
                });

                it("should resolve to the parent with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parent');
                });

                it("should fail with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });
                    defineWidget();
                });

                it("should resolve to the parent view controller with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller and defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController(),
                        defaultListenerScope: true
                    });
                    defineWidget();
                });

                it("should resolve to the parent with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parent');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with defaultListenerScope on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        defaultListenerScope: true
                    });
                    defineWidget();
                });

                it("should resolve to the grandparent with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparent');
                });

                it("should fail with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController()
                    });
                    defineWidget();
                });

                it("should resolve to the grandparent view controller with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller and defaultListenerScope on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController(),
                        defaultListenerScope: true
                    });
                    defineWidget();
                });

                it("should resolve to the grandparent with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparent');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on child and view controller on parent", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });

                    defineWidget({
                        controller: new Controller()
                    });
                });

                it("should resolve to the parent view controller with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on child and view controller on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController()
                    });

                    defineWidget({
                        controller: new Controller()
                    });
                });

                it("should resolve to the grandparent view controller with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on child and defaultListenerScope on parent", function() {
                beforeEach(function() {
                    defineParent({
                        defaultListenerScope: true
                    });

                    defineWidget({
                        controller: new Controller()
                    });
                });

                it("should resolve to the parent with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parent');
                });

                it("should fail with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on parent and defaultListenerScope on child", function() {
                beforeEach(function() {
                    defineParent({
                        controller: new ParentController()
                    });

                    defineWidget({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the parent view controller with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the parent view controller with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('parentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    parent = new Parent({
                        items: widget
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on child and defaultListenerScope on grandparent", function() {
                beforeEach(function() {
                    defineGrandparent({
                        defaultListenerScope: true
                    });

                    defineWidget({
                        controller: new Controller()
                    });
                });

                it("should resolve to the grandparent with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparent');
                });

                it("should fail with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with view controller on grandparent and defaultListenerScope on child", function() {
                beforeEach(function() {
                    defineGrandparent({
                        controller: new GrandparentController()
                    });

                    defineWidget({
                        defaultListenerScope: true
                    });
                });

                it("should resolve to the grandparent view controller with unspecified scope", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the grandparent view controller with scope:'controller'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'controller'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('grandparentController');
                });

                it("should resolve to the widget with scope:'this'", function() {
                    widget = new Widget({
                        listeners: {
                            foo: 'onFoo',
                            scope: 'this'
                        }
                    });
                    grandparent = new Grandparent({
                        items: {
                            items: widget
                        }
                    });
                    widget.fireEvent('foo');
                    expectScope('widget');
                });
            });

            describe("with handler declared as a function reference", function() {
                var handler, scope;

                function defineWidget(cfg) {
                    Widget = Ext.define(null, Ext.merge({
                        extend: 'Ext.Widget'
                    }, cfg));
                }

                beforeEach(function() {
                    handler = jasmine.createSpy();
                    handler.andCallFake(function() {
                        scope = this;
                    });
                });

                afterEach(function() {
                    scope = null;
                });

                it("should use the widget as the default scope", function() {
                    defineWidget();
                    widget = new Widget({
                        listeners: {
                            foo: handler
                        }
                    });
                    widget.fireEvent('foo');
                    expect(scope).toBe(widget);
                });

                it("should use an arbitrary object as the scope", function() {
                    defineWidget();
                    var scope = {};

                    widget = new Widget({
                        listeners: {
                            foo: handler,
                            scope: scope
                        }
                    });
                    widget.fireEvent('foo');
                    expect(scope).toBe(scope);
                });

                it("should use the widget with scope:'this'", function() {
                    defineWidget();
                    widget = new Widget({
                        listeners: {
                            foo: handler,
                            scope: 'this'
                        }
                    });
                    widget.fireEvent('foo');
                    expect(scope).toBe(widget);
                });

                it("should fail with scope:'controller'", function() {
                    defineWidget();
                    widget = new Widget({
                        listeners: {
                            foo: handler,
                            scope: 'controller'
                        }
                    });
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                it("should use the widget with scope:'this' specified on an inner object", function() {
                    defineWidget();
                    widget = new Widget({
                        listeners: {
                            foo: {
                                fn: handler,
                                scope: 'this'
                            }
                        }
                    });
                    widget.fireEvent('foo');
                    expect(scope).toBe(widget);
                });

                it("should fail with scope:'controller' specified on an inner object", function() {
                    defineWidget();
                    widget = new Widget({
                        listeners: {
                            foo: {
                                fn: handler,
                                scope: 'controller'
                            }
                        }
                    });
                    expect(function() {
                        widget.fireEvent('foo');
                    }).toThrow();
                });

                describe("with view controller", function() {
                    beforeEach(function() {
                        defineWidget({
                            controller: new Controller()
                        });
                    });

                    it("should resolve to the widget with unspecified scope", function() {
                        widget = new Widget({
                            listeners: {
                                foo: handler
                            }
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });

                    it("should fail with scope:'controller'", function() {
                        widget = new Widget({
                            listeners: {
                                foo: handler,
                                scope: 'controller'
                            }
                        });
                        expect(function() {
                            widget.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the widget with scope:'this'", function() {
                        widget = new Widget({
                            listeners: {
                                foo: handler,
                                scope: 'this'
                            }
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });
                });

                describe("with defaultListenerScope", function() {
                    beforeEach(function() {
                        defineWidget({
                            defaultListenerScope: true
                        });
                    });

                    it("should resolve to the widget with unspecified scope", function() {
                        widget = new Widget({
                            listeners: {
                                foo: handler
                            }
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });

                    it("should fail with scope:'controller'", function() {
                        widget = new Widget({
                            listeners: {
                                foo: handler,
                                scope: 'controller'
                            }
                        });
                        expect(function() {
                            widget.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the widget with scope:'this'", function() {
                        widget = new Widget({
                            listeners: {
                                foo: handler,
                                scope: 'this'
                            }
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });
                });

                describe("with defaultListenerScope on parent", function() {
                    beforeEach(function() {
                        defineParent({
                            defaultListenerScope: true
                        });
                        defineWidget();
                    });

                    it("should resolve to the widget with unspecified scope", function() {
                        widget = new Widget({
                            listeners: {
                                foo: handler
                            }
                        });
                        parent = new Parent({
                            items: widget
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });

                    it("should fail with scope:'controller'", function() {
                        widget = new Widget({
                            listeners: {
                                foo: handler,
                                scope: 'controller'
                            }
                        });
                        parent = new Parent({
                            items: widget
                        });
                        expect(function() {
                            widget.fireEvent('foo');
                        }).toThrow();
                    });

                    it("should resolve to the widget with scope:'this'", function() {
                        widget = new Widget({
                            listeners: {
                                foo: handler,
                                scope: 'this'
                            }
                        });
                        parent = new Parent({
                            items: widget
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });
                });

                describe("with view controller on parent", function() {
                    beforeEach(function() {
                        defineParent({
                            controller: new ParentController()
                        });
                        defineWidget();
                    });

                    it("should resolve to the widget with unspecified scope", function() {
                        widget = new Widget({
                            listeners: {
                                foo: handler
                            }
                        });
                        parent = new Parent({
                            items: widget
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });

                    it("should resolve to the parent view controller with scope:'controller'", function() {
                        widget = new Widget({
                            listeners: {
                                foo: handler,
                                scope: 'controller'
                            }
                        });
                        parent = new Parent({
                            items: widget
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(parent.getController());
                    });

                    it("should resolve to the widget with scope:'this'", function() {
                        widget = new Widget({
                            listeners: {
                                foo: handler,
                                scope: 'this'
                            }
                        });
                        parent = new Parent({
                            items: widget
                        });
                        widget.fireEvent('foo');
                        expect(scope).toBe(widget);
                    });
                });
            });
        });
    });
});
