topSuite("Ext.layout.container.CheckboxGroup",
    ['Ext.form.CheckboxGroup', 'Ext.form.field.Checkbox'],
function() {
    var checkboxGroup;

    function makeGroup(config) {
        config = Ext.apply({
            renderTo: Ext.getBody(),
            width: 300,
            height: 300,
            items: [
                { id: 'cb0', boxLabel: 'cb0' },
                { id: 'cb1', boxLabel: 'cb1' },
                { id: 'cb2', boxLabel: 'cb2' },
                { id: 'cb3', boxLabel: 'cb3' },
                { id: 'cb4', boxLabel: 'cb4' },
                { id: 'cb5', boxLabel: 'cb5' },
                { id: 'cb6', boxLabel: 'cb6' },
                { id: 'cb7', boxLabel: 'cb7' }
            ]
        }, config);

        return checkboxGroup = new Ext.form.CheckboxGroup(config);
    }

    function expectColumnToContainExactly() {
        var column = arguments[0],
            argumentsLength = arguments.length,
            checkboxes = [],
            checkboxesLength, i;

        for (i = 1; i < argumentsLength; i++) {
            checkboxes[i - 1] = Ext.getCmp(arguments[i]).el.dom;
        }

        checkboxesLength = checkboxes.length;

        expect(column.childNodes.length).toBe(checkboxesLength);

        for (i = 0; i < checkboxesLength; i++) {
            expect(column.childNodes[i]).toBe(checkboxes[i]);
        }
    }

    function expectRowToContain() {
        var row = arguments[0],
            boxes = [],
            column, i, len;

        for (i = 1, len = arguments.length; i < len; i++) {
            if (arguments[i]) {
                boxes[i - 1] = Ext.getCmp(arguments[i]).el.dom;
            }
            else {
                boxes[i - 1] = null;
            }
        }

        len = boxes.length;

        expect(row.childNodes.length).toBe(len);

        for (i = 0; i < len; i++) {
            column = row.childNodes[i];

            if (boxes[i]) {
                expect(boxes[i].parentNode).toBe(column);
            }
            else {
                expect(column.childNodes.length).toBe(0);
            }
        }
    }

    function createCheckbox(id) {
        return new Ext.form.field.Checkbox({
            id: id,
            boxLabel: id
        });
    }

    afterEach(function() {
        checkboxGroup = Ext.destroy(checkboxGroup);
    });

    describe("layout initialization", function() {
        it("should distribute items automatically with columns: 'auto'", function() {
            makeGroup({
                columns: 'auto'
            });

            var columns = checkboxGroup.layout.rowNodes[0].childNodes;

            expect(columns.length).toBe(8);
            expectColumnToContainExactly(columns[0], 'cb0');
            expectColumnToContainExactly(columns[1], 'cb1');
            expectColumnToContainExactly(columns[2], 'cb2');
            expectColumnToContainExactly(columns[3], 'cb3');
            expectColumnToContainExactly(columns[4], 'cb4');
            expectColumnToContainExactly(columns[5], 'cb5');
            expectColumnToContainExactly(columns[6], 'cb6');
            expectColumnToContainExactly(columns[7], 'cb7');
        });

        it("should distribute items automatically with missing columns config", function() {
            makeGroup();

            var columns = checkboxGroup.layout.rowNodes[0].childNodes;

            expect(columns.length).toBe(8);
            expectColumnToContainExactly(columns[0], 'cb0');
            expectColumnToContainExactly(columns[1], 'cb1');
            expectColumnToContainExactly(columns[2], 'cb2');
            expectColumnToContainExactly(columns[3], 'cb3');
            expectColumnToContainExactly(columns[4], 'cb4');
            expectColumnToContainExactly(columns[5], 'cb5');
            expectColumnToContainExactly(columns[6], 'cb6');
            expectColumnToContainExactly(columns[7], 'cb7');
        });

        it("should distribute items horizontally", function() {
            makeGroup({
                columns: 3
            });

            var rows = checkboxGroup.layout.tBodyNode.childNodes;

            expect(rows.length).toBe(3);

            expectRowToContain(rows[0], 'cb0', 'cb1', 'cb2');
            expectRowToContain(rows[1], 'cb3', 'cb4', 'cb5');
            expectRowToContain(rows[2], 'cb6', 'cb7');
        });

        it("should distribute items vertically", function() {
            makeGroup({
                columns: 3,
                vertical: true
            });

            var columns = checkboxGroup.layout.rowNodes[0].childNodes;

            expect(columns.length).toBe(3);
            expectColumnToContainExactly(columns[0], 'cb0', 'cb1', 'cb2');
            expectColumnToContainExactly(columns[1], 'cb3', 'cb4', 'cb5');
            expectColumnToContainExactly(columns[2], 'cb6', 'cb7');
        });

        it("should distribute items vertically with only one column", function() {
            makeGroup({
                columns: 1
            });

            var columns = checkboxGroup.layout.rowNodes[0].childNodes;

            expect(columns.length).toBe(1);
            expectColumnToContainExactly(columns[0], 'cb0', 'cb1', 'cb2', 'cb3',
                                                     'cb4', 'cb5', 'cb6', 'cb7');
        });
    });

    describe('adding items', function() {
        it("should distribute items automatically with columns: 'auto'", function() {
            makeGroup({
                columns: 'auto',
                items: [
                    { id: 'cb0', boxLabel: 'cb0' },
                    { id: 'cb1', boxLabel: 'cb1' }
                ]
            });

            var columns = checkboxGroup.layout.rowNodes[0].childNodes;

            expect(columns.length).toBe(2);
            expectColumnToContainExactly(columns[0], 'cb0');
            expectColumnToContainExactly(columns[1], 'cb1');

            checkboxGroup.add(
                createCheckbox('cb2'),
                createCheckbox('cb3')
            );

            expect(columns.length).toBe(4);
            expectColumnToContainExactly(columns[0], 'cb0');
            expectColumnToContainExactly(columns[1], 'cb1');
            expectColumnToContainExactly(columns[2], 'cb2');
            expectColumnToContainExactly(columns[3], 'cb3');
        });

        it("should distribute items automatically with missing columns config", function() {
            makeGroup({
                items: [
                    { id: 'cb0', boxLabel: 'cb0' },
                    { id: 'cb1', boxLabel: 'cb1' }
                ]
            });

            var columns = checkboxGroup.layout.rowNodes[0].childNodes;

            expect(columns.length).toBe(2);
            expectColumnToContainExactly(columns[0], 'cb0');
            expectColumnToContainExactly(columns[1], 'cb1');

            checkboxGroup.add(
                createCheckbox('cb2'),
                createCheckbox('cb3')
            );

            expect(columns.length).toBe(4);
            expectColumnToContainExactly(columns[0], 'cb0');
            expectColumnToContainExactly(columns[1], 'cb1');
            expectColumnToContainExactly(columns[2], 'cb2');
            expectColumnToContainExactly(columns[3], 'cb3');
        });

        it("should distribute items horizontally", function() {
            makeGroup({
                columns: 3,
                items: [
                    { id: 'cb0', boxLabel: 'cb0' },
                    { id: 'cb1', boxLabel: 'cb1' }
                ]
            });

            var rows = checkboxGroup.layout.tBodyNode.childNodes;

            expect(rows.length).toBe(1);
            expectRowToContain(rows[0], 'cb0', 'cb1', null);

            checkboxGroup.add(
                createCheckbox('cb2'),
                createCheckbox('cb3'),
                createCheckbox('cb4')
            );

            expect(rows.length).toBe(2);
            expectRowToContain(rows[0], 'cb0', 'cb1', 'cb2');
            expectRowToContain(rows[1], 'cb3', 'cb4');

            checkboxGroup.add(
                createCheckbox('cb5')
            );

            expect(rows.length).toBe(2);
            expectRowToContain(rows[0], 'cb0', 'cb1', 'cb2');
            expectRowToContain(rows[1], 'cb3', 'cb4', 'cb5');

            checkboxGroup.add(
                createCheckbox('cb6')
            );

            expect(rows.length).toBe(3);
            expectRowToContain(rows[0], 'cb0', 'cb1', 'cb2');
            expectRowToContain(rows[1], 'cb3', 'cb4', 'cb5');
            expectRowToContain(rows[2], 'cb6');
        });

        it("should distribute items vertically", function() {
            makeGroup({
                columns: 3,
                vertical: true,
                items: [
                    { id: 'cb0', boxLabel: 'cb0' },
                    { id: 'cb1', boxLabel: 'cb1' }
                ]
            });

            var columns = checkboxGroup.layout.rowNodes[0].childNodes;

            expect(columns.length).toBe(3);
            expectColumnToContainExactly(columns[0], 'cb0');
            expectColumnToContainExactly(columns[1], 'cb1');

            checkboxGroup.add(
                createCheckbox('cb2'),
                createCheckbox('cb3'),
                createCheckbox('cb4')
            );

            expect(columns.length).toBe(3);
            expectColumnToContainExactly(columns[0], 'cb0', 'cb1');
            expectColumnToContainExactly(columns[1], 'cb2', 'cb3');
            expectColumnToContainExactly(columns[2], 'cb4');
        });

        it("should distribute items vertically with only one column", function() {
            makeGroup({
                columns: 1,
                items: [
                    { id: 'cb0', boxLabel: 'cb0' },
                    { id: 'cb1', boxLabel: 'cb1' }
                ]
            });

            var columns = checkboxGroup.layout.rowNodes[0].childNodes;

            expect(columns.length).toBe(1);
            expectColumnToContainExactly(columns[0], 'cb0', 'cb1');

            checkboxGroup.add(
                createCheckbox('cb2'),
                createCheckbox('cb3'),
                createCheckbox('cb4')
            );

            expect(columns.length).toBe(1);
            expectColumnToContainExactly(columns[0], 'cb0', 'cb1', 'cb2', 'cb3', 'cb4');
        });
    });

    describe('removing items', function() {
        it("should distribute items automatically with columns: 'auto'", function() {
            makeGroup({
                columns: 'auto',
                items: [
                    { id: 'cb0', boxLabel: 'cb0' },
                    { id: 'cb1', boxLabel: 'cb1' }
                ]
            });

            var columns = checkboxGroup.layout.rowNodes[0].childNodes;

            // ensure removal works with both original and dynamically added items
            checkboxGroup.add(
                createCheckbox('cb2'),
                createCheckbox('cb3')
            );

            checkboxGroup.remove(Ext.getCmp('cb0'));
            checkboxGroup.remove(Ext.getCmp('cb3'));

            expect(columns.length).toBe(2);
            expectColumnToContainExactly(columns[0], 'cb1');
            expectColumnToContainExactly(columns[1], 'cb2');
        });

        it("should distribute items automatically with missing columns config", function() {
            makeGroup({
                columns: 'auto',
                items: [
                    { id: 'cb0', boxLabel: 'cb0' },
                    { id: 'cb1', boxLabel: 'cb1' }
                ]
            });

            var columns = checkboxGroup.layout.rowNodes[0].childNodes;

            // ensure removal works with both original and dynamically added items
            checkboxGroup.add(
                createCheckbox('cb2'),
                createCheckbox('cb3')
            );

            checkboxGroup.remove(Ext.getCmp('cb0'));
            checkboxGroup.remove(Ext.getCmp('cb3'));

            expect(columns.length).toBe(2);
            expectColumnToContainExactly(columns[0], 'cb1');
            expectColumnToContainExactly(columns[1], 'cb2');
        });

        it("should distribute items horizontally", function() {
            makeGroup({
                columns: 3,
                items: [
                    { id: 'cb0', boxLabel: 'cb0' },
                    { id: 'cb1', boxLabel: 'cb1' }
                ]
            });

            var rows = checkboxGroup.layout.tBodyNode.childNodes;

            // ensure removal works with both original and dynamically added items
            checkboxGroup.add(
                createCheckbox('cb2'),
                createCheckbox('cb3'),
                createCheckbox('cb4')
            );

            checkboxGroup.remove(Ext.getCmp('cb0'));
            checkboxGroup.remove(Ext.getCmp('cb2'));
            checkboxGroup.remove(Ext.getCmp('cb4'));

            expect(rows.length).toBe(1);
            expectRowToContain(rows[0], 'cb1', 'cb3', null);
        });

        it("should distribute items vertically", function() {
            makeGroup({
                columns: 3,
                vertical: true,
                items: [
                    { id: 'cb0', boxLabel: 'cb0' },
                    { id: 'cb1', boxLabel: 'cb1' }
                ]
            });

            var columns = checkboxGroup.layout.rowNodes[0].childNodes;

            // ensure removal works with both original and dynamically added items
            checkboxGroup.add(
                createCheckbox('cb2'),
                createCheckbox('cb3'),
                createCheckbox('cb4')
            );

            checkboxGroup.remove(Ext.getCmp('cb0'));
            checkboxGroup.remove(Ext.getCmp('cb2'));
            checkboxGroup.remove(Ext.getCmp('cb4'));

            expect(columns.length).toBe(3);
            expectColumnToContainExactly(columns[0], 'cb1');
            expectColumnToContainExactly(columns[1], 'cb3');
            expectColumnToContainExactly(columns[2]);
        });

        it("should distribute items vertically with only one column", function() {
            makeGroup({
                columns: 1,
                items: [
                    { id: 'cb0', boxLabel: 'cb0' },
                    { id: 'cb1', boxLabel: 'cb1' }
                ]
            });

            var columns = checkboxGroup.layout.rowNodes[0].childNodes;

            // ensure removal works with both original and dynamically added items
            checkboxGroup.add(
                createCheckbox('cb2'),
                createCheckbox('cb3'),
                createCheckbox('cb4')
            );

            checkboxGroup.remove(Ext.getCmp('cb0'));
            checkboxGroup.remove(Ext.getCmp('cb2'));
            checkboxGroup.remove(Ext.getCmp('cb4'));

            expect(columns.length).toBe(1);
            expectColumnToContainExactly(columns[0], 'cb1', 'cb3');
        });
    });
});
