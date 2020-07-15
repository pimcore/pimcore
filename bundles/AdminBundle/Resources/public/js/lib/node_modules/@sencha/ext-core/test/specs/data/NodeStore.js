topSuite("Ext.data.NodeStore", function() {
    var Model = Ext.define(null, {
        extend: 'Ext.data.TreeModel'
    });

    var store;

    function makeNode(id, leaf, children) {
        var node = new Model({
            id: id,
            leaf: !!leaf
        });

        if (children) {
            Ext.Array.forEach(children, function(child) {
                node.appendChild(child);
            });
        }

        return node;
    }

    function makeStore(cfg, node) {
        if (!node && node !== null) {
            node = makeNode('root');
        }

        store = new Ext.data.NodeStore(Ext.apply({
            model: Model,
            node: node
        }, cfg));
    }

    afterEach(function() {
        store = Ext.destroy(store);
    });

    function expectIds(expected) {
        var ids = Ext.Array.pluck(store.getRange(), 'id');

        expect(ids).toEqual(expected);
    }

    describe("folderSort", function() {
        var node;

        beforeEach(function() {
            node = makeNode('root', false, [
                makeNode(2, true),
                makeNode(6),
                makeNode(1, true),
                makeNode(5),
                makeNode(4, true),
                makeNode(3)
            ]);
        });

        afterEach(function() {
            node = null;
        });

        describe("with no sorters", function() {
            describe("configuration", function() {
                describe("with folderSort: true", function() {
                    it("should sort nodes", function() {
                        makeStore({
                            folderSort: true
                        }, node);

                        expectIds([6, 5, 3, 2, 1, 4]);
                    });
                });

                describe("with folderSort: false", function() {
                    it("should leave nodes in place", function() {
                        makeStore({
                            folderSort: false
                        }, node);

                        expectIds([2, 6, 1, 5, 4, 3]);
                    });
                });
            });

            describe("setting dynamically", function() {
                describe("setting folderSort: true", function() {
                    it("should sort nodes", function() {
                        makeStore({
                            folderSort: false
                        }, node);
                        store.setFolderSort(true);

                        expectIds([6, 5, 3, 2, 1, 4]);
                    });
                });

                describe("setting folderSort: false", function() {
                    it("should leave nodes in place", function() {
                        makeStore({
                            folderSort: true
                        }, node);
                        store.setFolderSort(false);

                        expectIds([6, 5, 3, 2, 1, 4]);
                    });
                });
            });
        });

        describe("with sorters", function() {
            describe("configuration", function() {
                describe("with folderSort: true", function() {
                    it("should sort and give priority to folderSort", function() {
                        makeStore({
                            folderSort: true,
                            sorters: [{
                                property: 'id',
                                direction: 'DESC'
                            }]
                        }, node);

                        expectIds([6, 5, 3, 4, 2, 1]);
                    });
                });

                describe("with folderSort: false", function() {
                    it("should sort according to the sorter", function() {
                        makeStore({
                            folderSort: false,
                            sorters: [{
                                property: 'id',
                                direction: 'DESC'
                            }]
                        }, node);

                        expectIds([6, 5, 4, 3, 2, 1]);
                    });
                });
            });

            describe("setting sorters dynamically", function() {
                describe("with folderSort: true", function() {
                    beforeEach(function() {
                        makeStore({
                            folderSort: true
                        }, node);

                        store.getSorters().add({
                            property: 'id',
                            direction: 'DESC'
                        });
                    });

                    describe("adding a sorter", function() {
                        it("should sort and give priority to folderSort", function() {
                            expectIds([6, 5, 3, 4, 2, 1]);
                        });
                    });

                    describe("removing a sorter", function() {
                        it("should leave nodes in place", function() {
                            store.getSorters().removeAll();
                            expectIds([6, 5, 3, 4, 2, 1]);
                        });
                    });
                });

                describe("with folderSort: false", function() {
                    beforeEach(function() {
                        makeStore({
                            folderSort: false
                        }, node);

                        store.getSorters().add({
                            property: 'id',
                            direction: 'DESC'
                        });
                    });

                    describe("adding a sorter", function() {
                        it("should sort nodes", function() {
                            expectIds([6, 5, 4, 3, 2, 1]);
                        });
                    });

                    describe("removing a sorter", function() {
                        it("should leave nodes in place", function() {
                            store.getSorters().removeAll();
                            expectIds([6, 5, 4, 3, 2, 1]);
                        });
                    });
                });
            });

            describe("setting folderSort dynamically", function() {
                describe("setting folderSort: true", function() {
                    it("should sort and give priority to folderSort", function() {
                        makeStore({
                            folderSort: false,
                            sorters: [{
                                property: 'id',
                                direction: 'DESC'
                            }]
                        }, node);
                        store.setFolderSort(true);

                        expectIds([6, 5, 3, 4, 2, 1]);
                    });
                });

                describe("setting folderSort: false", function() {
                    it("should sort nodes", function() {
                        makeStore({
                            folderSort: false,
                            sorters: [{
                                property: 'id',
                                direction: 'DESC'
                            }]
                        }, node);
                        store.setFolderSort(false);
                        expectIds([6, 5, 4, 3, 2, 1]);
                    });
                });
            });
        });
    });

    describe("node", function() {
        it("should accept an object", function() {
            makeStore({
                node: {
                    id: 'foo'
                }
            }, null);
            var node = store.getNode();

            expect(node.isNode).toBe(true);
            expect(node.id).toBe('foo');
        });

        it("should accept a node instance", function() {
            var node = makeNode();

            makeStore({
                node: node
            });
            expect(store.getNode()).toBe(node);
        });
    });

    describe("store content", function() {
        describe("configuring with a node", function() {
            it("should load node children", function() {
                var node = makeNode('root', false, [
                    makeNode(1),
                    makeNode(2),
                    makeNode(3)
                ]);

                makeStore(null, node);

                expectIds([1, 2, 3]);
            });

            it("should only include children of the node", function() {
                var node = makeNode('root', false, [
                    makeNode(1, false, [
                        makeNode(2),
                        makeNode(3)
                    ]),
                    makeNode(4, false, [
                        makeNode(5),
                        makeNode(6)
                    ]),
                    makeNode(7, false, [
                        makeNode(8),
                        makeNode(9)
                    ])
                ]);

                makeStore(null, node);

                expectIds([1, 4, 7]);
            });
        });

        describe("dynamic updates", function() {
            it("should add a child that is appended to the node", function() {
                makeStore();
                var node = makeNode();

                store.getNode().appendChild(node);
                expect(store.getCount()).toBe(1);
                expect(store.getAt(0)).toBe(node);
            });

            it("should insert a child that is inserted into the node", function() {
                var existing = makeNode(1);

                var node = makeNode('root', false, [
                    existing
                ]);

                var newNode = makeNode(2);

                makeStore(null, node);
                node.insertChild(0, newNode);
                expect(store.getCount()).toBe(2);
                expect(store.getAt(0)).toBe(newNode);
                expect(store.getAt(1)).toBe(existing);
            });

            it("should remove a child removed from the node", function() {
                var existing = makeNode(1);

                var node = makeNode('root', false, [
                    existing
                ]);

                makeStore(null, node);
                node.removeChild(existing);
                expect(store.getCount()).toBe(0);
            });

            it("should not add granchildren of the node", function() {
                var existing = makeNode(1);

                var node = makeNode('root', false, [
                    existing
                ]);

                makeStore(null, node);

                existing.appendChild({});
                expect(store.getCount()).toBe(1);
            });

            it("should not cause an error when removing grandchildren of the node", function() {
                var grandchild = makeNode(2);

                var existing = makeNode(1, false, [grandchild]);

                var node = makeNode('root', false, [
                    existing
                ]);

                makeStore(null, node);

                expect(function() {
                    existing.removeChild(grandchild);
                }).not.toThrow();
            });
        });
    });

});
