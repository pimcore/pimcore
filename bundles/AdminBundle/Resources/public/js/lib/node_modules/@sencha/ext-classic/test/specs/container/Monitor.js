topSuite("Ext.container.Monitor", ['Ext.Container', 'Ext.Button'], function() {
    var ct, mon, makeMon, deepCt, addSpy, removeSpy, invalidateSpy,
        c1, c2, c3;

    beforeEach(function() {
        c1 = new Ext.Component({
            foo: true
        });

        c2 = new Ext.Component();

        c3 = new Ext.Component({
            foo: true
        });

        ct = new Ext.container.Container({
            defaultType: 'container'
        });

        makeMon = function(cfg) {
            mon = new Ext.container.Monitor(cfg || {
                selector: '[foo]',
                addHandler: addSpy,
                removeHandler: removeSpy,
                invalidateHandler: invalidateSpy
            });

            mon.bind(ct);

            // Trigger the cache
            mon.getItems();
        };

        deepCt = function(depth, c) {
            var root = {
                    xtype: 'container'
                },
                out = root;

            while (depth > 1) {
                out.items = {
                    xtype: 'container'
                };
                out = out.items;
                --depth;
            }

            if (c) {
                out.items = c;
            }

            return root;
        };

        addSpy = jasmine.createSpy('add');
        removeSpy = jasmine.createSpy('remove');
        invalidateSpy = jasmine.createSpy('invalidate');
    });

    afterEach(function() {
        Ext.destroy(ct, c1, c2, c3);
        deepCt = makeMon = mon = addSpy = removeSpy = null;
    });

    describe("initialization", function() {
        it("should match direct children of the target", function() {
            ct.add(c1, c2, c3);

            makeMon();

            expect(addSpy.calls.length).toBe(2);
            expect(addSpy.calls[0].args[0]).toBe(c1);
            expect(addSpy.calls[1].args[0]).toBe(c3);
        });

        it("should match docked items", function() {
            ct.destroy();
            ct = new Ext.panel.Panel({
                dockedItems: [{
                    xtype: 'toolbar',
                    items: c1
                }]
            });

            makeMon();

            expect(addSpy.calls.length).toBe(1);
            expect(addSpy.calls[0].args[0]).toBe(c1);
        });

        it("should match Queryable items that aren't containers", function() {
            ct.add(new Ext.button.Button({
                menu: {
                    items: c1
                }
            }));
            makeMon();
            expect(addSpy.calls.length).toBe(1);
            expect(addSpy.calls[0].args[0]).toBe(c1);
        });

        it("should match items inside direct containers of the target", function() {
            ct.add(
                deepCt(1, c1),
                deepCt(1, c2),
                deepCt(1, c3)
            );

            makeMon();

            expect(addSpy.calls.length).toBe(2);
            expect(addSpy.calls[0].args[0]).toBe(c1);
            expect(addSpy.calls[1].args[0]).toBe(c3);
        });

        it("should match items inside deep containers of the target", function() {
            ct.add(
                deepCt(4, c1),
                deepCt(5, c2),
                deepCt(10, c3)
            );

            makeMon();

            expect(addSpy.calls.length).toBe(2);
            expect(addSpy.calls[0].args[0]).toBe(c1);
            expect(addSpy.calls[1].args[0]).toBe(c3);
        });
    });

    describe("dynamic adding", function() {
        var expectContains = function(o) {
            expect(mon.getItems().contains(o)).toBe(true);
        };

        it("should match a direct item of the target", function() {
            makeMon();

            ct.add(c1);
            expect(addSpy.calls.length).toBe(1);
            expect(addSpy.calls[0].args[0]).toBe(c1);
            expectContains(c1);
        });

        it("should match docked items", function() {
            ct.destroy();
            ct = new Ext.panel.Panel();
            makeMon();

            ct.addDocked({
                xtype: 'toolbar',
                items: c1
            });
            expect(addSpy.calls.length).toBe(1);
            expect(addSpy.calls[0].args[0]).toBe(c1);
            expectContains(c1);
        });

        it("should match items inside direct children of the target", function() {
            makeMon();

            var inner = ct.add({});

            inner.add(c1);
            expect(addSpy.calls.length).toBe(1);
            expect(addSpy.calls[0].args[0]).toBe(c1);
            expectContains(c1);
        });

        it("should match Queryable items that aren't containers", function() {
            makeMon();
            ct.add(new Ext.button.Button({
                menu: {
                    items: c1
                }
            }));

            expect(addSpy.calls.length).toBe(1);
            expect(addSpy.calls[0].args[0]).toBe(c1);
            expectContains(c1);
        });

        it("should match items inside deep containers of the target", function() {
            makeMon();

            var inner = new Ext.container.Container();

            ct.add(deepCt(10, inner));

            inner.add(c1);
            expect(addSpy.calls.length).toBe(1);
            expect(addSpy.calls[0].args[0]).toBe(c1);
            expectContains(c1);
        });

        it("should match items directly inside dynamically added containers", function() {
            makeMon();

            ct.add(deepCt(1, c1));
            expect(addSpy.calls.length).toBe(1);
            expect(addSpy.calls[0].args[0]).toBe(c1);
            expectContains(c1);
        });

        it("should match deep items inside dynamically added containers", function() {
            makeMon();

            ct.add(deepCt(5, c1));
            expect(addSpy.calls.length).toBe(1);
            expect(addSpy.calls[0].args[0]).toBe(c1);
            expectContains(c1);
        });

        it("should match items inside dynamic containers", function() {
            makeMon();

            var child1 = new Ext.container.Container();

            ct.add(deepCt(2, child1));

            child1.add(deepCt(3, c1));
            expect(addSpy.calls.length).toBe(1);
            expect(addSpy.calls[0].args[0]).toBe(c1);
            expectContains(c1);
        });
    });

    describe("removing", function() {
        var expectNotContains = function(o) {
            expect(mon.getItems().contains(o)).toBe(false);
        };

        it("should handle removal of direct children of the target", function() {
            ct.add(c1);
            makeMon();

            ct.remove(c1);
            expect(removeSpy.calls.length).toBe(1);
            expect(removeSpy.calls[0].args[0]).toBe(c1);
            expectNotContains(c1);
        });

        it("should handle the removal of docked items", function() {
            ct.destroy();
            ct = new Ext.panel.Panel({
                dockedItems: [{
                    xtype: 'toolbar',
                    items: c1
                }]
            });

            makeMon();
            ct.getDockedItems()[0].remove(c1);
            expect(removeSpy.calls.length).toBe(1);
            expect(removeSpy.calls[0].args[0]).toBe(c1);
            expectNotContains(c1);
        });

        xit("should handle the removal of Queryable items that aren't containers", function() {
            ct.add(new Ext.button.Button({
                menu: {
                    items: c1
                }
            }));

            makeMon();
            ct.remove(0);

            expect(addSpy.calls.length).toBe(1);
            expect(addSpy.calls[0].args[0]).toBe(c1);
            expectNotContains(c1);
        });

        it("should handle removal of items inside direct children of the target", function() {
            ct.add({
                xtype: 'container',
                items: c1
            });
            makeMon();

            ct.items.first().remove(c1);
            expect(removeSpy.calls.length).toBe(1);
            expect(removeSpy.calls[0].args[0]).toBe(c1);
            expectNotContains(c1);
        });

        it("should handle the removal of items in deep containers", function() {
            var inner = new Ext.container.Container({
                items: c1
            });

            ct.add(deepCt(10, inner));
            makeMon();

            inner.remove(c1);
            expect(removeSpy.calls.length).toBe(1);
            expect(removeSpy.calls[0].args[0]).toBe(c1);
            expectNotContains(c1);
        });

        it("should handle the removal of a container that contains an item", function() {
            var inner = new Ext.container.Container({
                items: c1
            });

            ct.add(inner);
            makeMon();
            ct.remove(inner, false);
            expect(removeSpy.calls.length).toBe(1);
            expect(removeSpy.calls[0].args[0]).toBe(c1);
            expectNotContains(c1);

            inner.destroy();
        });

        it("should handle the removal of a deep container that contains an item", function() {
            var inner = new Ext.container.Container({
                items: c1
            });

            ct.add(deepCt(10, inner));
            makeMon();
            inner.ownerCt.remove(inner, false);
            expect(removeSpy.calls.length).toBe(1);
            expect(removeSpy.calls[0].args[0]).toBe(c1);
            expectNotContains(c1);

            inner.destroy();
        });

        it("should update the collection when removing children that contain items matching the selector", function() {
            ct.add({
                xtype: 'container',
                items: c1
            });
            makeMon();

            ct.remove(0);
            expectNotContains(c1);
        });

        it("should update the collection when destroying a container that contains items", function() {
            ct.add({
                xtype: 'container',
                items: c1
            });
            makeMon();
            ct.items.first().destroy();
            expectNotContains(c1);
        });

        describe("container listeners", function() {
            it("should remove the listeners on a direct child ct", function() {
                var inner = new Ext.container.Container({
                    items: c1
                });

                ct.add(inner);
                makeMon();
                ct.remove(inner, false);
                expect(inner.hasListener('add')).toBe(false);
                expect(inner.hasListener('remove')).toBe(false);

                inner.destroy();
            });

            it("should remove listeners on all child containers", function() {
                var inner1 = new Ext.container.Container(),
                    inner2 = new Ext.container.Container(),
                    inner3 = new Ext.container.Container(),
                    inner4 = new Ext.container.Container(),
                    inner5 = new Ext.container.Container();

                inner4.add(inner5);
                inner3.add(inner4);
                inner2.add(inner3);
                inner1.add(inner2);
                ct.add(inner1);

                makeMon();

                inner3.remove(inner4, false);

                expect(inner4.hasListener('add')).toBe(false);
                expect(inner4.hasListener('remove')).toBe(false);
                expect(inner5.hasListener('add')).toBe(false);
                expect(inner5.hasListener('remove')).toBe(false);

                Ext.destroy(inner1, inner2, inner3, inner4, inner5);
            });
        });

        it("should call the invalidateHandler when destroying a container", function() {
            var myCt = new Ext.container.Container({
                items: c1
            });

            ct.add(myCt);

            makeMon();
            ct.remove(myCt);
            expect(invalidateSpy).toHaveBeenCalledWith(mon);
        });
    });

});
