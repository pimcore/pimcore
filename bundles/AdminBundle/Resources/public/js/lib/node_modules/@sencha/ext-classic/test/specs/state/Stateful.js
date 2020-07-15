topSuite("Ext.state.Stateful", ['Ext.Component'], function() {
    var origManager, makeComponent, comp,
        stateData = {};

    beforeEach(function() {
        origManager = Ext.state.Manager;

        Ext.state.Manager = {
            get: function(id) {
                return stateData[id];
            },

            set: function(id, value) {
                stateData[id] = value;
            }
        };

        makeComponent = function(cfg) {
            cfg = cfg || {};
            comp = new Ext.Component(cfg);
        };

    });

    afterEach(function() {
        Ext.state.Manager = origManager;

        if (comp) {
            comp.destroy();
        }

        makeComponent = comp = null;
    });

    describe("initialization", function() {
        beforeEach(function() {
            makeComponent();
        });

        it("should not have a stateId", function() {
            expect(comp.stateId).toBeUndefined();
        });

        it("should have an autoGenId", function() {
            expect(comp.autoGenId).toBe(true);
        });

        it("should have stateEvents as an empty array", function() {
            expect(comp.stateEvents).toEqual([]);
        });

        it("should not be stateful", function() {
            expect(comp.stateful).toBeFalsy();
        });

        it("should set the saveDelay to 100", function() {
            expect(comp.saveDelay).toBe(100);
        });
    });

    describe("state restore", function() {
        it("should not restore state if the component isn't stateful", function() {
            makeComponent({
                stateful: false,
                stateId: 'comp'
            });
            var o = {
                f1: function() {},
                f2: function() {}
            };

            var spy1 = spyOn(o, 'f1'),
                spy2 = spyOn(o, 'f2');

            comp.on({
                beforestaterestore: o.f1,
                staterestore: o.f2
            });

            comp.initState();
            expect(spy1).not.toHaveBeenCalled();
            expect(spy2).not.toHaveBeenCalled();
        });

        it("should not restore state if there is no stateId or autoGenId", function() {
            makeComponent({ stateful: true });
            var o = {
                f1: function() {},
                f2: function() {}
            };

            var spy1 = spyOn(o, 'f1'),
                spy2 = spyOn(o, 'f2');

            comp.on({
                beforestaterestore: o.f1,
                staterestore: o.f2
            });

            comp.initState();
            expect(spy1).not.toHaveBeenCalled();
            expect(spy2).not.toHaveBeenCalled();
        });

        it("should not restore if there is no state data and stateful", function() {
            makeComponent({
                stateId: 'comp',
                stateful: true
            });
            var o = {
                f1: function() {},
                f2: function() {}
            };

            var spy1 = spyOn(o, 'f1'),
                spy2 = spyOn(o, 'f2');

            comp.on({
                beforestaterestore: o.f1,
                staterestore: o.f2
            });

            comp.initState();
            expect(spy1).not.toHaveBeenCalled();
            expect(spy2).not.toHaveBeenCalled();
        });

        it("should not restore the state if beforestaterestore returns false and stateful", function() {
            makeComponent({
                stateId: 'comp',
                stateful: true
            });
            Ext.state.Manager.set('comp', {
                someVar: true
            });
            var o = {
                f1: function() {},
                f2: function() {}
            };

            var spy1 = spyOn(o, 'f1').andReturn(false),
                spy2 = spyOn(o, 'f2');

            comp.on({
                beforestaterestore: o.f1,
                staterestore: o.f2
            });

            comp.initState();
            expect(spy1).toHaveBeenCalled();
            expect(spy2).not.toHaveBeenCalled();
        });

        it("should restore the state if all the appropriate conditions are met", function() {
            makeComponent({
                id: 'comp',
                stateful: true
            });
            Ext.state.Manager.set('comp', {
                someVar: true
            });
            var o = {
                f1: function() {},
                f2: function() {}
            };

            var spy1 = spyOn(o, 'f1'),
                spy2 = spyOn(o, 'f2');

            comp.on({
                beforestaterestore: o.f1,
                staterestore: o.f2
            });

            comp.initState();
            expect(spy1).toHaveBeenCalled();
            expect(spy2).toHaveBeenCalled();
            expect(comp.someVar).toBe(true);
        });
    });

    describe("state save", function() {
        var stateFn = function() {
            return {
                param: 1
            };
        };

        it("should not save if the component isn't stateful", function() {
            makeComponent({
                stateful: false,
                stateId: 'comp',
                getState: stateFn
            });
            var o = {
                f1: function() {},
                f2: function() {}
            };

            var spy1 = spyOn(o, 'f1'),
                spy2 = spyOn(o, 'f2');

            comp.on({
                beforestatesave: o.f1,
                statesave: o.f2
            });

            comp.saveState();
            expect(spy1).not.toHaveBeenCalled();
            expect(spy2).not.toHaveBeenCalled();
        });

        it("should not save state if there is no stateId or autoGenId", function() {
            makeComponent({
                getState: stateFn,
                stateful: true
            });
            var o = {
                f1: function() {},
                f2: function() {}
            };

            var spy1 = spyOn(o, 'f1'),
                spy2 = spyOn(o, 'f2');

            comp.on({
                beforestatesave: o.f1,
                statesave: o.f2
            });

            comp.saveState();
            expect(spy1).not.toHaveBeenCalled();
            expect(spy2).not.toHaveBeenCalled();
        });

        it("should not save the state if beforestatesave returns false", function() {
            makeComponent({
                stateId: 'comp',
                stateful: true,
                getState: stateFn
            });
            Ext.state.Manager.set('comp', {
                someVar: true
            });
            var o = {
                f1: function() {},
                f2: function() {}
            };

            var spy1 = spyOn(o, 'f1').andReturn(false),
                spy2 = spyOn(o, 'f2');

            comp.on({
                beforestatesave: o.f1,
                statesave: o.f2
            });

            comp.saveState();
            expect(spy1).toHaveBeenCalled();
            expect(spy2).not.toHaveBeenCalled();
        });

        it("should save the state if all the appropriate conditions are met", function() {
            makeComponent({
                stateId: 'comp',
                stateful: true,
                getState: stateFn
            });
            var o = {
                f1: function() {},
                f2: function() {}
            };

            var spy1 = spyOn(o, 'f1'),
                spy2 = spyOn(o, 'f2');

            comp.on({
                beforestatesave: o.f1,
                statesave: o.f2
            });

            comp.saveState();
            expect(spy1).toHaveBeenCalled();
            expect(spy2).toHaveBeenCalled();
            expect(Ext.state.Manager.get('comp')).toEqual({
                param: 1
            });
        });
    });

    describe("stateEvents", function() {
        it("should fire the statesave event when a stateEvent is fired", function() {
            makeComponent({
                stateEvents: ['enable', 'disable'],
                stateId: 'comp',
                stateful: true,
                saveDelay: 0
            });
            var o = {
                fn: function() {}
            };

            spyOn(o, 'fn');
            comp.on('statesave', o.fn);
            comp.disable();
            expect(o.fn).toHaveBeenCalled();
        });

        it("should not fire the statesave event when an event not in the stateEvents is fired", function() {
            makeComponent({
                stateEvents: ['enable'],
                stateId: 'comp',
                stateful: true,
                saveDelay: 0
            });
            var o = {
                fn: function() {}
            };

            spyOn(o, 'fn');
            comp.on('statesave', o.fn);
            comp.disable();
            expect(o.fn).not.toHaveBeenCalled();
        });

        it("should buffer the saves", function() {
            makeComponent({
                stateEvents: ['disable'],
                stateId: 'comp',
                stateful: true,
                saveDelay: 1
            });
            var o = {
                fn: function() {}
            };

            spyOn(o, 'fn');
            comp.on('statesave', o.fn);

            for (var i = 0; i < 10; ++i) {
                comp.disable();
            }

            // i know this is frowned upon but it's the easiest way to test
            waits(15);
            runs(function() {
                expect(o.fn.callCount).toBe(1);
            });
        });
    });

    describe("getStateId", function() {
        it("should return a stateId if specified", function() {
            makeComponent({
                stateId: 'foo'
            });
            expect(comp.getStateId()).toBe('foo');
        });

        it("should return null if the component id is auto generated (implicitID = false)", function() {
            makeComponent();
            expect(comp.getStateId()).toBeNull();
        });

        it("should return the id if the id is not auto generated (implicitID = true)", function() {
            makeComponent({
                id: 'bar'
            });
            expect(comp.getStateId()).toBe('bar');
        });
    });

    // TODO - Restore when we restore Ext.state.Items
    xdescribe("itemState Plugin", function() {
        var container, comp,
            makeContainer = function(cfg) {
                cfg = cfg || {};
                container = new Ext.Container(cfg);
            };

        stateData = {};   // Flush for Asynch Container tests

        beforeEach(function() {
            makeContainer({
                stateId: 'foo',
                plugins: { ptype: 'stateitems', id: 'stateplug' },
                layout: { type: 'vbox', align: 'stretch' },
                saveDelay: 10,
                defaults: { flex: 1 },
                items: [
                    { xtype: 'panel', html: 'first item', itemId: 'first' },
                    { xtype: 'panel', html: 'second item', itemId: 'second' }
                ]
            });
        });

        afterEach(function() {
            if (container) {
                container.destroy();
            }

            container = comp = null;
        });

        it("should have 'add, move and remove' in the container's stateEventsByName and be stateful with itemstate plugin specified", function() {

            expect(container.stateful).toBe(true);
            expect(container.stateEventsByName.add).toBe(1);
            expect(container.stateEventsByName.remove).toBe(1);
            expect(container.stateEventsByName.move).toBe(1);

            container.add({ xtype: 'label', text: 'Newcomer' });

            waits(container.saveDelay + 10);
        });

        it("should have item count of 3 after re-instantiation ", function() {
            expect(container.items.getCount()).toBe(3);

            // prepare for next test
            container.remove('second');
            waits(container.saveDelay + 10);

        });

        it("should have item count of 2 after 'remove' of second item after re-instantiation", function() {
            expect(container.items.getCount()).toBe(2);

            // prepare for next test
            comp = container.getComponent('first');
            expect(comp && comp.isPanel).toBe(true);
            expect(container.move(comp, 1)).not.toBe(false);
            waits(container.saveDelay + 10);

        });

        it("should see the moved 'label' to first item after re-instantiation ", function() {
            expect(container.items.getAt(0).getXType()).toBe('label');

            // prepare for next test
            container.removeAll();
            waits(container.saveDelay + 10);

        });

        it("should have NO items after re-instantiation ", function() {
            expect(container.items.getCount()).toBe(0);

        });

    });
});
