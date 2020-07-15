topSuite("Ext.tree.Column", ['Ext.tree.Panel'], function() {
    var tree, colRef;

    function makeTree(columns, config) {
        if (!config) {
            config = {};
        }

        if (!config.root) {
            config.root = {
                text: 'Foo'
            };
        }

        tree = new Ext.tree.Panel(Ext.apply({
            renderTo: Ext.getBody(),
            width: 600,
            height: 300,
            store: {
                autoDestroy: true,
                root: config.root
            },
            columns: columns
        }, config));
        colRef = tree.getColumnManager().getColumns();
    }

    afterEach(function() {
        tree = Ext.destroy(tree);
    });

    it("should retain scope when assigned before calling parent initComponent & subclassing", function() {
        var spy = jasmine.createSpy(),
            o = {};

        Ext.define('spec.Foo', {
            extend: 'Ext.tree.Column',
            alias: 'widget.spectreecolumn',

            initComponent: function() {
                this.scope = o;
                this.callParent();
            }
        });

        makeTree([{
            xtype: 'spectreecolumn',
            renderer: spy
        }]);

        expect(spy.callCount).toBe(1);
        expect(spy.mostRecentCall.object).toBe(o);

        Ext.undefine('spec.Foo');
    });

    it("should be able to use a delayed customValue", function() {
        makeTree([{
            xtype: 'treecolumn',
            renderer: 'custom',
            custom: function(v, m, r) {
                return r.get('foo');
            }
        }], {
            root: {
                foo: 'bar'
            }
        });

        waits(500);

        runs(function() {
            var cell;

            tree.getStore().getRoot().set('foo', 'baz');
            cell = tree.getView().getCell(0, 0);

            expect(cell.querySelector('.x-tree-node-text').innerHTML).toBe('baz');
        });
    });
});
