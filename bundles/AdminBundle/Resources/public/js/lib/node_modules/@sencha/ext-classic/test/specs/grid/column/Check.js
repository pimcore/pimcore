topSuite("Ext.grid.column.Check", ['Ext.grid.Panel', 'Ext.grid.column.Template'], function() {
    var itNotIE9 = Ext.isIE9 ? xit : it,
        grid, view, store, col,
        invert = false;

    function getColCfg(cfg) {
        return Ext.apply({
            xtype: 'checkcolumn',
            text: 'Checked',
            dataIndex: 'val',
            invert: invert
        }, cfg);
    }

    function makeGrid(columns, data, gridCfg) {
        store = new Ext.data.Store({
            model: spec.CheckColumnModel,
            data: data || [{
                val: true
            }, {
                val: true
            }, {
                val: false
            }, {
                val: true
            }, {
                val: false
            }]
        });

        if (!columns) {
            columns = [getColCfg()];
        }

        grid = new Ext.grid.Panel(Ext.apply({
            width: 200,
            renderTo: Ext.getBody(),
            store: store,
            columns: columns
        }, gridCfg));
        view = grid.getView();
        col = grid.getColumnManager().getFirst();
    }

    function triggerCellMouseEvent(type, rowIdx, cellIdx, button, x, y) {
        var target = getCellImg(rowIdx, cellIdx);

        jasmine.fireMouseEvent(target, type, x, y, button);
    }

    function getCell(rowIdx) {
        return grid.getView().getCellInclusive({
            row: rowIdx,
            column: 0
        }, true);
    }

    function getCellImg(rowIdx) {
        var cell = getCell(rowIdx);

        return cell.querySelector('.x-grid-checkcolumn');
    }

    function hasCls(el, cls) {
        return Ext.fly(el).hasCls(cls);
    }

    function clickHeader() {
        jasmine.fireMouseEvent(col.el.dom.querySelector('.' + col.headerCheckboxCls), 'click');
    }

    beforeEach(function() {
        Ext.define('spec.CheckColumnModel', {
            extend: 'Ext.data.Model',
            fields: ['val']
        });
    });

    afterEach(function() {
        Ext.destroy(grid, store);
        col = grid = store = null;
        Ext.undefine('spec.CheckColumnModel');
        Ext.data.Model.schema.clear();
    });

    it("should be able to create an instance without passing a config", function() {
        var col = new Ext.grid.column.Check();

        expect(col.isCheckColumn).toBe(true);
        col.destroy();
    });

    describe("check rendering", function() {

        it("should add the x-grid-checkcolumn class to the checkbox element", function() {
            makeGrid();

            expect(hasCls(getCellImg(0), 'x-grid-checkcolumn')).toBe(true);
        });

        it("should set the x-grid-checkcolumn-checked class on checked items", function() {
            makeGrid();

            expect(hasCls(getCellImg(0), 'x-grid-checkcolumn-checked')).toBe(true);
            expect(hasCls(getCellImg(1), 'x-grid-checkcolumn-checked')).toBe(true);
            expect(hasCls(getCellImg(2), 'x-grid-checkcolumn-checked')).toBe(false);
            expect(hasCls(getCellImg(3), 'x-grid-checkcolumn-checked')).toBe(true);
            expect(hasCls(getCellImg(4), 'x-grid-checkcolumn-checked')).toBe(false);
        });
        it("should set the x-grid-checkcolumn-checked class on checked items with invert: true", function() {
            invert = true;
            makeGrid();
            invert = false;

            expect(hasCls(getCellImg(0), 'x-grid-checkcolumn-checked')).toBe(false);
            expect(hasCls(getCellImg(1), 'x-grid-checkcolumn-checked')).toBe(false);
            expect(hasCls(getCellImg(2), 'x-grid-checkcolumn-checked')).toBe(true);
            expect(hasCls(getCellImg(3), 'x-grid-checkcolumn-checked')).toBe(false);
            expect(hasCls(getCellImg(4), 'x-grid-checkcolumn-checked')).toBe(true);
        });
    });

    describe("enable/disable", function() {
        describe("during config", function() {
            it("should not include the disabledCls if the column is not disabled", function() {
                makeGrid();
                expect(hasCls(getCell(0), col.disabledCls)).toBe(false);
            });

            it("should include the disabledCls if the column is disabled", function() {
                var cfg = getColCfg();

                cfg.disabled = true;
                makeGrid([cfg]);
                expect(hasCls(getCell(0), col.disabledCls)).toBe(true);
            });
        });

        describe("after render", function() {
            it("should add the disabledCls if disabling", function() {
                makeGrid();
                col.disable();
                expect(hasCls(getCell(0), col.disabledCls)).toBe(true);
                expect(hasCls(getCell(1), col.disabledCls)).toBe(true);
                expect(hasCls(getCell(2), col.disabledCls)).toBe(true);
                expect(hasCls(getCell(3), col.disabledCls)).toBe(true);
                expect(hasCls(getCell(4), col.disabledCls)).toBe(true);
            });

            it("should remove the disabledCls if enabling", function() {
                var cfg = getColCfg();

                cfg.disabled = true;
                makeGrid([cfg]);
                col.enable();
                expect(hasCls(getCell(0), col.disabledCls)).toBe(false);
                expect(hasCls(getCell(1), col.disabledCls)).toBe(false);
                expect(hasCls(getCell(2), col.disabledCls)).toBe(false);
                expect(hasCls(getCell(3), col.disabledCls)).toBe(false);
                expect(hasCls(getCell(4), col.disabledCls)).toBe(false);
            });
        });
    });

    describe("interaction", function() {
        describe("stopSelection", function() {
            describe("stopSelection: false", function() {
                it("should select when a full row update is required", function() {
                    var cfg = getColCfg();

                    cfg.stopSelection = false;
                    // Template column always required a full update
                    makeGrid([cfg, {
                        xtype: 'templatecolumn',
                        dataIndex: 'val',
                        tpl: '{val}'
                    }]);
                    triggerCellMouseEvent('click', 0);
                    expect(grid.getSelectionModel().isSelected(store.getAt(0))).toBe(true);
                });

                it("should select when a full row update is not required", function() {
                    var cfg = getColCfg();

                    cfg.stopSelection = false;
                    // Template column always required a full update
                    makeGrid([cfg, {
                        dataIndex: 'val'
                    }]);
                    triggerCellMouseEvent('click', 0);
                    expect(grid.getSelectionModel().isSelected(store.getAt(0))).toBe(true);
                });
            });

            describe("stopSelection: true", function() {
                it("should not select when a full row update is required", function() {
                    var cfg = getColCfg();

                    cfg.stopSelection = true;
                    // Template column always required a full update
                    makeGrid([cfg, {
                        xtype: 'templatecolumn',
                        dataIndex: 'val',
                        tpl: '{val}'
                    }]);
                    triggerCellMouseEvent('click', 0);
                    expect(grid.getSelectionModel().isSelected(store.getAt(0))).toBe(false);
                });

                it("should not select when a full row update is not required", function() {
                    var cfg = getColCfg();

                    cfg.stopSelection = true;
                    // Template column always required a full update
                    makeGrid([cfg, {
                        dataIndex: 'val'
                    }]);
                    triggerCellMouseEvent('click', 0);
                    expect(grid.getSelectionModel().isSelected(store.getAt(0))).toBe(false);
                });
            });
        });

        describe("events", function() {
            it("should pass the column, record index, new checked state & record for beforecheckchange", function() {
                var arg1, arg2, arg3, arg4;

                makeGrid();
                col.on('beforecheckchange', function(a, b, c, d) {
                    arg1 = a;
                    arg2 = b;
                    arg3 = c;
                    arg4 = d;
                });
                triggerCellMouseEvent(col.triggerEvent, 0);
                expect(arg1).toBe(col);
                expect(arg2).toBe(0);
                expect(arg3).toBe(false);
                expect(arg4).toBe(store.getAt(0));
            });

            it("should pass the column, record index, new checked state & record for checkchange", function() {
                var arg1, arg2, arg3, arg4;

                makeGrid();
                col.on('checkchange', function(a, b, c, d) {
                    arg1 = a;
                    arg2 = b;
                    arg3 = c;
                    arg4 = d;
                });
                triggerCellMouseEvent(col.triggerEvent, 2);
                expect(arg1).toBe(col);
                expect(arg2).toBe(2);
                expect(arg3).toBe(true);
                expect(arg4).toBe(store.getAt(2));
            });

            it("should not fire fire checkchange if beforecheckchange returns false", function() {
                var called = false;

                makeGrid();
                col.on('checkchange', function() {
                    called = true;
                });
                col.on('beforecheckchange', function() {
                    return false;
                });
                triggerCellMouseEvent(col.triggerEvent, 2);
                expect(called).toBe(false);
            });
        });

        it("should toggle the record value", function() {
            makeGrid();
            triggerCellMouseEvent(col.triggerEvent, 0);
            expect(store.getAt(0).get('val')).toBe(false);
            expect(hasCls(getCellImg(0), 'x-grid-checkcolumn-checked')).toBe(false);
            triggerCellMouseEvent(col.triggerEvent, 2);
            expect(store.getAt(2).get('val')).toBe(true);
            expect(hasCls(getCellImg(2), 'x-grid-checkcolumn-checked')).toBe(true);
        });

        it("should toggle when using property without a dataIndex", function() {
            makeGrid([{
                xtype: 'checkcolumn',
                property: 'foo'
            }], ['bar']);

            triggerCellMouseEvent(col.triggerEvent, 0);
            expect(store.getAt(0).foo).toBe(true);
        });

        it("should toggle the record value with invert: true", function() {
            invert = true;
            makeGrid();
            invert = false;
            triggerCellMouseEvent(col.triggerEvent, 0);
            expect(store.getAt(0).get('val')).toBe(false);
            expect(hasCls(getCellImg(0), 'x-grid-checkcolumn-checked')).toBe(true);
            triggerCellMouseEvent(col.triggerEvent, 2);
            expect(store.getAt(2).get('val')).toBe(true);
            expect(hasCls(getCellImg(2), 'x-grid-checkcolumn-checked')).toBe(false);
        });

        it("should not trigger any changes when disabled", function() {
            var cfg = getColCfg();

            cfg.disabled = true;
            makeGrid([cfg]);
            triggerCellMouseEvent(col.triggerEvent, 0);
            expect(store.getAt(0).get('val')).toBe(true);
            triggerCellMouseEvent(col.triggerEvent, 2);
            expect(store.getAt(2).get('val')).toBe(false);
        });
    });

    describe('Header checkbox', function() {
        beforeEach(function() {
            var ready;

            makeGrid([{
                xtype: 'checkcolumn',
                headerCheckbox: true,
                text: 'Checked',
                dataIndex: 'val',
                listeners: {
                    headercheckchange: function() {
                        ready = true;
                    }
                }
            }]);

            // Wait for the header state to be synched.
            // This is done on animation frae, and there's no event.
            waits(100);
        });

        it('should toggle all on header checkbox click', function() {
            var headercheckchangeCount = 0;

            col.on({
                headercheckchange: function() {
                    headercheckchangeCount++;
                }
            });

            // Test selecting all
            clickHeader();
            store.each(function(rec) {
                expect(rec.get('val')).toBe(true);
            });

            // Header checkbox is updated on a timer for efficiency, so must wait
            waitsFor(function() {
                expect(headercheckchangeCount).toBe(1);

                return col.el.hasCls(col.headerCheckedCls) === true;
            });

            runs(function() {
                // Test deselecting all
                clickHeader();
                store.each(function(rec) {
                    expect(rec.get('val')).toBe(false);
                });
            });

            // Header checkbox is updated on a timer for efficiency, so must wait
            waitsFor(function() {
                expect(headercheckchangeCount).toBe(2);

                return col.el.hasCls(col.headerCheckedCls) === false;
            });
        });
        it('should not toggle all on header checkbox click if the beforeheadercheckchange event is vetoed', function() {
            var headercheckchangeCalled = false;

            col.on({
                beforeheadercheckchange: function() {
                    return false;
                },
                headercheckchange: function() {
                    headercheckchangeCalled = true;
                }
            });

            // Test vetoing of selecting all
            clickHeader();
            store.each(function(rec) {
                expect(rec.isModified('val')).toBe(false);
            });

            // Nothing should happen.
            // We are expecting the header checkbox state to remain false
            waits(100);

            // The header must not have been updated to true because of the veto
            runs(function() {
                expect(headercheckchangeCalled).toBe(false);
                expect(col.el.hasCls(col.headerCheckedCls)).toBe(false);
            });
        });

        itNotIE9('should set the header checkbox when all rows are checked', function() {
            var headercheckchangeCount = 0;

            col.on({
                headercheckchange: function() {
                    headercheckchangeCount++;
                }
            });

            // Rows 2 and 4 are unchecked. Header should start unchecked.
            expect(col.el.hasCls(col.headerCheckedCls)).toBe(false);

            triggerCellMouseEvent('click', 2);

            // Nothing should happen.
            // We are expecting the header checkbox state to remain false
            waits(100);

            // The header must not have been updated to true because of the veto
            runs(function() {
                expect(headercheckchangeCount).toBe(0);
                expect(col.el.hasCls(col.headerCheckedCls)).toBe(false);
                triggerCellMouseEvent('click', 4);
            });

            // Header checkbox is updated on a timer for efficiency, so must wait
            waitsFor(function() {
                return col.el.hasCls(col.headerCheckedCls) === true;
            }, 'column header to be checked');
        });

        itNotIE9('should clear the header checkbox when a new, unchecked record is added', function() {
            var rowCount = view.all.getCount();

            // Rows 2 and 4 are unchecked. Header should start unchecked.
            expect(col.el.hasCls(col.headerCheckedCls)).toBe(false);

            // Now all are selected
            clickHeader();

            // Header checkbox is updated on a timer for efficiency, so must wait
            waitsFor(function() {
                return col.el.hasCls(col.headerCheckedCls) === true;
            });

            // Add a record. This should cause the header checkbox to clear
            runs(function() {
                store.add({});
            });

            // Header checkbox is updated on a timer for efficiency, so must wait
            waitsFor(function() {
                return view.all.getCount() === rowCount + 1 &&
                       col.el.hasCls(col.headerCheckedCls) === false;
            }, 'column header to be unchecked');
        });

        itNotIE9('should set the header checkbox when all records have the dataIndex field set', function() {
            // Rows 2 and 4 are unchecked. Header should start unchecked.
            expect(col.el.hasCls(col.headerCheckedCls)).toBe(false);

            store.getAt(2).set('val', true);

            // Nothing should happen.
            // We are expecting the header checkbox state to remain false
            waits(100);

            runs(function() {
                expect(col.el.hasCls(col.headerCheckedCls)).toBe(false);
                store.getAt(4).set('val', true);
            });

            // Header checkbox is updated on a timer for efficiency, so must wait
            waitsFor(function() {
                return col.el.hasCls(col.headerCheckedCls) === true;
            }, 'column header to be checked');
        });
    });

    describe("aria", function() {
        it("should not throw when committing", function() {
            var cfg = getColCfg();

            cfg.useAriaElements = true;
            makeGrid([cfg]);
            var rec = store.first(),
                cell;

            rec.commit();
            cell = grid.getView().getCell(rec, col);
            expect(cell.getAttribute('aria-describedby')).toBe(col.id + '-cell-description-selected');
            rec.set('val', false);
            cell = grid.getView().getCell(rec, col);
            expect(cell.getAttribute('aria-describedby')).toBe(col.id + '-cell-description-not-selected');
        });
    });

    describe("tips", function() {
        function getTipEl(row) {
            row = grid.getView().getRow(row);

            return Ext.fly(row).down('.x-grid-cell', true);
        }

        describe("no tips", function() {
            it("should not have tips during render", function() {
                makeGrid(null, [{ val: true }, { val: false }]);
                expect(getTipEl(0).hasAttribute('data-qtip')).toBe(false);
                expect(getTipEl(1).hasAttribute('data-qtip')).toBe(false);
            });

            it("should not have tips after modifying values", function() {
                makeGrid(null, [{ val: true }, { val: false }]);
                store.getAt(0).set('val', false);
                store.getAt(1).set('val', true);
                expect(getTipEl(0).hasAttribute('data-qtip')).toBe(false);
                expect(getTipEl(1).hasAttribute('data-qtip')).toBe(false);
            });
        });

        describe("tooltip only", function() {
            it("should only have tips on unchecked cells during render", function() {
                makeGrid([getColCfg({
                    tooltip: 'foo'
                })], [{ val: true }, { val: false }]);
                expect(getTipEl(0).hasAttribute('data-qtip')).toBe(false);
                expect(getTipEl(1).getAttribute('data-qtip')).toBe('foo');
            });

            it("should only have tips on unchecked cells after modifying values", function() {
                makeGrid([getColCfg({
                    tooltip: 'foo'
                })], [{ val: true }, { val: false }]);
                store.getAt(0).set('val', false);
                store.getAt(1).set('val', true);
                expect(getTipEl(0).getAttribute('data-qtip')).toBe('foo');
                expect(getTipEl(1).hasAttribute('data-qtip')).toBe(false);
            });
        });

        describe("checkedTooltip only", function() {
            it("should only have tips on checked cells during render", function() {
                makeGrid([getColCfg({
                    checkedTooltip: 'foo'
                })], [{ val: true }, { val: false }]);
                expect(getTipEl(0).getAttribute('data-qtip')).toBe('foo');
                expect(getTipEl(1).hasAttribute('data-qtip')).toBe(false);
            });

            it("should only have tips on checked cells after modifying values", function() {
                makeGrid([getColCfg({
                    checkedTooltip: 'foo'
                })], [{ val: true }, { val: false }]);
                store.getAt(0).set('val', false);
                store.getAt(1).set('val', true);
                expect(getTipEl(0).hasAttribute('data-qtip')).toBe(false);
                expect(getTipEl(1).getAttribute('data-qtip')).toBe('foo');
            });
        });

        describe("both tips", function() {
            it("should have tips on both cell types during render", function() {
                makeGrid([getColCfg({
                    tooltip: 'foo',
                    checkedTooltip: 'bar'
                })], [{ val: true }, { val: false }]);
                expect(getTipEl(0).getAttribute('data-qtip')).toBe('bar');
                expect(getTipEl(1).getAttribute('data-qtip')).toBe('foo');
            });

            it("should html encode tips during render", function() {
                makeGrid([getColCfg({
                    tooltip: '<span>no</span>',
                    checkedTooltip: '<span>yes</span>'
                })], [{ val: true }, { val: false }]);
                expect(getTipEl(0).getAttribute('data-qtip')).toBe('<span>yes</span>');
                expect(getTipEl(1).getAttribute('data-qtip')).toBe('<span>no</span>');
            });

            it("should have cells on both cell types after modifying values", function() {
                makeGrid([getColCfg({
                    tooltip: 'foo',
                    checkedTooltip: 'bar'
                })], [{ val: true }, { val: false }]);
                store.getAt(0).set('val', false);
                store.getAt(1).set('val', true);
                expect(getTipEl(0).getAttribute('data-qtip')).toBe('foo');
                expect(getTipEl(1).getAttribute('data-qtip')).toBe('bar');
            });

            it("should html encode tips after modifying values", function() {
                makeGrid([getColCfg({
                    tooltip: '<span>no</span>',
                    checkedTooltip: '<span>yes</span>'
                })], [{ val: true }, { val: false }]);
                store.getAt(0).set('val', false);
                store.getAt(1).set('val', true);
                expect(getTipEl(0).getAttribute('data-qtip')).toBe('<span>no</span>');
                expect(getTipEl(1).getAttribute('data-qtip')).toBe('<span>yes</span>');
            });
        });
    });
});
