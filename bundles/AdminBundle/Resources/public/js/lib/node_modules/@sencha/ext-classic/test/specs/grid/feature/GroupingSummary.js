topSuite("Ext.grid.feature.GroupingSummary", ['Ext.grid.Panel'], function() {
    var data, grid, store, groupingSummary, columns, params, selector,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

    function createGrid(gridCfg, groupingSummaryCfg, columns, storeCfg) {
        data = [{
            student: 'Student 1',
            subject: 'Math',
            mark: 84,
            allowance: 15.50
        }, {
            student: 'Student 1',
            subject: 'Science',
            mark: 72,
            allowance: 10.75
        }, {
            student: 'Student 2',
            subject: 'Math',
            mark: 96,
            allowance: 100.75
        }, {
            student: 'Student 2',
            subject: 'Science',
            mark: 68,
            allowance: 1.55
        }];

        Ext.define('spec.GroupingSummary', {
            extend: 'Ext.data.Model',
            fields: [
                'student',
                'subject',
                {
                    name: 'mark',
                    type: 'int'
                },
                {
                    name: 'allowance',
                    type: 'float'
                }
            ]
        });

        storeCfg = Ext.apply({
            model: 'spec.GroupingSummary',
            data: data,
            autoDestroy: true
        }, storeCfg);

        if (!storeCfg.grouper && !storeCfg.hasOwnProperty('groupField')) {
            storeCfg.groupField = 'subject';
        }

        store = new Ext.data.Store(storeCfg);

        groupingSummary = new Ext.grid.feature.GroupingSummary(Ext.apply({
            ftype: 'groupingsummary'
        }, groupingSummaryCfg));

        columns = columns || [{
            itemId: 'studentColumn',
            dataIndex: 'student',
            text: 'Name',
            summaryType: 'count',
            summaryRenderer: function(value, summaryData, field, metaData) {
                params = arguments;

                return Ext.String.format('{0} student{1}', value, value !== 1 ? 's' : '');
            }
        }, {
            itemId: 'markColumn',
            dataIndex: 'mark',
            text: 'Mark',
            summaryType: 'average'
        }, {
            itemId: 'noDataIndexColumn',
            summaryType: function(records, values) {
                var i = 0,
                    length = records.length,
                    total = 0,
                    record;

                for (; i < length; ++i) {
                    record = records[i];
                    total += record.get('allowance');
                }

                return total;
            },
            summaryRenderer: function(value, summaryData, field, metaData) {
                return Ext.util.Format.usMoney(value || metaData.record.get('allowance'));
            },
            renderer: function(value, metaData, record, rowIdx, colIdx, store, view) {
                return Ext.util.Format.usMoney(record.get('allowance'));
            }
        }];

        grid = new Ext.grid.Panel(Ext.apply({
            store: store,
            columns: columns,
            width: 600,
            height: 300,
            features: groupingSummary,
            renderTo: Ext.getBody()
        }, gridCfg));
        selector = groupingSummary.summaryRowSelector;
    }

    function expectData(fields, expected) {
        var groupInfo = groupingSummary.getMetaGroup(store.getGroups().getAt(0)),
            record = groupInfo.aggregateRecord;

        expect(record.get(fields[0])).toBe(expected[0]);
        expect(record.get(fields[1])).toBe(expected[1]);
    }

    function expectSummaryRow(view) {
        var summaryRow = grid.view.body.down('.x-grid-row-summary', true);

        expect((summaryRow.textContent || summaryRow.innerText).replace(/\s/g, '')).toBe(view);
    }

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        Ext.data.ProxyStore.prototype.load = loadStore;
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;

        grid.destroy();
        grid = store = groupingSummary = columns = params = null;
        Ext.undefine('spec.GroupingSummary');
        Ext.data.Model.schema.clear();
    });

    describe('summaryRenderer', function() {
        it('should be passed the expected function parameters', function() {
            // Note that we're only capturing the values for the second group.
            createGrid();

            // Params should be:
            //     value - The calculated value.
            //     summaryData - Contains all raw summary values for the row.
            //     field - The name of the field we are calculating
            //     metaData - The collection of metadata about the current cell.
            expect(params.length).toBe(4);
            expect(params[0]).toBe(2);
            expect(params[1]).toEqual({
                studentColumn: 2,
                markColumn: 70,
                noDataIndexColumn: 12.3
            });
            expect(params[2]).toBe('student');
            expect(params[3].tdCls).toBeDefined();
        });

        it('should be able to read the data from the summary record when there is no column.dataIndex', function() {
            var node;

            createGrid();

            // The "Student 2" item is a wrapper which also encapsulates the summary row.
            // It will contain 2 rows: Student 2 and the summary.
            node = grid.view.all.item(1, true);

            expect((node.textContent || node.innerText).replace(/\r\n?|\n/g, '')).toBe('Student 296$100.752 students90$116.25');
        });

        it('should update when group records are removed', function() {
            var mathGroup;

            createGrid();
            mathGroup = groupingSummary.summaryData.Math;

            // Pre-removal.
            expect(mathGroup.markColumn).toBe(90);
            expect(mathGroup.noDataIndexColumn).toBe(116.25);
            expect(mathGroup.studentColumn).toBe(2);

            store.removeAt(1);

            // The summaryData record should be updated.
            expect(mathGroup.markColumn).toBe(84);
            expect(mathGroup.noDataIndexColumn).toBe(15.5);
            expect(mathGroup.studentColumn).toBe(1);
        });
    });

    describe("toggling the summary row", function() {
        function toggle(visible) {
            groupingSummary.toggleSummaryRow(visible);
        }

        it("should show the summary row by default", function() {
            createGrid();
            expect(grid.getView().getEl().select(selector).getCount()).toBe(2);
        });

        it("should not render the summary rows if configured with showSummaryRow: false", function() {
            createGrid(null, {
                showSummaryRow: false
            });
            expect(grid.getView().getEl().select(selector).getCount()).toBe(0);
        });

        it("should not show summary rows when toggling off", function() {
            createGrid();
            expect(grid.getView().getEl().select(selector).getCount()).toBe(2);
            toggle();
            expect(grid.getView().getEl().select(selector).getCount()).toBe(0);
        });

        it("should show summary rows when toggling on", function() {
            createGrid(null, {
                showSummaryRow: false
            });
            expect(grid.getView().getEl().select(selector).getCount()).toBe(0);
            toggle();
            expect(grid.getView().getEl().select(selector).getCount()).toBe(2);
        });

        it("should leave the summary visible when explicitly passing visible: true", function() {
            createGrid();
            toggle(true);
            expect(grid.getView().getEl().select(selector).getCount()).toBe(2);
        });

        it("should leave the summary off when explicitly passed visible: false", function() {
            createGrid();
            toggle();
            toggle(false);
            expect(grid.getView().getEl().select(selector).getCount()).toBe(0);
        });

        it("should update the summary row if the change happened while not visible", function() {
            createGrid();
            // Off
            toggle();
            store.first().set('mark', 0);
            toggle();

            var row = grid.getView().getEl().dom.querySelector(selector),
                cell = row.querySelector(grid.down('#markColumn').getCellSelector());

            var content = cell.querySelector(grid.getView().innerSelector).innerHTML;

            expect(content).toBe('48');
        });
    });

    describe('when the view is refreshed', function() {
        function expectIt(data) {
            it('should retain the summary feature row information in the feature cache', function() {
                // Get the cached information that the feature is retaining for the Math group.
                var groupInfo = groupingSummary.getMetaGroup(store.getGroups().getAt(0)),
                    record = groupInfo.aggregateRecord;

                expect(record.get('student')).toBe(data.student);
                expect(record.get('mark')).toBe(data.mark);
                expect(record.get('noDataIndexColumn')).toBe(data.noDataIndexColumn);
            });

            it('should retain the summary feature row information in the view', function() {
                var summaryRow = grid.view.body.down('.x-grid-row-summary', true);

                expect((summaryRow.textContent || summaryRow.innerText).replace(/\s/g, '')).toBe(data.view);
            });
        }

        describe('when toggling the enabled/disabled state of the groups', function() {
            // Note that the bug only happens when toggling twice (first to disable, then to enable).
            // See EXTJS-16141.
            beforeEach(function() {
                createGrid();

                groupingSummary.disable();
                groupingSummary.enable();
            });

            expectIt({
                student: 2,
                mark: 90,
                noDataIndexColumn: 116.25,
                view: '2students90$116.25'
            });
        });

        describe('when filtering the store', function() {
            // See EXTJS-15267.
            beforeEach(function() {
                createGrid();

                grid.store.addFilter({ property: 'mark', operator: 'eq', value: 84 });
            });

            describe('adding a filter', function() {
                expectIt({
                    student: 1,
                    mark: 84,
                    noDataIndexColumn: 15.50,
                    view: '1student84$15.50'
                });
            });

            describe('clearing the filters', function() {
                beforeEach(function() {
                    grid.store.clearFilter();
                });

                expectIt({
                    student: 2,
                    mark: 90,
                    noDataIndexColumn: 116.25,
                    view: '2students90$116.25'
                });
            });
        });
    });

    describe('reconfiguring', function() {
        beforeEach(function() {
            createGrid();
        });

        describe('new store', function() {
            it('should update the summary row', function() {
                store = new Ext.data.Store({
                    model: 'spec.GroupingSummary',
                    groupField: 'subject',
                    data: [{
                        student: 'Student 1',
                        subject: 'Math',
                        mark: 84,
                        allowance: 15.50
                    }, {
                        student: 'Student 2',
                        subject: 'Science',
                        mark: 68,
                        allowance: 1.55
                    }],
                    autoDestroy: true
                });

                grid.reconfigure(store);

                expectData(
                    ['mark', 'noDataIndexColumn'],
                    [84, 15.50]
                );

                expectSummaryRow('1student84$15.50');
            });
        });

        describe('new columns', function() {
            it('should update the summary row', function() {
                grid.reconfigure(null, [{
                    itemId: 'studentColumn',
                    dataIndex: 'student',
                    text: 'Name',
                    summaryType: 'count',
                    summaryRenderer: function(value, summaryData, field, metaData) {
                        params = arguments;

                        return Ext.String.format('{0} student{1}', value, value !== 1 ? 's' : '');
                    }
                }, {
                    itemId: 'allowance',
                    dataIndex: 'allowance',
                    text: 'Allowance',
                    summaryType: 'average'
                }]);

                expectData(
                    ['student', 'allowance'],
                    [2, 58.125]
                );

                expectSummaryRow('2students58.125');
            });
        });
    });

    describe("loading the store", function() {
        beforeEach(function() {
            MockAjaxManager.addMethods();
        });

        afterEach(function() {
            MockAjaxManager.removeMethods();
        });

        it("should update the summary when loading remote data", function() {
            createGrid(null, null, null, {
                data: null,
                proxy: {
                    type: 'ajax',
                    url: 'Foo'
                }
            });
            store.load();
            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode(data)
            });

            var rows = grid.view.el.query(groupingSummary.summaryRowSelector);

            expect((rows[0].textContent || rows[0].innerText).replace(/\s/g, '')).toBe('2students90$116.25');
            expect((rows[1].textContent || rows[1].innerText).replace(/\s/g, '')).toBe('2students70$12.30');

            store.load();
            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode([{
                    student: 'Student 1',
                    subject: 'Math',
                    mark: 77,
                    allowance: 30
                }, {
                    student: 'Student 2',
                    subject: 'Science',
                    mark: 20,
                    allowance: 30.12
                }, {
                    student: 'Student 3',
                    subject: 'Science',
                    mark: 30,
                    allowance: 12
                }, {
                    student: 'Student 4',
                    subject: 'Science',
                    mark: 40,
                    allowance: 1
                }])
            });

            rows = grid.view.el.query(groupingSummary.summaryRowSelector);

            expect((rows[0].textContent || rows[0].innerText).replace(/\s/g, '')).toBe('1student77$30.00');
            expect((rows[1].textContent || rows[1].innerText).replace(/\s/g, '')).toBe('3students30$43.12');
        });

        it("should update the summary when loading local data", function() {
            createGrid();

            var rows = grid.view.el.query(groupingSummary.summaryRowSelector);

            expect((rows[0].textContent || rows[0].innerText).replace(/\s/g, '')).toBe('2students90$116.25');
            expect((rows[1].textContent || rows[1].innerText).replace(/\s/g, '')).toBe('2students70$12.30');

            store.loadData([{
                student: 'Student 1',
                subject: 'Math',
                mark: 77,
                allowance: 30
            }, {
                student: 'Student 2',
                subject: 'Science',
                mark: 20,
                allowance: 30.12
            }, {
                student: 'Student 3',
                subject: 'Science',
                mark: 30,
                allowance: 12
            }, {
                student: 'Student 4',
                subject: 'Science',
                mark: 40,
                allowance: 1
            }]);

            rows = grid.view.el.query(groupingSummary.summaryRowSelector);

            expect((rows[0].textContent || rows[0].innerText).replace(/\s/g, '')).toBe('1student77$30.00');
            expect((rows[1].textContent || rows[1].innerText).replace(/\s/g, '')).toBe('3students30$43.12');
        });
    });

    describe('remoteRoot', function() {
        function completeWithData(data) {
            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.JSON.encode(data)
            });
        }

        beforeEach(function() {
            MockAjaxManager.addMethods();

            createGrid(null, {
                remoteRoot: 'summaryData'
            }, null, {
                remoteSort: true,
                proxy: {
                    type: 'ajax',
                    url: 'data.json',
                    reader: {
                        type: 'json',
                        rootProperty: 'data'
                    }
                },
                grouper: { property: 'student' },
                data: null
            });

            store.load();

            completeWithData({
                data: data,
                summaryData: [{
                    allowance: 67,
                    mark: 42,
                    student: 'Student 1'
                }, {
                    allowance: 100,
                    mark: 99,
                    student: 'Student 2'
                }],
                total: 4
            });
        });

        afterEach(function() {
            MockAjaxManager.removeMethods();
        });

        it('should correctly render the data in the view', function() {
            var rows = grid.view.body.query('.x-grid-row-summary');

            expect((rows[0].textContent || rows[0].innerText).replace(/\s/g, '')).toBe('Student1students42$67.00');
            expect((rows[1].textContent || rows[1].innerText).replace(/\s/g, '')).toBe('Student2students99$100.00');
        });

        it('should create a summaryData object for each group', function() {
            var summaryData = groupingSummary.summaryData;

            expect(summaryData['Student 1']).toBeDefined();
            expect(summaryData['Student 2']).toBeDefined();
        });

        it('should create a metaGroupCache entry for each group', function() {
            var metaGroupCache = groupingSummary.getCache();

            expect(metaGroupCache['Student 1']).toBeDefined();
            expect(metaGroupCache['Student 2']).toBeDefined();
        });
    });
});
