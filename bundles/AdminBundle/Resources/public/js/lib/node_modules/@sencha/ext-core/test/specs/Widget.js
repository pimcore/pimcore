/* global Foo */
topSuite("Ext.Widget", ['Ext.app.ViewController', 'Ext.Container'], function() {
    var widget;

    function defineWidget(first, config) {
        Ext.define('spec.Widget', Ext.apply({
            extend: 'Ext.Widget'
        }, config));

        if (!first) {
            // The spec wants to run in "not first" mode - this means we need to create
            // an instance and throw it away, so that the spec operates on the second
            // instance of the Widget ever created.
            first = new spec.Widget();
            first.destroy();
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

                expect(widget.element).toHaveCls('foo');
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

            it("should be able to direct element listeners to controllers", function() {
                var C = Ext.define(null, {
                    extend: 'Ext.app.ViewController',
                    someFn: Ext.emptyFn
                });

                defineWidget(first, {
                    xtype: 'custom'
                });

                var controller = new C();

                var ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    controller: controller,
                    items: {
                        xtype: 'custom',
                        listeners: {
                            click: 'someFn',
                            element: 'element'
                        }
                    }
                });

                widget = ct.down('custom');

                spyOn(controller, 'someFn');
                jasmine.fireMouseEvent(widget.element, 'click');
                expect(controller.someFn.callCount).toBe(1);

                ct.destroy();
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
                                    click: 'bazClick',
                                    scope: {} // make sure this scope is ignored
                                }
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

    describe("element listener merging", function() {
        var SuperWidget, SubWidget, superWidget, subWidget;

        it("should not allow listeners declared in a subclass to pollute the superclass cache (no listeners on superclass)", function() {
            // https://sencha.jira.com/browse/EXTJS-16984
            SuperWidget = Ext.define(null, {
                extend: Ext.Widget
            });

            SubWidget = Ext.define(null, {
                extend: SuperWidget,
                element: {
                    reference: 'element',
                    listeners: {
                        click: 'onClick'
                    }
                },
                onClick: Ext.emptyFn
            });

            // create an instance of SuperWidget first so that its listener cache gets created
            superWidget = new SuperWidget();

            // SubWidget should create its own listener cache
            subWidget = new SubWidget();

            // SubWidget's listeners should not invade SuperWidget's cache
            expect(SuperWidget.prototype._elementListeners).toEqual({});
            // SubWidget should have its own cache
            expect(SubWidget.prototype.hasOwnProperty('_elementListeners')).toBe(true);

            Ext.destroy(subWidget, superWidget);
        });

        it("should not allow listeners declared in a subclass to pollute the superclass cache (with listeners on superclass)", function() {
            // https://sencha.jira.com/browse/EXTJS-16984
            SuperWidget = Ext.define(null, {
                extend: Ext.Widget,
                element: {
                    reference: 'element',
                    listeners: {
                        mousedown: 'onMouseDown'
                    }
                },
                onMouseDown: Ext.emptyFn
            });

            SubWidget = Ext.define(null, {
                extend: SuperWidget,
                element: {
                    reference: 'element',
                    listeners: {
                        click: 'onClick'
                    }
                },
                onClick: Ext.emptyFn
            });

            // create an instance of SuperWidget first so that its listener cache gets created
            superWidget = new SuperWidget();

            // SubWidget should create its own listener cache
            subWidget = new SubWidget();

            // SubWidget's listeners should not invade SuperWidget's cache
            expect(SuperWidget.prototype._elementListeners).toEqual({
                element: {
                    mousedown: 'onMouseDown'
                }
            });
            // SubWidget should have its own cache
            expect(SubWidget.prototype.hasOwnProperty('_elementListeners')).toBe(true);

            Ext.destroy(subWidget, superWidget);
        });

        describe("when first instance of superclass has already been created", function() {
            var superMouseDownSpy, superMouseUpSpy, superClickSpy,
                subMouseDownSpy, subMouseUpSpy, subClickSpy;

            beforeEach(function() {
                superMouseDownSpy = jasmine.createSpy();
                superMouseUpSpy = jasmine.createSpy();
                superClickSpy = jasmine.createSpy();

                subMouseDownSpy = jasmine.createSpy();
                subMouseUpSpy = jasmine.createSpy();
                subClickSpy = jasmine.createSpy();
            });

            afterEach(function() {
                if (superWidget) {
                    superWidget.destroy();
                }

                if (subWidget) {
                    subWidget.destroy();
                }
            });

            it("should merge subclass element listeners with superclass element listeners", function() {
                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        listeners: {
                            click: 'superOnClick',
                            mousedown: 'superOnMouseDown'
                        }
                    },
                    superOnClick: superClickSpy,
                    superOnMouseDown: superMouseDownSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget,
                    element: {
                        reference: 'element',
                        listeners: {
                            // inherits mousedown, overrides click, and adds mouseup
                            click: 'subOnClick',
                            mouseup: 'subOnMouseUp'
                        }
                    },
                    subOnClick: subClickSpy,
                    subOnMouseUp: subMouseUpSpy
                });

                // create an instance of SuperWidget first so that its listener cache gets created
                superWidget = new SuperWidget();

                subWidget = new SubWidget();

                Ext.getBody().appendChild(subWidget.element);

                jasmine.fireMouseEvent(subWidget.element, 'click');

                expect(superMouseDownSpy.callCount).toBe(1);
                expect(superClickSpy).not.toHaveBeenCalled();
                expect(subClickSpy.callCount).toBe(1);
                expect(subMouseUpSpy.callCount).toBe(1);
            });

            it("should inherit element listeners from superclass", function() {
                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        listeners: {
                            click: 'superOnClick'
                        }
                    },
                    superOnClick: superClickSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget
                });

                // create an instance of SuperWidget first so that its listener cache gets created
                superWidget = new SuperWidget();

                subWidget = new SubWidget();

                Ext.getBody().appendChild(subWidget.element);

                jasmine.fireMouseEvent(subWidget.element, 'click');

                expect(superClickSpy.callCount).toBe(1);
            });

            it("should merge subclass child element listeners with superclass child element listeners", function() {
                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'foo',
                            listeners: {
                                click: 'superOnClick',
                                mousedown: 'superOnMouseDown'
                            }
                        }]
                    },
                    superOnClick: superClickSpy,
                    superOnMouseDown: superMouseDownSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget,
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'foo',
                            listeners: {
                                // inherits mousedown, overrides click, and adds mouseup
                                click: 'subOnClick',
                                mouseup: 'subOnMouseUp'
                            }
                        }]
                    },
                    subOnClick: subClickSpy,
                    subOnMouseUp: subMouseUpSpy
                });

                // create an instance of SuperWidget first so that its listener cache gets created
                superWidget = new SuperWidget();

                subWidget = new SubWidget();

                Ext.getBody().appendChild(subWidget.element);

                jasmine.fireMouseEvent(subWidget.foo, 'click');

                expect(superMouseDownSpy.callCount).toBe(1);
                expect(superClickSpy).not.toHaveBeenCalled();
                expect(subClickSpy.callCount).toBe(1);
                expect(subMouseUpSpy.callCount).toBe(1);
            });

            it("should inherit child element listeners from superclass", function() {
                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'foo',
                            listeners: {
                                click: 'superOnClick'
                            }
                        }]
                    },
                    superOnClick: superClickSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget
                });

                // create an instance of SuperWidget first so that its listener cache gets created
                superWidget = new SuperWidget();

                subWidget = new SubWidget();

                Ext.getBody().appendChild(subWidget.element);

                jasmine.fireMouseEvent(subWidget.foo, 'click');

                expect(superClickSpy.callCount).toBe(1);
            });

            it("should add listeners to subclass child elements that do not have a corresponding reference in the superclass template", function() {
                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'foo',
                            listeners: {
                                click: 'superOnClick'
                            }
                        }]
                    },
                    superOnClick: superClickSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget,
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'bar',
                            listeners: {
                                click: 'subOnClick'
                            }
                        }]
                    },
                    subOnClick: subClickSpy
                });

                // create an instance of SuperWidget first so that its listener cache gets created
                superWidget = new SuperWidget();

                subWidget = new SubWidget();

                Ext.getBody().appendChild(subWidget.element);

                jasmine.fireMouseEvent(subWidget.bar, 'click');

                expect(superClickSpy.callCount).toBe(0);
                expect(subClickSpy.callCount).toBe(1);
            });

            it("should merge subclass element listeners with superclass element listeners (multiple levels of inheritance)", function() {
                var mouseUpSpy = jasmine.createSpy(),
                    Widget;

                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        listeners: {
                            click: 'superOnClick',
                            mousedown: 'superOnMouseDown'
                        }
                    },
                    superOnClick: superClickSpy,
                    superOnMouseDown: superMouseDownSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget,
                    element: {
                        reference: 'element',
                        listeners: {
                            // inherits mousedown, overrides click, and adds mouseup
                            click: 'subOnClick',
                            mouseup: 'subOnMouseUp'
                        }
                    },
                    subOnClick: subClickSpy,
                    subOnMouseUp: subMouseUpSpy
                });

                Widget = Ext.define(null, {
                    extend: SubWidget,
                    element: {
                        reference: 'element',
                        listeners: {
                            // overrides mouseup, inherits click/mousedown
                            mouseup: 'onMouseUp'
                        }
                    },
                    onMouseUp: mouseUpSpy
                });

                // create an instance of SuperWidget/SubWidget first so that their listener caches get created
                superWidget = new SuperWidget();
                subWidget = new SubWidget();
                widget = new Widget();

                Ext.getBody().appendChild(widget.element);

                jasmine.fireMouseEvent(widget.element, 'click');

                expect(superMouseDownSpy.callCount).toBe(1);
                expect(superClickSpy).not.toHaveBeenCalled();
                expect(subClickSpy.callCount).toBe(1);
                expect(subMouseUpSpy).not.toHaveBeenCalled();
                expect(mouseUpSpy.callCount).toBe(1);
            });

            it("should inherit child element listeners from superclass over multiple inheritance levels", function() {
                var Widget;

                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'foo',
                            listeners: {
                                click: 'superOnClick'
                            }
                        }]
                    },
                    superOnClick: superClickSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget
                });

                Widget = Ext.define(null, {
                    extend: SubWidget
                });

                // create an instance of SuperWidget/SubWidget first so that their listener caches get created
                superWidget = new SuperWidget();
                subWidget = new SubWidget();

                widget = new Widget();

                Ext.getBody().appendChild(widget.element);

                jasmine.fireMouseEvent(widget.foo, 'click');

                expect(superClickSpy.callCount).toBe(1);
            });
        });

        describe("when first instance of superclass has not yet been created", function() {
            var superMouseDownSpy, superMouseUpSpy, superClickSpy,
                subMouseDownSpy, subMouseUpSpy, subClickSpy;

            beforeEach(function() {
                superMouseDownSpy = jasmine.createSpy();
                superMouseUpSpy = jasmine.createSpy();
                superClickSpy = jasmine.createSpy();

                subMouseDownSpy = jasmine.createSpy();
                subMouseUpSpy = jasmine.createSpy();
                subClickSpy = jasmine.createSpy();
            });

            afterEach(function() {
                if (superWidget) {
                    superWidget.destroy();
                }

                if (subWidget) {
                    subWidget.destroy();
                }
            });

            it("should merge subclass element listeners with superclass element listeners", function() {
                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        listeners: {
                            click: 'superOnClick',
                            mousedown: 'superOnMouseDown'
                        }
                    },
                    superOnClick: superClickSpy,
                    superOnMouseDown: superMouseDownSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget,
                    element: {
                        reference: 'element',
                        listeners: {
                            // inherits mousedown, overrides click, and adds mouseup
                            click: 'subOnClick',
                            mouseup: 'subOnMouseUp'
                        }
                    },
                    subOnClick: subClickSpy,
                    subOnMouseUp: subMouseUpSpy
                });

                subWidget = new SubWidget();

                Ext.getBody().appendChild(subWidget.element);

                jasmine.fireMouseEvent(subWidget.element, 'click');

                expect(superMouseDownSpy.callCount).toBe(1);
                expect(superClickSpy).not.toHaveBeenCalled();
                expect(subClickSpy.callCount).toBe(1);
                expect(subMouseUpSpy.callCount).toBe(1);
            });

            it("should inherit element listeners from superclass", function() {
                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        listeners: {
                            click: 'superOnClick'
                        }
                    },
                    superOnClick: superClickSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget
                });

                subWidget = new SubWidget();

                Ext.getBody().appendChild(subWidget.element);

                jasmine.fireMouseEvent(subWidget.element, 'click');

                expect(superClickSpy.callCount).toBe(1);
            });

            it("should merge subclass child element listeners with superclass child element listeners", function() {
                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'foo',
                            listeners: {
                                click: 'superOnClick',
                                mousedown: 'superOnMouseDown'
                            }
                        }]
                    },
                    superOnClick: superClickSpy,
                    superOnMouseDown: superMouseDownSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget,
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'foo',
                            listeners: {
                                // inherits mousedown, overrides click, and adds mouseup
                                click: 'subOnClick',
                                mouseup: 'subOnMouseUp'
                            }
                        }]
                    },
                    subOnClick: subClickSpy,
                    subOnMouseUp: subMouseUpSpy
                });

                subWidget = new SubWidget();

                Ext.getBody().appendChild(subWidget.element);

                jasmine.fireMouseEvent(subWidget.foo, 'click');

                expect(superMouseDownSpy.callCount).toBe(1);
                expect(superClickSpy).not.toHaveBeenCalled();
                expect(subClickSpy.callCount).toBe(1);
                expect(subMouseUpSpy.callCount).toBe(1);
            });

            it("should inherit child element listeners from superclass", function() {
                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'foo',
                            listeners: {
                                click: 'superOnClick'
                            }
                        }]
                    },
                    superOnClick: superClickSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget
                });

                subWidget = new SubWidget();

                Ext.getBody().appendChild(subWidget.element);

                jasmine.fireMouseEvent(subWidget.foo, 'click');

                expect(superClickSpy.callCount).toBe(1);
            });

            it("should add listeners to subclass child elements that do not have a corresponding reference in the superclass template", function() {
                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'foo',
                            listeners: {
                                click: 'superOnClick'
                            }
                        }]
                    },
                    superOnClick: superClickSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget,
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'bar',
                            listeners: {
                                click: 'subOnClick'
                            }
                        }]
                    },
                    subOnClick: subClickSpy
                });

                subWidget = new SubWidget();

                Ext.getBody().appendChild(subWidget.element);

                jasmine.fireMouseEvent(subWidget.bar, 'click');

                expect(superClickSpy.callCount).toBe(0);
                expect(subClickSpy.callCount).toBe(1);
            });

            it("should merge subclass element listeners with superclass element listeners (multiple levels of inheritance)", function() {
                var mouseUpSpy = jasmine.createSpy(),
                    Widget;

                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        listeners: {
                            click: 'superOnClick',
                            mousedown: 'superOnMouseDown'
                        }
                    },
                    superOnClick: superClickSpy,
                    superOnMouseDown: superMouseDownSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget,
                    element: {
                        reference: 'element',
                        listeners: {
                            // inherits mousedown, overrides click, and adds mouseup
                            click: 'subOnClick',
                            mouseup: 'subOnMouseUp'
                        }
                    },
                    subOnClick: subClickSpy,
                    subOnMouseUp: subMouseUpSpy
                });

                Widget = Ext.define(null, {
                    extend: SubWidget,
                    element: {
                        reference: 'element',
                        listeners: {
                            // overrides mouseup, inherits click/mousedown
                            mouseup: 'onMouseUp'
                        }
                    },
                    onMouseUp: mouseUpSpy
                });

                widget = new Widget();

                Ext.getBody().appendChild(widget.element);

                jasmine.fireMouseEvent(widget.element, 'click');

                expect(superMouseDownSpy.callCount).toBe(1);
                expect(superClickSpy).not.toHaveBeenCalled();
                expect(subClickSpy.callCount).toBe(1);
                expect(subMouseUpSpy).not.toHaveBeenCalled();
                expect(mouseUpSpy.callCount).toBe(1);
            });

            it("should inherit child element listeners from superclass over multiple inheritance levels", function() {
                var Widget;

                SuperWidget = Ext.define(null, {
                    extend: Ext.Widget,
                    element: {
                        reference: 'element',
                        children: [{
                            reference: 'foo',
                            listeners: {
                                click: 'superOnClick'
                            }
                        }]
                    },
                    superOnClick: superClickSpy
                });

                SubWidget = Ext.define(null, {
                    extend: SuperWidget
                });

                Widget = Ext.define(null, {
                    extend: SubWidget
                });

                widget = new Widget();

                Ext.getBody().appendChild(widget.element);

                jasmine.fireMouseEvent(widget.foo, 'click');

                expect(superClickSpy.callCount).toBe(1);
            });
        });
    });

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
                }
                else {
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
            widget = parent = grandparent = Ext.destroy(widget, parent, grandparent);
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

                function defineWidget(cfg, setScope) {
                    cfg = Ext.merge({
                        extend: 'Ext.Widget',
                        listeners: {
                            foo: handler
                        }
                    }, cfg);

                    if (setScope) {
                        cfg.listeners.scope = setScope;
                    }

                    Widget = Ext.define(null, cfg);
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

                    defineWidget({}, obj);
                    widget = new Widget();
                    widget.fireEvent('foo');
                    expect(scope).toBe(obj);
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
                    var obj = {};

                    widget = new Widget({
                        listeners: {
                            foo: handler,
                            scope: obj
                        }
                    });
                    widget.fireEvent('foo');
                    expect(scope).toBe(obj);
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

    (Ext.supports.TouchAction === 15 ? describe : xdescribe)("touchAction", function() {
        var Widget, widget;

        function makeWidgetWithTouchAction(touchAction) {
            Widget = Ext.define(null, {
                extend: 'Ext.Widget',
                element: {
                    reference: 'element',
                    children: [{
                        reference: 'child'
                    }]
                }
            });
            widget = new Widget({
                touchAction: touchAction
            });
        }

        function expectTouchAction(el, value) {
            // touch actions read from the dom are not always returned in the same order
            // as they were set, so we have to parse theme out.
            var expectedTouchAction = el.getStyle('touch-action').split(' '),
                actualTouchAction = value.split(' ');

            expect(actualTouchAction.length).toBe(expectedTouchAction.length);

            Ext.each(expectedTouchAction, function(item) {
                expect(Ext.Array.contains(actualTouchAction, item)).toBe(true);
            });
        }

        afterEach(function() {
            if (widget) {
                widget.destroy();
                widget = null;
            }
        });

        it("should default to auto", function() {
            makeWidgetWithTouchAction(null);

            expectTouchAction(widget.element, 'auto');
            expectTouchAction(widget.child, 'auto');
        });

        it("should disable panX", function() {
            makeWidgetWithTouchAction({
                panX: false
            });

            expectTouchAction(widget.element, 'pan-y pinch-zoom double-tap-zoom');
        });

        it("should disable panY", function() {
            makeWidgetWithTouchAction({
                panY: false
            });

            expectTouchAction(widget.element, 'pan-x pinch-zoom double-tap-zoom');
        });

        it("should disable panX and panY", function() {
            makeWidgetWithTouchAction({
                panX: false,
                panY: false
            });

            expectTouchAction(widget.element, 'pinch-zoom double-tap-zoom');
        });

        it("should disable pinchZoom", function() {
            makeWidgetWithTouchAction({
                pinchZoom: false
            });

            expectTouchAction(widget.element, 'pan-x pan-y double-tap-zoom');
        });

        it("should disable panX and pinchZoom", function() {
            makeWidgetWithTouchAction({
                panX: false,
                pinchZoom: false
            });

            expectTouchAction(widget.element, 'pan-y double-tap-zoom');
        });

        it("should disable panY and pinchZoom", function() {
            makeWidgetWithTouchAction({
                panY: false,
                pinchZoom: false
            });

            expectTouchAction(widget.element, 'pan-x double-tap-zoom');
        });

        it("should disable panX, panY, and PinchZoom", function() {
            makeWidgetWithTouchAction({
                panX: false,
                panY: false,
                pinchZoom: false
            });

            expectTouchAction(widget.element, 'double-tap-zoom');
        });

        it("should disable doubleTapZoom", function() {
            makeWidgetWithTouchAction({
                doubleTapZoom: false
            });

            expectTouchAction(widget.element, 'pan-x pan-y pinch-zoom');
        });

        it("should disable panX and doubleTapZoom", function() {
            makeWidgetWithTouchAction({
                panX: false,
                doubleTapZoom: false
            });

            expectTouchAction(widget.element, 'pan-y pinch-zoom');
        });

        it("should disable panY and doubleTapZoom", function() {
            makeWidgetWithTouchAction({
                panY: false,
                doubleTapZoom: false
            });

            expectTouchAction(widget.element, 'pan-x pinch-zoom');
        });

        it("should disable panX, panY, and doubleTapZoom", function() {
            makeWidgetWithTouchAction({
                panX: false,
                panY: false,
                doubleTapZoom: false
            });

            expectTouchAction(widget.element, 'pinch-zoom');
        });

        it("should disable pinchZoom and doubleTapZoom", function() {
            makeWidgetWithTouchAction({
                pinchZoom: false,
                doubleTapZoom: false
            });

            expectTouchAction(widget.element, 'pan-x pan-y');
        });

        it("should disable panX, pinchZoom, and doubleTapZoom", function() {
            makeWidgetWithTouchAction({
                panX: false,
                pinchZoom: false,
                doubleTapZoom: false
            });

            expectTouchAction(widget.element, 'pan-y');
        });

        it("should disable panY, pinchZoom, and doubleTapZoom", function() {
            makeWidgetWithTouchAction({
                panY: false,
                pinchZoom: false,
                doubleTapZoom: false
            });

            expectTouchAction(widget.element, 'pan-x');
        });

        it("should disable all touch actions", function() {
            makeWidgetWithTouchAction({
                panX: false,
                panY: false,
                pinchZoom: false,
                doubleTapZoom: false
            });

            expectTouchAction(widget.element, 'none');
        });

        it("should set touch action on a reference element", function() {
            makeWidgetWithTouchAction({
                panX: false,
                child: {
                    panY: false,
                    pinchZoom: false
                }
            });

            expectTouchAction(widget.element, 'pan-y pinch-zoom double-tap-zoom');
            expectTouchAction(widget.child, 'pan-x double-tap-zoom');
        });
    });

    describe("classCls", function() {
        it("should add the classCls to the element", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo'
            });

            widget = new Foo();

            expect(widget.element.dom.className).toBe('foo');
        });

        it("should inherit classCls from ancestors", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo'
            });

            var Bar = Ext.define(null, {
                extend: Foo,
                classCls: 'bar'
            });

            var Baz = Ext.define(null, {
                extend: Bar,
                classCls: 'baz'
            });

            widget = new Baz();

            expect(widget.element).toHaveCls('foo');
            expect(widget.element).toHaveCls('bar');
            expect(widget.element).toHaveCls('baz');
        });

        it("should allow the classCls to be changed via override", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo'
            });

            var Bar = Ext.define(null, {
                extend: Foo,
                classCls: 'bar'
            });

            var Baz = Ext.define(null, {
                extend: Bar,
                classCls: 'baz'
            });

            Foo.override({
                classCls: 'far'
            });

            widget = new Baz();

            expect(widget.element).toHaveCls('far');
            expect(widget.element).toHaveCls('bar');
            expect(widget.element).toHaveCls('baz');
        });

        it("should not inherit classCls if classClsRoot is true", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo'
            });

            var Bar = Ext.define(null, {
                extend: Foo,
                classCls: 'bar',
                classClsRoot: true
            });

            var Baz = Ext.define(null, {
                extend: Bar,
                classCls: 'baz'
            });

            widget = new Baz();

            expect(widget.element).not.toHaveCls('foo');
            expect(widget.element).toHaveCls('bar');
            expect(widget.element).toHaveCls('baz');
        });

        it("should not duplicate the classCls in the classClsList", function() {
            // This is more of an implementation detail, but it is important because the
            // classClsList is iterated elsewhere (e.g. border management in auto layout)
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo'
            });

            var Bar = Ext.define(null, {
                extend: Foo,
                classCls: 'bar'
            });

            var Baz = Ext.define(null, {
                extend: Bar
            });

            widget = new Baz();

            // Baz has no classCls, so it inherits classCls from bar via the prototype
            // We must ensure that classCls 'bar' is not repeated in the classCls list.
            expect(widget.classClsList).toEqual(['bar', 'foo']);
        });

        it("should add a UI reference after initialization", function() {
            // This is an unusual use case, but Toolable does this with its dock wrapper
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo',
                ui: 'you eye',
                doDestroy: function() {
                    this.callParent();
                    this.childEl.destroy();
                }
            });

            widget = new Foo();

            var childEl = widget.childEl = widget.element.append({
                cls: 'child-el'
            });

            widget.initUiReference('childEl', 'child');

            expect(childEl).toHaveCls('child-el foo-child foo-you-child foo-eye-child');
        });
    });

    describe("instanceCls", function() {
        it("should augment the classCls with a single instanceCls", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo',
                template: [{
                    reference: 'childEl',
                    cls: 'child-el',
                    uiCls: 'child'
                }]
            });

            var Bar = Ext.define(null, {
                extend: Foo,
                classCls: 'bar'
            });

            widget = new Bar({
                instanceCls: 'baz',
                ui: 'you eye'
            });

            expect(widget).toHaveCls('foo bar baz foo-you bar-you baz-you foo-eye bar-eye baz-eye');
            expect(widget.childEl).toHaveCls([
                'child-el', 'foo-child', 'foo-you-child', 'foo-eye-child',
                'bar-child', 'bar-you-child', 'bar-eye-child',
                'baz-child', 'baz-you-child', 'baz-eye-child'
            ]);
            expect(widget.classClsList.length).toBe(3);
            expect(Ext.Array.contains(widget.classClsList, 'foo')).toBe(true);
            expect(Ext.Array.contains(widget.classClsList, 'bar')).toBe(true);
            expect(Ext.Array.contains(widget.classClsList, 'baz')).toBe(true);
            // instanceCls should not modify the prototype
            expect(Bar.prototype.classClsList.length).toBe(2);
            expect(Ext.Array.contains(Bar.prototype.classClsList, 'foo')).toBe(true);
            expect(Ext.Array.contains(Bar.prototype.classClsList, 'bar')).toBe(true);
        });

        it("should augment the classCls with multiple instanceCls", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo',
                template: [{
                    reference: 'childEl',
                    cls: 'child-el',
                    uiCls: 'child'
                }]
            });

            var Bar = Ext.define(null, {
                extend: Foo,
                classCls: 'bar'
            });

            widget = new Bar({
                instanceCls: ['baz', 'jaz'],
                ui: 'you eye'
            });

            expect(widget).toHaveCls('foo bar baz jaz foo-you bar-you baz-you jaz-you foo-eye bar-eye baz-eye jaz-eye');
            expect(widget.childEl).toHaveCls([
                'child-el', 'foo-child', 'foo-you-child', 'foo-eye-child',
                'bar-child', 'bar-you-child', 'bar-eye-child',
                'baz-child', 'baz-you-child', 'baz-eye-child',
                'jaz-child', 'jaz-you-child', 'jaz-eye-child'
            ]);
            expect(widget.classClsList.length).toBe(4);
            expect(Ext.Array.contains(widget.classClsList, 'foo')).toBe(true);
            expect(Ext.Array.contains(widget.classClsList, 'bar')).toBe(true);
            expect(Ext.Array.contains(widget.classClsList, 'baz')).toBe(true);
            expect(Ext.Array.contains(widget.classClsList, 'jaz')).toBe(true);
            // instanceCls should not modify the prototype
            expect(Bar.prototype.classClsList.length).toBe(2);
            expect(Ext.Array.contains(Bar.prototype.classClsList, 'foo')).toBe(true);
            expect(Ext.Array.contains(Bar.prototype.classClsList, 'bar')).toBe(true);
        });

        it("should reconfigure and remove an old instanceCls", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo',
                template: [{
                    reference: 'childEl',
                    cls: 'child-el',
                    uiCls: 'child'
                }]
            });

            var Bar = Ext.define(null, {
                extend: Foo,
                classCls: 'bar'
            });

            widget = new Bar({
                instanceCls: 'baz',
                ui: 'you eye'
            });

            widget.setInstanceCls('jaz');

            expect(widget).toHaveCls('foo bar jaz foo-you bar-you jaz-you foo-eye bar-eye jaz-eye');
            expect(widget.childEl).toHaveCls([
                'child-el', 'foo-child', 'foo-you-child', 'foo-eye-child',
                'bar-child', 'bar-you-child', 'bar-eye-child',
                'jaz-child', 'jaz-you-child', 'jaz-eye-child'
            ]);
            expect(widget).not.toHaveCls('baz baz-you baz-eye');
            expect(widget.childEl).not.toHaveCls('baz-child baz-you-child baz-eye-child');
            expect(widget.classClsList.length).toBe(3);
            expect(Ext.Array.contains(widget.classClsList, 'foo')).toBe(true);
            expect(Ext.Array.contains(widget.classClsList, 'bar')).toBe(true);
            expect(Ext.Array.contains(widget.classClsList, 'jaz')).toBe(true);
            // instanceCls should not modify the prototype
            expect(Bar.prototype.classClsList.length).toBe(2);
            expect(Ext.Array.contains(Bar.prototype.classClsList, 'foo')).toBe(true);
            expect(Ext.Array.contains(Bar.prototype.classClsList, 'bar')).toBe(true);
        });

        it("should reconfigure and remove multiple old instanceCls", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo',
                template: [{
                    reference: 'childEl',
                    cls: 'child-el',
                    uiCls: 'child'
                }]
            });

            var Bar = Ext.define(null, {
                extend: Foo,
                classCls: 'bar'
            });

            widget = new Bar({
                instanceCls: ['baz', 'jaz'],
                ui: 'you eye'
            });

            widget.setInstanceCls(['huh', 'wat']);

            expect(widget).toHaveCls('foo bar huh wat foo-you bar-you huh-you wat-you foo-eye bar-eye huh-eye wat-eye');
            expect(widget.childEl).toHaveCls([
                'child-el', 'foo-child', 'foo-you-child', 'foo-eye-child',
                'bar-child', 'bar-you-child', 'bar-eye-child',
                'huh-child', 'huh-you-child', 'huh-eye-child',
                'wat-child', 'wat-you-child', 'wat-eye-child'
            ]);
            expect(widget).not.toHaveCls('baz baz-you baz-eye jaz jaz-you jaz-eye');
            expect(widget.childEl).not.toHaveCls([
                'baz-child', 'baz-you-child', 'baz-eye-child',
                'jaz-child', 'jaz-you-child', 'jaz-eye-child'
            ]);
            expect(widget.classClsList.length).toBe(4);
            expect(Ext.Array.contains(widget.classClsList, 'foo')).toBe(true);
            expect(Ext.Array.contains(widget.classClsList, 'bar')).toBe(true);
            expect(Ext.Array.contains(widget.classClsList, 'huh')).toBe(true);
            expect(Ext.Array.contains(widget.classClsList, 'wat')).toBe(true);
            // instanceCls should not modify the prototype
            expect(Bar.prototype.classClsList.length).toBe(2);
            expect(Ext.Array.contains(Bar.prototype.classClsList, 'foo')).toBe(true);
            expect(Ext.Array.contains(Bar.prototype.classClsList, 'bar')).toBe(true);
        });

        it("should add a UI reference after initialization", function() {
            // This is an unusual use case, but Toolable does this with its dock wrapper
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                doDestroy: function() {
                    this.callParent();
                    this.childEl.destroy();
                }
            });

            widget = new Foo({
                instanceCls: 'foo',
                ui: 'you eye'
            });

            var childEl = widget.childEl = widget.element.append({
                cls: 'child-el'
            });

            widget.initUiReference('childEl', 'child');

            expect(childEl).toHaveCls('child-el foo-child foo-you-child foo-eye-child');
        });
    });

    describe("baseCls", function() {
        it("should add the baseCls to the element", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                baseCls: 'foo'
            });

            widget = new Foo();

            expect(widget.element).toHaveCls('foo');
        });

        it("should add a ui cls by appending the ui to the baseCls", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                baseCls: 'foo',
                ui: 'bar'
            });

            widget = new Foo();

            expect(widget.element).toHaveCls('foo');
            expect(widget.element).toHaveCls('foo-bar');
        });

        it("should default to classCls when baseCls is unspecified", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo'
            });

            widget = new Foo();

            expect(widget.element).toHaveCls('foo');
            expect(widget.baseCls).toBe('foo');
        });

        it("should default to classCls when baseCls is true", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo',
                baseCls: true
            });

            widget = new Foo();

            expect(widget.element).toHaveCls('foo');
            expect(widget.baseCls).toBe('foo');
        });

        it("should not default to classCls when baseCls is a string", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo',
                baseCls: 'bar'
            });

            widget = new Foo();

            expect(widget.element).toHaveCls('foo');
            expect(widget.element).toHaveCls('bar');
            expect(widget.baseCls).toBe('bar');
        });

        it("should default to xtype when baseCls is unspecified and there is no classCls", function() {
            Ext.define('Foo', {
                extend: 'Ext.Widget',
                xtype: 'mywidget'
            });

            widget = new Foo();

            expect(widget.element).toHaveCls('x-mywidget');
            expect(widget.baseCls).toBe('x-mywidget');

            Ext.undefine('Foo');
        });

        it("should default to xtype when baseCls is true and there is no classCls", function() {
            // for compat with version 6
            Ext.define('Foo', {
                extend: 'Ext.Widget',
                xtype: 'mywidget',
                baseCls: true
            });

            widget = new Foo();

            expect(widget.element).toHaveCls('x-mywidget');
            expect(widget.baseCls).toBe('x-mywidget');

            Ext.undefine('Foo');
        });

        it("should default to classCls when baseCls is null", function() {
            Ext.define('Foo', {
                extend: 'Ext.Widget',
                classCls: 'foo',
                xtype: 'mywidget',
                baseCls: null
            });

            widget = new Foo();

            expect(widget.element).toHaveCls('foo');
            expect(widget.baseCls).toBe('foo');

            Ext.undefine('Foo');
        });

        it("should default to classCls when baseCls is undefined", function() {
            Ext.define('Foo', {
                extend: 'Ext.Widget',
                classCls: 'foo',
                xtype: 'mywidget',
                baseCls: undefined
            });

            widget = new Foo();

            expect(widget.element).toHaveCls('foo');
            expect(widget.baseCls).toBe('foo');

            Ext.undefine('Foo');
        });

        it("should default to xtype when baseCls is null and there is no classCls", function() {
            Ext.define('Foo', {
                extend: 'Ext.Widget',
                xtype: 'mywidget',
                baseCls: null
            });

            widget = new Foo();

            expect(widget.element).toHaveCls('x-mywidget');
            expect(widget.baseCls).toBe('x-mywidget');

            Ext.undefine('Foo');
        });

        it("should default to xtype when baseCls is undefined and there is no classCls", function() {
            Ext.define('Foo', {
                extend: 'Ext.Widget',
                xtype: 'mywidget',
                baseCls: undefined
            });

            widget = new Foo();

            expect(widget.element).toHaveCls('x-mywidget');
            expect(widget.baseCls).toBe('x-mywidget');

            Ext.undefine('Foo');
        });

        it("should throw an error if baseCls is used as an instance config", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget'
            });

            expect(function() {
                widget =  new Foo({
                    baseCls: 'foo'
                });
            }).toThrow('baseCls cannot be used as an instance config. It must be specified at class definition time.');
        });

        it("should throw an error if setBaseCls is called", function() {
            // in previous versions baseCls was defined in the config block and had a setter
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                baseCls: 'foo'
            });

            widget = new Foo();

            expect(function() {
                widget.setBaseCls('bar');
            }).toThrow('baseCls cannot be reconfigured. It must be specified at class definition time.');
        });

        it("should throw an error if a subclass defines baseCls inside the config block", function() {
            expect(function() {
                Ext.define(null, {
                    extend: 'Ext.Widget',
                    config: {
                        baseCls: 'foo'
                    }
                });
            }).toThrow('baseCls must be declared directly on the class body. Please move it outside of the config block.');
        });

        it("should have a getter for backward compatibility with versions less than 7", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                baseCls: 'foo'
            });

            widget = new Foo();

            expect(widget.baseCls).toBe('foo');
        });

        it("should add a UI reference after initialization", function() {
            // This is an unusual use case, but Toolable does this with its dock wrapper
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                baseCls: 'foo',
                ui: 'you eye',
                doDestroy: function() {
                    this.callParent();
                    this.childEl.destroy();
                }
            });

            widget = new Foo();

            var childEl = widget.childEl = widget.element.append({
                cls: 'child-el'
            });

            widget.initUiReference('childEl', 'child');

            expect(childEl).toHaveCls('child-el foo-child foo-you-child foo-eye-child');
        });
    });

    describe("ui", function() {
        it("should append ui suffix to baseCls", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                baseCls: 'foo',
                ui: 'xyz'
            });

            widget = new Foo();

            expect(widget.element).toHaveCls('foo');
            expect(widget.element).toHaveCls('foo-xyz');
        });

        it("should append ui suffix to classCls", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo'
            });

            var Bar = Ext.define(null, {
                extend: Foo,
                classCls: 'bar'
            });

            var Baz = Ext.define(null, {
                extend: Bar,
                classCls: 'baz'
            });

            widget = new Baz({
                ui: 'xyz'
            });

            expect(widget.element).toHaveCls('foo');
            expect(widget.element).toHaveCls('bar');
            expect(widget.element).toHaveCls('baz');
            expect(widget.element).toHaveCls('foo-xyz');
            expect(widget.element).toHaveCls('bar-xyz');
            expect(widget.element).toHaveCls('baz-xyz');
        });

        it("should append ui suffix to both classCls and baseCls", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo'
            });

            var Bar = Ext.define(null, {
                extend: Foo,
                classCls: 'bar',
                baseCls: 'baz'
            });

            widget = new Bar({
                ui: 'xyz'
            });

            expect(widget.element).toHaveCls('foo');
            expect(widget.element).toHaveCls('bar');
            expect(widget.element).toHaveCls('baz');
            expect(widget.element).toHaveCls('foo-xyz');
            expect(widget.element).toHaveCls('bar-xyz');
            expect(widget.element).toHaveCls('baz-xyz');
        });

        it("should add UIs", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo'
            });

            widget = new Foo({
                ui: 'abc def'
            });

            widget.addUi('def ghi jkl');
            widget.addUi(['mno', 'pqr']);

            expect(widget.getUi()).toBe('abc def ghi jkl mno pqr');

            expect(widget.element).toHaveCls('foo-abc');
            expect(widget.element).toHaveCls('foo-def');
            expect(widget.element).toHaveCls('foo-ghi');
            expect(widget.element).toHaveCls('foo-jkl');
            expect(widget.element).toHaveCls('foo-mno');
            expect(widget.element).toHaveCls('foo-pqr');
        });

        it("should remove UIs", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo'
            });

            widget = new Foo({
                ui: 'abc def ghi jkl mno pqr'
            });

            widget.removeUi('def jkl');
            widget.removeUi(['mno', 'pqr']);

            expect(widget.getUi()).toBe('abc ghi');

            expect(widget.element).toHaveCls('foo-abc');
            expect(widget.element).not.toHaveCls('foo-def');
            expect(widget.element).toHaveCls('foo-ghi');
            expect(widget.element).not.toHaveCls('foo-jkl');
            expect(widget.element).not.toHaveCls('foo-mno');
            expect(widget.element).not.toHaveCls('foo-pqr');
        });

        it("should add UIs provided as an Array", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo'
            });

            widget = new Foo({
                ui: ['abc', 'def']
            });

            expect(widget.element).toHaveCls('foo-abc');
            expect(widget.element).toHaveCls('foo-def');
        });

        it("should normalize the UIs to a String", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo'
            });

            widget = new Foo({
                ui: ['abc', 'def']
            });
            expect(widget.getUi()).toBe('abc def');

            widget.setUi('ghi jkl');
            expect(widget.getUi()).toBe('ghi jkl');
        });
    });

    describe("uiCls", function() {
        it("should add ui classes using baseCls", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                baseCls: 'foo',
                ui: 'abc xyz',
                template: [{
                    reference: 'bodyElement',
                    uiCls: 'body-el'
                }]
            });

            widget = new Foo();

            expect(widget.bodyElement).toHaveCls('foo-body-el');
            expect(widget.bodyElement).toHaveCls('foo-abc-body-el');
            expect(widget.bodyElement).toHaveCls('foo-xyz-body-el');
        });

        it("should add ui classes using classCls", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo',
                template: [{
                    reference: 'bodyElement',
                    uiCls: 'body-el'
                }]
            });

            var Bar = Ext.define(null, {
                extend: Foo,
                classCls: 'bar'
            });

            var Baz = Ext.define(null, {
                extend: Bar,
                classCls: 'baz'
            });

            widget = new Baz({
                ui: 'abc xyz'
            });

            expect(widget.bodyElement).toHaveCls('foo-body-el');
            expect(widget.bodyElement).toHaveCls('bar-body-el');
            expect(widget.bodyElement).toHaveCls('baz-body-el');
            expect(widget.bodyElement).toHaveCls('foo-abc-body-el');
            expect(widget.bodyElement).toHaveCls('foo-xyz-body-el');
            expect(widget.bodyElement).toHaveCls('bar-abc-body-el');
            expect(widget.bodyElement).toHaveCls('bar-xyz-body-el');
            expect(widget.bodyElement).toHaveCls('baz-abc-body-el');
            expect(widget.bodyElement).toHaveCls('baz-xyz-body-el');
        });

        it("should add ui classes using both classCls and baseCls", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo',
                template: [{
                    reference: 'bodyElement',
                    uiCls: 'body-el'
                }]
            });

            var Bar = Ext.define(null, {
                extend: Foo,
                classCls: 'bar',
                baseCls: 'baz'
            });

            widget = new Bar({
                ui: 'abc xyz'
            });

            expect(widget.bodyElement).toHaveCls('foo-body-el');
            expect(widget.bodyElement).toHaveCls('bar-body-el');
            expect(widget.bodyElement).toHaveCls('baz-body-el');
            expect(widget.bodyElement).toHaveCls('foo-abc-body-el');
            expect(widget.bodyElement).toHaveCls('foo-xyz-body-el');
            expect(widget.bodyElement).toHaveCls('bar-abc-body-el');
            expect(widget.bodyElement).toHaveCls('bar-xyz-body-el');
            expect(widget.bodyElement).toHaveCls('baz-abc-body-el');
            expect(widget.bodyElement).toHaveCls('baz-xyz-body-el');
        });

        it("should throw an error if uiCls is used without a reference element", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo',
                template: [{
                    uiCls: 'body-el'
                }]
            });

            expect(function() {
                widget = new Foo();
            }).toThrow('Cannot render element with uiCls="body-el". uiCls is only allowed on elements that have a reference name.');
        });

        it("should not have the uiCls attribute in the rendered dom", function() {
            var Foo = Ext.define(null, {
                extend: 'Ext.Widget',
                classCls: 'foo',
                template: [{
                    reference: 'bodyElement',
                    uiCls: 'body-el'
                }]
            });

            widget = new Foo({
                ui: 'abc'
            });

            expect(widget.bodyElement.dom.getAttribute('uiCls')).toBe(null);
        });
    });

    describe("style", function() {
        var Widget, widget;

        function createWidget(config) {
            widget = new Widget(Ext.apply({
                renderTo: document.body
            }, config));
        }

        beforeEach(function() {
            Widget = Ext.define(null, {
                extend: 'Ext.Widget'
            });
        });

        afterEach(function() {
            widget.destroy();
            Widget = widget = null;
        });

        it("should initialize style using a string", function() {
            createWidget({
                style: 'position: absolute; cursor: pointer'
            });

            expect(widget.element.getStyle('position')).toBe('absolute');
            expect(widget.element.getStyle('cursor')).toBe('pointer');
        });

        it("should initialize style using an object", function() {
            createWidget({
                style: {
                    position: 'absolute',
                    cursor: 'pointer'
                }
            });

            expect(widget.element.getStyle('position')).toBe('absolute');
            expect(widget.element.getStyle('cursor')).toBe('pointer');
        });

        it("should set style using a string", function() {
            createWidget();

            widget.setStyle('position: absolute; cursor: pointer');

            expect(widget.element.getStyle('position')).toBe('absolute');
            expect(widget.element.getStyle('cursor')).toBe('pointer');
        });

        it("should set style using an object", function() {
            createWidget();

            widget.setStyle({
                position: 'absolute',
                cursor: 'pointer'
            });

            expect(widget.element.getStyle('position')).toBe('absolute');
            expect(widget.element.getStyle('cursor')).toBe('pointer');
        });

        it("should throw an error when getStyle is called", function() {
            createWidget();

            expect(function() {
                widget.getStyle();
            }).toThrow("'style' is a write-only config. To query element styles use the Ext.dom.Element API.");
        });

        it("should support a style declared on a subclass", function() {
            Widget = Ext.define(null, {
                extend: 'Ext.Widget',
                style: {
                    borderWidth: '1px'
                }
            });
            createWidget();
            expect(widget.element.dom.style.borderWidth).toBe('1px');
        });
    });

    describe('keyMap', function() {
        it('should allow keyMap on class', function() {
            var Ev = Ext.event.Event;

            var log = [];

            var keyMap = {
                ESC: 'onEscape',
                A: 'onA',
                F2: 'onF2',
                '#123': 'on123', // F12
                'Alt+#123': 'onAlt123',
                CONTEXT_MENU: 'onContextMenuAlone',
                '*+CONTEXT_MENU': 'onAnyContextMenu',
                1: 'onOne',
                'Ctrl+1': 'onCtrlOne'
            };

            var keyCodes = [
                ['keydown', Ev.ESC],
                ['keydown', Ev.A],
                ['keydown', Ev.F2],
                ['keypress', 123],
                ['keypress', 'altKey', 123],
                ['keydown', Ev.CONTEXT_MENU],
                ['keydown', 'altKey', Ev.CONTEXT_MENU],
                ['keypress', '1'.charCodeAt(0)],
                ['keypress', 'ctrlKey', '1'.charCodeAt(0)]
            ];

            var handlers = {};

            Ext.iterate(keyMap, function(key, value) {
                handlers[value] = function() {
                    log.push(value);
                };
            });

            var T = Ext.define(null, Ext.apply({
                extend: 'Ext.Widget',

                keyMap: keyMap
            }, handlers));

            Ext.each(['first', 'second'], function(which) {
                widget = new T();
                widget.render(Ext.getBody());

                var keys = Ext.clone(keyCodes);

                log.length = 0;
                log.push(which);

                for (var i = 0; i < keys.length; ++i) {
                    var kc = keys[i];

                    var ke = {
                        altKey: false,
                        ctrlKey: false,
                        metaKey: false,
                        shiftKey: false,
                        type: kc.shift(),
                        keyCode: kc.pop()
                    };

                    if (ke.type === 'keypress') {
                        ke.charCode = ke.keyCode;
                        delete ke.keyCode;
                    }

                    while (kc.length) {
                        ke[kc.pop()] = true; // eg 'ctrlKey'
                    }

                    ke = new Ext.event.Event(ke);

                    widget.onKeyMapEvent(ke);
                }

                expect(log).toEqual([
                    which,
                    'onEscape',
                    'onA',
                    'onF2',
                    'on123',
                    'onAlt123',
                    'onContextMenuAlone',
                    'onAnyContextMenu',
                    'onAnyContextMenu',
                    'onOne',
                    'onCtrlOne'
                ]);

                widget.destroy();
            });
        });

        it('should support group enable/disable', function() {
            var log = [];

            var T = Ext.define(null, {
                extend: 'Ext.Widget',

                keyMap: {
                    'ESC:GRP': 'onEscape'
                },

                onEscape: function(e) {
                    log.push('onEscape');
                }
            });

            widget = new T();
            widget.render(Ext.getBody());

            var ke = new Ext.event.Event({
                type: 'keydown',
                keyCode: 27
            });

            widget.onKeyMapEvent(ke);
            expect(log).toEqual(['onEscape']);

            widget.disableKeyMapGroup('GRP');
            widget.onKeyMapEvent(ke);
            expect(log).toEqual(['onEscape']);

            widget.enableKeyMapGroup('GRP');
            widget.onKeyMapEvent(ke);
            expect(log).toEqual(['onEscape', 'onEscape']);
        });

        it('should allow keyMap on class and instance', function() {
            var log = [];

            var T = Ext.define(null, {
                extend: 'Ext.Widget',

                keyMap: {
                    ESC: 'onEscape',
                    F2: 'onF2'
                },

                onEscape: function(e) {
                    log.push('onEscape');
                },

                onF2: function() {
                    log.push('F2');
                }
            });

            widget = new T({
                keyMap: {
                    ESC: function(e) {
                        log.push('esc');
                    }
                }
            });
            widget.render(Ext.getBody());

            var ke = new Ext.event.Event({
                type: 'keydown',
                keyCode: 27
            });

            widget.onKeyMapEvent(ke);
            expect(log).toEqual(['onEscape', 'esc']);

            log.length = 0;
            widget.destroy();

            widget = new T({
                keyMap: {
                    ESC: function(e) {
                        log.push('esc2');
                    }
                }
            });
            widget.render(Ext.getBody());

            widget.onKeyMapEvent(ke);
            expect(log).toEqual(['onEscape', 'esc2']);

            var f2 = new Ext.event.Event({
                type: 'keydown',
                keyCode: Ext.event.Event.F2
            });

            widget.onKeyMapEvent(f2);
            expect(log).toEqual(['onEscape', 'esc2', 'F2']);
        });
    });

    describe('Ext.updateWidget', function() {
        var creator, instance;

        beforeEach(function() {
            defineWidget(true, {
                xtype: 'test-base',

                config: {
                    foo: null
                },

                isTestBase: true
            });

            Ext.define('spec.TestWidget', {
                extend: 'Ext.Widget',
                xtype: 'test-config',

                config: {
                    defaults: null,
                    foo: null
                },

                isTestConfig: true,

                createInstance: function(config) {
                    return Ext.apply({
                        foo: 'bar'
                    }, config);
                }
            });
        });

        afterEach(function() {
            Ext.undefine('spec.TestWidget');

            creator = instance = Ext.destroy(creator, instance);
        });

        describe('create tests', function() {
            it('should create a new instance', function() {
                widget = Ext.updateWidget(null, {
                    xclass: 'spec.Widget'
                });

                expect(widget.isTestBase).toBe(true);
            });

            it('should create new instance passing a string as config', function() {
                widget = Ext.updateWidget(null, 'test-base');

                expect(widget.isTestBase).toBe(true);
            });

            it('should create new instance with xclass mismatch', function() {
                widget = new spec.Widget();

                instance = Ext.updateWidget(widget, {
                    xclass: 'spec.TestWidget',
                    foo: 'baz'
                });

                expect(instance).not.toBe(widget);
                expect(instance.isTestConfig).toBe(true);
                expect(instance.getFoo()).toBe('baz');
            });

            it('should create new instance with type mismatch', function() {
                widget = new spec.Widget();

                instance = Ext.updateWidget(widget, {
                    xtype: 'test-config',
                    foo: 'baz'
                });

                expect(instance).not.toBe(widget);
                expect(instance.isTestConfig).toBe(true);
                expect(instance.getFoo()).toBe('baz');
            });

            describe('creator', function() {
                it('should use defaults from creator', function() {
                    creator = new spec.TestWidget({
                        defaults: {
                            foo: 'bar'
                        }
                    });

                    widget = Ext.updateWidget(null, {
                        xtype: 'test-config'
                    }, creator, null, 'defaults');

                    expect(widget.isTestConfig).toBe(true);
                    expect(widget.getFoo()).toBe('bar');
                });

                it('should use defaults from creator but favor own config', function() {
                    creator = new spec.TestWidget({
                        defaults: {
                            foo: 'bar'
                        }
                    });

                    widget = Ext.updateWidget(null, {
                        xtype: 'test-config',
                        foo: 'baz'
                    }, creator, null, 'defaults');

                    expect(widget.isTestConfig).toBe(true);
                    expect(widget.getFoo()).toBe('baz');
                });

                it('should use a creator method', function() {
                    creator = new spec.TestWidget();
                    widget = Ext.updateWidget(null, {
                        xtype: 'test-config'
                    }, creator, 'createInstance');

                    expect(widget.isTestConfig).toBe(true);
                    expect(widget.getFoo()).toBe('bar');
                });

                it('should use a creator method but favor own config', function() {
                    creator = new spec.TestWidget();
                    widget = Ext.updateWidget(null, {
                        xtype: 'test-config',
                        foo: 'baz'
                    }, creator, 'createInstance');

                    expect(widget.isTestConfig).toBe(true);
                    expect(widget.getFoo()).toBe('baz');
                });
            });
        });

        describe('update tests', function() {
            it('should update existing instance', function() {
                widget = new spec.Widget({
                    foo: 'bar'
                });

                instance = Ext.updateWidget(widget, {
                    xclass: 'spec.Widget',
                    foo: 'baz'
                });

                expect(instance).toBe(widget);
                expect(instance.getFoo()).toBe('baz');
            });
        });

        describe('destroy tests', function() {
            it('should destroy old instance', function() {
                widget = new spec.Widget();

                instance = Ext.updateWidget(widget);

                expect(widget.destroyed).toBe(true);
                expect(instance).toBeUndefined();
            });

            it('should not destroy instance passing as config also', function() {
                widget = new spec.Widget();

                instance = Ext.updateWidget(widget, widget);

                expect(widget.destroyed).toBe(false);
                expect(instance).toBe(instance);
            });

            it('should destroy old instance passing an instance as config', function() {
                widget = new spec.Widget();
                instance = new spec.Widget();

                var test = Ext.updateWidget(widget, instance);

                expect(widget.destroyed).toBe(true);
                expect(test).toBe(instance);
            });
        });
    });
});
