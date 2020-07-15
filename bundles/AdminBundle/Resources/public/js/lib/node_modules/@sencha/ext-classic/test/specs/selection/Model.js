topSuite("Ext.selection.Model", ['Ext.data.ArrayStore'], function() {
    var spy, store, selModel, defaultMode,
        fallbackMode = 'MULTI';

    beforeEach(function() {
        spy = jasmine.createSpy();

        Ext.define("spec.Model", {
            extend: 'Ext.data.Model',

            fields: [
                'id',
                'name'
            ]
        });
    });

    afterEach(function() {
        Ext.undefine('spec.Model');
        Ext.data.Model.schema.clear();
        selModel = store = Ext.destroy(store, selModel);
    });

    function setupModel(mode, data) {
        var i;

        store = new Ext.data.Store({
            model: spec.Model
        });

        if (Ext.isObject(mode)) {
            Ext.applyIf(mode, {
                mode: defaultMode
            });
        }
        else {
            mode = {
                mode: mode || defaultMode || fallbackMode
            };
        }

        if (Ext.isArray(data)) {
            store.loadData(data);
        }
        else {
            data = data || 10;

            for (i = 0; i < data; ++i) {
                store.add({
                    id: i + 1,
                    name: 'Name ' + (i + 1)
                });
            }
        }

        selModel = new Ext.selection.Model(mode);
        selModel.bindStore(store, true);
    }

    function expectSelected(rec) {
        var i, len;

        if (arguments.length === 1) {
            if (typeof rec === 'number') {
                rec = store.getAt(rec);
            }

            expect(selModel.isSelected(rec)).toBe(true);
        }
        else {
            for (i = 0, len = arguments.length; i < len; ++i) {
                expectSelected(arguments[i]);
            }
        }
    }

    function expectNotSelected(rec) {
        var i, len;

        if (arguments.length === 1) {
            if (typeof rec === 'number') {
                rec = store.getAt(rec);
            }

            expect(selModel.isSelected(rec)).toBe(false);
         }
         else {
             for (i = 0, len = arguments.length; i < len; ++i) {
                expectNotSelected(arguments[i]);
            }
         }
    }

    function expectNone() {
        expect(selModel.getCount()).toBe(0);
    }

    function get(index) {
        return store.getAt(index);
    }

    function range(from, to) {
        return store.getRange(from, to);
    }

    function retFalse() {
        return false;
    }

    function select() {
        selModel.select.apply(selModel, arguments);
    }

    function deselect() {
        selModel.deselect.apply(selModel, arguments);
    }

    function selectAll(suppressEvent) {
        selModel.selectAll(suppressEvent);
    }

    function deselectAll(suppressEvent) {
        selModel.deselectAll(suppressEvent);
    }

    function evenOnly(sm, rec) {
        return store.indexOf(rec) % 2 === 0;
    }

    // SIMPLE/MULTI only matters when we're talking about selection from events, so
    // we will cover that later on with specific tests

    describe("select", function() {
        describe('passed selection (`records` function arg)', function() {
            beforeEach(function() {
                setupModel();
                spyOn(selModel, 'doSelect');
            });

            it('should allow a number', function() {
                select(1);
                expect(selModel.doSelect).toHaveBeenCalled();
            });

            it('should allow a model instance', function() {
                select(get(2));
                expect(selModel.doSelect).toHaveBeenCalled();
            });

            it('should allow a non-empty array', function() {
                select(range(4, 6));
                expect(selModel.doSelect).toHaveBeenCalled();
            });

            it('should not allow an empty array', function() {
                select([]);
                expect(selModel.doSelect).not.toHaveBeenCalled();
            });
        });

        describe("single", function() {
            beforeEach(function() {
                defaultMode = 'SINGLE';
                setupModel();
            });

            it("should select a record by index", function() {
                select(1);
                expectSelected(1);
            });

            it("should select a record instance", function() {
                select(get(2));
                expectSelected(2);
            });

            it("should ignore an index not in the store", function() {
                select(100);
                expectNone();
            });

            it("should select the first in an array", function() {
                select(range(4, 6));
                expectSelected(4);
                expectNotSelected(5, 6);
            });

            it("should always deselect an existing item", function() {
                select(1);
                select(2);
                expectSelected(2);
                expectNotSelected(1);
            });

            it("should stop selection if any deselect is vetoed", function() {
                select(1);
                selModel.on('beforedeselect', retFalse);
                select(2);
                expectSelected(1);
                expectNotSelected(2);
            });

            describe("events", function() {

                describe("select", function() {
                    it("should fire an event", function() {
                        selModel.on('select', spy);
                        select(1);
                        expect(spy).toHaveBeenCalled();
                    });

                    it("should pass the selModel & the selected record", function() {
                        selModel.on('select', spy);
                        select(1);
                        expect(spy.mostRecentCall.args[0]).toBe(selModel);
                        expect(spy.mostRecentCall.args[1]).toBe(get(1));
                    });

                    it("should not fire an event if suppressEvent is passed", function() {
                        selModel.on('select', spy);
                        select(1, undefined, true);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should not fire the event if the record is selected", function() {
                        select(1);
                        selModel.on('select', spy);
                        select(1);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should not fire the event if beforeselect vetoes the selection", function() {
                        selModel.on('select', spy);
                        selModel.on('beforeselect', retFalse);
                        select(1);
                        expect(spy).not.toHaveBeenCalled();
                    });
                });

                describe("beforeselect", function() {

                    it("should fire the beforeselect event", function() {
                        selModel.on('beforeselect', spy);
                        select(1);
                        expect(spy).toHaveBeenCalled();
                    });

                    it("should pass the selModel & the record", function() {
                        selModel.on('beforeselect', spy);
                        select(1);
                        expect(spy.mostRecentCall.args[0]).toBe(selModel);
                        expect(spy.mostRecentCall.args[1]).toBe(get(1));
                    });

                    it("should return false to veto the selection", function() {
                        selModel.on('beforeselect', retFalse);
                        select(1);
                        expectNotSelected(1);
                    });

                    it("should not fire the event if suppressEvent is passed", function() {
                        selModel.on('beforeselect', spy);
                        select(1, undefined, true);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should not fire the event if the record is selected", function() {
                        select(1);
                        selModel.on('beforeselect', spy);
                        select(1);
                        expect(spy).not.toHaveBeenCalled();
                    });
                });

                describe("selectionchange", function() {
                    it("should fire the selection change event when an item is selected", function() {
                        selModel.on('selectionchange', spy);
                        select(1);
                        expect(spy).toHaveBeenCalled();
                    });

                    it("should not fire if suppressEvent is passed", function() {
                        selModel.on('selectionchange', spy);
                        select(1, undefined, true);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should not fire if the beforeselect event is vetoed", function() {
                        selModel.on('selectionchange', spy);
                        selModel.on('beforeselect', retFalse);
                        select(1);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should not fire if the item is selected", function() {
                        select(1);
                        selModel.on('selectionchange', spy);
                        select(1);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should fire once if deselecting an existing selection", function() {
                        select(1);
                        selModel.on('selectionchange', spy);
                        select(2);
                        expect(spy.callCount).toBe(1);
                    });

                    it("should not fire if deselecting as part of a select and the deselect it vetoed", function() {
                        select(1);
                        selModel.on('selectionchange', spy);
                        selModel.on('beforedeselect', retFalse);
                        select(2);
                        expect(spy).not.toHaveBeenCalled();
                    });
                });
            });

            it("should do nothing if locked", function() {
                selModel.setLocked(true);
                select(1);
                expectNone();
            });
        });

        describe("multi", function() {
            beforeEach(function() {
                defaultMode = 'MULTI';
                setupModel();
            });

            it("should select a record by index", function() {
                select(1);
                expectSelected(1);
            });

            it("should select a record instance", function() {
                select(get(2));
                expectSelected(2);
            });

            it("should ignore an index not in the store", function() {
                select(100);
                expectNone();
            });

            it("should select all items in an array", function() {
                select(range(4, 6));
                expectSelected(4, 5, 6);
            });

            it("should select a discontinuous range", function() {
                select([get(1), get(4), get(7)]);
                expectSelected(1, 4, 7);
            });

            describe("events", function() {

                describe("select", function() {

                    it("should fire an event", function() {
                        selModel.on('select', spy);
                        select(1);
                        expect(spy).toHaveBeenCalled();
                    });

                    it("should pass the selModel & the selected record", function() {
                        selModel.on('select', spy);
                        select(1);
                        expect(spy.mostRecentCall.args[0]).toBe(selModel);
                        expect(spy.mostRecentCall.args[1]).toBe(get(1));
                    });

                    it("should fire the event for each item", function() {
                        selModel.on('select', spy);
                        select(range(0, 2));
                        expect(spy.calls[0].args[1]).toBe(get(0));
                        expect(spy.calls[1].args[1]).toBe(get(1));
                        expect(spy.calls[2].args[1]).toBe(get(2));
                    });

                    it("should not fire an event if suppressEvent is passed", function() {
                        selModel.on('select', spy);
                        select(1, undefined, true);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should not fire the event if the record is selected", function() {
                        select(1);
                        selModel.on('select', spy);
                        select(1);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should fire the event only for unselected records", function() {
                        select(range(0, 3));
                        selModel.on('select', spy);
                        select(range(0, 5));
                        expect(spy.calls[0].args[1]).toBe(get(4));
                        expect(spy.calls[1].args[1]).toBe(get(5));
                    });
                });

                describe("beforeselect", function() {
                     it("should fire the beforeselect event", function() {
                        selModel.on('beforeselect', spy);
                        select(1);
                        expect(spy).toHaveBeenCalled();
                    });

                    it("should pass the selModel & the record", function() {
                        selModel.on('beforeselect', spy);
                        select(1);
                        expect(spy.mostRecentCall.args[0]).toBe(selModel);
                        expect(spy.mostRecentCall.args[1]).toBe(get(1));
                    });

                    it("should return false to veto the selection", function() {
                        selModel.on('beforeselect', retFalse);
                        select(1);
                        expectNotSelected(1);
                    });

                    it("should fire the event for each selection", function() {
                        selModel.on('beforeselect', spy);
                        select(range(1, 3));
                        expect(spy.calls[0].args[1]).toBe(get(1));
                        expect(spy.calls[1].args[1]).toBe(get(2));
                        expect(spy.calls[2].args[1]).toBe(get(3));
                    });

                    it("should be able to veto multiple items", function() {
                        selModel.on('beforeselect', evenOnly);
                        select(range(0, 3));
                        expectSelected(0, 2);
                        expectNotSelected(1, 3);
                    });

                    it("should not fire the event if suppressEvent is passed", function() {
                        selModel.on('beforeselect', spy);
                        select(1, undefined, true);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should not fire the event if the record is selected", function() {
                        select(1);
                        selModel.on('beforeselect', spy);
                        select(1);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should not fire the event for any selected records", function() {
                        select([get(1), get(3)]);
                        selModel.on('beforeselect', spy);
                        select(range(0, 3));
                        expect(spy.calls[0].args[1]).toBe(get(0));
                        expect(spy.calls[1].args[1]).toBe(get(2));
                    });
                });

                describe("selectionchange", function() {
                    it("should fire the selection change event when an item is selected", function() {
                        selModel.on('selectionchange', spy);
                        select(1);
                        expect(spy).toHaveBeenCalled();
                    });

                    it("should not fire if suppressEvent is passed", function() {
                        selModel.on('selectionchange', spy);
                        select(1, undefined, true);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should not fire if the beforeselect event is vetoed", function() {
                        selModel.on('selectionchange', spy);
                        selModel.on('beforeselect', retFalse);
                        select(1);
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should not fire if all items are selected", function() {
                        select(range(0, 2));
                        selModel.on('selectionchange', spy);
                        select(range(0, 2));
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should fire once if deselecting an existing selection", function() {
                        select(1);
                        selModel.on('selectionchange', spy);
                        select(2);
                        expect(spy.callCount).toBe(1);
                    });

                    it("should fire once when selecting multiple items", function() {
                        selModel.on('selectionchange', spy);
                        select(range(1, 3));
                        expect(spy.callCount).toBe(1);
                    });

                    it("should fire once when selecting and deselecting multiple items", function() {
                        select(range(1, 3));
                        selModel.on('selectionchange', spy);
                        select(range(5, 7));
                        expect(spy.callCount).toBe(1);
                    });

                    it("should not fire if deselecting as part of a select and all deselects are vetoed", function() {
                        select(range(0, 3));
                        selModel.on('selectionchange', spy);
                        selModel.on('beforedeselect', retFalse);
                        select(range(4, 8));
                        expect(spy).not.toHaveBeenCalled();
                    });

                    it("should fire if deselecting as part of a select and any deselection occurs", function() {
                        select(range(0, 5));
                        selModel.on('selectionchange', spy);
                        selModel.on('beforedeselect', evenOnly);
                        select(range(7, 9));
                        expect(spy).toHaveBeenCalled();
                    });

                    it("should fire if the selection is a subset of an existing set", function() {
                        selectAll();
                        selModel.on('selectionchange', spy);
                        select(range(0, 3));
                        expect(spy).toHaveBeenCalled();
                    });
                });
            });

            describe("keepExisting", function() {
                it("should remove a selection when not passing keepExisting", function() {
                    select(1);
                    select(2);
                    expectSelected(2);
                    expectNotSelected(1);
                });

                it("should remove all selections when not passing keepExisting", function() {
                    select(range(0, 2));
                    select(6);
                    expectSelected(6);
                    expectNotSelected(0, 1, 2);
                });

                it("should keep a single selection with keepExisting: true", function() {
                    select(1);
                    select(range(2, 4), true);
                    expectSelected(1, 2, 3, 4);
                });

                it("should keep multiple selections with keepExisting: true", function() {
                    select(range(0, 2));
                    select(range(3, 5), true);
                    expectSelected(0, 1, 2, 3, 4, 5);
                });
            });

            it("should do nothing if locked", function() {
                selModel.setLocked(true);
                select(1);
                expectNone();
            });
        });
    });

    describe("deselect", function() {

        // deselection behaves the same for single/multi

        beforeEach(function() {
            defaultMode = 'MULTI';
            setupModel();
        });

        it("should deselect a record by index", function() {
            select(1);
            deselect(1);
            expectNotSelected(1);
        });

        it("should deselect a record instance", function() {
            select(2);
            deselect(get(2));
            expectNotSelected(2);
         });

        it("should ignore an index not in the store", function() {
            deselect(100);
            expectNone();
        });

        it("should deselect an array of records", function() {
            select(range(0, 2));
            deselect(range(0, 2));
            expectNone();
        });

        it("should do nothing if the record is not selected", function() {
            deselect(0);
            expectNone();
        });

        it("should stop a deselect is vetoed", function() {
            select(1);
            selModel.on('beforedeselect', retFalse);
            deselect(1);
            expectSelected(1);
        });

        it("should stop any vetoed deselects", function() {
            select(range(0, 3));
            selModel.on('beforedeselect', evenOnly);
            deselect(range(0, 3));
            expectNotSelected(0, 2);
            expectSelected(1, 3);
        });

        describe("events", function() {
            describe("beforedeselect", function() {
                it("should fire the event", function() {
                    select(1);
                    selModel.on('beforedeselect', spy);
                    deselect(1);
                    expect(spy).toHaveBeenCalled();
                });

                it("should pass the selModel & the record", function() {
                    select(1);
                    selModel.on('beforedeselect', spy);
                    deselect(1);
                    expect(spy.mostRecentCall.args[0]).toBe(selModel);
                    expect(spy.mostRecentCall.args[1]).toBe(get(1));
                });

                it("should return false to veto the deselection", function() {
                    select(1);
                    selModel.on('beforedeselect', retFalse);
                    deselect(1);
                    expectSelected(1);
                });

                it("should fire the event for each deselection", function() {
                    select(range(1, 3));
                    selModel.on('beforedeselect', spy);
                    deselect(range(1, 3));
                    expect(spy.calls[0].args[1]).toBe(get(1));
                    expect(spy.calls[1].args[1]).toBe(get(2));
                    expect(spy.calls[2].args[1]).toBe(get(3));
                });

                it("should be able to veto multiple items", function() {
                    select(range(0, 3));
                    selModel.on('beforedeselect', evenOnly);
                    deselect(range(0, 3));
                    expectNotSelected(0, 2);
                    expectSelected(1, 3);
                });

                it("should not fire the event if suppressEvent is passed", function() {
                    select(1);
                    selModel.on('beforedeselect', spy);
                    deselect(1, true);
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should not fire the event if the record is not selected", function() {
                    selModel.on('beforedeselect', spy);
                    deselect(1);
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should not fire the event for any deselected records", function() {
                    select([get(1), get(3)]);
                    selModel.on('beforedeselect', spy);
                    deselect(range(0, 3));
                    expect(spy.calls[0].args[1]).toBe(get(1));
                    expect(spy.calls[1].args[1]).toBe(get(3));
                });
            });

            describe("deselect", function() {
                it("should fire an event", function() {
                    select(1);
                    selModel.on('deselect', spy);
                    deselect(1);
                    expect(spy).toHaveBeenCalled();
                });

                it("should pass the selModel & the selected record", function() {
                    select(1);
                    selModel.on('deselect', spy);
                    deselect(1);
                    expect(spy.mostRecentCall.args[0]).toBe(selModel);
                    expect(spy.mostRecentCall.args[1]).toBe(get(1));
                });

                it("should fire the event for each item", function() {
                    select(range(0, 2));
                    selModel.on('deselect', spy);
                    deselect(range(0, 2));
                    expect(spy.calls[0].args[1]).toBe(get(0));
                    expect(spy.calls[1].args[1]).toBe(get(1));
                    expect(spy.calls[2].args[1]).toBe(get(2));
                });

                it("should not fire an event if suppressEvent is passed", function() {
                    select(1);
                    selModel.on('deselect', spy);
                    deselect(1, true);
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should not fire the event if the record is not selected", function() {
                    selModel.on('deselect', spy);
                    deselect(1);
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should fire the event only for selected records", function() {
                    select(range(0, 3));
                    selModel.on('deselect', spy);
                    deselect(range(0, 5));
                    expect(spy.calls[0].args[1]).toBe(get(0));
                    expect(spy.calls[1].args[1]).toBe(get(1));
                    expect(spy.calls[2].args[1]).toBe(get(2));
                    expect(spy.calls[3].args[1]).toBe(get(3));
                });
            });

            describe("selectionchange", function() {
                it("should fire the selection change event when an item is deselected", function() {
                    select(1);
                    selModel.on('selectionchange', spy);
                    deselect(1);
                    expect(spy).toHaveBeenCalled();
                });

                it("should not fire if suppressEvent is passed", function() {
                    select(1);
                    selModel.on('selectionchange', spy);
                    deselect(1, true);
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should not fire if the beforedeselect event is vetoed", function() {
                    select(1);
                    selModel.on('selectionchange', spy);
                    selModel.on('beforedeselect', retFalse);
                    deselect(1);
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should not fire if all items are deselected", function() {
                    selModel.on('selectionchange', spy);
                    deselect(range(0, 2));
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should fire once when deselecting multiple items", function() {
                    select(range(1, 3));
                    selModel.on('selectionchange', spy);
                    deselect(range(1, 3));
                    expect(spy.callCount).toBe(1);
                });
            });
        });

        it("should do nothing when locked", function() {
            select(1);
            selModel.setLocked(true);
            deselect(1);
            expectSelected(1);
        });

    });

    describe("isSelected", function() {
        beforeEach(function() {
            setupModel();
        });

        it("should return false when nothing is selected", function() {
            expectNotSelected(0);
        });

        it("should return false when that record is not selected", function() {
            select(1);
            expectNotSelected(2);
        });

        it("should return true when the record is selected", function() {
            select(1);
            expectSelected(1);
        });

        it("should return true when the index is selected", function() {
            select(1);
            // Don't use expectSelected since it normalizes indexes to records
            expect(selModel.isSelected(1)).toBe(true);
        });

        it("should be up to date after a series of operations", function() {
            select(range(2, 4));
            expectNotSelected(0);
            expectSelected(3);
            deselect(3);
            expectNotSelected(3);
            select(range(7, 9));
            expectSelected(8);
            deselect(4);
            expectNotSelected(4);
        });
    });

    describe("getCount", function() {
        function expectCount(n) {
            return expect(selModel.getCount()).toBe(n);
        }

        beforeEach(function() {
            setupModel();
        });

        it("should return 0 when nothing is selected", function() {
            expectCount(0);
        });

        it("should return 1 when a single item is selected", function() {
            select(1);
            expectCount(1);
        });

        it("should return the correct amount when multiple items are selected", function() {
            select(range(2, 7));
            expectCount(6);
        });

        it("should maintain the count during operations", function() {
            select(range(1, 5));
            expectCount(5);
            deselect(1);
            deselect(3);
            expectCount(3);
            select(1, true);
            expectCount(4);
            select(range(7, 9), true);
            expectCount(7);
            deselect(range(8, 9));
            expectCount(5);
        });
    });

    describe("hasSelection", function() {
        beforeEach(function() {
            setupModel();
        });

        it("should return true when there is 1 selection", function() {
            select(1);
            expect(selModel.hasSelection()).toBe(true);
        });

        it("should return true when there is more than 1 selection", function() {
            select(range(2, 4));
            expect(selModel.hasSelection()).toBe(true);
        });

        it("should return false when there are no selections", function() {
            expect(selModel.hasSelection()).toBe(false);
        });
    });

    describe("selectRange", function() {

        function selectRange(from, to, keepExisting) {
            selModel.selectRange(from, to, keepExisting);
        }

        beforeEach(function() {
            setupModel();
        });

        it("should not create a range if we have not selected a range", function() {
            spyOn(selModel, 'selectRange');

            selModel.selectWithEvent(store.getAt(4), {
                shiftKey: true
            });

            expect(selModel.selectRange).not.toHaveBeenCalled();
        });

        it("should select items in the given range", function() {
            selectRange(3, 7);
            expectSelected(3, 4, 5, 6, 7);
        });

        it("should accept a record as a start point", function() {
            selectRange(get(1), 4);
            expectSelected(1, 2, 3, 4);
        });

        it("should accept a record as an end point", function() {
            selectRange(1, get(3));
            expectSelected(1, 2, 3);
        });

        it("should limit the start to 0 if passed less than 0", function() {
            selectRange(-5, 3);
            expectSelected(0, 1, 2, 3);
        });

        it("should limit the end to the store count if greater than the total", function() {
            selectRange(6, 100);
            expectSelected(6, 7, 8, 9);
        });

        it("should select a single record if the start == end", function() {
            selectRange(3, 3);
            expectSelected(3);
        });

        it("should swap start/end if start > end", function() {
            selectRange(5, 2);
            expectSelected(2, 3, 4, 5);
        });

        it("should do nothing if the model is locked", function() {
            selModel.setLocked(true);
            selectRange(4, 7);
            expectNone();
        });

        it("should only select unselected items", function() {
            var recs = [];

            select(2);
            selModel.on('select', function(sm, rec) {
                recs.push(rec);
            });
            selectRange(1, 3);
            expect(recs).toEqual([get(1), get(3)]);
        });

        it("should fire a single selectionchange event", function() {
            setupModel();
            select(range(0, 3));
            selModel.on('selectionchange', spy);
            selectRange(4, 7);
            expect(spy.callCount).toBe(1);
        });

        it("should fire a selectionchange event if only deselections happen", function() {
            selModel.selectAll();
            selModel.on('selectionchange', spy);
            selectRange(0, 3);
            expect(spy.callCount).toBe(1);
        });

        describe("keepExisting", function() {
            it("should deselect other records by default", function() {
                select(range(0, 1));
                selectRange(4, 7);
                expectNotSelected(0, 1);
            });

            it("should keep any selections if keepExisting is passed", function() {
                select(range(0, 1));
                selectRange(4, 7, true);
                expectSelected(0, 1, 4, 5, 6, 7);
            });

            it("should allow a subset of the current selection to be selected", function() {
                select(range(0, 3));
                selectRange(0, 1, true);
                expectSelected(0, 1, 2, 3);
            });
        });
    });

    describe("deselectRange", function() {

        function selectRange(from, to) {
            selModel.selectRange(from, to);
        }

        function deselectRange(from, to) {
            selModel.deselectRange(from, to);
        }

        beforeEach(function() {
            setupModel();
        });

        it("should deselect items in the given range", function() {
            selectRange(3, 7);
            deselectRange(3, 7);
            expectNotSelected(3, 4, 5, 6, 7);
        });

        it("should accept a record as a start point", function() {
            selectRange(1, 4);
            deselectRange(get(1), 4);
            expectNotSelected(1, 2, 3, 4);
        });

        it("should accept a record as an end point", function() {
            selectRange(1, 3);
            deselectRange(1, get(3));
            expectNotSelected(1, 2, 3);
        });

        it("should limit the start to 0 if passed less than 0", function() {
            selectRange(0, 3);
            deselectRange(-5, 3);
            expectNotSelected(0, 1, 2, 3);
        });

        it("should limit the end to the store count if greater than the total", function() {
            selectRange(6, 9);
            deselectRange(6, 100);
            expectNotSelected(6, 7, 8, 9);
        });

        it("should deselect a single record if the start == end", function() {
            select(3);
            deselectRange(3, 3);
            expectNotSelected(3);
        });

        it("should swap start/end if start > end", function() {
            selectRange(2, 5);
            deselectRange(5, 2);
            expectNotSelected(2, 3, 4, 5);
        });

        it("should do nothing if the model is locked", function() {
            selectRange(4, 7);
            selModel.setLocked(true);
            deselectRange(4, 7);
            expectSelected(4, 5, 6, 7);
        });

        it("should only deselect selected items", function() {
            select([get(1), get(3)]);
            selModel.on('deselect', spy);
            deselectRange(1, 3);
            expect(spy.calls[0].args[1]).toBe(get(1));
            expect(spy.calls[1].args[1]).toBe(get(3));
        });

        it("should fire a single selectionchange event", function() {
            select(range(0, 7));
            selModel.on('selectionchange', spy);
            deselectRange(4, 7);
            expect(spy.callCount).toBe(1);
        });
    });

    describe("isRangeSelected", function() {
        function expectRange(from, to) {
            expect(selModel.isRangeSelected(from, to)).toBe(true);
        }

        function expectNotRange(from, to) {
            expect(selModel.isRangeSelected(from, to)).toBe(false);
        }

        beforeEach(function() {
            setupModel();
        });

        it("should return true if all items in the range are selected", function() {
            select(range(3, 6));
            expectRange(3, 6);
        });

        it("should return false if not all items in the range are selected", function() {
            select(1);
            select(3);
            select(4);
            expectNotRange(1, 4);
        });

        it("should accept a range where start = end", function() {
            select(1);
            expectRange(1, 1);
        });

        it("should accept a record as a start value", function() {
            select(range(1, 4));
            expectRange(get(1), 4);
        });

        it("should accept a record as an end value", function() {
            select(range(1, 4));
            expectRange(1, get(4));
        });

        it("should limit the start to 0 if passed less than 0", function() {
            select(range(0, 3));
            expectRange(-5, 3);
        });

        it("should limit the end to the store count if greater than the total", function() {
            select(range(6, 9));
            expectRange(6, 100);
        });
    });

    describe("selectAll", function() {
        beforeEach(function() {
            setupModel();
        });

        it("should do nothing when the model is locked", function() {
            selModel.setLocked(true);
            selectAll();
            expectNone();
        });

        it("should select all items", function() {
            selectAll();
            expectSelected(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
        });

        it("should only fire select events for unselected items", function() {
            select(range(1, 8));
            selModel.on('select', spy);
            selectAll();
            expect(spy.calls[0].args[1]).toBe(get(0));
            expect(spy.calls[1].args[1]).toBe(get(9));
        });

        it("should fire a single selectionchange event", function() {
            selModel.on('selectionchange', spy);
            selectAll();
            expect(spy.callCount).toBe(1);
        });

        it("should not fire select events if suppressEvent is passed", function() {
            selModel.on('select', spy);
            selectAll(true);
            expect(spy).not.toHaveBeenCalled();
        });

        it("should not fire selectionchange if suppressEvent is passed", function() {
            selModel.on('selectionchange', spy);
            selectAll(true);
            expect(spy).not.toHaveBeenCalled();
        });

        describe("event vetoing", function() {
            it("should only select items that were not vetoed", function() {
                selModel.on('beforeselect', evenOnly);
                selectAll();
                expectSelected(0, 2, 4, 6, 8);
                expectNotSelected(1, 3, 5, 7, 9);
            });

            it("should fire selectionchange once if any selections change", function() {
                selModel.on('beforeselect', evenOnly);
                selModel.on('selectionchange', spy);
                selectAll();
                expect(spy.callCount).toBe(1);
            });

            it("should not fire selectionchange if the selection did not change", function() {
                selModel.on('beforeselect', retFalse);
                selModel.on('selectionchange', spy);
                selectAll();
                expect(spy.callCount).toBe(0);
            });
        });
    });

    describe("deselectAll", function() {
        beforeEach(function() {
            setupModel();
        });

        it("should do nothing when the model is locked", function() {
            selectAll();
            selModel.setLocked(true);
            deselectAll();
            expectSelected(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
        });

        it("should deselect all items", function() {
            selectAll();
            deselectAll();
            expectNone();
        });

        it("should only fire select events for selected items", function() {
            select([get(0), get(9)]);
            selModel.on('deselect', spy);
            deselectAll();
            expect(spy.calls[0].args[1]).toBe(get(0));
            expect(spy.calls[1].args[1]).toBe(get(9));
        });

        it("should fire a single selectionchange event", function() {
            selectAll();
            selModel.on('selectionchange', spy);
            deselectAll();
            expect(spy.callCount).toBe(1);
        });

        it("should not fire deselect events if suppressEvent is passed", function() {
            selectAll();
            selModel.on('deselect', spy);
            deselectAll(true);
            expect(spy).not.toHaveBeenCalled();
        });

        it("should not fire selectionchange if suppressEvent is passed", function() {
            selectAll();
            selModel.on('selectionchange', spy);
            deselectAll(true);
            expect(spy).not.toHaveBeenCalled();
        });

        describe("event vetoing", function() {
            it("should only deselect items that were not vetoed", function() {
                selectAll();
                selModel.on('beforedeselect', evenOnly);
                deselectAll();
                expectSelected(1, 3, 5, 7, 9);
                expectNotSelected(0, 2, 4, 6, 8);
            });

            it("should fire selectionchange once if any selections change", function() {
                selectAll();
                selModel.on('beforedeselect', evenOnly);
                selModel.on('selectionchange', spy);
                deselectAll();
                expect(spy.callCount).toBe(1);
            });

            it("should not fire selectionchange if the selection did not change", function() {
                selectAll();
                selModel.on('beforedeselect', retFalse);
                selModel.on('selectionchange', spy);
                deselectAll();
                expect(spy).not.toHaveBeenCalled();
            });
        });
    });

    describe('key navigation in SINGLE mode with allowDeselect', function() {
        beforeEach(function() {
            defaultMode = 'SINGLE';
            setupModel();
        });

        it("should NOT deselect when uparrowing at the top", function() {
            selModel.allowDeselect = true;
            select(0);
            selModel.onNavigate({
                record: store.getAt(0),
                recordIndex: 0,
                keyEvent: new Ext.event.Event({
                    charCode: Ext.event.Event.UP
                })
            });
            expectSelected(0);
        });

        it("should NOT deselect when downarrowing at the bottom", function() {
            var lastIndex = store.getCount() - 1;

            selModel.allowDeselect = true;
            select(lastIndex);
            selModel.onNavigate({
                record: store.getAt(lastIndex),
                recordIndex: lastIndex,
                keyEvent: new Ext.event.Event({
                    charCode: Ext.event.Event.DOWN
                })
            });
            expectSelected(lastIndex);
        });
    });

    describe("selectWithEvent", function() {
        function selectWithEvent(record, e) {
            if (typeof record === 'number') {
                record = store.getAt(record);
            }

            selModel.selectWithEvent(record, e || {});
        }

        var shift = {
                shiftKey: true
            },
            ctrl = {
                ctrlKey: true
            };

        describe("SINGLE", function() {
            beforeEach(function() {
                defaultMode = 'SINGLE';
                setupModel();
            });

            it("should select the item when nothing is selected", function() {
                selectWithEvent(1);
                expectSelected(1);
            });

            it("should overwrite an existing selection", function() {
                select(1);
                selectWithEvent(2);
                expectSelected(2);
                expectNotSelected(1);
            });

            describe("with allowDeselect", function() {
                it("should deselect a selected model", function() {
                    selModel.allowDeselect = true;
                    select(1);
                    selectWithEvent(1);
                    expectNotSelected(1);
                });

                it("should select the model if not selected", function() {
                    selModel.allowDeselect = true;
                    select(1);
                    selectWithEvent(2);
                    expectSelected(2);
                    expectNotSelected(1);
                });

                describe("with toggleOnClick", function() {
                    it("should not select the record if ctrlKey isn't pressed", function() {
                        selModel.toggleOnClick = false;
                        selModel.allowDeselect = true;
                        select(1);
                        selectWithEvent(1);
                        expectSelected(1);
                    });

                    it("should deselect the record if ctrlKey is pressed", function() {
                        selModel.toggleOnClick = false;
                        selModel.allowDeselect = true;
                        select(1);
                        selectWithEvent(1, ctrl);
                        expectNotSelected(1);
                    });
                });
            });
        });

        describe("SIMPLE", function() {
            beforeEach(function() {
                defaultMode = 'SIMPLE';
                setupModel();
            });

            it("should select a record if none are selected", function() {
                selectWithEvent(1);
                expectSelected(1);
            });

            it("should deselect a record if it's selected", function() {
                select(1);
                selectWithEvent(1);
                expectNotSelected(1);
            });

            it("should select a new record and keep existing selections", function() {
                select(1);
                selectWithEvent(2);
                expectSelected(1, 2);
                selectWithEvent(3);
                expectSelected(1, 2, 3);
            });

            it("should deselect a selected record but keep other selections", function() {
                select(range(1, 3));
                selectWithEvent(2);
                expectNotSelected(2);
                expectSelected(1, 3);
            });
        });

        describe("MULTI", function() {
            beforeEach(function() {
                defaultMode = 'MULTI';
                setupModel();
            });

            it("should select a range if we have a selection start point and shift is pressed", function() {
                selectWithEvent(0);
                selectWithEvent(4, shift);
                expectSelected(0, 1, 2, 3, 4);
            });

            it("should return the single selection if we have not selected a range", function() {
                selectWithEvent(3, shift);
                expectSelected(3);
            });

            it("should deselect the record if it's selected and ctrl is pressed", function() {
                select(1);
                selectWithEvent(1, ctrl);
                expectNotSelected(1);
            });

            it("should add to the selection if ctrl is pressed and the record is not selected", function() {
                selectWithEvent(1, ctrl);
                expectSelected(1);
                selectWithEvent(4, ctrl);
                expectSelected(1, 4);
                selectWithEvent(9, ctrl);
                expectSelected(1, 4, 9);
            });

            it("should deselect all other records if the record id selected, with no ctrl/shift", function() {
                select(4);
                selectWithEvent(1);
                expectSelected(1);
                expectNotSelected(4);
            });

            it("should add and keep to the selection if none of the above are met", function() {
                selectWithEvent(7);
                expectSelected(7);
            });

            it("should maintain selection with a complex sequence", function() {
                selectWithEvent(2);
                expectSelected(2);
                selectWithEvent(5, shift);
                expectSelected(2, 3, 4, 5);
                selectWithEvent(4);
                expectSelected(4);
                selectWithEvent(8, ctrl);
                expectSelected(4, 8);
                selectWithEvent(4, ctrl);
                expectSelected(8);
                selectWithEvent(1);
                expectSelected(1);

            });
        });
    });

    describe("model id change", function() {
        it("should be selected when the id changes", function() {
            setupModel();
            var rec = store.getAt(3);

            select(rec);
            rec.set('id', 100);
            expectSelected(rec);
        });

        it("should be able to remove the selection", function() {
            setupModel();
            var rec = store.getAt(3);

            select(rec);
            rec.set('id', 100);
            deselect(rec);
            expectNone();
        });
    });

    describe("store events", function() {
        beforeEach(function() {
            defaultMode = 'MULTI';
            setupModel();
        });

        it("should clear selections when the store is cleared", function() {
            selectAll();
            store.removeAll();
            expectNone();
        });

        describe("store remove", function() {
            describe("pruneRemoved: true", function() {
                it("should remove a selection if it's removed from the store", function() {
                    selectAll();
                    store.removeAt(0);
                    expect(selModel.getCount()).toBe(9);
                });

                it("should fire the deselect event", function() {
                    selectAll();
                    selModel.on('deselect', spy);
                    store.removeAt(0);
                    expect(spy).toHaveBeenCalled();
                });

                it("should fire the selectionchange event", function() {
                    selectAll();
                    selModel.on('selectionchange', spy);
                    store.removeAt(0);
                    expect(spy).toHaveBeenCalled();
                });
            });

            describe("pruneRemoved: false", function() {
                beforeEach(function() {
                    selModel.pruneRemoved = false;
                });

                it("should not remove a selection if it's removed from the store", function() {
                    selectAll();
                    store.removeAt(0);
                    expect(selModel.getCount()).toBe(10);
                });

                it("should not fire the deselect event", function() {
                    selectAll();
                    selModel.on('deselect', spy);
                    store.removeAt(0);
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should not fire the selectionchange event", function() {
                    selectAll();
                    selModel.on('selectionchange', spy);
                    store.removeAt(0);
                    expect(spy).not.toHaveBeenCalled();
                });
            });
        });

        describe("updating that triggers a sort", function() {
            it("should not fire any events & should remain selected", function() {
                store.sort('name');
                var rec = store.first();

                select(rec);
                selModel.on({
                    select: spy,
                    deselect: spy,
                    selectionchange: spy
                });
                rec.set('name', 'zzzzzz');
                expect(spy).not.toHaveBeenCalled();
                expectSelected(rec);
            });
        });

        describe("insert of an existing record", function() {
            it("should not fire any events & should remain selected", function() {
                var rec = store.last();

                select(rec);
                selModel.on({
                    select: spy,
                    deselect: spy,
                    selectionchange: spy
                });
                store.insert(0, rec);
                expect(spy).not.toHaveBeenCalled();
                expectSelected(rec);
            });
        });

        describe("store reload", function() {
            it("should retain selections", function() {
                select(0);
                var rec = selModel.getSelection()[0];

                expect(rec.getId()).toBe(1);

                store.loadData([{
                    id: 1,
                    name: 'Foo'
                }, {
                    id: 2,
                    name: 'Bar'
                }]);

                rec = selModel.getSelection()[0];
                expect(rec.getId()).toBe(1);
                expect(rec).toBe(store.getById(1));

                expect(selModel.getCount()).toBe(1);
            });

            it("should update the selected model data", function() {
                select(0);
                var rec = selModel.getSelection()[0];

                expect(rec.get('name')).toBe('Name 1');

                store.loadData([{
                    id: 1,
                    name: 'Foo'
                }, {
                    id: 2,
                    name: 'Bar'
                }]);

                rec = selModel.getSelection()[0];
                expect(rec.get('name')).toBe('Foo');
                expect(rec).toBe(store.getById(1));
            });

            it("should update the last selected", function() {
                select(1);
                var rec = selModel.getLastSelected();

                expect(rec.get('name')).toBe('Name 2');

                store.loadData([{
                    id: 1,
                    name: 'Foo'
                }, {
                    id: 2,
                    name: 'Bar'
                }]);

                rec = selModel.getLastSelected();
                expect(rec.get('name')).toBe('Bar');
                expect(rec).toBe(store.getById(2));
            });

            it("should be able to reload a store that had multiple items selected", function() {
                select(range(1, 4));
                expect(function() {
                    store.loadData([{
                        name: 'Foo'
                    }, {
                        name: 'Bar'
                    }]);
                }).not.toThrow();

                expectNone();
            });
        });

        describe("pruneRemoved: true", function() {
            it("should remove items no longer in the store", function() {
                select(1);
                store.loadData([{
                    id: 101
                }, {
                    id: 102
                }]);
                expect(selModel.getSelection()).toEqual([]);
            });

            it("should only remove items no longer in the store", function() {
                select([get(0), get(1)]);
                store.loadData([{
                    id: 1
                }, {
                    id: 102
                }]);
                var selection = selModel.getSelection();

                expect(selection).toEqual([store.getById(1)]);
            });

            // This has historically been the behaviour, so preserve it
            it("should not fire the deselect event", function() {
                select(1);
                selModel.on('deselect', spy);
                store.loadData([{
                    id: 101
                }]);
                expect(spy).not.toHaveBeenCalled();
            });

            it("should fire the selectionchange event", function() {
                select(1);
                selModel.on('selectionchange', spy);
                store.loadData([{
                    id: 101
                }]);
                expect(spy.callCount).toBe(1);
            });

            it("should not fire selectionchange if nothing is removed", function() {
                select([get(0), get(1)]);
                selModel.on('selectionchange', spy);
                store.loadData([{
                    id: 1
                }, {
                    id: 2
                }]);
                expect(spy).not.toHaveBeenCalled();
            });
        });

        describe("pruneRemoved: false", function() {
            beforeEach(function() {
                selModel.pruneRemoved = false;
            });

            it("should not remove selections", function() {
                select(1);
                var rec = selModel.getSelection()[0];

                store.loadData([{
                    id: 101
                }, {
                    id: 102
                }]);
                expect(selModel.getSelection()).toEqual([rec]);
            });

            it("should not fire the deselect or selectionchange event", function() {
                select(1);
                selModel.on('selectionchange', spy);
                selModel.on('deselect', spy);
                store.loadData([{
                    id: 101
                }, {
                    id: 102
                }]);
                expect(spy).not.toHaveBeenCalled();
            });
        });

        describe("store clear", function() {
            beforeEach(function() {
                select(1);

                selModel.on('selectionchange', spy);

                // BufferedStore will set this flag during clearing
                store.clearing = true;
                store.fireEvent('clear', store);
            });

            it("should clear selections", function() {
                var selection = selModel.getSelection();

                expect(selection.length).toBe(0);
            });

            it("should fire selectionchange event", function() {
                expect(spy).toHaveBeenCalled();
            });
        });
    });

    describe("destruction", function() {
        describe("during events", function() {
            var changeSpy, spy, destroyOn;

            beforeEach(function() {
                spy = jasmine.createSpy().andCallFake(function(sm, rec) {
                    if (store.indexOf(rec) === destroyOn) {
                        selModel.destroy();
                    }
                });
                changeSpy = jasmine.createSpy();
            });

            afterEach(function() {
                destroyOn = changeSpy = spy = null;
            });

            describe("with single", function() {
                beforeEach(function() {
                    setupModel('SINGLE');
                    selModel.on('selectionchange', changeSpy);
                });

                describe("select", function() {
                    it("should not cause an exception or fire the selectionchange event", function() {
                        selModel.on('select', spy);
                        destroyOn = 0;
                        expect(function() {
                            select(0);
                        }).not.toThrow();
                        expect(spy.callCount).toBe(1);
                        expect(changeSpy.callCount).toBe(0);
                    });

                    it("should not fire the select event if deselect is vetoed", function() {
                        var selectSpy = jasmine.createSpy();

                        select(0);
                        selModel.on('deselect', spy);
                        selModel.on('select', selectSpy);
                        destroyOn = 0;
                        changeSpy.reset();
                        expect(function() {
                            selModel.select(1);
                        }).not.toThrow();
                        expect(selectSpy).not.toHaveBeenCalled();
                        expect(changeSpy).not.toHaveBeenCalled();
                    });
                });

                describe("deselect", function() {
                    it("should not cause an exception or fire the selectionchange event", function() {
                        select(0);
                        changeSpy.reset();
                        selModel.on('deselect', spy);
                        destroyOn = 0;
                        expect(function() {
                            selModel.deselect(0);
                        }).not.toThrow();
                        expect(spy.callCount).toBe(1);
                        expect(changeSpy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("with multi", function() {
                beforeEach(function() {
                    setupModel();
                    selModel.on('selectionchange', changeSpy);
                });

                describe("select", function() {
                    it("should stop firing events if destroyed on the first record", function() {
                        selModel.on('select', spy);
                        destroyOn = 0;
                        expect(function() {
                            select(range(0, 4));
                        }).not.toThrow();
                        expect(spy.callCount).toBe(1);
                        expect(changeSpy).not.toHaveBeenCalled();
                    });

                    it("should stop firing events if destroyed on a middle record", function() {
                        selModel.on('select', spy);
                        destroyOn = 2;
                        expect(function() {
                            select(range(0, 4));
                        }).not.toThrow();
                        expect(spy.callCount).toBe(3);
                        expect(changeSpy).not.toHaveBeenCalled();

                    });

                    it("should stop firing events if destroyed on the last record", function() {
                        selModel.on('select', spy);
                        destroyOn = 4;
                        expect(function() {
                            select(range(0, 4));
                        }).not.toThrow();
                        expect(spy.callCount).toBe(5);
                        expect(changeSpy).not.toHaveBeenCalled();
                    });

                    it("should not fire any select events if deselection is vetoed", function() {
                        var selectSpy = jasmine.createSpy();

                        select(range(0, 2));
                        selModel.on('deselect', spy);
                        selModel.on('select', selectSpy);
                        destroyOn = 0;
                        changeSpy.reset();
                        expect(function() {
                            selModel.select(range(3, 5));
                        }).not.toThrow();
                        expect(selectSpy).not.toHaveBeenCalled();
                        expect(changeSpy).not.toHaveBeenCalled();
                    });
                });

                describe("deselect", function() {
                    it("should stop firing events if destroyed on the first record", function() {
                        select(range(0, 4));
                        changeSpy.reset();
                        selModel.on('deselect', spy);
                        destroyOn = 0;
                        expect(function() {
                            deselect(range(0, 4));
                        }).not.toThrow();
                        expect(spy.callCount).toBe(1);
                        expect(changeSpy).not.toHaveBeenCalled();
                    });

                    it("should stop firing events if destroyed on a middle record", function() {
                        select(range(0, 4));
                        changeSpy.reset();
                        selModel.on('deselect', spy);
                        destroyOn = 2;
                        expect(function() {
                            deselect(range(0, 4));
                        }).not.toThrow();
                        expect(spy.callCount).toBe(3);
                        expect(changeSpy).not.toHaveBeenCalled();

                    });

                    it("should stop firing events if destroyed on the last record", function() {
                        select(range(0, 4));
                        changeSpy.reset();
                        selModel.on('deselect', spy);
                        destroyOn = 4;
                        expect(function() {
                            deselect(range(0, 4));
                        }).not.toThrow();
                        expect(spy.callCount).toBe(5);
                        expect(changeSpy).not.toHaveBeenCalled();
                    });
                });

                describe("selectAll", function() {
                    it("should not throw an exception or fire selectionchange if a select is vetoed", function() {
                        selModel.on('select', spy);
                        destroyOn = 0;
                        expect(function() {
                            selModel.selectAll();
                        }).not.toThrow();
                        expect(spy.callCount).toBe(1);
                        expect(changeSpy).not.toHaveBeenCalled();
                    });
                });

                describe("deselectAll", function() {
                    it("should not throw an exception or fire selectionchange if a deselection is vetoed", function() {
                        selModel.selectAll();
                        changeSpy.reset();
                        selModel.on('deselect', spy);
                        destroyOn = 0;
                        expect(function() {
                            selModel.deselectAll();
                        }).not.toThrow();
                        expect(spy.callCount).toBe(1);
                        expect(changeSpy).not.toHaveBeenCalled();
                    });
                });

                describe("selectRange", function() {
                    it("should not throw an exception or fire selectionchange if a select is vetoed", function() {
                        selModel.on('select', spy);
                        destroyOn = 0;
                        expect(function() {
                            selModel.selectRange(0, 9);
                        }).not.toThrow();
                        expect(spy.callCount).toBe(1);
                        expect(changeSpy).not.toHaveBeenCalled();
                    });

                    it("should not throw an exception or fire selectionchange if a deselection is vetoed", function() {
                        select(range(0, 3));
                        changeSpy.reset();
                        selModel.on('deselect', spy);
                        destroyOn = 0;
                        expect(function() {
                            selModel.selectRange(4, 6);
                        }).not.toThrow();
                        expect(spy.callCount).toBe(1);
                        expect(changeSpy).not.toHaveBeenCalled();
                    });
                });
            });
        });
    });

    describe("bindStore", function() {
        var other;

        beforeEach(function() {
            setupModel();
        });

        afterEach(function() {
            other = Ext.destroy(other);
        });

        function makeOtherStore(data) {
            other = new Ext.data.Store({
                model: spec.Model,
                data: data || [{
                    id: 101,
                    name: 'Foo'
                }, {
                    id: 102,
                    name: 'Bar'
                }]
            });

            return other;
        }

        describe("lastSelected", function() {
            it("clear lastSelected if it doesn't exist", function() {
                select(0);
                selModel.bindStore(makeOtherStore());
                expect(selModel.getLastSelected()).toBeNull();
            });

            it("should update the selected details if it exists", function() {
                var old = get(0);

                select(old);
                selModel.bindStore(makeOtherStore([{
                    id: 1,
                   name: 'Foo'
                }]));
                var last = selModel.getLastSelected();

                expect(last).not.toBe(old);
                expect(last).toBe(other.getAt(0));
                expect(last.get('name')).toBe('Foo');
            });
        });

        describe("selections", function() {
            it("should update selected record information", function() {
                var recs = [get(1), get(3), get(4)];

                select(recs);
                selModel.bindStore(makeOtherStore([{
                    id: 1,
                    name: 'A'
                }, {
                    id: 2,
                    name: 'B'
                }, {
                    id: 3,
                    name: 'C'
                }, {
                    id: 4,
                    name: 'D'
                }, {
                    id: 5,
                    name: 'E'
                }]));

                var selection = selModel.getSelection();

                expect(selection).not.toEqual(recs);
                expect(selection.length).toBe(3);
                expect(selection[0]).toBe(other.getAt(1));
                expect(selection[1]).toBe(other.getAt(3));
                expect(selection[2]).toBe(other.getAt(4));
            });

            it("should prune records no longer included", function() {
                var recs = [get(1), get(3), get(4)];

                select(recs);
                selModel.bindStore(makeOtherStore([{
                    id: 1,
                    name: 'A'
                }, {
                    id: 2,
                    name: 'B'
                }, {
                    id: 3,
                    name: 'C'
                }, {
                    id: 5,
                    name: 'E'
                }]));

                var selection = selModel.getSelection();

                expect(selection.length).toBe(2);
                expect(selection[0]).toBe(other.getAt(1));
                expect(selection[1]).toBe(other.getAt(3));
            });
        });

        describe("events", function() {
            it("should not fire events if all selections are retained", function() {
                var recs = [get(1), get(3), get(4)];

                select(recs);
                selModel.on('selectionchange', spy);
                selModel.bindStore(makeOtherStore([{
                    id: 1,
                    name: 'A'
                }, {
                    id: 2,
                    name: 'B'
                }, {
                    id: 3,
                    name: 'C'
                }, {
                    id: 4,
                    name: 'D'
                }, {
                    id: 5,
                    name: 'E'
                }]));
                expect(spy).not.toHaveBeenCalled();
            });

            it("should fire events if the selection changes", function() {
                var recs = [get(1), get(3), get(4)];

                select(recs);
                selModel.on('selectionchange', spy);
                selModel.bindStore(makeOtherStore([{
                    id: 1,
                    name: 'A'
                }, {
                    id: 2,
                    name: 'B'
                }, {
                    id: 3,
                    name: 'C'
                }, {
                    id: 5,
                    name: 'E'
                }]));
                expect(spy.callCount).toBe(1);
            });
        });
    });
});
