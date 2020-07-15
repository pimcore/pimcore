
topSuite("Ext.container.Container",
    ['Ext.grid.Panel', 'Ext.layout.container.Anchor', 'Ext.form.field.Text', 'Ext.form.field.Number',
     'Ext.form.field.TextArea', 'Ext.form.FieldSet', 'Ext.window.Window', 'Ext.container.Viewport',
     'Ext.app.ViewController', 'Ext.form.field.ComboBox', 'Ext.button.Button'],
function() {
    var ct;

    afterEach(function() {
        ct = Ext.destroy(ct);
    });

    function makeContainer(cfg) {
        if (Ext.isArray(cfg)) {
            cfg = {
                items: cfg
            };
        }

        ct = new Ext.container.Container(cfg);

        return ct;
    }

    describe("alternate class name", function() {
        it("should have Ext.Container as the alternate class name", function() {
            expect(Ext.container.Container.prototype.alternateClassName).toEqual(["Ext.Container", "Ext.AbstractContainer"]);
        });

        it("should allow the use of Ext.Container", function() {
            expect(Ext.Container).toBeDefined();
        });
    });

    describe("retaining scroll position", function() {
        var endSpy, s;

        beforeEach(function() {
            endSpy = jasmine.createSpy();
        });

        afterEach(function() {
            endSpy = s = null;
        });

        function makeScrollCt(cfg) {
            makeContainer(Ext.apply(cfg, {
                renderTo: Ext.getBody(),
                scrollable: true,
                defaultType: 'component'
            }));
            s = ct.getScrollable();
            s.on('scrollend', endSpy);
        }

        function sizeHtml(width, height) {
            return Ext.String.format('<div style="width: {0}px; height: {1}px;"></div>', width, height);
        }

        // Box layouts are the only real applicable ones for scrolling with configured sizing
        describe("configured size", function() {
            describe("hbox", function() {
                it("should retain position when a child causes a layout", function() {
                    makeScrollCt({
                        layout: 'hbox',
                        width: 200,
                        height: 200,
                        items: [{
                            flex: 1,
                            height: 400
                        }, {
                            html: sizeHtml(300, 400)
                        }]
                    });

                    s.scrollTo(100, 150);

                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });

                    runs(function() {
                        ct.items.last().setHtml(sizeHtml(300, 400));
                    });

                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });

                    runs(function() {
                        expect(s.getPosition()).toEqual({
                            x: 100,
                            y: 150
                        });
                    });
                });
            });

            describe("vbox", function() {
                it("should retain position when a child causes a layout", function() {
                    makeScrollCt({
                        layout: 'vbox',
                        width: 200,
                        height: 200,
                        items: [{
                            flex: 1,
                            width: 400
                        }, {
                            html: sizeHtml(400, 300)
                        }]
                    });

                    s.scrollTo(150, 100);

                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });

                    runs(function() {
                        ct.items.last().setHtml(sizeHtml(400, 300));
                    });

                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });

                    runs(function() {
                        expect(s.getPosition()).toEqual({
                            x: 150,
                            y: 100
                        });
                    });
                });
            });
        });

        describe("shrinkwrap with constraints", function() {
            function makeShrinkScrollCt(cfg) {
                makeScrollCt(Ext.apply(cfg, {
                    floating: true,
                    x: 0,
                    y: 0,
                    maxWidth: 200,
                    maxHeight: 200
                }));
            }

            describe("hbox", function() {
                it("should retain position when a child causes a layout", function() {
                    makeShrinkScrollCt({
                        layout: 'hbox',
                        items: [{
                            width: 300,
                            height: 400
                        }, {
                            width: 300,
                            height: 400
                        }]
                    });

                    s.scrollTo(150, 150);

                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });

                    runs(function() {
                        ct.items.last().setSize(400, 500);
                    });

                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });

                    runs(function() {
                        expect(s.getPosition()).toEqual({
                            x: 150,
                            y: 150
                        });
                    });
                });
            });

            describe("vbox", function() {
                it("should retain position when a child causes a layout", function() {
                    makeShrinkScrollCt({
                        layout: 'vbox',
                        items: [{
                            width: 400,
                            height: 300
                        }, {
                            width: 400,
                            height: 300
                        }]
                    });

                    s.scrollTo(150, 150);

                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });

                    runs(function() {
                        ct.items.last().setSize(500, 400);
                    });

                    waitsFor(function() {
                        var pos = s.getPosition();

                        return pos.x > 0 && pos.y > 0;
                    });

                    runs(function() {
                        expect(s.getPosition()).toEqual({
                            x: 150,
                            y: 150
                        });
                    });
                });
            });

            describe("anchor", function() {
                it("should retain position when a child causes a layout", function() {
                    makeShrinkScrollCt({
                        layout: 'anchor',
                        items: [{
                            height: 200,
                            width: 100
                        }, {
                            height: 200,
                            width: 100
                        }, {
                            height: 200,
                            width: 100
                        }]
                    });

                    s.scrollTo(null, 150);

                    waitsFor(function() {
                        return endSpy.callCount > 0;
                    });

                    runs(function() {
                        ct.items.last().setHeight(300);
                    });

                    waitsFor(function() {
                        return s.getPosition().y > 0;
                    });

                    runs(function() {
                        expect(s.getPosition()).toEqual({
                            x: 0,
                            y: 150
                        });
                    });
                });
            });
        });
    });

    describe("Floating descendants", function() {
        afterEach(function() {
            ct.destroy();
        });

        it("should find floating descentants using down(), not child()", function() {
            makeContainer({
                renderTo: Ext.getBody(),
                layout: 'fit',
                title: 'Test',
                height: 400,
                width: 600,
                items: {
                    xtype: 'fieldset',
                    items: {
                        xtype: 'window',
                        title: 'Descendant',
                        height: 100,
                        width: 200,
                        constrain: true
                    }
                }
            });
            expect(ct.child('window')).toBeNull();
            expect(ct.down('window')).not.toBeNull();
        });

        describe("layouts", function() {
            it("should allow floater layouts to run when the container has one queued", function() {
                var p = new Ext.panel.Panel({
                    width: 200,
                    height: 200,
                    floating: true
                });

                makeContainer({
                    renderTo: Ext.getBody(),
                    width: 400,
                    height: 400,
                    items: p
                });
                p.show();

                Ext.suspendLayouts();
                ct.setSize(500, 500);
                p.add({
                    title: 'X'
                });
                Ext.resumeLayouts(true);
                expect(p.items.first().rendered).toBe(true);
            });
        });
    });

    describe("onRender sequence", function() {
        beforeEach(function() {
            spyOn(Ext.Component.prototype, "onRender").andCallThrough();
        });
        it("should call onRender passing parentNode and position", function() {
            makeContainer({
                renderTo: document.body,
                items: {
                    xtype: 'panel'
                }
            });
            expect(Ext.Component.prototype.onRender.calls[0].args[0]).toEqual(Ext.getBody());
            expect(Ext.Component.prototype.onRender.calls[0].args[1]).toBeUndefined();
            expect(Ext.Component.prototype.onRender.calls[1].args[0]).toEqual(ct.layout.innerCt);
            expect(Ext.Component.prototype.onRender.calls[1].args[1]).toEqual(0);
        });
    });

    describe("reading items", function() {
        it("should have item count 0 if items is ommitted", function() {
            makeContainer();
            expect(ct.items.getCount()).toEqual(0);
        });

        it("should have an item count 0 if an empty array is specified", function() {
            makeContainer({
                items: []
            });
            expect(ct.items.getCount()).toEqual(0);
        });

        it("should handle adding a single item configuration", function() {
            makeContainer({
                items: {
                    itemId: 'first'
                }
            });
            expect(ct.items.getCount()).toEqual(1);
            expect(ct.items.first().itemId).toEqual('first');
        });

        it("should handle an array of items", function() {
            makeContainer({
                items: [{
                    itemId: 'item1'
                }, {
                    itemId: 'item2'
                }]
            });
            expect(ct.items.getCount()).toEqual(2);
            expect(ct.items.first().itemId).toEqual('item1');
            expect(ct.items.last().itemId).toEqual('item2');
        });

        describe("subclassing", function() {
            var Cls, items;

            beforeEach(function() {
                items = [{
                    itemId: 'item1'
                }, {
                    itemId: 'item2'
                }, {
                    itemId: 'item3'
                }];
            });

            afterEach(function() {
                items = null;
            });

            it("should be able to apply items in initComponent", function() {
                Cls = Ext.define(null, {
                    extend: 'Ext.container.Container',
                    initComponent: function() {
                        Ext.apply(this, { items: items });
                        this.callParent();
                    }
                });

                ct = new Cls();

                expect(ct.items.getCount()).toBe(3);
            });

            it("should be able to applyIf items in initComponent", function() {
                Cls = Ext.define(null, {
                    extend: 'Ext.container.Container',
                    initComponent: function() {
                        Ext.applyIf(this, { items: items });
                        this.callParent();
                    }
                });

                ct = new Cls();

                expect(ct.items.getCount()).toBe(3);
            });
        });
    });

    describe("defaultType", function() {
        it("should use panel if one isn't specified", function() {
            makeContainer({
                items: [{
                    itemId: 'item'
                }]
            });
            expect(ct.items.first() instanceof Ext.Panel).toBeTruthy();
        });

        it("should use a specified default type", function() {
            makeContainer({
                defaultType: 'container',
                items: [{
                    itemId: 'item'
                }]
            });
            expect(ct.items.first() instanceof Ext.container.Container);
        });
    });

    describe("getComponent", function() {
        var a, b, c, d, cmp;

        beforeEach(function() {
            a = new Ext.Component({
                itemId: 'a'
            });
            b = new Ext.Component({
                id: 'b'
            });
            c = new Ext.Component({
                itemId: 'c'
            });
            d = new Ext.Component({
                itemId: 'd'
            });
            makeContainer({
                items: [a, b, c, d]
            });
        });

        afterEach(function() {
            Ext.destroy(a, b, c, d, cmp);
            a = b = c = d = cmp = null;
        });

        it("should return undefined if id is not found", function() {
            expect(ct.getComponent('foo')).not.toBeDefined();
        });

        it("should return undefined if index is not found", function() {
            expect(ct.getComponent(100)).not.toBeDefined();
        });

        it("should return undefined if instance is not found", function() {
            cmp = new Ext.Component();

            expect(ct.getComponent(cmp)).not.toBeDefined();
        });

        it("should find a passed instance", function() {
            expect(ct.getComponent(b)).toEqual(b);
        });

        it("should find a passed index", function() {
            expect(ct.getComponent(2)).toEqual(c);
        });

        it("should find by id", function() {
            expect(ct.getComponent('d')).toEqual(d);
        });

        describe("with floaters", function() {
            var floater;

            beforeEach(function() {
                floater = new Ext.Component({
                    floating: true,
                    itemId: 'floater'
                });
            });

            afterEach(function() {
                floater = null;
            });

            it("should not get a floater by index", function() {
                ct.removeAll();
                ct.add(floater);
                expect(ct.getComponent(0)).toBeUndefined();
            });

            it("should be able to get a floater by id", function() {
                ct.add(floater);
                expect(ct.getComponent('floater')).toBe(floater);
            });

            it('should get a component via the instance', function() {
                ct.add(floater);
                expect(ct.getComponent(floater)).toBe(floater);
            });

        });
    });

    describe("add", function() {

        it("should return the added item", function() {
            makeContainer();

            var c = new Ext.Component();

            expect(ct.add(c)).toEqual(c);
        });

        it("should accept a single item", function() {
            makeContainer();

            var c = ct.add({
                itemId: 'foo'
            });

            expect(ct.items.getCount()).toEqual(1);
            expect(ct.items.first()).toEqual(c);
        });

        it("should be able to be called sequentiallly", function() {
            makeContainer();

            var a = ct.add({}),
                b = ct.add({}),
                c = ct.add({});

            expect(ct.items.getCount()).toEqual(3);
            expect(ct.items.first()).toEqual(a);
            expect(ct.items.getAt(1)).toEqual(b);
            expect(ct.items.last()).toEqual(c);
        });

        it("should accept an array of items", function() {
            makeContainer();

            var a = new Ext.Component(),
                b = new Ext.Component(),
                c = new Ext.Component(),
                result;

            result = ct.add([a, b, c]);
            expect(result[0]).toEqual(a);
            expect(result[1]).toEqual(b);
            expect(result[2]).toEqual(c);
            expect(ct.items.first()).toEqual(a);
            expect(ct.items.getAt(1)).toEqual(b);
            expect(ct.items.last()).toEqual(c);
        });

        it("should accept n parameters, similar to array", function() {
            makeContainer();

            var a = new Ext.Component(),
                b = new Ext.Component(),
                c = new Ext.Component(),
                d = new Ext.Component(),
                result;

            result = ct.add(a, b, c, d);
            expect(result[0]).toEqual(a);
            expect(result[1]).toEqual(b);
            expect(result[2]).toEqual(c);
            expect(result[3]).toEqual(d);
            expect(ct.items.first()).toEqual(a);
            expect(ct.items.getAt(1)).toEqual(b);
            expect(ct.items.getAt(2)).toEqual(c);
            expect(ct.items.last()).toEqual(d);
        });

        it("should fire the beforeadd event", function() {
            makeContainer();

            var o = {
                    fn: Ext.emptyFn
                },
                c = new Ext.Component();

            spyOn(o, 'fn');
            ct.on('beforeadd', o.fn);
            ct.add(c);
            // expect(o.fn).toHaveBeenCalledWith(ct, c, 0);
            expect(o.fn).toHaveBeenCalled();
        });

        it("should cancel if beforeadd returns false", function() {
            makeContainer();

            ct.on('beforeadd', function() {
                return false;
            });

            var cmp = ct.add({});

            expect(ct.items.getCount()).toEqual(0);
            cmp.destroy();
        });

        it("should fire the add event", function() {
            makeContainer();

            var o = {
                    fn: Ext.emptyFn
                },
                c = new Ext.Component();

            spyOn(o, 'fn');
            ct.on('add', o.fn);
            ct.add(c);

            expect(o.fn.calls.length).toBe(1);
            expect(o.fn.calls[0].args[0]).toBe(ct);
            expect(o.fn.calls[0].args[1]).toBe(c);
            expect(o.fn.calls[0].args[2]).toBe(0);
        });

        it("should fire the add event for floating items", function() {
            makeContainer();

            var o = {
                    fn: Ext.emptyFn
                },
                floatingPanel = Ext.create('Ext.panel.Panel', {
                    floating: true
                });

            spyOn(o, 'fn');
            ct.on('add', o.fn);
            ct.add(floatingPanel);

            expect(o.fn.calls.length).toBe(1);
            expect(o.fn.calls[0].args[0]).toBe(ct);
            expect(o.fn.calls[0].args[1]).toBe(floatingPanel);
            expect(o.fn.calls[0].args[2]).toBe(0);
        });

    });

    describe("insert", function() {

        it("should return the component instance", function() {
            makeContainer();

            var c = new Ext.Component();

            expect(ct.insert(0, c)).toEqual(c);
        });

        it("should insert to the first spot when empty", function() {
            makeContainer();

            var c = ct.insert(0, {});

            expect(ct.items.first()).toEqual(c);
        });

        it("should be able to be called sequentially", function() {
            makeContainer();

            var a = new Ext.Component(),
                b = new Ext.Component(),
                c = new Ext.Component();

            ct.insert(0, c);
            ct.insert(0, b);
            ct.insert(0, a);

            expect(ct.items.first()).toEqual(a);
            expect(ct.items.getAt(1)).toEqual(b);
            expect(ct.items.last()).toEqual(c);
        });

        it("should insert to the lowest possible index if the specified index is too high", function() {
            makeContainer({
                items: [{}, {}, {}]
            });

            var c = ct.insert(100, {});

            expect(ct.items.last()).toEqual(c);
        });

        it("should insert at at the end if we use -1", function() {
            makeContainer({
                items: [{}, {}, {}]
            });

            var c = ct.insert(-1, {});

            expect(ct.items.last()).toEqual(c);
        });

        it("should put the item into the correct position", function() {
            makeContainer({
                items: [{}, {}, {}]
            });

            var c = ct.insert(1, {});

            expect(ct.items.getAt(1)).toEqual(c);
        });

        it("should accept an array", function() {
            makeContainer({
                items: [{}, {}, {}]
            });

            var a = new Ext.Component(),
                b = new Ext.Component(),
                c = new Ext.Component();

            ct.insert(1, [a, b, c]);
            expect(ct.items.getAt(1)).toEqual(a);
            expect(ct.items.getAt(2)).toEqual(b);
            expect(ct.items.getAt(3)).toEqual(c);
        });

        it("should move a component if it already exists, not insert/add", function() {
            var a = new Ext.Component(),
                b = new Ext.Component(),
                c = new Ext.Component(),
                d = new Ext.Component(),
                e = new Ext.Component(),
                called = false;

            makeContainer({
                items: [a, b, c, d, e]
            });

            ct.on('add', function() {
                called = true;
            });

            ct.insert(1, d);
            expect(called).toBe(false);
            expect(ct.items.indexOf(d)).toBe(1);
        });
    });

    describe("moving items", function() {
        describe("move", function() {
            it("should return false if the index doesn't exist in the container", function() {
                makeContainer({
                    items: [{}, {}, {}]
                });

                expect(ct.move(4, 1)).toBe(false);
            });

            it("should return false if the component doesn't exist in the container", function() {
                makeContainer({
                    items: [{}, {}, {}]
                });
                var c = new Ext.Component();

                expect(ct.move(c, 1)).toBe(false);

                c.destroy();
            });

            it("should move components by index", function() {
                var a = new Ext.Component(),
                    b = new Ext.Component(),
                    c = new Ext.Component(),
                    d = new Ext.Component(),
                    e = new Ext.Component();

                makeContainer({
                    items: [a, b, c, d, e]
                });
                ct.move(4, 1);
                expect(ct.items.indexOf(e)).toBe(1);
            });

            it("should move components by instance", function() {
                var a = new Ext.Component(),
                    b = new Ext.Component(),
                    c = new Ext.Component(),
                    d = new Ext.Component(),
                    e = new Ext.Component();

                makeContainer({
                    items: [a, b, c, d, e]
                });
                ct.move(c, 1);
                expect(ct.items.indexOf(c)).toBe(1);
            });

            it("should limit the index to the item size", function() {
                var a = new Ext.Component(),
                    b = new Ext.Component();

                makeContainer({
                    items: [a, b]
                });

                var spy = jasmine.createSpy();

                ct.on('childmove', spy);

                ct.move(a, 100);

                var args = spy.mostRecentCall.args;

                expect(args[2]).toBe(0);
                expect(args[3]).toBe(1);
            });

            describe("events", function() {
                it("should fire the move event and pass the container, the item, fromIdx & toIdx", function() {
                    var spy = jasmine.createSpy(),
                        c = new Ext.Component(),
                        args;

                    makeContainer({
                        items: [{}, c, {}]
                    });

                    ct.on('childmove', spy);
                    ct.move(1, 0);

                    expect(spy).toHaveBeenCalled();
                    args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(ct);
                    expect(args[1]).toBe(c);
                    expect(args[2]).toBe(1);
                    expect(args[3]).toBe(0);
                });

                it("should not fire the move event if the fromIdx === toIdx", function() {
                    var spy = jasmine.createSpy();

                    makeContainer({
                        items: [{}, {}, {}]
                    });
                    ct.on('childmove', spy);
                    ct.move(1, 1);
                    expect(spy).not.toHaveBeenCalled();
                });
            });
        });

        describe("moveBefore", function() {
            var ref, c;

            beforeEach(function() {
                ref = new Ext.Component();
                c = new Ext.Component();
            });

            afterEach(function() {
                Ext.destroy(c, ref);
                c = ref = null;
            });

            it("should be able to add a component instance that does not exist in the container", function() {
                makeContainer([ref]);
                expect(ct.moveBefore(c, ref)).toBe(c);
                expect(ct.items.getAt(0)).toBe(c);
                expect(ct.items.getAt(1)).toBe(ref);
            });

            it("should be able to add a component config and return it", function() {
                makeContainer([ref]);

                var fromCfg = ct.moveBefore({
                    xtype: 'panel',
                    title: 'Foo'
                }, ref);

                expect(ct.items.getAt(0)).toBe(fromCfg);
                expect(fromCfg.getTitle()).toBe('Foo');
            });

            it("should return the moved item", function() {
                makeContainer({}, ref, {}, c);
                expect(ct.moveBefore(c, ref)).toBe(c);
            });

            it("should be able to add items from another container", function() {
                var other = new Ext.container.Container({
                    items: c
                });

                makeContainer([ref]);

                expect(ct.moveBefore(c, ref)).toBe(c);
                expect(other.items.indexOf(c)).toBe(-1);
                expect(ct.items.getAt(0)).toBe(c);
                expect(ct.items.getAt(1)).toBe(ref);

                other.destroy();
            });

            it("should be able to move existing items in a container", function() {
                makeContainer([{}, {}, ref, {}, {}, {}, c, {}, {}]);
                expect(ct.moveBefore(c, ref)).toBe(c);
                expect(ct.items.getAt(2)).toBe(c);
                expect(ct.items.getAt(3)).toBe(ref);
            });

            describe("with the item not in the container", function() {
                it("should move to the end if the before reference is null", function() {
                    makeContainer([{}, {}, {}, {}]);
                    expect(ct.moveBefore(c, null)).toBe(c);
                    expect(ct.items.getAt(4)).toBe(c);
                });

                it("should be able to move before the first item", function() {
                    makeContainer([ref, {}, {}, {}]);
                    expect(ct.moveBefore(c, ref)).toBe(c);
                    expect(ct.items.getAt(0)).toBe(c);
                    expect(ct.items.getAt(1)).toBe(ref);
                });

                it("should be able to move before the last item", function() {
                    makeContainer([{}, {}, {}, ref]);
                    expect(ct.moveBefore(c, ref)).toBe(c);
                    expect(ct.items.getAt(3)).toBe(c);
                    expect(ct.items.getAt(4)).toBe(ref);
                });

                it("should be able to add to an empty container", function() {
                    makeContainer([]);
                    expect(ct.moveBefore(c, null)).toBe(c);
                    expect(ct.items.getAt(0)).toBe(c);
                });

                it("should be able to move into the middle of the container", function() {
                    makeContainer([{}, {}, {}, ref, {}, {}, {}]);
                    expect(ct.moveBefore(c, ref)).toBe(c);
                    expect(ct.items.getAt(3)).toBe(c);
                    expect(ct.items.getAt(4)).toBe(ref);
                });
            });

            describe("with the item in the container", function() {
                it("should do nothing if the reference is the component", function() {
                    makeContainer([{}, c, {}]);
                    expect(ct.moveBefore(c, c)).toBe(c);
                    expect(ct.items.getAt(1)).toBe(c);
                });

                it("should move to the end if the before reference is null", function() {
                    makeContainer([c, {}, {}, {}, {}]);
                    expect(ct.moveBefore(c, null)).toBe(c);
                    expect(ct.items.getAt(4)).toBe(c);
                });

                it("should be able to move before the first item", function() {
                    makeContainer([ref, {}, c, {}, {}]);
                    expect(ct.moveBefore(c, ref)).toBe(c);
                    expect(ct.items.getAt(0)).toBe(c);
                    expect(ct.items.getAt(1)).toBe(ref);
                });

                it("should be able to move before the last item", function() {
                    makeContainer([{}, c, {}, {}, ref]);
                    expect(ct.moveBefore(c, ref)).toBe(c);
                    expect(ct.items.getAt(3)).toBe(c);
                    expect(ct.items.getAt(4)).toBe(ref);
                });

                it("should be able to move into the middle of the container when before the reference", function() {
                    makeContainer([{}, c, {}, {}, ref, {}, {}, {}]);
                    expect(ct.moveBefore(c, ref)).toBe(c);
                    expect(ct.items.getAt(3)).toBe(c);
                    expect(ct.items.getAt(4)).toBe(ref);
                });

                it("should be able to move into the middle of the container when after the reference", function() {
                    makeContainer([{}, {}, {}, ref, {}, {}, c, {}]);
                    expect(ct.moveBefore(c, ref)).toBe(c);
                    expect(ct.items.getAt(3)).toBe(c);
                    expect(ct.items.getAt(4)).toBe(ref);
                });
            });

            describe("events", function() {
                var spy;

                beforeEach(function() {
                    spy = jasmine.createSpy();
                });

                afterEach(function() {
                    spy = null;
                });

                describe("item exists in the container", function() {
                    it("should not fire the remove event", function() {
                        makeContainer([{}, ref, {}, {}, c]);
                        ct.on('remove', spy);
                        ct.moveBefore(c, ref);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should fire the childmove event & pass the container, component, prevIndex & newIndex", function() {
                        makeContainer([{}, ref, {}, {}, c]);
                        ct.on('childmove', spy);
                        ct.moveBefore(c, ref);

                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(ct);
                        expect(args[1]).toBe(c);
                        expect(args[2]).toBe(4);
                        expect(args[3]).toBe(1);
                    });
                });

                describe("item exists in another container", function() {
                    var other;

                    beforeEach(function() {
                        other = new Ext.container.Container({
                            items: c
                        });
                    });

                    afterEach(function() {
                        other.destroy();
                        other = null;
                    });

                    it("should fire the remove event on the other container", function() {
                        makeContainer([{}, ref, {}]);
                        other.on('remove', spy);
                        ct.moveBefore(c, ref);
                        expect(spy.callCount).toBe(1);
                    });

                    it("should fire the add event & pass the container, component, & index", function() {
                        makeContainer([{}, ref, {}]);
                        ct.on('add', spy);
                        ct.moveBefore(c, ref);

                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(ct);
                        expect(args[1]).toBe(c);
                        expect(args[2]).toBe(1);
                    });
                });

                describe("new item", function() {
                    it("should fire the add event & pass the container, component, & index", function() {
                        makeContainer([{}, ref, {}]);
                        ct.on('add', spy);
                        ct.moveBefore(c, ref);

                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(ct);
                        expect(args[1]).toBe(c);
                        expect(args[2]).toBe(1);
                    });
                });
            });
        });

        describe("moveAfter", function() {
            var ref, c;

            beforeEach(function() {
                ref = new Ext.Component();
                c = new Ext.Component();
            });

            afterEach(function() {
                Ext.destroy(c, ref);
                c = ref = null;
            });

            it("should be able to add a component instance that does not exist in the container", function() {
                makeContainer([ref]);
                expect(ct.moveAfter(c, ref)).toBe(c);
                expect(ct.items.getAt(1)).toBe(c);
                expect(ct.items.getAt(0)).toBe(ref);
            });

            it("should be able to add a component config and return it", function() {
                makeContainer([ref]);

                var fromCfg = ct.moveAfter({
                    xtype: 'panel',
                    title: 'Foo'
                }, ref);

                expect(ct.items.getAt(1)).toBe(fromCfg);
                expect(fromCfg.getTitle()).toBe('Foo');
            });

            it("should return the moved item", function() {
                makeContainer({}, ref, {}, c);
                expect(ct.moveAfter(c, ref)).toBe(c);
            });

            it("should be able to add items from another container", function() {
                var other = new Ext.container.Container({
                    items: c
                });

                makeContainer([ref]);

                expect(ct.moveAfter(c, ref)).toBe(c);
                expect(other.items.indexOf(c)).toBe(-1);
                expect(ct.items.getAt(1)).toBe(c);
                expect(ct.items.getAt(0)).toBe(ref);

                other.destroy();
            });

            it("should be able to move existing items in a container", function() {
                makeContainer([{}, {}, ref, {}, {}, {}, c, {}, {}]);
                expect(ct.moveAfter(c, ref)).toBe(c);
                expect(ct.items.getAt(3)).toBe(c);
                expect(ct.items.getAt(2)).toBe(ref);
            });

            describe("with the item not in the container", function() {
                it("should move to the start if the after reference is null", function() {
                    makeContainer([{}, {}, {}, {}]);
                    expect(ct.moveAfter(c, null)).toBe(c);
                    expect(ct.items.getAt(0)).toBe(c);
                });

                it("should be able to move after the first item", function() {
                    makeContainer([ref, {}, {}, {}]);
                    expect(ct.moveAfter(c, ref)).toBe(c);
                    expect(ct.items.getAt(1)).toBe(c);
                    expect(ct.items.getAt(0)).toBe(ref);
                });

                it("should be able to move after the last item", function() {
                    makeContainer([{}, {}, {}, ref]);
                    expect(ct.moveAfter(c, ref)).toBe(c);
                    expect(ct.items.getAt(4)).toBe(c);
                    expect(ct.items.getAt(3)).toBe(ref);
                });

                it("should be able to add to an empty container", function() {
                    makeContainer([]);
                    expect(ct.moveAfter(c, null)).toBe(c);
                    expect(ct.items.getAt(0)).toBe(c);
                });

                it("should be able to move into the middle of the container", function() {
                    makeContainer([{}, {}, {}, ref, {}, {}, {}]);
                    expect(ct.moveAfter(c, ref)).toBe(c);
                    expect(ct.items.getAt(4)).toBe(c);
                    expect(ct.items.getAt(3)).toBe(ref);
                });
            });

            describe("with the item in the container", function() {
                it("should move to the start if the after reference is null", function() {
                    makeContainer([{}, {}, c, {}, {}]);
                    expect(ct.moveAfter(c, null)).toBe(c);
                    expect(ct.items.getAt(0)).toBe(c);
                });

                it("should do nothing if the reference is the component", function() {
                    makeContainer([{}, c, {}]);
                    expect(ct.moveAfter(c, c)).toBe(c);
                    expect(ct.items.getAt(1)).toBe(c);
                });

                it("should be able to move after the first item", function() {
                    makeContainer([ref, {}, c, {}, {}]);
                    expect(ct.moveAfter(c, ref)).toBe(c);
                    expect(ct.items.getAt(1)).toBe(c);
                    expect(ct.items.getAt(0)).toBe(ref);
                });

                it("should be able to move after the last item", function() {
                    makeContainer([{}, c, {}, {}, ref]);
                    expect(ct.moveAfter(c, ref)).toBe(c);
                    expect(ct.items.getAt(4)).toBe(c);
                    expect(ct.items.getAt(3)).toBe(ref);
                });

                it("should be able to move into the middle of the container when before the reference", function() {
                    makeContainer([{}, c, {}, {}, ref, {}, {}, {}]);
                    expect(ct.moveAfter(c, ref)).toBe(c);
                    expect(ct.items.getAt(4)).toBe(c);
                    expect(ct.items.getAt(3)).toBe(ref);
                });

                it("should be able to move into the middle of the container when after the reference", function() {
                    makeContainer([{}, {}, {}, ref, {}, {}, c, {}]);
                    expect(ct.moveAfter(c, ref)).toBe(c);
                    expect(ct.items.getAt(4)).toBe(c);
                    expect(ct.items.getAt(3)).toBe(ref);
                });
            });

            describe("events", function() {
                var spy;

                beforeEach(function() {
                    spy = jasmine.createSpy();
                });

                afterEach(function() {
                    spy = null;
                });

                describe("item exists in the container", function() {
                    it("should not fire the remove event", function() {
                        makeContainer([{}, ref, {}, {}, c]);
                        ct.on('remove', spy);
                        ct.moveAfter(c, ref);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should fire the childmove event & pass the container, component, prevIndex & newIndex", function() {
                        makeContainer([{}, ref, {}, {}, c]);
                        ct.on('childmove', spy);
                        ct.moveAfter(c, ref);

                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(ct);
                        expect(args[1]).toBe(c);
                        expect(args[2]).toBe(4);
                        expect(args[3]).toBe(2);
                    });
                });

                describe("item exists in another container", function() {
                    var other;

                    beforeEach(function() {
                        other = new Ext.container.Container({
                            items: c
                        });
                    });

                    afterEach(function() {
                        other.destroy();
                        other = null;
                    });

                    it("should fire the remove event on the other container", function() {
                        makeContainer([{}, ref, {}]);
                        other.on('remove', spy);
                        ct.moveAfter(c, ref);
                        expect(spy.callCount).toBe(1);
                    });

                    it("should fire the add event & pass the container, component, & index", function() {
                        makeContainer([{}, ref, {}]);
                        ct.on('add', spy);
                        ct.moveAfter(c, ref);

                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(ct);
                        expect(args[1]).toBe(c);
                        expect(args[2]).toBe(2);
                    });
                });

                describe("new item", function() {
                    it("should fire the add event & pass the container, component, & index", function() {
                        makeContainer([{}, ref, {}]);
                        ct.on('add', spy);
                        ct.moveAfter(c, ref);

                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(ct);
                        expect(args[1]).toBe(c);
                        expect(args[2]).toBe(2);
                    });
                });
            });
        });
    });

    describe("remove", function() {
        var a, b, c;

        function makeContainer(items) {
            ct = new Ext.container.Container({
                items: items ||
                [a, b, c]
            });
        }

        beforeEach(function() {
            a = new Ext.Component({
                itemId: 'item1'
            });

            b = new Ext.Component();
            c = new Ext.Component();
        });

        afterEach(function() {
            a = b = c = Ext.destroy(a, b, c);
        });

        describe("and reAttaching later", function() {
            it("should clear the isDetached flag", function() {
                makeContainer([a]);
                ct.render(document.body);

                ct.remove(a, {
                    destroy: false,
                    detach: true
                });

                expect(a.isDetached).toBe(true);

                ct.add(a);

                expect(a.isDetached).toBe(false);
            });
        });

        describe("Removing during a layout", function() {
            it("Should cancel a component's layout when the component is removed and destroyed", function() {
                // Override afterRender, and remove & destroy the first child component immediately after the second one has rendered
                b.afterRender = Ext.Function.createSequence(b.afterRender, function() {
                    ct.remove(a, true);
                });

                makeContainer([a]);
                ct.render(document.body);

                // Adding b, triggers a layout which renders the new component
                // The afterRender removes and destroys component a. This must remove component a from
                // the running layout context.
                ct.add(b);

                // The clearEl node is the other node in old IE
                expect(ct.layout.innerCt.dom.childNodes.length).toEqual(1);

                expect(ct.layout.innerCt.dom.childNodes[0]).toBe(b.el.dom);
                expect(ct.items.items.length).toEqual(1);
                expect(ct.items.items[0]).toBe(b);
                expect(b.rendered).toBe(true);
                expect(a.destroyed).toBe(true);
                ct.destroy();
            });

            it("Should cancel a component's layout when the component is removed and not destroyed", function() {
                // Override afterRender, and remove the first child component immediately after the second one has rendered
                b.afterRender = Ext.Function.createSequence(b.afterRender, function() {
                    ct.remove(a, false);
                });

                makeContainer([a]);
                ct.render(document.body);

                // Adding b, triggers a layout which renders the new component
                // The afterRender removes and destroys component a. This must remove component a from
                // the running layout context.
                ct.add(b);

                // The clearEl node is the other node in old IE
                expect(ct.layout.innerCt.dom.childNodes.length).toEqual(1);

                expect(ct.layout.innerCt.dom.childNodes[0]).toBe(b.el.dom);
                expect(ct.items.items.length).toEqual(1);
                expect(ct.items.items[0]).toBe(b);
                expect(b.rendered).toBe(true);
                expect(a.destroyed).toBeFalsy();
                ct.destroy();
            });
        });

        describe("if the component isn't in the container", function() {
            var cmp;

            beforeEach(function() {
                makeContainer();
                cmp = new Ext.Component();
                ct.remove(cmp);
            });

            afterEach(function() {
                Ext.destroy(cmp);
                cmp = null;
            });

            it("should not remove", function() {
                expect(ct.items.getCount()).toEqual(3);
            });
        });

        describe("if the container is empty", function() {
            beforeEach(function() {
                makeContainer([]);
                ct.remove(a);
            });

            it("should do nothing if the container is empty", function() {
                expect(ct.items.getCount()).toEqual(0);
            });
        });

        it("should remove a floater", function() {
            makeContainer();

            var floater = new Ext.Component({
                floating: true
            });

            ct.add(floater);
            ct.remove(floater);

            expect(floater.destroyed).toBe(true);
        });

        it("should return the removed item", function() {
            makeContainer();
            expect(ct.remove(b)).toEqual(b);
        });

        it("should be able to remove by instance", function() {
            makeContainer();
            ct.remove(a);
            expect(ct.items.getCount()).toEqual(2);
        });

        it("should be able to remove by index", function() {
            makeContainer();
            ct.remove(1);
            expect(ct.items.getCount()).toEqual(2);
        });

        it("should be able to remove by id", function() {
            makeContainer();
            ct.remove('item1');
            expect(ct.items.getCount()).toEqual(2);
        });

        it("should be able to be called sequentially", function() {
            makeContainer();
            ct.remove(a);
            ct.remove(b);
            expect(ct.items.getCount()).toEqual(1);
            expect(ct.items.first()).toEqual(c);
        });

        it("should leave items in the correct order", function() {
            makeContainer();
            ct.remove(1);
            expect(ct.items.first()).toEqual(a);
            expect(ct.items.last()).toEqual(c);
        });

        it("should fire beforeremove", function() {
            makeContainer();

            var o = {
                fn: Ext.emptyFn
            };

            spyOn(o, 'fn');
            ct.on('beforeremove', o.fn);
            ct.remove(a);

            // expect(o.fn).toHaveBeenCalledWith(ct, a);
            expect(o.fn).toHaveBeenCalled();
        });

        it("should cancel the remove if beforeremove returns false", function() {
            makeContainer();

            ct.on('beforeremove', function() {
                return false;
            });

            ct.remove(a);

            expect(ct.items.getCount()).toEqual(3);
            expect(ct.items.first()).toEqual(a);
        });

        it("should fire the remove event", function() {
            makeContainer();

            var o = {
                fn: Ext.emptyFn
            };

            spyOn(o, 'fn');
            ct.on('remove', o.fn);
            ct.remove(b);

            // expect(o.fn).toHaveBeenCalledWith(ct, b);
            expect(o.fn).toHaveBeenCalled();
        });

        it("should use container autoDestroy as a default", function() {
            makeContainer();
            ct.remove(a);
            expect(a.destroyed).toBeTruthy();
            ct.autoDestroy = false;
            ct.remove(b);
            expect(b.destroyed).toBeFalsy();
        });

        it("should respect the autoDestroy paramater", function() {
            makeContainer();
            ct.autoDestroy = false;
            ct.remove(a, true);
            expect(a.destroyed).toBeTruthy();
            ct.autoDestroy = true;
            ct.remove(b, false);
            expect(b.destroyed).toBeFalsy();
        });

        it("should move the component to the detachedBody when removed and not destroyed", function() {
            makeContainer();
            ct.render(Ext.getBody());
            ct.remove(a, false);
            expect(a.el.dom.parentNode).not.toBe(ct.el.dom);
        });

        it("should respect the detachOnRemove config option", function() {
            makeContainer();
            ct.detachOnRemove = false;
            ct.render(Ext.getBody());
            ct.remove(a, false);
            expect(a.el.dom.parentNode).toBe(ct.layout.innerCt.dom);
        });

        it("should remove childEls from the cache by id", function() {
            makeContainer({
                xtype: 'textfield',
                id: 'text1'
            });
            ct.render(Ext.getBody());

            ct.remove('text1');
            expect(Ext.cache['text1-inputEl']).toBe(undefined);
        });

        it("should remove childEls from the cache by inputId", function() {
            makeContainer({
                xtype: 'textfield',
                inputId: 'foo',
                id: 'text1'
            });
            ct.render(Ext.getBody());

            ct.remove('text1');
            expect(Ext.cache['foo']).toBe(undefined);
        });

        // Newer API with second argument being object with flags instead of boolean
        describe("disposition object", function() {
            beforeEach(function() {
                makeContainer();
                ct.render(Ext.getBody());
            });

            describe("destroy option", function() {
                it("should destroy child when set to true", function() {
                    ct.remove(a, { destroy: true });

                    expect(a.destroyed).toBe(true);
                });

                it("should not destroy child when set to false", function() {
                    ct.remove(a, { destroy: false });

                    expect(a.destroyed).toBe(false);
                    expect(ct.items.indexOf(a)).toBe(-1);
                });

                it("should default to autoDestroy:true when omitted", function() {
                    ct.remove(a, {});

                    expect(a.destroyed).toBe(true);
                });

                it("should not destroy child when omitted and autoDestroy == false", function() {
                    ct.autoDestroy = false;
                    ct.remove(a, {});

                    expect(a.destroyed).toBe(false);
                    expect(ct.items.indexOf(a)).toBe(-1);
                });
            });

            describe("detach option", function() {
                beforeEach(function() {
                    ct.autoDestroy = false;
                });

                it("should detach child when set to true", function() {
                    ct.remove(a, { detach: true });

                    expect(a.destroyed).toBe(false);
                    expect(ct.items.indexOf(a)).toBe(-1);
                    expect(a.el.dom.parentElement).toBe(Ext.getDetachedBody().dom);
                });

                it("should not detach child when set to false", function() {
                    ct.remove(a, { detach: false });

                    expect(a.destroyed).toBe(false);
                    expect(ct.items.indexOf(a)).toBe(-1);
                    expect(a.el.dom.parentElement).toBe(b.el.dom.parentElement);
                });

                it("should default to detachOnRemove:true when omitted", function() {
                    ct.remove(a, {});

                    expect(a.destroyed).toBe(false);
                    expect(ct.items.indexOf(a)).toBe(-1);
                    expect(a.el.dom.parentElement).toBe(Ext.getDetachedBody().dom);
                });

                it("should not detach child when omitted and detachOnRemove == false", function() {
                    ct.detachOnRemove = false;
                    ct.remove(a, {});

                    expect(a.destroyed).toBe(false);
                    expect(ct.items.indexOf(a)).toBe(-1);
                    expect(a.el.dom.parentElement).toBe(b.el.dom.parentElement);
                });
            });
        });
    });

    describe("removeAll", function() {
        var a, b, c;

        function makeContainer(items) {
            ct = new Ext.container.Container({
                items: items ||
                [a, b, c]
            });
        }

        beforeEach(function() {
            a = new Ext.Component({
                itemId: 'item1'
            });
            b = new Ext.Component();
            c = new Ext.Component();
        });

        afterEach(function() {
            a.destroy();
            b.destroy();
            c.destroy();
            a = b = c = null;
        });

        it("should do nothing if the container is empty", function() {
            makeContainer([]);
            ct.removeAll();
            expect(ct.items.getCount()).toEqual(0);
        });

        it("should remove all the items", function() {
            makeContainer();
            ct.removeAll();
            expect(ct.items.getCount()).toEqual(0);
        });

        it("should remove all floating components", function() {
            var floater = new Ext.Component({
                floating: true
            });

            makeContainer();
            ct.add(floater);
            ct.removeAll();
            expect(floater.destroyed).toBe(true);
        });

        it("should return the removed items", function() {
            var result;

            makeContainer();

            result = ct.removeAll();

            expect(result[0]).toEqual(a);
            expect(result[1]).toEqual(b);
            expect(result[2]).toEqual(c);
        });

        it("should include floating items in the return statement", function() {
            var floater = new Ext.Component({
                    floating: true
                }),
                result;

            makeContainer();
            ct.add(floater);
            result = ct.removeAll();
            expect(result[3]).toBe(floater);
        });

        it("should destroy items if autoDestroy is true", function() {
            makeContainer();
            ct.removeAll(true);
            expect(a.destroyed).toBeTruthy();
            expect(b.destroyed).toBeTruthy();
            expect(c.destroyed).toBeTruthy();
        });

        it("should not destroy items if autoDestroy is false", function() {
            makeContainer();
            ct.removeAll(false);
            expect(a.destroyed).toBeFalsy();
            expect(b.destroyed).toBeFalsy();
            expect(c.destroyed).toBeFalsy();
        });

        it("should remove childEls from the cache by id", function() {
            makeContainer({
                xtype: 'textfield',
                id: 'text1'
            });
            ct.render(Ext.getBody());

            ct.removeAll();
            expect(Ext.cache['text1-inputEl']).toBe(undefined);
        });

        it("should remove childEls from the cache by inputId", function() {
            makeContainer({
                xtype: 'textfield',
                inputId: 'foo'
            });
            ct.render(Ext.getBody());

            ct.removeAll();
            expect(Ext.cache['foo']).toBe(undefined);
        });
    });

    describe("defaults", function() {
        describe("using proper configs", function() {
            beforeEach(function() {
                Ext.define('spec.Cmp', {
                    extend: 'Ext.Component',
                    xtype: 'spec.cmp',
                    config: {
                        foo: null
                    }
                });
            });

            afterEach(function() {
                Ext.undefine('spec.Cmp');
            });

            it("should apply default to component config", function() {
                ct = makeContainer({
                    defaults: {
                        foo: 1
                    },
                    items: {
                        xtype: 'spec.cmp'
                    }
                });

                expect(ct.items.getAt(0).getFoo()).toBe(1);
            });

            it("should apply default to component instance", function() {
                var cmp = new spec.Cmp();

                spyOn(cmp, 'setFoo').andCallThrough();

                ct = makeContainer({
                    defaults: {
                        foo: 1
                    },
                    items: cmp
                });

                expect(cmp.setFoo).toHaveBeenCalledWith(1);
                expect(cmp.getFoo()).toBe(1);
            });

            it("should not apply default to component config if property exists in config", function() {
                ct = makeContainer({
                    defaults: {
                        foo: 1
                    },
                    items: {
                        xtype: 'spec.cmp',
                        foo: 2
                    }
                });

                expect(ct.items.getAt(0).getFoo()).toBe(2);
            });

            it("should not apply default to component instance if config is already set on the instance", function() {
                var cmp = new spec.Cmp({
                    foo: 2
                });

                spyOn(cmp, 'setFoo').andCallThrough();

                ct = makeContainer({
                    defaults: {
                        foo: 1
                    },
                    items: cmp
                });

                expect(cmp.setFoo).not.toHaveBeenCalled();
                expect(cmp.getFoo()).toBe(2);
            });
        });

        describe("using old-style configs", function() {
            beforeEach(function() {
                Ext.define('spec.Cmp', {
                    extend: 'Ext.Component',
                    xtype: 'spec.cmp'
                });
            });

            afterEach(function() {
                Ext.undefine('spec.Cmp');
            });

            it("should apply default to component config", function() {
                ct = makeContainer({
                    defaults: {
                        foo: 1
                    },
                    items: {
                        xtype: 'spec.cmp'
                    }
                });

                expect(ct.items.getAt(0).foo).toBe(1);
            });

            it("should apply default to component instance", function() {
                var cmp = new spec.Cmp();

                ct = makeContainer({
                    defaults: {
                        foo: 1
                    },
                    items: cmp
                });

                expect(cmp.foo).toBe(1);
            });

            it("should not apply default to component config if property exists in config", function() {
                ct = makeContainer({
                    defaults: {
                        foo: 1
                    },
                    items: {
                        xtype: 'spec.cmp',
                        foo: 2
                    }
                });

                expect(ct.items.getAt(0).foo).toBe(2);
            });

            it("should not apply default to component instance if config is already set on the instance", function() {
                var cmp = new spec.Cmp({
                    foo: 2
                });

                ct = makeContainer({
                    defaults: {
                        foo: 1
                    },
                    items: cmp
                });

                expect(cmp.foo).toBe(2);
            });
        });

        it("should accept a defaults function", function() {
            makeContainer({
                defaults: function() {
                    return {
                        disabled: true
                    };
                },
                items: [{}, {}]
            });
            expect(ct.items.first().disabled).toBeTruthy();
            expect(ct.items.last().disabled).toBeTruthy();
        });

        it("should not apply defaults to component instances", function() {
            makeContainer({
                items: new Ext.Component({
                    disabled: false
                }),
                defaults: {
                    disabled: true
                }
            });
            expect(ct.items.first().disabled).toBe(false);
        });

        it("should only apply defaults to configs if they don't exist", function() {
            makeContainer({
                items: {
                    disabled: false
                },
                defaults: {
                    disabled: true,
                    hidden: true
                }
            });
            expect(ct.items.first().disabled).toBeFalsy();
            expect(ct.items.first().hidden).toBeTruthy();
        });
    });

    // the intent here is not to test ComponentQuery, just that the API calls the appropriate methods
    describe("ComponentQuery", function() {
        beforeEach(function() {
            ct = new Ext.container.Container({
                items: [{
                    foo: 1,
                    id: 'top1',
                    items: [{
                        foo: 3,
                        id: 'child1'
                    }, {
                        bar: 2,
                        itemId: 'child2',
                        items: [{
                            foo: 5
                        }]
                    }]
                }, {
                    foo: 2,
                    itemId: 'top2',
                    items: [{
                        foo: 7,
                        itemId: 'child3'
                    }, {
                        bar: 4
                    }]
                }, {
                    bar: 3
                }, {
                    foo: 8
                }]
            });
        });

        describe("query", function() {
            it("should return all items if the selector is empty", function() {
                var arr = [];

                function buildItems(root) {
                    root.items.each(function(item) {
                        arr.push(item);
                        buildItems(item);
                    });
                }

                buildItems(ct);
                expect(ct.query()).toEqual(arr);
            });

            it("should return an empty array for no matches", function() {
                var arr = ct.query('list');

                expect(arr).toEqual([]);
            });

            it("should return a filled array with matches", function() {
                var arr = ct.query('#child1');

                expect(arr).toEqual([Ext.getCmp('child1')]);
                arr = ct.query('[foo=1] #child1');
                expect(arr).toEqual([Ext.getCmp('child1')]);
            });

        });

        describe('child', function() {
            it('should return the first item if the selector is empty', function() {
                var c = ct.items.first();

                expect(ct.child()).toBe(c);
            });

            describe('selector is a string', function() {
                it('should return null if no match is found', function() {
                    expect(ct.child('#foo')).toBeNull();
                });

                it('should only return direct children', function() {
                    expect(ct.child('#child3')).toBeNull();
                });

                it('should return matching direct children', function() {
                    var c = ct.items.last();

                    expect(ct.child('component[foo="8"]')).toEqual(c);
                });

                it('should return null if component is not a direct child', function() {
                    var child1 = ct.query('#child1')[0],
                        child2 = ct.query('#child2')[0];

                    expect(ct.child('#child2')).toBeNull();
                    expect(child1.child('#child2')).toBeNull();
                    expect(child2.child('#top1')).toBeNull();
                });
            });

            describe('selector is a component', function() {
                it('should return null if no match is found', function() {
                    var cmp = Ext.create('Ext.Component', {
                        renderTo: document.body
                    });

                    expect(ct.child(cmp)).toBeNull();

                    cmp.destroy();
                    cmp = null;
                });

                it('should only return direct children', function() {
                    var child0 = ct.query('#child1')[0],
                        child1 = ct.query('#child2')[0],
                        child2 = ct.query('#child3')[0];

                    expect(ct.child(child0)).toBeNull();
                    expect(ct.child(child1)).toBeNull();
                    expect(ct.child(child2)).toBeNull();
                });

                it('should return matching direct children', function() {
                    var level0 = ct.query('#top1')[0],
                        c = ct.items.last();

                    expect(ct.child(level0)).toBe(level0);
                    expect(ct.child(c)).toBe(c);
                });

                it('should return null if component is not a direct child', function() {
                    var top1 = ct.query('#top1')[0],
                        child1 = ct.query('#child1')[0],
                        child2 = ct.query('#child2')[0];

                    expect(ct.child(child2)).toBeNull();
                    expect(child1.child(child2)).toBeNull();
                    expect(child2.child(top1)).toBeNull();
                });
            });

            describe('component ids with non alpha-numeric chars', function() {
                var ct,
                    makeCt = function(id1, id2) {
                        return new Ext.container.Container({
                            items: [{
                                id: id1,
                                items: [{
                                    id: id2
                                }, {
                                    itemId: 'child2'
                                }]
                            }]
                        });
                    },
                    name1, name2, child1, child2;

                afterEach(function() {
                    Ext.destroy(ct);
                    ct = child1 = child2 = name1 = name2 = null;
                });

                it('should allow non alpha-numeric chars', function() {
                    name1 = 'a-1_23_456-';
                    name2 = 'b-------222222222______';
                    ct = makeCt(name1, name2);

                    child1 = ct.query('#' + name1)[0];
                    child2 = child1.query('#' + name2)[0];

                    expect(ct.child(child1)).toBe(child1);
                    expect(child1.child(child2)).toBe(child2);

                });
            });
        });

        describe('down', function() {
            it('should return the first item if the selector is empty', function() {
                var c = ct.items.first();

                expect(ct.down()).toBe(c);
            });

            describe('selector is a string', function() {
                it('should return null if no match is found', function() {
                    expect(ct.down('#foo')).toBeNull();
                });

                it('should return null if component is not a descendant', function() {
                    var child1 = ct.query('#child1')[0],
                        child2 = ct.query('#child2')[0];

                    expect(child1.down('#child2')).toBeNull();
                    expect(child2.down('#top1')).toBeNull();
                });

                it('should return children at any level', function() {
                    var c = ct.items.getAt(1).items.first();

                    expect(ct.down('#child3')).toEqual(c);
                });

                it('should return the first match', function() {
                    var c = ct.items.first();

                    expect(ct.down('component[foo]')).toEqual(c);
                });
            });

            describe('selector is a component', function() {
                it('should return null if no match is found', function() {
                    var cmp = Ext.create('Ext.Component', {
                        renderTo: document.body
                    });

                    expect(ct.down(cmp)).toBeNull();

                    cmp.destroy();
                    cmp = null;
                });

                it('should return null if component is not a descendant', function() {
                    var top1 = ct.query('#top1')[0],
                        child1 = ct.query('#child1')[0],
                        child2 = ct.query('#child2')[0];

                    expect(child1.down(child2)).toBeNull();
                    expect(child2.down(top1)).toBeNull();
                });

                it('should return children at any level', function() {
                    var top1 = ct.query('#top1')[0],
                        child1 = ct.query('#child1')[0],
                        child2 = ct.query('#child2')[0];

                    expect(ct.down(top1)).toEqual(top1);
                    expect(ct.down(child1)).toEqual(child1);
                    expect(ct.down(child2)).toEqual(child2);
                });
            });

            describe('component ids with non alpha-numeric chars', function() {
                var ct,
                    makeCt = function(id1, id2) {
                        return new Ext.container.Container({
                            items: [{
                                id: 'foo',
                                items: [{
                                    id: id1
                                }, {
                                    items: [{
                                        itemId: id2
                                    }]
                                }]
                            }]
                        });
                    },
                    name1, name2, descendant1, descendant2;

                afterEach(function() {
                    Ext.destroy(ct);
                    ct = descendant1 = descendant2 = name1 = name2 = null;
                });

                it('should allow non alpha-numeric chars', function() {
                    name1 = 'a-1_23_456-';
                    name2 = 'b-------222222222______';
                    ct = makeCt(name1, name2);

                    descendant1 = ct.query('#' + name1)[0];
                    descendant2 = ct.query('#' + name2)[0];

                    expect(ct.down(descendant1)).toBe(descendant1);
                    expect(ct.down(descendant2)).toBe(descendant2);

                });
            });
        });
    });

    describe("queryBy", function() {
        it("should return no items if the container is empty", function() {
            makeContainer();
            expect(ct.queryBy(function() {})).toEqual([]);
        });

        it("should default the scope to the current component", function() {
            var scopes = [],
                c1 = new Ext.Component(),
                c2 = new Ext.Component(),
                c3 = new Ext.Component();

            makeContainer({
                items: [c1, c2, c3]
            });

            ct.queryBy(function(c) {
                scopes.push(c);
            });
            expect(scopes).toEqual([c1, c2, c3]);
        });

        it("should use the specified scope", function() {
            var o = {},
                c1 = new Ext.Component(),
                scope;

            makeContainer({
                items: c1
            });
            ct.queryBy(function() {
                scope = this;
            }, o);

            expect(scope).toBe(o);
        });

        it("should only exclude items if the return value is false", function() {
            var c1 = new Ext.Component(),
                c2 = new Ext.Component(),
                c3 = new Ext.Component();

            makeContainer({
                items: [c1, c2, c3]
            });
            expect(ct.queryBy(function(c) {

            })).toEqual([c1, c2, c3]);
        });

        it("should exclude items if the return value is false", function() {
            var c1 = new Ext.Component(),
                c2 = new Ext.Component(),
                c3 = new Ext.Component();

            makeContainer({
                items: [c1, c2, c3]
            });
            expect(ct.queryBy(function(c) {
                return c !== c2;
            })).toEqual([c1, c3]);
        });

        it("should retrieve items in nested containers", function() {
            var c1 = new Ext.Component(),
                c2 = new Ext.Container({
                    items: c1
                }),
                c3 = new Ext.Container({
                    items: c2
                });

            makeContainer({
                items: c3
            });
            expect(ct.queryBy(function(c) {
                return c === c1;
            })).toEqual([c1]);
        });
    });

    it("should destroy any child items on destroy", function() {
        var a = new Ext.Component(),
            b = new Ext.Component(),
            c = new Ext.Component({
                floating: true
            });

        makeContainer({
            items: [a, b, c]
        });
        ct.destroy();
        expect(a.destroyed).toBe(true);
        expect(b.destroyed).toBe(true);
        expect(c.destroyed).toBe(true);
    });

    describe("enable/disable", function() {
        var disableFn,
            a, b, c, a1, a2, a3, b1, b2, b3;

        var CT = Ext.define(null, {
            extend: 'Ext.container.Container',

            privates: {
                getChildItemsToDisable: function() {
                    return disableFn.call(this);
                }
            }
        });

        beforeEach(function() {
            Ext.define('spec.Custom', {
                extend: 'Ext.Component',
                alias: 'widget.custom',
                disableCount: 0,
                enableCount: 0,
                onDisableCount: 0,
                onEnableCount: 0,

                initComponent: function() {
                    this.on({
                        scope: this,
                        enable: function() {
                            ++this.enableCount;
                        },
                        disable: function() {
                            ++this.disableCount;
                        }
                    });
                    this.callParent();
                },

                onDisable: function() {
                    ++this.onDisableCount;
                    this.callParent(arguments);
                },

                onEnable: function() {
                    ++this.onEnableCount;
                    this.callParent(arguments);
                }
            });

            disableFn = function() {
                return this.query('custom');
            };
        });

        afterEach(function() {
            Ext.undefine('spec.Custom');
            a1 = a2 = a3 = b1 = b2 = b3 = a = b = c = disableFn = null;
        });

        function makeDisableCt(cfg) {
            cfg = Ext.apply({
                defaultType: 'custom'
            }, cfg);
            ct = new CT(cfg);
            a = ct.down('#a');
            b = ct.down('#b');
            c = ct.down('#c');

            a1 = ct.down('#a1');
            a2 = ct.down('#a2');
            a3 = ct.down('#a3');

            b1 = ct.down('#b1');
            b2 = ct.down('#b2');
            b3 = ct.down('#b3');
        }

        function makeItems(ids, ct) {
            var ret = [];

            Ext.Array.forEach(ids, function(id) {
                ret.push({
                    xtype: 'custom',
                    itemId: id
                });
            });

            return ret;
        }

        function expectEnabled() {
            var len = arguments.length,
                i;

            for (i = 0; i < len; ++i) {
                expect(arguments[i].disabled).toBe(false);
            }
        }

        function expectDisabled() {
            var len = arguments.length,
                i;

            for (i = 0; i < len; ++i) {
                expect(arguments[i].disabled).toBe(true);
            }
        }

        function expectOnEnabled() {
            var len = arguments.length,
                count = arguments[0],
                i;

            for (i = 1; i < len; ++i) {
                expect(arguments[i].onEnableCount).toBe(count);
            }
        }

        function expectOnDisabled() {
            var len = arguments.length,
                count = arguments[0],
                i;

            for (i = 1; i < len; ++i) {
                expect(arguments[i].onDisableCount).toBe(count);
            }
        }

        function expectEnableEvent() {
            var len = arguments.length,
                count = arguments[0],
                i;

            for (i = 1; i < len; ++i) {
                expect(arguments[i].enableCount).toBe(count);
            }
        }

        function expectDisableEvent() {
           var len = arguments.length,
                count = arguments[0],
                i;

            for (i = 1; i < len; ++i) {
                expect(arguments[i].disableCount).toBe(count);
            }
        }

        // In thests tests whenever referring to children, this means
        // children that match the childItemsToDisable
        describe("via configuration", function() {
            describe("candidates to disable", function() {
                it("should disable children", function() {
                    makeDisableCt({
                        disabled: true,
                        items: makeItems(['a', 'b', 'c'])
                    });
                    expectDisabled(a, b, c);
                });

                it("should only disable matching children", function() {
                    disableFn = function() {
                        return this.query('#a,#c');
                    };

                    makeDisableCt({
                        disabled: true,
                        items: makeItems(['a', 'b', 'c'])
                    });
                    expectDisabled(a, c);
                    expectEnabled(b);
                });

                it("should disable children deeply", function() {
                    makeDisableCt({
                        disabled: true,
                        items: [{
                            xtype: 'container',
                            itemId: 'a',
                            items: makeItems(['a1', 'a2', 'a3'])
                        }, {
                            xtype: 'container',
                            itemId: 'b',
                            items: makeItems(['b1', 'b2', 'b3'])
                        }]
                    });
                    expectDisabled(a1, a2, a3, b1, b2, b3);
                });

                it("should only disable matching deep children", function() {
                    disableFn = function() {
                        return this.query('#a1,#a3,#b2');
                    };

                    makeDisableCt({
                        disabled: true,
                        items: [{
                            xtype: 'container',
                            itemId: 'a',
                            items: makeItems(['a1', 'a2', 'a3'])
                        }, {
                            xtype: 'container',
                            itemId: 'b',
                            items: makeItems(['b1', 'b2', 'b3'])
                        }]
                    });
                    expectDisabled(a1, a3, b2);
                    expectEnabled(a2, b1, b3);
                });

                it("should not disable non-matching containers", function() {
                    makeDisableCt({
                        disabled: true,
                        items: [{
                            xtype: 'container',
                            itemId: 'a',
                            items: makeItems(['a1', 'a2', 'a3'])
                        }, {
                            xtype: 'container',
                            itemId: 'b',
                            items: makeItems(['b1', 'b2', 'b3'])
                        }]
                    });
                    expectEnabled(a, b);
                });
            });

            describe("events/template methods", function() {
                it("should not fire the disable event", function() {
                    makeDisableCt({
                        disabled: true,
                        items: [{
                            xtype: 'container',
                            itemId: 'a',
                            items: makeItems(['a1', 'a2', 'a3'])
                        }, {
                            xtype: 'container',
                            itemId: 'b',
                            items: makeItems(['b1', 'b2', 'b3'])
                        }]
                    });
                    expectOnDisabled(0, a1, a2, a3, b1, b2, b3);
                });

                it("should not call onDisable until rendered", function() {
                    makeDisableCt({
                        disabled: true,
                        items: [{
                            xtype: 'container',
                            itemId: 'a',
                            items: makeItems(['a1', 'a2', 'a3'])
                        }, {
                            xtype: 'container',
                            itemId: 'b',
                            items: makeItems(['b1', 'b2', 'b3'])
                        }]
                    });
                    expectOnDisabled(0, a1, a2, a3, b1, b2, b3);
                    ct.render(Ext.getBody());
                    expectOnDisabled(1, a1, a2, a3, b1, b2, b3);
                });
            });
        });

        describe("enable", function() {
            describe("before render", function() {
                beforeEach(function() {
                    makeDisableCt({
                        disabled: true,
                        items: [{
                            xtype: 'container',
                            itemId: 'a',
                            items: makeItems(['a1', 'a2', 'a3'])
                        }, {
                            xtype: 'container',
                            itemId: 'b',
                            items: makeItems(['b1', 'b2', 'b3'])
                        }]
                    });
                });

                it("should enable child components", function() {
                    expectDisabled(a1, a2, a3, b1, b2, b3);
                    ct.enable();
                    expectEnabled(a1, a2, a3, b1, b2, b3);
                });

                describe("events/template methods", function() {
                    it("should fire the enable event", function() {
                        ct.enable();
                        expectEnableEvent(1, a1, a2, a3, b1, b2, b3);
                    });

                    it("should not fire the enable event with silent: true", function() {
                        ct.enable(true);
                        expectEnableEvent(0, a1, a2, a3, b1, b2, b3);
                    });

                    it("should not call onEnable", function() {
                        ct.enable();
                        expectOnEnabled(0, a1, a2, a3, b1, b2, b3);
                        ct.render(Ext.getBody());
                        expectOnEnabled(0, a1, a2, a3, b1, b2, b3);
                    });
                });
            });

            describe("after render", function() {
                beforeEach(function() {
                    makeDisableCt({
                        renderTo: Ext.getBody(),
                        disabled: true,
                        items: [{
                            xtype: 'container',
                            itemId: 'a',
                            items: makeItems(['a1', 'a2', 'a3'])
                        }, {
                            xtype: 'container',
                            itemId: 'b',
                            items: makeItems(['b1', 'b2', 'b3'])
                        }]
                    });
                });

                it("should enable child components", function() {
                    expectDisabled(a1, a2, a3, b1, b2, b3);
                    ct.enable();
                    expectEnabled(a1, a2, a3, b1, b2, b3);
                });

                describe("events/template methods", function() {
                    it("should fire the enable event", function() {
                        ct.enable();
                        expectEnableEvent(1, a1, a2, a3, b1, b2, b3);
                    });

                    it("should not fire the enable event with silent: true", function() {
                        ct.enable(true);
                        expectEnableEvent(0, a1, a2, a3, b1, b2, b3);
                    });

                    it("should call onEnable", function() {
                        ct.enable();
                        expectOnEnabled(1, a1, a2, a3, b1, b2, b3);
                    });
                });
            });
        });

        describe("disable", function() {
            describe("before render", function() {
                beforeEach(function() {
                    makeDisableCt({
                        items: [{
                            xtype: 'container',
                            itemId: 'a',
                            items: makeItems(['a1', 'a2', 'a3'])
                        }, {
                            xtype: 'container',
                            itemId: 'b',
                            items: makeItems(['b1', 'b2', 'b3'])
                        }]
                    });
                });

                it("should disable child components", function() {
                    expectEnabled(a1, a2, a3, b1, b2, b3);
                    ct.disable();
                    expectDisabled(a1, a2, a3, b1, b2, b3);
                });

                describe("events/template methods", function() {
                    it("should fire the disable event", function() {
                        ct.disable();
                        expectDisableEvent(1, a1, a2, a3, b1, b2, b3);
                    });

                    it("should not fire the disable event with silent: true", function() {
                        ct.disable(true);
                        expectDisableEvent(0, a1, a2, a3, b1, b2, b3);
                    });

                    it("should not call onDisable until rendering", function() {
                        ct.disable();
                        expectOnDisabled(0, a1, a2, a3, b1, b2, b3);
                        ct.render(Ext.getBody());
                        expectOnDisabled(1, a1, a2, a3, b1, b2, b3);
                    });
                });
            });

            describe("after render", function() {
                beforeEach(function() {
                    makeDisableCt({
                        renderTo: Ext.getBody(),
                        items: [{
                            xtype: 'container',
                            itemId: 'a',
                            items: makeItems(['a1', 'a2', 'a3'])
                        }, {
                            xtype: 'container',
                            itemId: 'b',
                            items: makeItems(['b1', 'b2', 'b3'])
                        }]
                    });
                });

                it("should disable child components", function() {
                    expectEnabled(a1, a2, a3, b1, b2, b3);
                    ct.disable();
                    expectDisabled(a1, a2, a3, b1, b2, b3);
                });

                describe("events/template methods", function() {
                    it("should fire the disable event", function() {
                        ct.disable();
                        expectDisableEvent(1, a1, a2, a3, b1, b2, b3);
                    });

                    it("should not fire the disable event with silent: true", function() {
                        ct.disable(true);
                        expectDisableEvent(0, a1, a2, a3, b1, b2, b3);
                    });

                    it("should call onDisable", function() {
                        ct.disable();
                        expectOnDisabled(1, a1, a2, a3, b1, b2, b3);
                    });
                });
            });
        });

        describe("masking", function() {
            it("should only mask the top level component", function() {
                makeDisableCt({
                    renderTo: Ext.getBody(),
                    disabled: true,
                    items: [{
                        xtype: 'container',
                        itemId: 'a',
                        items: makeItems(['a1'])
                    }]
                });
                expect(ct.isMasked()).toBe(true);
                expect(a.isMasked()).toBe(false);
                expect(a1.isMasked()).toBe(false);
            });
        });

        describe("child state", function() {
            describe("child disabled before container", function() {
                it("should keep the child disabled state", function() {
                    makeDisableCt({
                        items: [{
                            itemId: 'a'
                        }]
                    });

                    a.disable();
                    ct.disable();
                    expect(ct.disabled).toBe(true);
                    expect(a.disabled).toBe(true);

                    ct.enable();
                    expect(ct.disabled).toBe(false);
                    expect(a.disabled).toBe(true);

                });
            });

            describe("child disabled after container", function() {
                it("should keep the child disabled state", function() {
                    makeDisableCt({
                        items: [{
                            itemId: 'a'
                        }]
                    });

                    ct.disable();
                    a.disable();
                    expect(ct.disabled).toBe(true);
                    expect(a.disabled).toBe(true);

                    ct.enable();
                    expect(ct.disabled).toBe(false);
                    expect(a.disabled).toBe(true);

                });
            });

            describe("child disabled before being added to container", function() {
                it("should keep the child disabled state", function() {
                    makeDisableCt();
                    ct.disable();

                    a = new Ext.Component({
                        disabled: true
                    });

                    ct.add(a);
                    expect(ct.disabled).toBe(true);
                    expect(a.disabled).toBe(true);

                    ct.enable();
                    expect(ct.disabled).toBe(false);
                    expect(a.disabled).toBe(true);
                });
            });
        });
    });

    describe('afterrender event', function() {
        var mock,
            fireEventSpy;

        beforeEach(function() {
            mock = { handler: function() {} };
            fireEventSpy = spyOn(mock, 'handler');
        });

        it('should fire "afterrender" after render', function() {
            expect(fireEventSpy.callCount).toEqual(0);

            makeContainer({
                listeners: { afterrender: mock.handler },
                renderTo: Ext.getBody()
            });

            expect(fireEventSpy.callCount).toEqual(1);
        });

        it('should fire "afterrender" after render, with no items', function() {
            expect(fireEventSpy.callCount).toEqual(0);

            makeContainer({
                listeners: { afterrender: mock.handler },
                renderTo: Ext.getBody(),
                items: []
            });

            expect(fireEventSpy.callCount).toEqual(1);
        });

        it('should fire "afterrender" only once with one item', function() {
            expect(fireEventSpy.callCount).toEqual(0);

            makeContainer({
                listeners: { afterrender: mock.handler },
                renderTo: Ext.getBody(),
                items: [{}, {}, {}]
            });

            expect(fireEventSpy.callCount).toEqual(1);
        });

    });

    describe('nextChild', function() {
        var container, age;

        beforeEach(function() {
            container = new Ext.container.Container({
                items: [
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Name',
                        name: 'name'
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Email',
                        name: 'email',
                        vtype: 'email'
                    },
                    {
                        xtype: 'numberfield',
                        fieldLabel: 'Age',
                        name: 'age'
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Country',
                        name: 'country'
                    },
                    {
                        xtype: 'textareafield',
                        fieldLabel: 'Bio',
                        labelAlign: 'top',
                        name: 'bio'
                    }
                ]
            });

            age = container.getComponent(2);
        });

        afterEach(function() {
            container.destroy();
            container = null;
        });

        it('should return the next child', function() {
            expect(container.nextChild(age)).toBe(container.getComponent(3));
        });

        it('should return the next child using a selector', function() {
            expect(container.nextChild(age, 'field[name=bio]')).toBe(container.getComponent(4));
        });

        it("should return null if there is no child", function() {
            expect(container.nextChild(null, 'field[name=bio]')).toBeNull();
        });

        it("should return null if there are no matches", function() {
            expect(container.nextChild(age, 'madeupxtype')).toBeNull();
        });
    });

    describe('prevChild', function() {
        var container, age;

        beforeEach(function() {
            container = new Ext.container.Container({
                items: [
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Name',
                        name: 'name'
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Email',
                        name: 'email',
                        vtype: 'email'
                    },
                    {
                        xtype: 'numberfield',
                        fieldLabel: 'Age',
                        name: 'age'
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Country',
                        name: 'country'
                    },
                    {
                        xtype: 'textareafield',
                        fieldLabel: 'Bio',
                        labelAlign: 'top',
                        name: 'bio'
                    }
                ]
            });

            age = container.getComponent(2);
        });

        afterEach(function() {
            container.destroy();
            container = null;
        });

        it('should return the previous child', function() {
            expect(container.prevChild(age)).toBe(container.getComponent(1));
        });

        it('should return the previous child using a selector', function() {
            expect(container.prevChild(age, 'field[name=name]')).toBe(container.getComponent(0));
        });

        it("should return null if there is no child", function() {
            expect(container.prevChild(null, 'field[name=name]')).toBeNull();
        });

        it("should return null if there are no matches", function() {
            expect(container.prevChild(age, 'madeupxtype')).toBeNull();
        });
    });

    describe('render', function() {
        it('should allow a child item to veto its render', function() {
            var container = Ext.create('Ext.Container', {
                renderTo: document.body,
                items: [{
                    xtype: 'component',
                    listeners: {
                        beforerender: function() {
                            return false;
                        }
                    }
                }]
            });

            // Child not rendered
            expect(container.child().rendered).toBe(false);

            container.destroy();
        });

        it('should only perform one layout on render', function() {
            var container = new Ext.panel.Panel({
                title: 'Panel',
                items: [{
                    xtype: 'gridpanel',
                    columns: [{ text: 'Col1', dataIndex: 'field1' }],
                    store: {
                        fields: ['field1'],
                        data: [{ field1: 'value1' }, { field1: 'value2' }],
                        autoDestroy: true
                    },
                    width: 200
                }, {
                    xtype: 'gridpanel',
                    columns: [{ text: 'Col1', dataIndex: 'field1' }],
                    store: {
                        fields: ['field1'],
                        data: [{ field1: 'value1' }, { field1: 'value2' }],
                        autoDestroy: true
                    },
                    width: 200
                }],
                renderTo: Ext.getBody()
            });

            expect(container.layoutCounter).toBe(1);
            container.destroy();
        });

        // Even with no variableRowHeight setting, view refreshes cause a layout when height is not configured.
        it('should perform three layouts in total if embedded views are shrinkwrap height', function() {
            var container = new Ext.panel.Panel({
                title: 'Panel',
                items: [{
                    xtype: 'gridpanel',
                    columns: [{ text: 'Col1', dataIndex: 'field1' }],
                    store: {
                        fields: ['field1'],
                        data: [{ field1: 'value1' }, { field1: 'value2' }],
                        autoDestroy: true
                    },
                    width: 200
                }, {
                    xtype: 'gridpanel',
                    columns: [{ text: 'Col1', dataIndex: 'field1' }],
                    store: {
                        fields: ['field1'],
                        data: [{ field1: 'value1' }, { field1: 'value2' }],
                        autoDestroy: true
                    },
                    width: 200
                }],
                renderTo: Ext.getBody()
            }),
            v1 = container.getComponent(0).view,
            v2 = container.getComponent(1).view;

            v1.refresh();
            v2.refresh();

            // Each layout bubbles to the outermost container
            expect(container.layoutCounter).toBe(3);
            container.destroy();
        });

        // The one layout here is the initial one.
        // With no variableRowHeight setting, view refreshes do NOT causes a layout when height is configured.
        it('should only perform one layout in total if embedded views do deferred refreshes and variableRowHeight is NOT set', function() {
            var container = new Ext.panel.Panel({
                title: 'Panel',
                items: [{
                    xtype: 'gridpanel',
                    columns: [{ text: 'Col1', dataIndex: 'field1' }],
                    store: {
                        fields: ['field1'],
                        data: [{ field1: 'value1' }, { field1: 'value2' }],
                        autoDestroy: true
                    },
                    width: 200,
                    height: 100
                }, {
                    xtype: 'gridpanel',
                    columns: [{ text: 'Col1', dataIndex: 'field1' }],
                    store: {
                        fields: ['field1'],
                        data: [{ field1: 'value1' }, { field1: 'value2' }],
                        autoDestroy: true
                    },
                    width: 200,
                    height: 100
                }],
                renderTo: Ext.getBody()
            }),
            v1 = container.getComponent(0).view,
            v2 = container.getComponent(1).view;

            v1.refresh();
            v2.refresh();

            // No more layouts. We're fixed height and NOT variableRowHeight
            expect(v1.ownerGrid.layoutCounter).toBe(1);
            expect(v2.ownerGrid.layoutCounter).toBe(1);
            expect(container.layoutCounter).toBe(1);
            container.destroy();
        });

        // Three layouts here include the initial one, and then one caused by each data refresh
        // With variableRowHeight as true, view refresh causes a layout
        it('should only perform two grid layouts in total if embedded views have variableRowHeight', function() {
            var container = new Ext.panel.Panel({
                title: 'Panel',
                items: [{
                    xtype: 'gridpanel',
                    variableRowHeight: true,
                    columns: [{ text: 'Col1', dataIndex: 'field1' }],
                    store: {
                        fields: ['field1'],
                        data: [{ field1: 'value1' }, { field1: 'value2' }],
                        autoDestroy: true
                    },
                    width: 200,
                    height: 100
                }, {
                    xtype: 'gridpanel',
                    columns: [{ text: 'Col1', dataIndex: 'field1', variableRowHeight: true }],
                    store: {
                        fields: ['field1'],
                        data: [{ field1: 'value1' }, { field1: 'value2' }],
                        autoDestroy: true
                    },
                    width: 200,
                    height: 100
                }],
                renderTo: Ext.getBody()
            }),
            v1 = container.getComponent(0).view,
            v2 = container.getComponent(1).view;

            v1.refresh();
            v2.refresh();

            expect(v1.ownerGrid.layoutCounter).toBe(2);
            expect(v2.ownerGrid.layoutCounter).toBe(2);

            // Grids are fixed size, so layout does not escape upwards to container
            expect(container.layoutCounter).toBe(1);
            container.destroy();
        });
    });

    describe("hierarchy state", function() {
        var aCfg, a, b, c, fa, fb, fc, fd, fe, ff, fg, fh, fi, fj;

        function createHierarchy(cfg) {
            cfg = cfg || {};

            var floatHidden = cfg.hidden || cfg.collapsed;

            fa = Ext.widget({
                xtype: 'component',
                id: 'fa',
                floating: true,
                shadow: false
            });
            fb = Ext.widget({
                xtype: 'component',
                id: 'fb',
                floating: true,
                shadow: false,
                hidden: floatHidden
            });
            fc = Ext.widget({
                xtype: 'component',
                id: 'fc',
                floating: true,
                shadow: false
            });
            fd = Ext.widget({
                xtype: 'component',
                id: 'fd',
                floating: true,
                shadow: false,
                hidden: floatHidden
            });
            fe = Ext.widget({
                xtype: 'component',
                id: 'fe',
                floating: true,
                shadow: false
            });
            ff = Ext.widget({
                xtype: 'component',
                id: 'ff',
                floating: true,
                shadow: false
            });
            fg = Ext.widget({
                xtype: 'component',
                id: 'fg',
                floating: true,
                shadow: false
            });
            fh = Ext.widget({
                xtype: 'component',
                id: 'fh',
                floating: true,
                shadow: false,
                hidden: floatHidden
            });
            fi = Ext.widget({
                xtype: 'component',
                id: 'fi',
                floating: true,
                shadow: false
            });
            fj = Ext.widget({
                xtype: 'component',
                id: 'fj',
                floating: true,
                shadow: false,
                hidden: floatHidden
            });
            aCfg = {
                renderTo: document.body,
                xtype: 'panel',
                id: 'a',
                animCollapse: false,
                header: {
                    items: [fg, fh]
                },
                items: [{
                    xtype: 'panel',
                    id: 'b',
                    header: {
                        items: [fi, fj]
                    },
                    items: [{
                        xtype: 'container',
                        id: 'c',
                        items: [fa, fb, fc, fd, fe, ff]
                    }]
                }]
            };

            if (cfg.hidden) {
                aCfg.hidden = true;
            }

            if (cfg.collapsed) {
                aCfg.collapsed = true;
            }

            a = Ext.widget(aCfg);
            b = Ext.getCmp('b');
            c = Ext.getCmp('c');
            fa.show();
            fc.show();
            fe.show();
            ff.show();
            fg.show();
            fi.show();

            if (!floatHidden) {
                fb.show();
                fb.hide();
                fd.show();
                fd.hide();
                fh.show();
                fh.hide();
                fj.show();
                fj.hide();
            }
        }

        afterEach(function() {
            // These should be destroyed by being children of a, but lets be sure here
            Ext.destroy(a, b, c, fa, fb, fc, fd, fe, ff, fg, fh, fi, fj);
        });

        it("should chain the hierarchy state of descendants", function() {
            createHierarchy();
            var state = a.getInherited();

            state.foo = 1;
            expect(b.getInherited().foo).toBe(1);
            expect(c.getInherited().foo).toBe(1);
            expect(fa.getInherited().foo).toBe(1);
            expect(fb.getInherited().foo).toBe(1);
        });

        describe("moving to from visible, expanded parent to visible, expanded parent", function() {
            var parent;

            beforeEach(function() {
                createHierarchy();
                parent = Ext.widget({
                    xtype: 'panel',
                    renderTo: document.body
                });
                parent.add(b);
                parent.getInherited().foo = 1;
            });

            afterEach(function() {
                parent.destroy();
            });

            it("should chain the hierarchy state to the new parent", function() {
                expect(b.getInherited().foo).toBe(1);
                expect(c.getInherited().foo).toBe(1);
                expect(fa.getInherited().foo).toBe(1);
            });
        });

        function createHierarchySuite(mode) {
            var oppositeMode, hideOrCollapse, showOrExpand;

            if (mode === 'hidden') {
                oppositeMode = 'visible';
                hideOrCollapse = 'hide';
                showOrExpand = 'show';
            }
            else {
                oppositeMode = 'expanded';
                hideOrCollapse = 'collapse';
                showOrExpand = 'expand';
            }

            describe("hierarchical " + ((mode === 'hidden') ? 'hiding' : 'collapsing'), function() {
                // hierarchically hiding floaters when an ancestor is hidden or collapsed

                beforeEach(function() {
                    createHierarchy();
                });

                it("should set " + mode + " in the hierarchy state", function() {
                    a[hideOrCollapse]();
                    expect(b.getInherited()[mode]).toBe(true);
                    expect(c.getInherited()[mode]).toBe(true);
                    expect(fa.getInherited()[mode]).toBe(true);
                    expect(fb.getInherited()[mode]).toBe(true);
                });

                it("should hide floating descendant", function() {
                    var onHide = jasmine.createSpy(),
                        onBeforeHide = jasmine.createSpy();

                    spyOn(fa, 'hide').andCallThrough();
                    fa.on('beforehide', onBeforeHide);
                    fa.on('hide', onHide);
                    spyOn(fa, 'afterHide').andCallThrough();

                    a[hideOrCollapse]();

                    expect(fa.hide).toHaveBeenCalled();
                    expect(onBeforeHide).toHaveBeenCalled();
                    expect(onHide).toHaveBeenCalled();
                    expect(fa.afterHide).toHaveBeenCalled();
                    expect(fa.hidden).toBe(true);
                    expect(fa.el.isVisible()).toBe(false);
                });

                it("should not hide hidden floating descendant", function() {
                    spyOn(fb, 'hide').andCallThrough();

                    a[hideOrCollapse]();

                    expect(fb.hide).not.toHaveBeenCalled();
                    expect(fb.hidden).toBe(true);
                    expect(fb.el.isVisible()).toBe(false);
                });

                it("should not allow beforehide to veto the hierarchical hide of a floating descendant", function() {
                    var onHide = jasmine.createSpy(),
                        onBeforeHide = jasmine.createSpy().andReturn(false);

                    spyOn(fc, 'hide').andCallThrough();
                    fc.on('beforehide', onBeforeHide);
                    fc.on('hide', onHide);
                    spyOn(fc, 'afterHide').andCallThrough();

                    a[hideOrCollapse]();

                    expect(fc.hide).toHaveBeenCalled();
                    expect(onBeforeHide).toHaveBeenCalled();
                    expect(onHide).toHaveBeenCalled();
                    expect(fc.afterHide).toHaveBeenCalled();
                    expect(fc.hidden).toBe(true);
                    expect(fc.el.isVisible()).toBe(false);
                });

                if (mode === 'hidden') {
                    it("should hide floater that is a child of a collapse-immune child", function() {
                        var onHide = jasmine.createSpy(),
                            onBeforeHide = jasmine.createSpy();

                        spyOn(fg, 'hide').andCallThrough();
                        fg.on('beforehide', onBeforeHide);
                        fg.on('hide', onHide);
                        spyOn(fg, 'afterHide').andCallThrough();

                        a.hide();

                        expect(fg.hide).toHaveBeenCalled();
                        expect(onBeforeHide).toHaveBeenCalled();
                        expect(onHide).toHaveBeenCalled();
                        expect(fg.afterHide).toHaveBeenCalled();
                        expect(fg.hidden).toBe(true);
                        expect(fg.el.isVisible()).toBe(false);
                    });
                }
                else {
                    it("should not hide floater that is a child of a collapse-immune child", function() {
                        spyOn(fg, 'hide').andCallThrough();

                        a.collapse();

                        expect(fg.hide).not.toHaveBeenCalled();
                        expect(fg.hidden).toBe(false);
                        expect(fg.el.isVisible()).toBe(true);
                    });
                }

                it("should not hide hidden floater that is a child of a collapse-immune child", function() {
                    spyOn(fh, 'hide').andCallThrough();

                    a[hideOrCollapse]();

                    expect(fh.hide).not.toHaveBeenCalled();
                    expect(fh.hidden).toBe(true);
                    expect(fh.el.isVisible()).toBe(false);
                });

                it("should hide floater that is a child of a collapse-immune grandchild", function() {
                        var onHide = jasmine.createSpy(),
                            onBeforeHide = jasmine.createSpy();

                        spyOn(fi, 'hide').andCallThrough();
                        fi.on('beforehide', onBeforeHide);
                        fi.on('hide', onHide);
                        spyOn(fi, 'afterHide').andCallThrough();

                        a[hideOrCollapse]();

                        expect(fi.hide).toHaveBeenCalled();
                        expect(onBeforeHide).toHaveBeenCalled();
                        expect(onHide).toHaveBeenCalled();
                        expect(fi.afterHide).toHaveBeenCalled();
                        expect(fi.hidden).toBe(true);
                        expect(fi.el.isVisible()).toBe(false);

                });

                it("should not hide hidden floater that is a child of a collapse-immune grandchild", function() {
                    spyOn(fj, 'hide').andCallThrough();

                    a[hideOrCollapse]();

                    expect(fj.hide).not.toHaveBeenCalled();
                    expect(fj.hidden).toBe(true);
                    expect(fj.el.isVisible()).toBe(false);
                });
            });

            function createShowExpandSuite(initialRenderHiddenOrCollapsed) {
                describe("hierarchical " + ((mode === 'hidden') ? 'showing' : 'expanding') + " (parent " + (initialRenderHiddenOrCollapsed ? ("initially rendered " + mode) : (mode + " after inital render")) + ")", function() {
                    // hierarchically showing floaters when an ancestor is shown or expanded

                    beforeEach(function() {
                        var cfg;

                        if (initialRenderHiddenOrCollapsed) {
                            // initially rendered hidden or collapsed
                            cfg = {};
                            cfg[mode] = true;
                            createHierarchy(cfg);
                        }
                        else {
                            // hidden or collapsed after render
                            createHierarchy();
                            a[mode === 'hidden' ? 'hide' : 'collapse']();
                        }
                    });

                    it("should remove " + mode + " from the hierarchy state", function() {
                        a[showOrExpand]();
                        expect(mode in b.getInherited()).toBe(false);
                        expect(mode in c.getInherited()).toBe(false);
                        expect(mode in fa.getInherited()).toBe(false);
                        expect(mode in fb.getInherited()).toBe(mode === 'hidden' ? true : false);
                    });

                    it("should show hierarchically hidden floating descendant", function() {
                        var onShow = jasmine.createSpy(),
                            onBeforeShow = jasmine.createSpy();

                        spyOn(fa, 'show').andCallThrough();
                        fa.on('beforeshow', onBeforeShow);
                        fa.on('show', onShow);
                        spyOn(fa, 'afterShow').andCallThrough();

                        a[showOrExpand]();

                        expect(fa.show).toHaveBeenCalled();
                        expect(onBeforeShow).toHaveBeenCalled();
                        expect(onShow).toHaveBeenCalled();
                        expect(fa.afterShow).toHaveBeenCalled();
                        expect(fa.hidden).toBe(false);
                        expect(fa.el.isVisible()).toBe(true);
                    });

                    it("should not show explicitly hidden floating descendant", function() {
                        spyOn(fb, 'show').andCallThrough();

                        a[showOrExpand]();

                        expect(fb.show).not.toHaveBeenCalled();
                        expect(fb.hidden).toBe(true);

                        if (initialRenderHiddenOrCollapsed) {
                            expect(fb.rendered).toBe(false);
                        }
                        else {
                            expect(fb.el.isVisible()).toBe(false);
                        }
                    });

                    it("should allow beforeshow to veto the show of a hierarchically hidden floating descendant", function() {
                        var onShow = jasmine.createSpy(),
                            onBeforeShow = jasmine.createSpy().andReturn(false);

                        spyOn(fc, 'show').andCallThrough();
                        fc.on('beforeshow', onBeforeShow);
                        fc.on('show', onShow);
                        spyOn(fc, 'afterShow').andCallThrough();

                        a[showOrExpand]();

                        expect(fc.show).toHaveBeenCalled();
                        expect(onBeforeShow).toHaveBeenCalled();
                        expect(onShow).not.toHaveBeenCalled();
                        expect(fc.afterShow).not.toHaveBeenCalled();

                        if (initialRenderHiddenOrCollapsed) {
                            expect(fb.rendered).toBe(false);
                        }
                        else {
                            expect(fc.hidden).toBe(true);
                            expect(fc.el.isVisible()).toBe(false);
                        }
                    });

                    it("should defer the show of an explicitly hidden floating descendant whose show method was called while hierarchically hidden", function() {
                        var onShow = jasmine.createSpy(),
                            onBeforeShow = jasmine.createSpy(),
                            cb = jasmine.createSpy(),
                            scope = {};

                        fd.on('beforeshow', onBeforeShow);
                        fd.on('show', onShow);
                        spyOn(fd, 'afterShow').andCallThrough();

                        fd.show('foo', cb, scope);

                        expect(onBeforeShow).not.toHaveBeenCalled();
                        expect(onShow).not.toHaveBeenCalled();
                        expect(fd.afterShow).not.toHaveBeenCalled();
                        expect(cb).not.toHaveBeenCalled();

                        spyOn(fd, 'show').andCallThrough();

                        a[showOrExpand]();

                        expect(fd.show).toHaveBeenCalledWith(null, cb, scope);
                        expect(onBeforeShow).toHaveBeenCalled();
                        expect(onShow).toHaveBeenCalled();
                        expect(fd.afterShow).toHaveBeenCalled();
                        expect(cb).toHaveBeenCalled();
                    });

                    it("should not show a hierarchically hidden floating descendant whose hide method was called while hierarchically hidden", function() {
                        fe.hide();
                        spyOn(fe, 'show').andCallThrough();
                        a[showOrExpand]();
                        expect(fe.show).not.toHaveBeenCalled();
                        expect(fe.hidden).toBe(true);

                        if (initialRenderHiddenOrCollapsed) {
                            expect(fb.rendered).toBe(false);
                        }
                        else {
                            expect(fb.el.isVisible()).toBe(false);
                        }
                    });

                    it("should defer the show of a hierarchically hidden floating descendant whose show method was called while hierarchically hidden", function() {
                        var onShow = jasmine.createSpy(),
                            onBeforeShow = jasmine.createSpy(),
                            cb = jasmine.createSpy(),
                            scope = {};

                        ff.on('beforeshow', onBeforeShow);
                        ff.on('show', onShow);
                        spyOn(ff, 'afterShow').andCallThrough();

                        ff.show('foo', cb, scope);

                        expect(onBeforeShow).not.toHaveBeenCalled();
                        expect(onShow).not.toHaveBeenCalled();
                        expect(ff.afterShow).not.toHaveBeenCalled();
                        expect(cb).not.toHaveBeenCalled();

                        spyOn(ff, 'show').andCallThrough();

                        a[showOrExpand]();

                        expect(ff.show).toHaveBeenCalledWith(null, cb, scope);
                        expect(onBeforeShow).toHaveBeenCalled();
                        expect(onShow).toHaveBeenCalled();
                        expect(ff.afterShow).toHaveBeenCalled();
                        expect(cb).toHaveBeenCalled();
                    });

                    if (mode === 'hidden') {
                        it("should show hierarchically hidden floater that is a child of a collapse-immune child", function() {
                            var onShow = jasmine.createSpy(),
                                onBeforeShow = jasmine.createSpy();

                            spyOn(fg, 'show').andCallThrough();
                            fg.on('beforeshow', onBeforeShow);
                            fg.on('show', onShow);
                            spyOn(fg, 'afterShow').andCallThrough();

                            a[showOrExpand]();

                            expect(fg.show).toHaveBeenCalled();
                            expect(onBeforeShow).toHaveBeenCalled();
                            expect(onShow).toHaveBeenCalled();
                            expect(fg.afterShow).toHaveBeenCalled();
                            expect(fg.hidden).toBe(false);
                            expect(fg.el.isVisible()).toBe(true);
                        });
                    }
                    else {
                        it("should not show visible floater that is a child of a collapse-immune child", function() {
                            spyOn(fg, 'show').andCallThrough();

                            a[showOrExpand]();

                            expect(fg.show).not.toHaveBeenCalled();
                            expect(fg.hidden).toBe(false);
                            expect(fg.el.isVisible()).toBe(true);
                        });
                    }

                    it("should not show explicitly hidden floater that is a child of a collapse-immune child", function() {
                        spyOn(fh, 'show').andCallThrough();

                        a[showOrExpand]();

                        expect(fh.show).not.toHaveBeenCalled();
                        expect(fh.hidden).toBe(true);

                        if (initialRenderHiddenOrCollapsed) {
                            expect(fh.rendered).toBe(false);
                        }
                        else {
                            expect(fh.el.isVisible()).toBe(false);
                        }
                    });

                    it("should show hierarchically hidden floater that is a child of a collapse-immune grandchild", function() {
                        var onShow = jasmine.createSpy(),
                            onBeforeShow = jasmine.createSpy();

                        spyOn(fi, 'show').andCallThrough();
                        fi.on('beforeshow', onBeforeShow);
                        fi.on('show', onShow);
                        spyOn(fi, 'afterShow').andCallThrough();

                        a[showOrExpand]();

                        expect(fi.show).toHaveBeenCalled();
                        expect(onBeforeShow).toHaveBeenCalled();
                        expect(onShow).toHaveBeenCalled();
                        expect(fi.afterShow).toHaveBeenCalled();
                        expect(fi.hidden).toBe(false);
                        expect(fi.el.isVisible()).toBe(true);
                    });

                    it("should not show explicitly hidden floater that is a child of a collapse-immune grandchild", function() {
                        spyOn(fj, 'show').andCallThrough();

                        a[showOrExpand]();

                        expect(fj.show).not.toHaveBeenCalled();
                        expect(fj.hidden).toBe(true);

                        if (initialRenderHiddenOrCollapsed) {
                            expect(fj.rendered).toBe(false);
                        }
                        else {
                            expect(fj.el.isVisible()).toBe(false);
                        }
                    });
                });
            }

            createShowExpandSuite();
            createShowExpandSuite(true);

            describe("moving from " + oppositeMode + " parent to " + mode + " parent", function() {
                // moving from visible to hidden, or from expanded to collapsed
                var parent;

                beforeEach(function() {
                    createHierarchy();
                    parent = Ext.widget({
                        xtype: 'panel',
                        renderTo: document.body,
                        hidden: mode === 'hidden',
                        collapsed: mode === 'collapsed'
                    });
                });

                afterEach(function() {
                    parent.destroy();
                });

                it("should chain the hierarchy state to the new parent", function() {
                    parent.add(b);
                    expect(b.getInherited()[mode]).toBe(true);
                    expect(c.getInherited()[mode]).toBe(true);
                    expect(fa.getInherited()[mode]).toBe(true);
                    expect(fb.getInherited()[mode]).toBe(true);
                });

                it("should hide floating descendant", function() {
                    var onHide = jasmine.createSpy(),
                        onBeforeHide = jasmine.createSpy();

                    spyOn(fa, 'hide').andCallThrough();
                    fa.on('beforehide', onBeforeHide);
                    fa.on('hide', onHide);
                    spyOn(fa, 'afterHide').andCallThrough();

                    parent.add(b);

                    expect(fa.hide).toHaveBeenCalled();
                    expect(onBeforeHide).toHaveBeenCalled();
                    expect(onHide).toHaveBeenCalled();
                    expect(fa.afterHide).toHaveBeenCalled();
                    expect(fa.hidden).toBe(true);
                    expect(fa.el.isVisible()).toBe(false);
                });

                it("should not hide hidden floating descendant", function() {
                    spyOn(fb, 'hide').andCallThrough();

                    parent.add(b);

                    expect(fb.hide).not.toHaveBeenCalled();
                    expect(fb.hidden).toBe(true);
                    expect(fb.el.isVisible()).toBe(false);
                });

                it("should not allow beforehide to veto the hierarchical hide of a floating descendant", function() {
                    var onHide = jasmine.createSpy(),
                        onBeforeHide = jasmine.createSpy().andReturn(false);

                    spyOn(fc, 'hide').andCallThrough();
                    fc.on('beforehide', onBeforeHide);
                    fc.on('hide', onHide);
                    spyOn(fc, 'afterHide').andCallThrough();

                    parent.add(b);

                    expect(fc.hide).toHaveBeenCalled();
                    expect(onBeforeHide).toHaveBeenCalled();
                    expect(onHide).toHaveBeenCalled();
                    expect(fc.afterHide).toHaveBeenCalled();
                    expect(fc.hidden).toBe(true);
                    expect(fc.el.isVisible()).toBe(false);
                });

                it("should hide floater that is a child of a hierarchically hidden collapse-immune child", function() {
                        var onHide = jasmine.createSpy(),
                            onBeforeHide = jasmine.createSpy();

                        spyOn(fi, 'hide').andCallThrough();
                        fi.on('beforehide', onBeforeHide);
                        fi.on('hide', onHide);
                        spyOn(fi, 'afterHide').andCallThrough();

                        parent.add(b);

                        expect(fi.hide).toHaveBeenCalled();
                        expect(onBeforeHide).toHaveBeenCalled();
                        expect(onHide).toHaveBeenCalled();
                        expect(fi.afterHide).toHaveBeenCalled();
                        expect(fi.hidden).toBe(true);
                        expect(fi.el.isVisible()).toBe(false);

                });

                it("should not hide hidden floater that is a child of a hierarchically hidden collapse-immune child", function() {
                    spyOn(fj, 'hide').andCallThrough();

                    parent.add(b);

                    expect(fj.hide).not.toHaveBeenCalled();
                    expect(fj.hidden).toBe(true);
                    expect(fj.el.isVisible()).toBe(false);
                });
            });

            function createHiddenToVisibleMoveSuite(initialRenderHiddenOrCollapsed) {
                describe("moving from " + mode + " parent to " + oppositeMode + " (parent" + (initialRenderHiddenOrCollapsed ? ("initially rendered " + mode) : (mode + " after inital render")) + ")", function() {
                    // moving from hidden to visible, or from collapsed to expanded
                    var parent;

                    beforeEach(function() {
                        var cfg;

                        if (initialRenderHiddenOrCollapsed) {
                            // initially rendered hidden or collapsed
                            cfg = {};
                            cfg[mode] = true;
                            createHierarchy(cfg);
                        }
                        else {
                            // hidden or collapsed after render
                            createHierarchy();
                            a[mode === 'hidden' ? 'hide' : 'collapse']();
                        }

                        parent = Ext.widget({
                            xtype: 'panel',
                            renderTo: document.body
                        });
                    });

                    afterEach(function() {
                        parent.destroy();
                    });

                    it("should chain the hierarchy state to the new parent", function() {
                        parent.add(b);
                        expect(mode in b.getInherited()).toBe(false);
                        expect(mode in c.getInherited()).toBe(false);
                        expect(mode in fa.getInherited()).toBe(false);
                        expect(mode in fb.getInherited()).toBe(mode === 'hidden' ? true : false);
                    });

                    it("should show hierarchically hidden floating descendant", function() {
                        var onShow = jasmine.createSpy(),
                            onBeforeShow = jasmine.createSpy();

                        spyOn(fa, 'show').andCallThrough();
                        fa.on('beforeshow', onBeforeShow);
                        fa.on('show', onShow);
                        spyOn(fa, 'afterShow').andCallThrough();

                        parent.add(b);

                        expect(fa.show).toHaveBeenCalled();
                        expect(onBeforeShow).toHaveBeenCalled();
                        expect(onShow).toHaveBeenCalled();
                        expect(fa.afterShow).toHaveBeenCalled();
                        expect(fa.hidden).toBe(false);
                        expect(fa.el.isVisible()).toBe(true);
                    });

                    it("should not show explicitly hidden floating descendant", function() {
                        spyOn(fb, 'show').andCallThrough();

                        parent.add(b);

                        expect(fb.show).not.toHaveBeenCalled();
                        expect(fb.hidden).toBe(true);

                        if (initialRenderHiddenOrCollapsed) {
                            expect(fb.rendered).toBe(false);
                        }
                        else {
                            expect(fb.el.isVisible()).toBe(false);
                        }
                    });

                    it("should allow beforeshow to veto the show of a hierarchically hidden floating descendant", function() {
                        var onShow = jasmine.createSpy(),
                            onBeforeShow = jasmine.createSpy().andReturn(false);

                        spyOn(fc, 'show').andCallThrough();
                        fc.on('beforeshow', onBeforeShow);
                        fc.on('show', onShow);
                        spyOn(fc, 'afterShow').andCallThrough();

                        parent.add(b);

                        expect(fc.show).toHaveBeenCalled();
                        expect(onBeforeShow).toHaveBeenCalled();
                        expect(onShow).not.toHaveBeenCalled();
                        expect(fc.afterShow).not.toHaveBeenCalled();

                        if (initialRenderHiddenOrCollapsed) {
                            expect(fb.rendered).toBe(false);
                        }
                        else {
                            expect(fc.hidden).toBe(true);
                            expect(fc.el.isVisible()).toBe(false);
                        }
                    });

                    it("should defer the show of an explicitly hidden floating descendant whose show method was called while hierarchically hidden", function() {
                        var onShow = jasmine.createSpy(),
                            onBeforeShow = jasmine.createSpy(),
                            cb = jasmine.createSpy(),
                            scope = {};

                        fd.on('beforeshow', onBeforeShow);
                        fd.on('show', onShow);
                        spyOn(fd, 'afterShow').andCallThrough();

                        fd.show('foo', cb, scope);

                        expect(onBeforeShow).not.toHaveBeenCalled();
                        expect(onShow).not.toHaveBeenCalled();
                        expect(fd.afterShow).not.toHaveBeenCalled();
                        expect(cb).not.toHaveBeenCalled();

                        spyOn(fd, 'show').andCallThrough();

                        parent.add(b);

                        expect(fd.show).toHaveBeenCalledWith(null, cb, scope);
                        expect(onBeforeShow).toHaveBeenCalled();
                        expect(onShow).toHaveBeenCalled();
                        expect(fd.afterShow).toHaveBeenCalled();
                        expect(cb).toHaveBeenCalled();
                    });

                    it("should not show a hierarchically hidden floating descendant whose hide method was called while hierarchically hidden", function() {
                        fe.hide();
                        spyOn(fe, 'show').andCallThrough();
                        parent.add(b);
                        expect(fe.show).not.toHaveBeenCalled();
                        expect(fe.hidden).toBe(true);

                        if (initialRenderHiddenOrCollapsed) {
                            expect(fb.rendered).toBe(false);
                        }
                        else {
                            expect(fb.el.isVisible()).toBe(false);
                        }
                    });

                    it("should defer the show of a hierarchically hidden floating descendant whose show method was called while hierarchically hidden", function() {
                        var onShow = jasmine.createSpy(),
                            onBeforeShow = jasmine.createSpy(),
                            cb = jasmine.createSpy(),
                            scope = {};

                        ff.on('beforeshow', onBeforeShow);
                        ff.on('show', onShow);
                        spyOn(ff, 'afterShow').andCallThrough();

                        ff.show('foo', cb, scope);

                        expect(onBeforeShow).not.toHaveBeenCalled();
                        expect(onShow).not.toHaveBeenCalled();
                        expect(ff.afterShow).not.toHaveBeenCalled();
                        expect(cb).not.toHaveBeenCalled();

                        spyOn(ff, 'show').andCallThrough();

                        parent.add(b);

                        expect(ff.show).toHaveBeenCalledWith(null, cb, scope);
                        expect(onBeforeShow).toHaveBeenCalled();
                        expect(onShow).toHaveBeenCalled();
                        expect(ff.afterShow).toHaveBeenCalled();
                        expect(cb).toHaveBeenCalled();
                    });

                    it("should show hierarchically hidden floater that is a child of a collapse-immune grandchild", function() {
                        var onShow = jasmine.createSpy(),
                            onBeforeShow = jasmine.createSpy();

                        spyOn(fi, 'show').andCallThrough();
                        fi.on('beforeshow', onBeforeShow);
                        fi.on('show', onShow);
                        spyOn(fi, 'afterShow').andCallThrough();

                        parent.add(b);

                        expect(fi.show).toHaveBeenCalled();
                        expect(onBeforeShow).toHaveBeenCalled();
                        expect(onShow).toHaveBeenCalled();
                        expect(fi.afterShow).toHaveBeenCalled();
                        expect(fi.hidden).toBe(false);
                        expect(fi.el.isVisible()).toBe(true);
                    });

                    it("should not show explicitly hidden floater that is a child of a collapse-immune grandchild", function() {
                        spyOn(fj, 'show').andCallThrough();

                        parent.add(b);

                        expect(fj.show).not.toHaveBeenCalled();
                        expect(fj.hidden).toBe(true);

                        if (initialRenderHiddenOrCollapsed) {
                            expect(fj.rendered).toBe(false);
                        }
                        else {
                            expect(fj.el.isVisible()).toBe(false);
                        }
                    });
                });
            }

            createHiddenToVisibleMoveSuite();
            createHiddenToVisibleMoveSuite(true);
        }

        createHierarchySuite('hidden');
        createHierarchySuite('collapsed');
    });

    describe("references", function() {
        describe("static", function() {
            it("should not be a reference holder by default", function() {
                makeContainer({
                    items: {
                        xtype: 'component',
                        reference: 'a'
                    }
                });
                expect(ct.lookupReference('foo')).toBeNull();
            });

            it("should support a direct child", function() {
                makeContainer({
                    referenceHolder: true,
                    items: {
                        xtype: 'component',
                        itemId: 'compA',
                        reference: 'a'
                    }
                });
                expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
            });

            it("should support a deep child", function() {
                makeContainer({
                    referenceHolder: true,
                    items: {
                        xtype: 'container',
                        items: {
                            xtype: 'container',
                            items: {
                                xtype: 'container',
                                items: {
                                    xtype: 'component',
                                    itemId: 'compA',
                                    reference: 'a'
                                }
                            }
                        }
                    }
                });
                expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
            });

            it("should support children at multiple depths", function() {
                makeContainer({
                    referenceHolder: true,
                    items: [{
                        xtype: 'component',
                        itemId: 'compA',
                        reference: 'a'
                    }, {
                        xtype: 'container',
                        items: {
                            xtype: 'component',
                            itemId: 'compB',
                            reference: 'b'
                        }
                    }]
                });
                expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                expect(ct.lookupReference('b')).toBe(ct.down('#compB'));
            });

            it("should support multiple children at the same depth", function() {
                makeContainer({
                    referenceHolder: true,
                    items: [{
                        xtype: 'component',
                        itemId: 'compA',
                        reference: 'a'
                    }, {
                        xtype: 'component',
                        itemId: 'compB',
                        reference: 'b'
                    }]
                });
                expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                expect(ct.lookupReference('b')).toBe(ct.down('#compB'));
            });

            it("should support multiple children down the some tree", function() {
                    makeContainer({
                    referenceHolder: true,
                    items: [{
                        xtype: 'container',
                        itemId: 'compA',
                        reference: 'a',
                        items: {
                            xtype: 'container',
                            itemId: 'compB',
                            reference: 'b',
                            items: {
                                xtype: 'component',
                                itemId: 'compC',
                                reference: 'c'
                            }
                        }
                    }]
                });
                expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                expect(ct.lookupReference('b')).toBe(ct.down('#compB'));
                expect(ct.lookupReference('c')).toBe(ct.down('#compC'));
            });

            it("should support a reference holder not being at the root", function() {
                makeContainer({
                    items: {
                        xtype: 'container',
                        items: {
                            xtype: 'container',
                            itemId: 'ref',
                            referenceHolder: true,
                            items: {
                                xtype: 'container',
                                items: {
                                    xtype: 'component',
                                    itemId: 'compA',
                                    reference: 'a'
                                }
                            }
                        }
                    }
                });
                var ref = ct.down('#ref');

                expect(ref.lookupReference('a')).toBe(ref.down('#compA'));
            });

            it("should support multiple ref holders in a tree", function() {
                makeContainer({
                    referenceHolder: true,
                    items: {
                        xtype: 'container',
                        itemId: 'compA',
                        reference: 'a',
                        items: {
                            xtype: 'container',
                            referenceHolder: true,
                            itemId: 'ref',
                            items: {
                                xtype: 'container',
                                items: {
                                    xtype: 'component',
                                    itemId: 'compB',
                                    reference: 'b'
                                }
                            }
                        }
                    }
                });
                var ref = ct.down('#ref');

                expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                expect(ref.lookupReference('b')).toBe(ref.down('#compB'));
            });

            it("should hide inner references from outer holders", function() {
                makeContainer({
                    referenceHolder: true,
                    items: {
                        xtype: 'container',
                        itemId: 'compA',
                        reference: 'a',
                        items: {
                            xtype: 'container',
                            referenceHolder: true,
                            itemId: 'ref',
                            items: {
                                xtype: 'container',
                                items: {
                                    xtype: 'component',
                                    itemId: 'compB',
                                    reference: 'b'
                                }
                            }
                        }
                    }
                });
                expect(ct.lookupReference('b')).toBeNull();
            });

            it("should allow a reference holder to have a reference", function() {
                makeContainer({
                    referenceHolder: true,
                    items: {
                        referenceHolder: true,
                        xtype: 'container',
                        itemId: 'compA',
                        reference: 'a',
                        items: {
                            xtype: 'container',
                            itemId: 'compB',
                            reference: 'b'
                        }
                    }
                });

                var inner = ct.down('#compA');

                expect(inner.lookupReference('b')).toBe(inner.down('#compB'));
                expect(ct.lookupReference('a')).toBe(inner);
            });

            it("should not loose items dom on setting html", function() {
                var childNodesCount;

                makeContainer({
                    renderTo: Ext.getBody(),
                    items: [{
                        xtype: 'combobox'
                    }]
                });

                // Numb of child nodes for 1st item
                childNodesCount = ct.items.items[0].el.dom.childNodes.length;

                // Should have some child nodes
                expect(childNodesCount).toBeGreaterThan(0);

                // update html 
                // In specific case of IE on updating DOM, items losses it child dom nodes.
                ct.setHtml("test");

                // this should not change the childnodes count for item.
                expect(ct.items.items[0].el.dom.childNodes.length).toBe(childNodesCount);
            });

            describe("docking", function() {
                function makePanel(cfg) {
                    ct = new Ext.panel.Panel(cfg);
                }

                it("should get a reference to a direct child", function() {
                    makePanel({
                        referenceHolder: true,
                        dockedItems: {
                            xtype: 'component',
                            itemId: 'compA',
                            reference: 'a'
                        }
                    });
                    expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                });

                it("should get a reference to an indirect child", function() {
                    makePanel({
                        referenceHolder: true,
                        dockedItems: {
                            xtype: 'container',
                            items: {
                                xtype: 'component',
                                itemId: 'compA',
                                reference: 'a'
                            }
                        }
                    });
                    expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                });
            });

            describe("chained references", function() {
                it("should gain a reference to a deep child", function() {
                    makeContainer({
                        referenceHolder: true,
                        items: [{
                            xtype: 'container',
                            reference: 'parent>',
                            items: {
                                xtype: 'component',
                                itemId: 'compA',
                                reference: 'a'
                            }
                        }]
                    });
                    expect(ct.lookupReference('parent.a')).toBe(ct.down('#compA'));
                });

                it("should strip the > from the parent reference", function() {
                    makeContainer({
                        referenceHolder: true,
                        items: [{
                            xtype: 'container',
                            reference: 'a>',
                            itemId: 'compA',
                            items: {
                                xtype: 'component',
                                reference: 'b'
                            }
                        }]
                    });
                    expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                });

                it("should allow the parent to be reference even if there's no children", function() {
                    makeContainer({
                        referenceHolder: true,
                        items: [{
                            xtype: 'container',
                            reference: 'a>',
                            itemId: 'compA'
                        }]
                    });
                    expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                });

                it("should not setup a deep reference if the there's an intervening referenceHolder", function() {
                    makeContainer({
                        referenceHolder: true,
                        items: [{
                            xtype: 'container',
                            referenceHolder: true,
                            reference: 'a>',
                            items: {
                                xtype: 'component',
                                reference: 'b'
                            }
                        }]
                    });
                    expect(ct.lookupReference('b')).toBeNull();
                });

                it("should allow for a multiple depth reference", function() {
                    makeContainer({
                        referenceHolder: true,
                        items: [{
                            xtype: 'container',
                            reference: 'a>',
                            items: {
                                xtype: 'container',
                                reference: 'b>',
                                items: {
                                    xtype: 'container',
                                    reference: 'c>',
                                    items: {
                                        xtype: 'container',
                                        reference: 'd>',
                                        items: {
                                            xtype: 'component',
                                            reference: 'e',
                                            itemId: 'compE'
                                        }
                                    }
                                }
                            }
                        }]
                    });
                    expect(ct.lookupReference('a.b.c.d.e')).toBe(ct.down('#compE'));
                });

                it("should isolate references by parent", function() {
                    makeContainer({
                        referenceHolder: true,
                        items: [{
                            xtype: 'container',
                            reference: 'parent1>',
                            items: {
                                xtype: 'component',
                                reference: 'child',
                                itemId: 'compA'
                            }
                        }, {
                            xtype: 'container',
                            reference: 'parent2>',
                            items: {
                                xtype: 'component',
                                reference: 'child',
                                itemId: 'compB'
                            }
                        }]
                    });
                    expect(ct.lookupReference('parent1.child')).toBe(ct.down('#compA'));
                    expect(ct.lookupReference('parent2.child')).toBe(ct.down('#compB'));
                });

                it("should allow the reference holder to begin at any depth", function() {
                    makeContainer({
                        items: [{
                            xtype: 'container',
                            reference: 'a>',
                            items: {
                                xtype: 'container',
                                reference: 'b>',
                                items: {
                                    xtype: 'container',
                                    referenceHolder: true,
                                    reference: 'c>',
                                    itemId: 'compC',
                                    items: {
                                        xtype: 'container',
                                        reference: 'd>',
                                        items: {
                                            xtype: 'component',
                                            reference: 'e',
                                            itemId: 'compE'
                                        }
                                    }
                                }
                            }
                        }]
                    });
                    var inner = ct.down('#compC');

                    expect(inner.lookupReference('d.e')).toBe(ct.down('#compE'));
                });

                it("should allow multiple references in the tree", function() {
                    makeContainer({
                        referenceHolder: true,
                        items: [{
                            xtype: 'container',
                            reference: 'a>',
                            itemId: 'compA',
                            items: {
                                xtype: 'container',
                                reference: 'b>',
                                itemId: 'compB',
                                items: {
                                    xtype: 'container',
                                    referenceHolder: true,
                                    reference: 'c>',
                                    itemId: 'compC',
                                    items: {
                                        xtype: 'container',
                                        reference: 'd>',
                                        itemId: 'compD',
                                        items: {
                                            xtype: 'component',
                                            reference: 'e',
                                            itemId: 'compE'
                                        }
                                    }
                                }
                            }
                        }]
                    });
                    expect(ct.lookupReference('a.b')).toBe(ct.down('#compB'));
                    expect(ct.lookupReference('a.b.c')).toBe(ct.down('#compC'));
                    expect(ct.lookupReference('a.b.c.d')).toBeNull();
                    expect(ct.lookupReference('a.b.c.d.e')).toBeNull();
                });

                describe("docking", function() {
                    function makePanel(cfg) {
                        ct = new Ext.panel.Panel(cfg);
                    }

                    it("should get a reference to an indirect child", function() {
                        makePanel({
                            referenceHolder: true,
                            dockedItems: {
                                xtype: 'container',
                                reference: 'a>',
                                items: {
                                    xtype: 'component',
                                    itemId: 'compB',
                                    reference: 'b'
                                }
                            }
                        });
                        expect(ct.lookupReference('a.b')).toBe(ct.down('#compB'));
                    });
                });
            });
        });

        describe("dynamic", function() {
            describe("adding", function() {
                it("should gain a reference to a direct child", function() {
                    makeContainer({
                        referenceHolder: true
                    });
                    ct.add({
                        xtype: 'component',
                        itemId: 'compA',
                        reference: 'a'
                    });
                    expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                });

                it("should gain a reference to an indirect child", function() {
                    makeContainer({
                        referenceHolder: true
                    });
                    ct.add({
                        xtype: 'container',
                        items: {
                            xtype: 'component',
                            itemId: 'compA',
                            reference: 'a'
                        }
                    });
                    expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                });

                it("should gain a reference to a component inside an already constructed container", function() {
                    var local = new Ext.container.Container({
                        items: {
                            xtype: 'component',
                            itemId: 'compA',
                            reference: 'a'
                        }
                    });

                    makeContainer({
                        referenceHolder: true,
                        items: local
                    });
                    expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                });

                it("should gain a reference to a component added to containers child", function() {
                    makeContainer({
                        referenceHolder: true,
                        items: {
                            xtype: 'container'
                        }
                    });
                    ct.items.first().add({
                        xtype: 'component',
                        itemId: 'compA',
                        reference: 'a'
                    });
                    expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                });

                describe("chained references", function() {
                    it("should gain a reference to an indirect child", function() {
                        makeContainer({
                            referenceHolder: true
                        });
                        ct.add({
                            xtype: 'container',
                            reference: 'parent>',
                            items: {
                                xtype: 'component',
                                itemId: 'compA',
                                reference: 'a'
                            }
                        });
                        expect(ct.lookupReference('parent.a')).toBe(ct.down('#compA'));
                    });

                    it("should gain a reference to a component inside an already constructed container", function() {
                        var local = new Ext.container.Container({
                            reference: 'parent>',
                            items: {
                                xtype: 'component',
                                itemId: 'compA',
                                reference: 'a'
                            }
                        });

                        makeContainer({
                            referenceHolder: true,
                            items: local
                        });
                        expect(ct.lookupReference('parent.a')).toBe(ct.down('#compA'));
                    });

                    it("should gain a reference to a component added to containers child", function() {
                        makeContainer({
                            referenceHolder: true,
                            items: {
                                xtype: 'container',
                                reference: 'parent>'
                            }
                        });
                        ct.items.first().add({
                            xtype: 'component',
                            itemId: 'compA',
                            reference: 'a'
                        });
                        expect(ct.lookupReference('parent.a')).toBe(ct.down('#compA'));
                    });

                    describe("docking", function() {
                        function makePanel(cfg) {
                            ct = new Ext.panel.Panel(cfg);
                        }

                        it("should gain a reference to an indirect child", function() {
                            makePanel({
                                referenceHolder: true
                            });
                            ct.addDocked({
                                xtype: 'container',
                                reference: 'parent>',
                                items: {
                                    xtype: 'component',
                                    itemId: 'compA',
                                    reference: 'a'
                                }
                            });
                            expect(ct.lookupReference('parent.a')).toBe(ct.down('#compA'));
                        });

                        it("should gain a reference to a component inside an already constructed container", function() {
                            var local = new Ext.container.Container({
                                reference: 'parent>',
                                items: {
                                    xtype: 'component',
                                    itemId: 'compA',
                                    reference: 'a'
                                }
                            });

                            makePanel({
                                referenceHolder: true,
                                dockedItems: local
                            });
                            expect(ct.lookupReference('parent.a')).toBe(ct.down('#compA'));
                        });

                        it("should gain a reference to a component added to containers child", function() {
                            makePanel({
                                referenceHolder: true,
                                dockedItems: {
                                    xtype: 'container',
                                    reference: 'parent>'
                                }
                            });
                            ct.getDockedItems()[0].add({
                                xtype: 'component',
                                itemId: 'compA',
                                reference: 'a'
                            });
                            expect(ct.lookupReference('parent.a')).toBe(ct.down('#compA'));
                        });
                    });
                });

                describe("docking", function() {
                    function makePanel(cfg) {
                        ct = new Ext.panel.Panel(cfg);
                    }

                    it("should gain a reference to a direct child", function() {
                        makePanel({
                            referenceHolder: true
                        });
                        ct.addDocked({
                            xtype: 'component',
                            itemId: 'compA',
                            reference: 'a'
                        });
                        expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                    });

                    it("should gain a reference to an indirect child", function() {
                        makePanel({
                            referenceHolder: true
                        });
                        ct.addDocked({
                            xtype: 'container',
                            items: {
                                xtype: 'component',
                                itemId: 'compA',
                                reference: 'a'
                            }
                        });
                        expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                    });

                    it("should gain a reference to a component inside an already constructed container", function() {
                        var local = new Ext.container.Container({
                            items: {
                                xtype: 'component',
                                itemId: 'compA',
                                reference: 'a'
                            }
                        });

                        makePanel({
                            referenceHolder: true,
                            dockedItems: local
                        });
                        expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                    });

                    it("should gain a reference to a component added to containers child", function() {
                        makePanel({
                            referenceHolder: true,
                            dockedItems: {
                                xtype: 'container'
                            }
                        });
                        ct.getDockedItems()[0].add({
                            xtype: 'component',
                            itemId: 'compA',
                            reference: 'a'
                        });
                        expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
                    });
                });
            });

            describe("removing", function() {
                it("should not have a reference when removing a direct child", function() {
                    makeContainer({
                        referenceHolder: true,
                        items: {
                            xtype: 'component',
                            reference: 'a'
                        }
                    });
                    var c = ct.lookupReference('a');

                    c.destroy();
                    expect(ct.lookupReference('a')).toBeNull();
                });

                it("should not have a reference when removing an indirect child", function() {
                    makeContainer({
                        referenceHolder: true,
                        items: {
                            xtype: 'container',
                            items: {
                                xtype: 'component',
                                reference: 'a'
                            }
                        }
                    });
                    var c = ct.lookupReference('a');

                    c.destroy();
                    expect(ct.lookupReference('a')).toBeNull();
                });

                it("should not have a reference when removing+destroying a container that has references", function() {
                    makeContainer({
                        referenceHolder: true,
                        items: {
                            xtype: 'container',
                            items: {
                                xtype: 'component',
                                reference: 'a'
                            }
                        }
                    });
                    var c = ct.lookupReference('a');

                    var removed = ct.remove(0);

                    expect(ct.lookupReference('a')).toBeNull();
                    removed.destroy();
                });

                it("should not have a reference when only removing a container that has references", function() {
                    makeContainer({
                        referenceHolder: true,
                        items: {
                            xtype: 'container',
                            items: {
                                xtype: 'component',
                                reference: 'a'
                            }
                        }
                    });
                    var c = ct.lookupReference('a');

                    var removed = ct.remove(0, false);

                    expect(ct.lookupReference('a')).toBeNull();
                    removed.destroy();
                });

                describe("chained references", function() {
                    it("should not have a reference when removing an indirect child", function() {
                        makeContainer({
                            referenceHolder: true,
                            items: {
                                xtype: 'container',
                                reference: 'parent>',
                                items: {
                                    xtype: 'component',
                                    reference: 'a'
                                }
                            }
                        });
                        var c = ct.lookupReference('parent.a');

                        c.destroy();
                        expect(ct.lookupReference('parent.a')).toBeNull();
                    });

                    it("should not have a reference when removing+destroying a container that has references", function() {
                        makeContainer({
                            referenceHolder: true,
                            items: {
                                xtype: 'container',
                                reference: 'parent>',
                                items: {
                                    xtype: 'component',
                                    reference: 'a'
                                }
                            }
                        });
                        var removed = ct.remove(0);

                        expect(ct.lookupReference('parent.a')).toBeNull();
                        removed.destroy();
                    });

                    it("should not have a reference when only removing a container that has references", function() {
                        makeContainer({
                            referenceHolder: true,
                            items: {
                                xtype: 'container',
                                reference: 'parent>',
                                items: {
                                    xtype: 'component',
                                    reference: 'a'
                                }
                            }
                        });
                        var removed = ct.remove(0, false);

                        expect(ct.lookupReference('parent.a')).toBeNull();
                        removed.destroy();
                    });

                    describe("docking", function() {
                        function makePanel(cfg) {
                            ct = new Ext.panel.Panel(cfg);
                        }

                        it("should not have a reference when removing an indirect child", function() {
                            makePanel({
                                referenceHolder: true,
                                dockedItems: {
                                    xtype: 'container',
                                    reference: 'parent>',
                                    items: {
                                        xtype: 'component',
                                        reference: 'a'
                                    }
                                }
                            });
                            var c = ct.lookupReference('parent.a');

                            c.destroy();
                            expect(ct.lookupReference('parent.a')).toBeNull();
                        });

                        it("should not have a reference when removing+destroying a container that has references", function() {
                            makePanel({
                                referenceHolder: true,
                                dockedItems: {
                                    xtype: 'container',
                                    reference: 'parent>',
                                    items: {
                                        xtype: 'component',
                                        reference: 'a'
                                    }
                                }
                            });
                            var dock = ct.getDockedItems()[0];

                            ct.removeDocked(dock);
                            expect(ct.lookupReference('parent.a')).toBeNull();
                        });

                        it("should not have a reference when only removing a container that has references", function() {
                            makePanel({
                                referenceHolder: true,
                                dockedItems: {
                                    xtype: 'container',
                                    reference: 'parent>',
                                    items: {
                                        xtype: 'component',
                                        reference: 'a'
                                    }
                                }
                            });
                            var dock = ct.getDockedItems()[0];

                            var removed = ct.removeDocked(dock, false);

                            expect(ct.lookupReference('parent.a')).toBeNull();
                            removed.destroy();
                        });
                    });
                });

                describe("docking", function() {
                    function makePanel(cfg) {
                        ct = new Ext.panel.Panel(cfg);
                    }

                    it("should not have a reference when removing a direct child", function() {
                        makePanel({
                            referenceHolder: true,
                            dockedItems: {
                                xtype: 'component',
                                reference: 'a'
                            }
                        });
                        var c = ct.lookupReference('a');

                        c.destroy();
                        expect(ct.lookupReference('a')).toBeNull();
                    });

                    it("should not have a reference when removing an indirect child", function() {
                        makePanel({
                            referenceHolder: true,
                            dockedItems: {
                                xtype: 'container',
                                items: {
                                    xtype: 'component',
                                    reference: 'a'
                                }
                            }
                        });
                        var c = ct.lookupReference('a');

                        c.destroy();
                        expect(ct.lookupReference('a')).toBeNull();
                    });

                    it("should not have a reference when removing+destroying a container that has references", function() {
                        makePanel({
                            referenceHolder: true,
                            dockedItems: {
                                xtype: 'container',
                                items: {
                                    xtype: 'component',
                                    reference: 'a'
                                }
                            }
                        });
                        var dock = ct.getDockedItems()[0];

                        ct.removeDocked(dock);
                        expect(ct.lookupReference('a')).toBeNull();
                    });

                    it("should not have a reference when only removing a container that has references", function() {
                        makePanel({
                            referenceHolder: true,
                            dockedItems: {
                                xtype: 'container',
                                items: {
                                    xtype: 'component',
                                    reference: 'a'
                                }
                            }
                        });
                        var dock = ct.getDockedItems()[0];

                        var removed = ct.removeDocked(dock, false);

                        expect(ct.lookupReference('a')).toBeNull();
                        removed.destroy();
                    });
                });
            });
        });

        describe("setup", function() {
            it("should not create references on the rootInheritedState if not requested", function() {
                var vp = new Ext.container.Viewport({
                    referenceHolder: true
                });

                var temp = new Ext.container.Container({
                    items: {
                        xtype: 'component',
                        reference: 'a'
                    }
                });

                var c = temp.items.first();

                ct = new Ext.container.Container({
                    referenceHolder: true,
                    items: temp
                });

                expect(vp.lookupReference('a')).toBeNull();
                expect(ct.lookupReference('a')).toBe(c);

                vp.destroy();
            });

            describe("with a controller", function() {
                it("should mark the container as a referenceHolder", function() {
                    makeContainer({
                        controller: 'controller',
                        items: [{
                            reference: 'child'
                        }]
                    });
                    expect(ct.referenceHolder).toBe(true);
                    var c = ct.lookup('child');

                    expect(ct.items.first()).toBe(c);

                    ct.remove(c);

                    expect(ct.lookup('child')).toBeNull();

                    c = ct.add({
                        reference: 'child'
                    });
                    expect(ct.lookup('child')).toBe(c);
                });
            });
        });
    });

    describe("view controllers", function() {
        beforeEach(function() {
            Ext.define('spec.TestController', {
                extend: 'Ext.app.ViewController',
                alias: 'controller.test'
            });
        });

        afterEach(function() {
            Ext.undefine('spec.TestController');
            Ext.Factory.controller.instance.clearCache();
        });

        it("should use a defined controller as a referenceHolder", function() {
            makeContainer({
                controller: 'test',
                items: {
                    xtype: 'component',
                    itemId: 'compA',
                    reference: 'a'
                }
            });
            expect(ct.lookupReference('a')).toBe(ct.down('#compA'));
        });
    });

    describe("defaultListenerScope", function() {
        describe("static", function() {
            it("should fire on a direct parent", function() {
                makeContainer({
                    defaultListenerScope: true,
                    items: {
                        xtype: 'container',
                        itemId: 'compA',
                        listeners: {
                            custom: 'callFn'
                        }
                    }
                });
                ct.callFn = jasmine.createSpy();
                ct.down('#compA').fireEvent('custom');
                expect(ct.callFn).toHaveBeenCalled();
            });

            it("should fire on an indirect parent", function() {
                makeContainer({
                    defaultListenerScope: true,
                    items: {
                        xtype: 'container',
                        items: {
                            xtype: 'container',
                            itemId: 'compA',
                            listeners: {
                                custom: 'callFn'
                            }
                        }
                    }
                });
                ct.callFn = jasmine.createSpy();
                ct.down('#compA').fireEvent('custom');
                expect(ct.callFn).toHaveBeenCalled();
            });

            it("should fire children in the same tree", function() {
                makeContainer({
                    defaultListenerScope: true,
                    items: {
                        xtype: 'container',
                        itemId: 'compA',
                        listeners: {
                            custom: 'callFn'
                        },
                        items: {
                            xtype: 'container',
                            itemId: 'compB',
                            listeners: {
                                custom: 'callFn'
                            }
                        }
                    }
                });
                ct.callFn = jasmine.createSpy();
                ct.down('#compA').fireEvent('custom');
                ct.down('#compB').fireEvent('custom');
                expect(ct.callFn.callCount).toBe(2);
            });

            it("should fire when the ref holder isn't at the root", function() {
                makeContainer({
                    items: {
                        defaultListenerScope: true,
                        xtype: 'container',
                        itemId: 'compA',
                        items: {
                            xtype: 'container',
                            itemId: 'compB',
                            listeners: {
                                custom: 'callFn'
                            }
                        }
                    }
                });
                var c = ct.down('#compA');

                c.callFn = jasmine.createSpy();
                ct.down('#compB').fireEvent('custom');
                expect(c.callFn).toHaveBeenCalled();
            });

            it("should only fire the event at the closest defaultListenerScope holder", function() {
                makeContainer({
                    defaultListenerScope: true,
                    items: {
                        defaultListenerScope: true,
                        xtype: 'container',
                        itemId: 'compA',
                        items: {
                            xtype: 'container',
                            itemId: 'compB',
                            listeners: {
                                custom: 'callFn'
                            }
                        }
                    }
                });
                var c = ct.down('#compA');

                ct.callFn = jasmine.createSpy();
                c.callFn = jasmine.createSpy();

                ct.down('#compB').fireEvent('custom');
                expect(c.callFn).toHaveBeenCalled();
                expect(ct.callFn).not.toHaveBeenCalled();
            });
        });

        describe("dynamic", function() {
            it("should fire on a direct parent", function() {
                makeContainer({
                    defaultListenerScope: true
                });

                var c = ct.add({
                    xtype: 'component',
                    listeners: {
                        custom: 'callFn'
                    }
                });

                ct.callFn = jasmine.createSpy();
                c.fireEvent('custom');
                expect(ct.callFn).toHaveBeenCalled();
            });

            it("should fire on an indirect parent", function() {
                makeContainer({
                    defaultListenerScope: true,
                    items: {
                        xtype: 'container'
                    }
                });

                var c = ct.items.first().add({
                    xtype: 'component',
                    listeners: {
                        custom: 'callFn'
                    }
                });

                ct.callFn = jasmine.createSpy();
                c.fireEvent('custom');
                expect(ct.callFn).toHaveBeenCalled();
            });

            it("should resolve a new method in a new hierarchy", function() {
                makeContainer({
                    defaultListenerScope: true,
                    items: {
                        xtype: 'component',
                        itemId: 'compA',
                        listeners: {
                            custom: 'callFn'
                        }
                    }
                });

                var other = new Ext.container.Container({
                    defaultListenerScope: true
                });

                var c = ct.down('#compA');

                ct.callFn = jasmine.createSpy();
                other.callFn = jasmine.createSpy();

                c.fireEvent('custom');
                expect(ct.callFn).toHaveBeenCalled();

                other.add(c);
                ct.callFn.reset();
                c.fireEvent('custom');

                expect(ct.callFn).not.toHaveBeenCalled();
                expect(other.callFn).toHaveBeenCalled();

                other.destroy();
            });

            it("should resolve a new method in the same hierarchy", function() {
                makeContainer({
                    defaultListenerScope: true,
                    items: {
                        defaultListenerScope: true,
                        xtype: 'container',
                        itemId: 'compA',
                        items: {
                            xtype: 'component',
                            itemId: 'compB',
                            listeners: {
                                custom: 'callFn'
                            }
                        }
                    }
                });

                var inner = ct.down('#compA'),
                    c = ct.down('#compB');

                ct.callFn = jasmine.createSpy();
                inner.callFn = jasmine.createSpy();

                c.fireEvent('custom');
                expect(inner.callFn).toHaveBeenCalled();
                expect(ct.callFn).not.toHaveBeenCalled();

                ct.add(c);
                inner.callFn.reset();

                c.fireEvent('custom');
                expect(ct.callFn).toHaveBeenCalled();
                expect(inner.callFn).not.toHaveBeenCalled();
            });
        });
    });

    describe("cascade", function() {
        var spy;

        beforeEach(function() {
            spy = jasmine.createSpy();
        });

        function comp() {
            return new Ext.Component();
        }

        function wrap(items) {
            return new Ext.container.Container({
                items: items
            });
        }

        function expectCalls(order) {
            var calls = spy.calls,
                len = calls.length,
                i;

            expect(spy.callCount).toBe(order.length);

            for (i = 0; i < len; ++i) {
                expect(calls[i].args[0]).toBe(order[i]);
            }
        }

        it("should include the container as part of the calls", function() {
            makeContainer();
            ct.cascade(spy);
            expectCalls([ct]);
        });

        it("should return the container", function() {
            makeContainer();
            expect(ct.cascade(spy)).toBe(ct);
        });

        describe("call order", function() {
            it("should call the container first", function() {
                var a = comp(),
                    b = comp(),
                    c = comp();

                makeContainer({
                    items: [a, b, c]
                });
                ct.cascade(spy);
                expectCalls([ct, a, b, c]);
            });

            it("should call children in item order", function() {
                var a = comp(),
                    b = comp(),
                    c = comp(),
                    d = comp();

                makeContainer({
                    items: [d, b, a, c]
                });
                ct.cascade(spy);
                expectCalls([ct, d, b, a, c]);
            });

            it("should call depth first", function() {
                var a1 = comp(),
                    a2 = comp(),
                    a3 = comp(),
                    a  = wrap([a1, a2, a3]),
                    b1 = comp(),
                    b  = wrap([b1]),
                    c1 = comp(),
                    c2 = comp(),
                    c  = wrap([c1, c2]),
                    d1 = comp(),
                    d2 = comp(),
                    d3 = comp(),
                    d  = wrap([d1, d2, d3]);

                makeContainer({
                    items: [a, b, c, d]
                });
                ct.cascade(spy);
                expectCalls([
                    ct,
                    a, a1, a2, a3,
                    b, b1,
                    c, c1, c2,
                    d, d1, d2, d3
                ]);
            });
        });

        describe("scoping", function() {
            it("should default the scope to the component", function() {
                var a = comp(),
                    b = comp(),
                    c = comp();

                makeContainer({
                    items: [a, b, c]
                });
                ct.cascade(spy);
                expect(spy.calls[0].object).toBe(ct);
                expect(spy.calls[1].object).toBe(a);
                expect(spy.calls[2].object).toBe(b);
                expect(spy.calls[3].object).toBe(c);
            });

            it("should use a passed scope", function() {
                var a = comp(),
                    b = comp(),
                    c = comp(),
                    scope = {};

                makeContainer({
                    items: [a, b, c]
                });
                ct.cascade(spy, scope);
                expect(spy.calls[0].object).toBe(scope);
                expect(spy.calls[1].object).toBe(scope);
                expect(spy.calls[2].object).toBe(scope);
                expect(spy.calls[3].object).toBe(scope);
            });
        });

        describe("args", function() {
            it("should pass the component as the default args", function() {
                var a = comp(),
                    b = comp(),
                    c = comp();

                makeContainer({
                    items: [a, b, c]
                });
                ct.cascade(spy);
                expectCalls([ct, a, b, c]);
            });

            it("should pass custom args and append the component", function() {
                var a = comp(),
                    b = comp(),
                    c = comp();

                makeContainer({
                    items: [a, b, c]
                });
                ct.cascade(spy, null, [1, 2, 3]);
                expect(spy.calls[0].args).toEqual([1, 2, 3, ct]);
                expect(spy.calls[1].args).toEqual([1, 2, 3, a]);
                expect(spy.calls[2].args).toEqual([1, 2, 3, b]);
                expect(spy.calls[3].args).toEqual([1, 2, 3, c]);
            });
        });

        describe("stopping iteration", function() {
            it("should stop iterating at the container if the callback returns false", function() {
                makeContainer({
                    items: [{}, {}, {}]
                });
                spy = spy.andReturn(false);
                ct.cascade(spy);
                expect(spy.callCount).toBe(1);
            });

            it("should stop iterating deeper if the callback returns false", function() {
                var a1 = comp(),
                    a  = wrap([a1]),
                    b1 = comp(),
                    b  = wrap([b1]),
                    c1 = comp(),
                    c  = wrap([c1]);

                makeContainer({
                    items: [a, b, c]
                });

                spy = spy.andCallFake(function() {
                    // Skip A
                    return this !== a;
                });
                ct.cascade(spy);
                expectCalls([ct, a, b, b1, c, c1]);
            });

            it("should not stop iterating siblings if the callback returns false", function() {
                var a = wrap([]),
                    b = wrap([]),
                    c = wrap([]),
                    d = wrap([]);

                makeContainer({
                    items: [a, b, c, d]
                });
                spy = spy.andCallFake(function() {
                    return this !== b;
                });
                ct.cascade(spy);
                expectCalls([ct, a, b, c, d]);
            });
        });
    });

    // This spec is duplicated in Panel's suite; when adding something
    // really significant don't forget to copy it over there too.
    describe("defaultFocus", function() {
        function makeCt(cfg) {
            makeContainer(Ext.apply({
                renderTo: Ext.getBody(),
                width: 100,
                height: 100
            }, cfg));
        }

        describe("with defaultFocus", function() {
            var fooCmp, barCmp;

            beforeEach(function() {
                makeCt({
                    items: [{
                        xtype: 'component',
                        html: 'foo'
                    }, {
                        xtype: 'component',
                        itemId: 'bar',
                        html: 'bar'
                    }]
                });

                fooCmp = ct.items.getAt(0);
                barCmp = ct.items.getAt(1);
            });

            it("should return foo", function() {
                ct.defaultFocus = 'component';

                var focusEl = ct.getFocusEl();

                expect(focusEl).toBe(fooCmp);
            });

            it("should return bar", function() {
                ct.defaultFocus = '#bar';

                var focusEl = ct.getFocusEl();

                expect(focusEl).toBe(barCmp);
            });
        });

        describe("no defaultFocus", function() {
            beforeEach(function() {
                makeCt();
            });

            it("should return targetEl when focusable", function() {
                ct.focusable = true;

                var focusEl = ct.getFocusEl();

                expect(focusEl).toBe(ct.getTargetEl());
            });

            it("should return undefined when not focusable", function() {
                var focusEl = ct.getFocusEl();

                expect(focusEl).toBe(undefined);
            });
        });
    });

    describe("layout configuration", function() {
        it("should not mutate the initial configuration", function() {
            makeContainer({
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                }
            });

            var l = ct.initialConfig.layout;

            expect(l).toEqual({
                type: 'vbox',
                align: 'stretch'
            });
            expect(l.owner).toBeUndefined();
        });
    });

    describe("layout", function() {
        it("should position window in center", function() {
            var button, win;

            makeContainer({
                renderTo: Ext.getBody(),
                width: '100%',
                height: 1000,
                defaultType: 'component',
                items: [{

                    flex: 1,
                    height: 800
                },
                {
                    xtype: 'button',
                    text: 'test',
                    handler: function() {
                        win = Ext.create('Ext.window.Window', {
                            title: 'MyName',
                            height: 400,
                            width: 400,
                            modal: true
                        }).show();
                    }
                }]
            });

            button = ct.items.items[1];
            button.focus();
            button.click();

            // Focus will bring container to around button. 
            // As btuton is starting at aorund 800
            // So, window should come around between 600 and 800 (700 precise)
            expect(parseInt(win.el.dom.style.top)).toBeGreaterThan(600);
            expect(parseInt(win.el.dom.style.top)).not.toBeGreaterThan(800);

            Ext.destroy(win);
        });

    });
});
