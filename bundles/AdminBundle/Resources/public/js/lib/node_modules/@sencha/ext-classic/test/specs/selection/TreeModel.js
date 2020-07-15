topSuite("Ext.selection.TreeModel", ['Ext.tree.Panel', 'Ext.grid.Panel'], function() {
    var tree, data, selModel, col;

    function makeTree(cfg, root) {
        tree = new Ext.tree.Panel({
            width: 800,
            height: 600,
            renderTo: Ext.getBody(),
            root: root || data,
            animate: false,
            selModel: Ext.apply(cfg || {}, {
                type: 'treemodel'
            })
        });
        selModel = tree.getSelectionModel();
        col = tree.down('treecolumn');
    }

    beforeEach(function() {
        data = {
            id: 'root',
            expanded: true,
            children: [{
                id: 'node1',
                expanded: true,
                children: [{
                    id: 'node1_1',
                    leaf: true
                }, {
                    id: 'node1_2',
                    leaf: true
                }]
            }, {
                id: 'node2',
                expanded: true,
                children: [{
                    id: 'node2_1',
                    leaf: true
                }, {
                    id: 'node2_2',
                    leaf: true
                }]
            }, {
                id: 'node3',
                expanded: true,
                children: [{
                    id: 'node3_1',
                    leaf: true
                }, {
                    id: 'node3_2',
                    leaf: true
                }]
            }]
        };
    });

    afterEach(function() {
        Ext.destroy(tree);
        col = selModel = data = tree = null;
    });

    function byId(id) {
        return tree.getStore().getNodeById(id);
    }

    describe('locking treegrid', function() {
        // https://sencha.jira.com/browse/EXTJS-16149
        it('should not veto a navigation event when using a locking treegrid', function() {
            var row = 0;

            tree = new Ext.tree.Panel({
                width: 800,
                height: 600,
                renderTo: Ext.getBody(),
                root: data,
                columns: [{
                    xtype: 'treecolumn', // this is so we know which column will show the tree
                    text: 'Text',
                    width: 200,
                    sortable: true,
                    dataIndex: 'id',
                    locked: true
                }, {
                    renderer: function() {
                        return String(++row);
                    }
                }],
                animate: false,
                selModel: {
                    type: 'treemodel'
                }
            });
            selModel = tree.getSelectionModel();
            jasmine.fireMouseEvent(tree.view.lockedView.getCellByPosition({ row: 1, column: 0 }, true), 'click');
            expect(selModel.isSelected(1)).toBe(true);
        });
    });

    describe("deselecting on removal", function() {
        it("should deselect when the selected node is removed", function() {
            makeTree();
            var node = byId('node3_2');

            selModel.select(node);
            node.remove();
            expect(selModel.isSelected(node)).toBe(false);
        });

        it("should deselect when the selected node is a child of the removed node", function() {
            makeTree();
            var node = byId('node2_1');

            selModel.select(node);
            node.parentNode.remove();
            expect(selModel.isSelected(node)).toBe(false);
        });

        it("should remove collapsed children", function() {
            makeTree();
            var node = byId('node2_1');

            selModel.select(node);
            node.parentNode.collapse();
            node.parentNode.remove();
            expect(selModel.isSelected(node)).toBe(false);
        });

        it("should deselect a deep child of the removed node", function() {
            makeTree(null, {
                expanded: true,
                children: [{
                    id: 'node1',
                    expanded: true,
                    children: [{
                        id: 'node2',
                        expanded: true,
                        children: [{
                            id: 'node3',
                            expanded: true,
                            children: [{
                                id: 'node4',
                                expanded: true,
                                children: [{
                                    id: 'node5'
                                }]
                            }]
                        }]
                    }]
                }]
            });
            var node = byId('node5');

            selModel.select(node);
            byId('node1').remove();
            expect(selModel.isSelected(node)).toBe(false);
        });

        it("should remove the children of a node that is not a direct child of the root", function() {
            makeTree(null, {
                expanded: true,
                children: [{
                    id: 'node1',
                    expanded: true,
                    children: [{
                        id: 'node2',
                        expanded: true,
                        children: [{
                            id: 'node3',
                            expanded: true,
                            children: [{
                                id: 'node4',
                                expanded: true,
                                children: [{
                                    id: 'node5'
                                }]
                            }]
                        }]
                    }]
                }]
            });
            var node = byId('node4');

            selModel.select(node);
            byId('node2').remove();
            expect(selModel.isSelected(node)).toBe(false);
        });

        it("should remove all children at various depths", function() {
            makeTree(null, {
                expanded: true,
                children: [{
                    id: 'node1',
                    expanded: true,
                    children: [{
                        id: 'node2'
                    }, {
                        id: 'node3',
                        expanded: true,
                        children: [{
                            id: 'node4',
                            expanded: true,
                            children: [{
                                id: 'node5'
                            }]
                        }, {
                            id: 'node6'
                        }]
                    }]
                }]
            });

            var node2 = byId('node2'),
                node5 = byId('node5'),
                node6 = byId('node6');

            selModel.select([node2, node5, node6]);
            byId('node1').remove();
            expect(selModel.isSelected(node2)).toBe(false);
            expect(selModel.isSelected(node5)).toBe(false);
            expect(selModel.isSelected(node6)).toBe(false);
        });
    });

    describe("selectOnExpanderClick", function() {
        function click(el) {
            jasmine.fireMouseEvent(el, 'click');
        }

        describe("with selectOnExpanderClick: false", function() {
            var node, row, view;

            beforeEach(function() {
                makeTree({
                    selectOnExpanderClick: false
                });
                view = tree.getView();
                node = byId('node1');
                row = view.getRow(node);
            });

            afterEach(function() {
                row = view = node = null;
            });

            it("should not select when clicking on the expander", function() {
                click(row.querySelector(view.expanderSelector));
                expect(selModel.isSelected(node)).toBe(false);
            });

            it("should select when clicking on another part of the row", function() {
                click(row.querySelector('.' + col.iconCls));
                expect(selModel.isSelected(node)).toBe(true);
            });
        });

        describe("with selectOnExpanderClick: true", function() {
            var node, row, view;

            beforeEach(function() {
                makeTree({
                    selectOnExpanderClick: true
                });
                view = tree.getView();
                node = byId('node1');
                row = view.getRow(node);
            });

            afterEach(function() {
                row = view = node = null;
            });

            it("should select when clicking on the expander", function() {
                click(row.querySelector(view.expanderSelector));
                expect(selModel.isSelected(node)).toBe(true);
            });

            it("should select when clicking on another part of the row", function() {
                click(row.querySelector('.' + col.iconCls));
                expect(selModel.isSelected(node)).toBe(true);
            });
        });
    });

});
