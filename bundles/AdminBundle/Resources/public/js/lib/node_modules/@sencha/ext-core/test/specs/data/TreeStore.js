topSuite("Ext.data.TreeStore", function() {
    var store,
        root,
        loadStore,
        dummyData,
        NodeModel = Ext.define(null, {
            extend: 'Ext.data.TreeModel',
            fields: ['name'],
            proxy: {
                type: 'ajax',
                url: 'foo.json',
                reader: {
                    type: 'json'
                }
            }
        }),
        TaskModel = Ext.define(null, {
            extend: 'Ext.data.TreeModel',
            idProperty: 'id',
            fields: [
                { name: 'id',       type: 'int', allowNull: true },
                { name: 'task',     type: 'string' },
                { name: 'duration', type: 'string' }
            ]
        });

    function expandify(nodes) {
        if (Ext.isNumber(nodes[0])) {
            nodes = Ext.Array.map(nodes, function(id) {
                return {
                    id: id,
                    leaf: true
                };
            });
        }

        Ext.Array.forEach(nodes, function(node) {
            if (node.children || node.leaf === false) {
                node.expanded = true;

                if (node.children) {
                    node.children = expandify(node.children);
                }
                else {
                    node.children = [];
                }
            }
            else {
                node.leaf = true;
            }
        });

        return nodes;
    }

    function makeStore(nodes, cfg) {
        store = new Ext.data.TreeStore(Ext.apply({
            asynchronousLoad: false,
            root: {
                expanded: true,
                children: expandify(nodes)
            }
        }, cfg));
        root = store.getRootNode();
    }

    function expectOrder(parent, ids) {
        var childNodes = parent.childNodes,
            i, len;

        expect((childNodes || []).length).toBe(ids.length);

        if (childNodes) {
            for (i = 0, len = childNodes.length; i < len; ++i) {
                expect(childNodes[i].id).toBe(ids[i]);
            }
        }
    }

    beforeEach(function() {
        dummyData = {
            success: true,
            children: [{
                id: 1,
                name: "aaa"
            }, {
                id: 2,
                name: "bbb",
                children: [{
                    id: 3,
                    name: "ccc"
                }, {
                    id: 4,
                    name: "ddd",
                    children: [{
                        id: 5,
                        name: "eee",
                        leaf: true
                    }]
                }]
            }, {
                id: 6,
                name: "fff",
                children: [{ id: 7,
                    name: "ggg"
                }]
            }]
        };

        MockAjaxManager.addMethods();

        loadStore = function(store, options) {
            store.load(options);
            completeWithData(dummyData);
        };

    });

    afterEach(function() {
        store = Ext.destroy(store);
        MockAjaxManager.removeMethods();
    });

    function completeWithData(data) {
        Ext.Ajax.mockComplete({
            status: 200,
            responseText: Ext.encode(data)
        });
    }

    function completeWithFailure() {
        Ext.Ajax.mockComplete({
            status: 200,
            responseText: Ext.encode({
                success: false
            })
        });
    }

    function byId(id) {
        return store.getNodeById(id);
    }

    describe('NodeInterface#removeAll', function() {
        // Test https://sencha.jira.com/browse/EXTJS-20023
        it('should remove all descendant nodes from node lookup map', function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                proxy: {
                    type: 'memory'
                },
                root: {
                    id: 0,
                    name: 'Root Node',
                    autoLoad: true,
                    children: dummyData.children
                }
            });
            store.getRootNode().expand(true);
            expect(Ext.Object.getKeys(store.byIdMap).length).toBe(8);
            store.getRootNode().removeAll();

            // All descendant nodes must have gone from the node map.
            // Only the root must remain.
            expect(Ext.Object.getKeys(store.byIdMap).length).toBe(1);
            expect(Ext.Object.getValues(store.byIdMap)[0]).toBe(store.getRootNode());

            // All removed records should be represented in the removed records list.
            expect(store.getRemovedRecords().length).toBe(7);
        });
    });

    describe("expand node when children not set", function() {
        it("should expand node without any error if filter function is applied", function() {
            var node;

            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    id: 0,
                    name: 'Root Node',
                    children: [{
                        text: 'Leaf Node',
                        leaf: true
                    }, {
                        text: 'node with chlidren not set',
                        expanded: false
                    }, {
                        text: 'node with children set',
                        expanded: false,
                        children: []
                    }]
                },
                filters: function(rec) {
                    if (rec.get('text').indexOf('.txt') > -1) {
                        return false;
                    }

                    return true;
                }
            });

            node = store.getAt(1);

            expect(function() {
                node.expand();
            }).not.toThrow();

            expect(node.isExpanded()).toBe(true);

            node = store.getAt(2);

            expect(function() {
                node.expand();
            }).not.toThrow();

            expect(node.isExpanded()).toBe(true);
        });
    });

    describe('reload of a TreeStore after a node load', function() {
        it('should pass the root\'s id', function() {
            var lastLoadedId;

            store = new Ext.data.TreeStore({
                model: NodeModel,
                asynchronousLoad: false,
                root: {
                    expanded: true,
                    id: 'root-id'
                },
                listeners: {
                    beforeload: function(store, operation) {
                        lastLoadedId = operation.getId();
                    }
                }
            });

            expect(lastLoadedId).toBe('root-id');
            completeWithData([{
                id: '1',
                name: 'Node 1'
            }, {
                id: '2',
                name: 'Node 2'
            }]);
            store.getNodeById(1).expand();

            // Expanding node id 1 will put id:'1' in the operation
            expect(lastLoadedId).toBe('1');
            completeWithData([{
                id: '1.1',
                name: 'Node 1.1'
            }, {
                id: '1.2',
                name: 'Node 1.2'
            }]);
            store.reload();
            // Reloading will put id:'root-id' in the operation
            expect(lastLoadedId).toBe('root-id');
        });
    });

    describe('Aqcuiring a Proxy', function() {
        it("should use the configured Model's Proxy by default", function() {
            var usedProxy;

            store = new Ext.data.TreeStore({
                model: NodeModel,
                listeners: {
                    beforeload: function(store, operation) {
                        usedProxy = operation.getProxy();

                        return false;
                    }
                }
            });
            store.load();

            // The store should use the proxy from the model
            expect(store.getProxy()).toBe(NodeModel.getProxy());
            expect(usedProxy).toBe(NodeModel.getProxy());
        });
        it("should use its own Proxy Proxy if it is configured with one", function() {
            var storeProxy = new Ext.data.proxy.Ajax({
                    url: '/foo'
                }),
                usedProxy;

            store = new Ext.data.TreeStore({
                model: NodeModel,
                proxy: storeProxy,
                listeners: {
                    beforeload: function(store, operation) {
                        usedProxy = operation.getProxy();

                        return false;
                    }
                }
            });
            store.load();

            // The store should use the proxy it was configured with.
            expect(store.getProxy()).toBe(storeProxy);
            expect(usedProxy).toBe(storeProxy);
        });
    });

    describe('success: false in return packet', function() {
        // Set to bug condition to ensure event fires as expected.
        var wasSuccessful = true;

        it("should fire the load event with the success parameter false", function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true
                },
                listeners: {
                    load: function(store, records, successful, operation, node) {
                        wasSuccessful = successful;
                    }
                }
            });
            completeWithFailure();
            expect(wasSuccessful).toBe(false);
        });
    });

    describe("the model", function() {
        it("should be able to use a non TreeModel", function() {
            var Model = Ext.define(null, {
                extend: 'Ext.data.Model',
                fields: ['foo']
            });

            // Important that the proxy gets applied first here
            store = new Ext.data.TreeStore({
                proxy: {
                    type: 'ajax',
                    url: 'fake'
                },
                model: Model
            });
            expect(store.getModel()).toBe(Model);
            expect(Model.prototype.isNode).toBe(true);
        });

        describe("using an implicit model", function() {
            it("should use the model's memory proxy when no proxy is defined on the store", function() {
                store = new Ext.data.TreeStore({
                    fields: ['id', 'height', 'width']
                });
                expect(store.getProxy().isMemoryProxy).toBe(true);
                expect(store.getProxy()).toBe(store.getModel().getProxy());
            });

            it("should set the store's proxy on the model", function() {
                store = new Ext.data.TreeStore({
                    fields: ['id', 'height', 'width'],
                    proxy: {
                        type: 'ajax',
                        url: 'foo'
                    }
                });
                expect(store.getProxy().isAjaxProxy).toBe(true);
                expect(store.getProxy().url).toBe('foo');
                expect(store.getProxy()).toBe(store.getModel().getProxy());
            });

            it("should have the model set on the proxy & the reader", function() {
                store = new Ext.data.TreeStore({
                    fields: ['id', 'height', 'width'],
                    proxy: {
                        type: 'ajax',
                        url: 'foo'
                    }
                });
                expect(store.getProxy().getModel()).toBe(store.getModel());
                expect(store.getProxy().getReader().getModel()).toBe(store.getModel());
            });

            it("should extend Ext.data.Model", function() {
                store = new Ext.data.TreeStore({
                    fields: ['id', 'height', 'width']
                });
                expect(store.getModel().superclass.self).toBe(Ext.data.TreeModel);
            });
        });
    });

    describe("grouping", function() {
        it("should always be ungroupable", function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    children: [{
                        id: 'l1',
                        leaf: true,
                        age: 20
                    }, {
                        id: 'f1',
                        age: 30
                    }, {
                        id: 'l2',
                        leaf: true,
                        age: 20
                    }, {
                        id: 'f2',
                        age: 30
                    }]
                }
            });
            expect(function() {
                store.setGrouper('age');
            }).toThrow();
            expect(store.getGrouper()).toBeNull();
            store.setGroupField('age');
            expect(store.getGroupField()).toBe('');
            store.setGroupDir('DESC');
            expect(store.getGroupDir()).toBeNull();
        });
    });

    describe("sorting", function() {
        function expectStoreOrder(ids) {
            var len = ids.length,
                i;

            expect(store.getCount()).toBe(len);

            for (i = 0; i < len; ++i) {
                expect(store.getAt(i).id).toBe(ids[i]);
            }

        }

        describe("with local data", function() {
            describe("with folderSort: true", function() {
                it("should sort when setting folderSort dynamically", function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        root: {
                            expanded: true,
                            children: [{
                                id: 'l1',
                                leaf: true
                            }, {
                                id: 'f1'
                            }, {
                                id: 'l2',
                                leaf: true
                            }, {
                                id: 'f2'
                            }]
                        }
                    });
                    store.setFolderSort(true);
                    expectOrder(store.getRoot(), ['f1', 'f2', 'l1', 'l2']);
                });

                it("should leave the original sort order if there are no other sorters", function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        folderSort: true,
                        root: {
                            expanded: true,
                            children: [{
                                id: 'l3',
                                leaf: true
                            }, {
                                id: 'l2',
                                leaf: true
                            }, {
                                id: 'f3'
                            }, {
                                id: 'l1',
                                leaf: true
                            }, {
                                id: 'f2'
                            }, {
                                id: 'f1'
                            }]
                        }
                    });
                    expectOrder(store.getRoot(), ['f3', 'f2', 'f1', 'l3', 'l2', 'l1']);
                });

                it("should do a deep sort", function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        folderSort: true,
                        root: {
                            expanded: true,
                            children: [{
                                id: 'p1',
                                children: [{
                                    id: 'l1',
                                    leaf: true
                                }, {
                                    id: 'f1'
                                }]
                            }, {
                                id: 'p2',
                                children: [{
                                    id: 'l2',
                                    leaf: true
                                }, {
                                    id: 'f2'
                                }]
                            }]
                        }
                    });
                    expectOrder(byId('p1'), ['f1', 'l1']);
                    expectOrder(byId('p2'), ['f2', 'l2']);
                });

                it("should sort folder/non folder groups by any additional sorters", function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        folderSort: true,
                        sorters: ['id'],
                        root: {
                            expanded: true,
                            children: [{
                                id: 'f4'
                            }, {
                                id: 'l3'
                            }, {
                                id: 'f1'
                            }, {
                                id: 'l1'
                            }, {
                                id: 'l2'
                            }, {
                                id: 'f3'
                            }, {
                                id: 'l4'
                            }, {
                                id: 'f2'
                            }]
                        }
                    });
                    expectOrder(store.getRoot(), ['f1', 'f2', 'f3', 'f4', 'l1', 'l2', 'l3', 'l4']);
                });
            });

            describe("with folderSort: false", function() {
                it("should sort by existing sorters when setting folderSort: false", function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        folderSort: false,
                        sorters: ['id'],
                        root: {
                            expanded: true,
                            children: [{
                                id: 'a',
                                leaf: true
                            }, {
                                id: 'b'
                            }, {
                                id: 'c',
                                leaf: true
                            }, {
                                id: 'd'
                            }]
                        }
                    });
                    store.setFolderSort(false);
                    expectOrder(store.getRoot(), ['a', 'b', 'c', 'd']);
                });

                it("should do a deep sort", function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        folderSort: false,
                        sorters: ['id'],
                        root: {
                            expanded: true,
                            children: [{
                                id: 'p1',
                                expanded: true,
                                children: [{
                                    id: 'b',
                                    leaf: true
                                }, {
                                    id: 'c',
                                    leaf: true
                                }, {
                                    id: 'a',
                                    leaf: true
                                }, {
                                    id: 'd',
                                    leaf: true
                                }]

                            }, {
                                id: 'p2',
                                expanded: true,
                                children: [{
                                    id: 'g',
                                    leaf: true
                                }, {
                                    id: 'e',
                                    leaf: true
                                }, {
                                    id: 'h',
                                    leaf: true
                                }, {
                                    id: 'f',
                                    leaf: true
                                }]
                            }]
                        }
                    });
                    store.setFolderSort(false);
                    expectOrder(byId('p1'), ['a', 'b', 'c', 'd']);
                    expectOrder(byId('p2'), ['e', 'f', 'g', 'h']);
                });
            });
        });

        describe("with remote data", function() {
            describe("with folderSort: true", function() {
                it("should sort when setting folderSort dynamically", function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        root: {
                            expanded: true
                        }
                    });
                    completeWithData([{
                        id: 'l1',
                        leaf: true
                    }, {
                        id: 'f1'
                    }, {
                        id: 'l2',
                        leaf: true
                    }, {
                        id: 'f2'
                    }]);
                    store.setFolderSort(true);
                    expectOrder(store.getRoot(), ['f1', 'f2', 'l1', 'l2']);
                });

                it("should leave the original sort order if there are no other sorters", function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        folderSort: true,
                        root: {
                            expanded: true
                        }
                    });
                    completeWithData([{
                        id: 'l3',
                        leaf: true
                    }, {
                        id: 'l2',
                        leaf: true
                    }, {
                        id: 'f3'
                    }, {
                        id: 'l1',
                        leaf: true
                    }, {
                        id: 'f2'
                    }, {
                        id: 'f1'
                    }]);
                    expectOrder(store.getRoot(), ['f3', 'f2', 'f1', 'l3', 'l2', 'l1']);
                });

                it("should do a deep sort", function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        folderSort: true,
                        root: {
                            expanded: true
                        }
                    });
                    completeWithData([{
                        id: 'p1',
                        children: [{
                            id: 'l1',
                            leaf: true
                        }, {
                            id: 'f1'
                        }]
                    }, {
                        id: 'p2',
                        children: [{
                            id: 'l2',
                            leaf: true
                        }, {
                            id: 'f2'
                        }]
                    }]);
                    expectOrder(byId('p1'), ['f1', 'l1']);
                    expectOrder(byId('p2'), ['f2', 'l2']);
                });

                it("should sort folder/non folder groups by any additional sorters", function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        folderSort: true,
                        sorters: ['id'],
                        root: {
                            expanded: true
                        }
                    });
                    completeWithData([{
                        id: 'f4'
                    }, {
                        id: 'l3'
                    }, {
                        id: 'f1'
                    }, {
                        id: 'l1'
                    }, {
                        id: 'l2'
                    }, {
                        id: 'f3'
                    }, {
                        id: 'l4'
                    }, {
                        id: 'f2'
                    }]);
                    expectOrder(store.getRoot(), ['f1', 'f2', 'f3', 'f4', 'l1', 'l2', 'l3', 'l4']);
                });
            });

            describe("with folderSort: false", function() {
                it("should sort by existing sorters when setting folderSort: false", function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        folderSort: false,
                        sorters: ['id'],
                        root: {
                            expanded: true
                        }
                    });
                    completeWithData([{
                        id: 'a',
                        leaf: true
                    }, {
                        id: 'b'
                    }, {
                        id: 'c',
                        leaf: true
                    }, {
                        id: 'd'
                    }]);
                    store.setFolderSort(false);
                    expectOrder(store.getRoot(), ['a', 'b', 'c', 'd']);
                });

                it("should do a deep sort", function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        folderSort: false,
                        sorters: ['id'],
                        root: {
                            expanded: true
                        }
                    });
                    completeWithData([{
                        id: 'p1',
                        expanded: true,
                        children: [{
                            id: 'b',
                            leaf: true
                        }, {
                            id: 'c',
                            leaf: true
                        }, {
                            id: 'a',
                            leaf: true
                        }, {
                            id: 'd',
                            leaf: true
                        }]

                    }, {
                        id: 'p2',
                        expanded: true,
                        children: [{
                            id: 'g',
                            leaf: true
                        }, {
                            id: 'e',
                            leaf: true
                        }, {
                            id: 'h',
                            leaf: true
                        }, {
                            id: 'f',
                            leaf: true
                        }]
                    }]);
                    store.setFolderSort(false);
                    expectOrder(byId('p1'), ['a', 'b', 'c', 'd']);
                    expectOrder(byId('p2'), ['e', 'f', 'g', 'h']);
                });
            });
        });

        describe("adding/expanding nodes", function() {
            it("should sort nodes correctly on expand", function() {
                store = new Ext.data.TreeStore({
                    model: NodeModel,
                    sorters: ['id'],
                    root: {
                        expanded: true,
                        children: [{
                            id: 'a',
                            children: [{
                                id: 'z'
                            }, {
                                id: 'y'
                            }]
                        }, {
                            id: 'b',
                            children: [{
                                id: 'x'
                            }, {
                                id: 'w'
                            }]
                        }, {
                            id: 'c',
                            children: [{
                                id: 'v'
                            }, {
                                id: 'u'
                            }]
                        }]
                    }
                });

                byId('a').expand();
                expectOrder(byId('a'), ['y', 'z']);
                expectStoreOrder(['a', 'y', 'z', 'b', 'c']);

                byId('b').expand();
                expectOrder(byId('b'), ['w', 'x']);
                expectStoreOrder(['a', 'y', 'z', 'b', 'w', 'x', 'c']);

                byId('c').expand();
                expectOrder(byId('c'), ['u', 'v']);
                expectStoreOrder(['a', 'y', 'z', 'b', 'w', 'x', 'c', 'u', 'v']);
            });

            it("should sort nodes correctly on add", function() {
                store = new Ext.data.TreeStore({
                    model: NodeModel,
                    sorters: ['id'],
                    root: {
                        expanded: true,
                        children: [{
                            id: 'a',
                            expanded: true,
                            children: []
                        }, {
                            id: 'b',
                            expanded: true,
                            children: []
                        }, {
                            id: 'c',
                            expanded: true,
                            children: []
                        }]
                    }
                });

                byId('a').appendChild([{
                    id: 'y'
                }, {
                    id: 'z'
                }]);
                expectOrder(byId('a'), ['y', 'z']);
                expectStoreOrder(['a', 'y', 'z', 'b', 'c']);

                byId('b').appendChild([{
                    id: 'w'
                }, {
                    id: 'x'
                }]);
                expectOrder(byId('b'), ['w', 'x']);
                expectStoreOrder(['a', 'y', 'z', 'b', 'w', 'x', 'c']);

                byId('c').appendChild([{
                    id: 'u'
                }, {
                    id: 'v'
                }]);
                expectOrder(byId('c'), ['u', 'v']);
                expectStoreOrder(['a', 'y', 'z', 'b', 'w', 'x', 'c', 'u', 'v']);

            });
        });
    });

    describe("getNodeById", function() {
        it("should return null if there is no matching id", function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    text: 'Root'
                }
            });
            expect(store.getNodeById('foo')).toBeNull();
        });

        it("should be able to return the root", function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    id: 'root'
                }
            });
            expect(store.getNodeById('root')).toBe(store.getRoot());
        });

        it("should be able to return a deep node", function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    children: [{
                        expanded: true,
                        children: [{
                            expanded: true,
                            children: [{
                                expanded: true,
                                children: [{
                                    id: 'deep'
                                }]
                            }]
                        }]
                    }]
                }
            });

            var idNode;

            store.getRoot().cascade(function(node) {
                if (node.id === 'deep') {
                    idNode = node;
                }
            });

            expect(store.getNodeById('deep')).toBe(idNode);
        });

        it('should be usable during nodeappend event', function() {
            var ids = [];

            store = new Ext.data.TreeStore({
                model: NodeModel,
                listeners: {
                    nodeappend: function(parent, child, index) {
                        ids.push(child.id);
                        var treeStore = child.getTreeStore();

                        var c = treeStore.getNodeById(child.id);

                        // easy to read output:
                        expect(c && c.id).toBe(child.id);

                        // nearly useless output on failure (but not infinite expansion):
                        expect(c === child).toBe(true);
                    }
                },
                root: {
                    expanded: true,
                    id: 'root',
                    children: [{
                        id: 'child',
                        expanded: false,
                        children: [{
                            id: 'leaf'
                        }]
                    }]
                }
            });

            expect(ids.join(' ')).toBe('root child leaf');
        });

        it("should find loaded children of collapsed nodes", function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    children: [{
                        expanded: false,
                        children: [{
                            id: 'leaf'
                        }]
                    }]
                }
            });
            expect(store.getNodeById('leaf')).toBe(store.getRoot().firstChild.firstChild);
        });

        it("should find nodes that are filtered out", function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    children: [{
                        text: 'A'
                    }, {
                        text: 'A'
                    }, {
                        text: 'A'
                    }, {
                        id: 'bNode',
                        text: 'B'
                    }]
                }
            });
            expect(store.getCount()).toBe(4);
            store.filter('text', 'A');
            expect(store.getCount()).toBe(3);
            expect(store.getNodeById('bNode')).toBe(store.getRoot().lastChild);
        });
    });

    describe("loading data", function() {
        describe("isLoaded", function() {
            it("should be false by default", function() {
                store = new Ext.data.TreeStore({
                    root: {
                        text: 'Root'
                    }
                });
                expect(store.isLoaded()).toBe(false);
            });

            it("should be true after a load", function() {
                store = new Ext.data.TreeStore({
                    root: {
                        text: 'Root'
                    }
                });
                store.load();
                expect(store.isLoaded()).toBe(true);
            });
        });

        describe("when loading asynchronously from a url", function() {
           describe("if the root node is expanded", function() {
                it("should load the TreeStore automatically", function() {
                    spyOn(Ext.data.TreeStore.prototype, 'load').andCallThrough();

                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        asynchronousLoad: true,
                        root: {
                            expanded: true,
                            id: 0,
                            name: 'Root Node'
                        }
                    });

                    expect(store.load.callCount).toBe(1);
                });

                describe("with autoLoad: true", function() {
                    it("should not load twice with a root defined", function() {
                        spyOn(Ext.data.TreeStore.prototype, 'flushLoad').andCallThrough();

                        runs(function() {
                            store = Ext.create('Ext.data.TreeStore', {
                                model: NodeModel,
                                autoLoad: true,
                                asynchronousLoad: true,
                                root: {
                                    expanded: true,
                                    id: 0,
                                    name: 'Root Node'
                                }
                            });
                        });
                        // autoLoad runs on a timer, can't use waitsFor here
                        waits(10);
                        runs(function() {
                            expect(store.flushLoad.callCount).toBe(1);
                        });
                    });

                    it("should not load twice without a root defined", function() {
                        spyOn(Ext.data.TreeStore.prototype, 'flushLoad').andCallThrough();

                        runs(function() {
                            store = Ext.create('Ext.data.TreeStore', {
                                model: NodeModel,
                                autoLoad: true,
                                asynchronousLoad: true
                            });
                        });

                        // autoLoad runs on a timer, can't use waitsFor here
                        waits(10);
                        runs(function() {
                            expect(store.flushLoad.callCount).toBe(1);
                        });
                    });
                });
            });

            describe("if the root node is not expanded", function() {
                beforeEach(function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        autoLoad: false,
                        asynchronousLoad: true,
                        root: {
                            expanded: false,
                            id: 0,
                            name: 'Root Node'
                        }
                    });
                });

                it("should not be loading before load is called", function() {
                    expect(store.isLoading()).toBe(false);
                });

                it("should be loading while the request is still in progress", function() {
                    store.load();
                    store.flushLoad();
                    expect(store.isLoading()).toBe(true);
                });

                it("should not be loading after the request has finished", function() {
                    loadStore(store);

                    expect(store.isLoading()).toBe(false);
                });

                describe("if autoLoad is set to true", function() {
                    beforeEach(function() {
                        spyOn(Ext.data.TreeStore.prototype, 'load').andCallThrough();

                        store = new Ext.data.TreeStore({
                            model: NodeModel,
                            autoLoad: true,
                            asynchronousLoad: true,
                            root: {
                                expanded: false,
                                id: 0,
                                name: 'Root Node'
                            }
                        });
                    });

                    it("should load the TreeStore automatically", function() {
                        expect(store.load).toHaveBeenCalled();
                    });
                });
            });

            describe("when reloading a store that already contains records", function() {
                beforeEach(function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        autoLoad: false,
                        asynchronousLoad: false,
                        root: {
                            expanded: false,
                            id: 0,
                            name: 'Root Node'
                        }
                    });

                    store.fillNode(store.getRootNode(), store.getProxy().getReader().readRecords(dummyData.children).getRecords());
                });

                describe("if records have been removed from the store", function() {
                    beforeEach(function() {
                        store.getNodeById(1).remove();
                        store.getNodeById(5).remove();
                        store.getNodeById(4).remove();
                    });
                    describe("if the node being loaded is the root node", function() {
                        beforeEach(function() {
                            loadStore(store);
                        });
                        it("should reset the store's removed array", function() {
                            expect(store.getRemovedRecords().length).toBe(0);
                        });
                    });
                    describe("if the node being loaded is not the root node", function() {
                        var removed;

                        beforeEach(function() {
                            loadStore(store, { node: store.getNodeById(2) });
                        });
                        it("should only remove records from the removed array that were previously descendants of the node being reloaded", function() {
                            removed = store.getRemovedRecords();

                            expect(removed.length).toBe(1);
                            expect(removed[0].getId()).toBe(1);
                        });
                    });
                    describe("if clearRemovedOnLoad is false", function() {
                        var removed;

                        beforeEach(function() {
                            store.clearRemovedOnLoad = false;
                            loadStore(store);
                        });
                        afterEach(function() {
                            store.clearRemovedOnLoad = true;
                        });
                        it("should not alter the store's removed array", function() {
                            removed = store.getRemovedRecords();

                            expect(removed.length).toBe(3);
                            expect(removed[0].getId()).toBe(1);
                            expect(removed[1].getId()).toBe(5);
                            expect(removed[2].getId()).toBe(4);
                        });
                    });

                });

            });

            describe("when the records in the response data have an index field", function() {
                beforeEach(function() {
                    dummyData = {
                        success: true,
                        children: [{
                            id: 1,
                            name: "aaa",
                            index: 2
                        }, {
                            id: 2,
                            name: "bbb",
                            index: 0,
                            children: [{
                                id: 3,
                                name: "ccc",
                                index: 1
                            }, {
                                id: 4,
                                name: "ddd",
                                index: 0
                            }],
                            expanded: true
                        }, {
                            id: 5,
                            name: "eee",
                            index: 1
                        }]
                    };

                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        root: {
                            expanded: true,
                            id: 0,
                            name: 'Root Node'
                        }
                    });

                    loadStore(store);
                });

                it("should sort the root level nodes by index", function() {
                    // use getRootNode (as opposed to new getter getRoot) to test backward compatibilty.
                    expect(store.getRootNode().childNodes[0].getId()).toBe(2);
                    expect(store.getRootNode().childNodes[1].getId()).toBe(5);
                    expect(store.getRootNode().childNodes[2].getId()).toBe(1);
                });

                it("should sort descendants by index", function() {
                    expect(store.getNodeById(2).firstChild.getId()).toBe(4);
                    expect(store.getNodeById(2).lastChild.getId()).toBe(3);
                });

                it("should sort folders first, then in index order", function() {
                    expect(store.getAt(0).getId()).toBe(2);
                    expect(store.getAt(1).getId()).toBe(4);
                    expect(store.getAt(2).getId()).toBe(3);
                    expect(store.getAt(3).getId()).toBe(5);
                    expect(store.getAt(4).getId()).toBe(1);
                });
            });
        });

        describe("clearOnLoad", function() {

            beforeEach(function() {
                store = new Ext.data.TreeStore({
                    model: NodeModel,
                    asynchronousLoad: false,
                    root: {
                        expanded: true,
                        id: 0,
                        name: 'Root Node'
                    }
                });
                completeWithData({
                    children: []
                });
            });

            it("should remove existing nodes with clearOnLoad: true", function() {
                dummyData = {
                    children: []
                };
                var root = store.getRootNode();

                root.appendChild({
                    id: 'node1',
                    text: 'A'
                });

                root.appendChild({
                    id: 'node2',
                    text: 'B'
                });
                loadStore(store);
                expect(store.getRootNode().childNodes.length).toBe(0);
                expect(store.getNodeById('node1')).toBeNull();
                expect(store.getNodeById('node2')).toBeNull();
            });

            it("should leave existing nodes with clearOnLoad: false", function() {
                store.clearOnLoad = false;
                dummyData = {
                    children: []
                };
                var root = store.getRootNode(),
                    childNodes = root.childNodes,
                    node1, node2;

                root.appendChild({
                    id: 'node1',
                    text: 'A'
                });
                node1 = childNodes[0];

                root.appendChild({
                    id: 'node2',
                    text: 'B'
                });
                node2 = childNodes[1];

                loadStore(store);
                expect(childNodes.length).toBe(2);
                expect(store.getNodeById('node1')).toBe(node1);
                expect(store.getNodeById('node2')).toBe(node2);
            });

            it("should ignore dupes with clearOnLoad: false", function() {
                store.clearOnLoad = false;
                dummyData = {
                    children: [{
                        id: 'node1',
                        text: 'A'
                    }, {
                        id: 'node3',
                        text: 'C'
                    }]
                };
                var root = store.getRootNode();

                root.appendChild({
                    id: 'node1',
                    text: 'A'
                });

                root.appendChild({
                    id: 'node2',
                    text: 'B'
                });
                loadStore(store);
                expect(store.getRootNode().childNodes.length).toBe(3);
            });
        });
    });

    describe('adding data', function() {
        // See EXTJS-13509.
        var root, child;

        afterEach(function() {
            Ext.destroy(store);
            root = child =  null;
        });

        describe('adding non-leaf nodes with children', function() {
            var root, child;

            function doIt(desc, method) {
                describe(desc + ' an existing node', function() {
                    doAdd(method, false);
                    doAdd(method, true);
                });
            }

            function doAdd(method, expanded) {
                describe('expanded: ' + expanded.toString(), function() {
                    it('should add the node and create its child nodes', function() {
                        root[method]({
                            text: 'child',
                            expanded: expanded,
                            children: [{
                                text: 'detention',
                                expanded: expanded,
                                children: [{
                                    text: 'ben',
                                    leaf: true
                                }, {
                                    text: 'bill',
                                    leaf: true
                                }]
                            }]
                        });

                        child = store.getNewRecords()[0];
                        expect(child.childNodes.length).toBe(1);
                        expect(child.firstChild.childNodes.length).toBe(2);
                        expect(store.getNewRecords().length).toBe(4);
                    });

                    it('should mark the new nodes as "loaded"', function() {
                        expect(child.get('loaded')).toBe(true);
                        expect(child.firstChild.get('loaded')).toBe(true);
                    });
                });
            }

            beforeEach(function() {
                store = new Ext.data.TreeStore({
                    root: {
                        name: 'Root Node'
                    }
                });

                root = store.getRootNode();
            });

            doIt('appending to', 'appendChild');
            doIt('inserting before', 'insertBefore');
        });

        describe('adding childless non-leaf nodes', function() {
            beforeEach(function() {
                spyOn(Ext.data.TreeStore.prototype, 'load').andCallThrough();

                store = new Ext.data.TreeStore({
                    model: NodeModel,
                    root: {
                        name: 'Root Node'
                    }
                });

                root = store.getRootNode();

                root.appendChild({
                    text: 'child2',
                    expanded: false
                });
            });

            it('should not make a request for data when expanded', function() {
                root.firstChild.expand();
                expect(store.load).not.toHaveBeenCalled();
            });
        });
    });

    describe("modifying records", function() {
        it("should fire the update event and pass the store, record, type & modified fields", function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    text: 'Root',
                    children: [{
                        text: 'A child',
                        someProp: 'a'
                    }]
                }
            });

            var rec = store.getRoot().firstChild,
                spy = jasmine.createSpy();

            store.on('update', spy);
            rec.set('someProp', 'b');
            expect(spy).toHaveBeenCalled();
            var args = spy.mostRecentCall.args;

            expect(args[0]).toBe(store);
            expect(args[1]).toBe(rec);
            expect(args[2]).toBe(Ext.data.Model.EDIT);
            expect(args[3]).toEqual(['someProp']);
        });

        it("should fire the update event and pass the store, record, type & modified fields when attached to another store", function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    text: 'Root',
                    children: [{
                        text: 'A child',
                        someProp: 'a'
                    }]
                }
            });

            var rec = store.getRoot().firstChild,
                spy = jasmine.createSpy();

            var other = new Ext.data.Store({
                model: NodeModel,
                data: [rec]
            });

            store.on('update', spy);
            rec.set('someProp', 'b');
            expect(spy).toHaveBeenCalled();
            var args = spy.mostRecentCall.args;

            expect(args[0]).toBe(store);
            expect(args[1]).toBe(rec);
            expect(args[2]).toBe(Ext.data.Model.EDIT);
            expect(args[3]).toEqual(['someProp']);
        });
    });

    describe("rejecting changes", function() {
        var parent, rec;

        beforeEach(function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                asynchronousLoad: false,
                root: {
                    expanded: true,
                    text: 'Root',
                    children: [{
                        id: 'a',
                        text: 'A child',
                        expanded: true,
                        children: [{
                            id: 'aa',
                            text: 'AA child',
                            someProp: 'foo'
                        }]
                    }]
                }
            });

            parent = store.getNodeById('a');
            rec = parent.firstChild;
        });

        it("should be able to reject record changes", function() {
            rec.set('someProp', 'bar');

            store.rejectChanges();

            expect(rec.get('someProp')).toBe('foo');
        });

        it("should be able to reject record changes when it's parent is collapsed", function() {
            rec.set('someProp', 'foo');

            parent.collapse();

            store.rejectChanges();

            expect(rec.get('someProp')).toBe('foo');
        });
    });

    describe("saving data", function() {
        var record, records, syncSpy;

        beforeEach(function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                asynchronousLoad: false,
                root: {
                    expanded: true,
                    name: 'Root Node'
                }
            });

            loadStore(store);

            // If overriding the sync, we need to clear the needsSync flag so that future endUpdate calls do not sync again
            syncSpy = spyOn(store, 'sync').andCallFake(function() {
                this.needsSync = false;
            });
        });

        describe("creating records", function() {
            describe("appending a single node", function() {
                beforeEach(function() {
                    record = new NodeModel({ name: 'Phil' });
                    store.getRootNode().appendChild(record);
                });

                it("should add the node to getNewRecords", function() {
                    records = store.getNewRecords();
                    expect(records.length).toBe(1);
                    expect(records[0]).toBe(record);
                });

                it("should not add anything to getUpdatedRecords", function() {
                    expect(store.getUpdatedRecords().length).toBe(0);
                });

                it("should not sync the store", function() {
                    expect(syncSpy).not.toHaveBeenCalled();
                });
            });

            describe("inserting a single node", function() {
                beforeEach(function() {
                    record = new NodeModel({ name: 'Phil' });
                    store.getNodeById(2).insertBefore(record, store.getNodeById(4));
                });

                it("should add the node to getNewRecords", function() {
                    records = store.getNewRecords();
                    expect(records.length).toBe(1);
                    expect(records[0]).toBe(record);
                });

                it("should not add any records to getUpdatedRecords", function() {
                    expect(store.getUpdatedRecords().length).toBe(0);
                });

                it("should not sync the store", function() {
                    expect(syncSpy).not.toHaveBeenCalled();
                });
            });

            describe("appending and inserting multiple nodes", function() {
                var record1, record2, record3;

                beforeEach(function() {
                    record1 = new NodeModel({ name: '1' });
                    record2 = new NodeModel({ name: '2' });
                    record3 = new NodeModel({ name: '3' });

                    store.getRootNode().appendChild(record1);
                    store.getNodeById(2).insertBefore(record2, store.getNodeById(4));
                    record2.appendChild(record3);
                });

                it("should add the nodes to getNewRecords", function() {
                    var newRecords = store.getNewRecords();

                    expect(newRecords.length).toBe(3);
                    expect(Ext.Array.contains(newRecords, record1)).toBe(true);
                    expect(Ext.Array.contains(newRecords, record2)).toBe(true);
                    expect(Ext.Array.contains(newRecords, record3)).toBe(true);
                });

                it("should not add any records to getUpdatedRecords", function() {
                    expect(store.getUpdatedRecords().length).toBe(0);
                });

                it("should not sync the store", function() {
                    expect(syncSpy).not.toHaveBeenCalled();
                });
            });

            describe("when the index field is persistent", function() {
                var updateSpy;

                beforeEach(function() {
                    NodeModel.getField('index').persist = true;
                });
                afterEach(function() {
                    NodeModel.getField('index').persist = false;
                });

                describe("appending a single node", function() {
                    beforeEach(function() {
                        record = new NodeModel({ name: 'Phil' });
                        updateSpy = spyOnEvent(store, 'update');
                        store.getRootNode().appendChild(record);
                    });

                    it("should add the node to getNewRecords", function() {
                        records = store.getNewRecords();
                        expect(records.length).toBe(1);
                        expect(records[0]).toBe(record);

                        // Persistent fields must be recorded as modified
                        expect(record.modified).toEqual({
                            index: -1,
                            parentId: null
                        });
                        expect(record.getChanges()).toEqual({
                            index: 3,
                            parentId: 'root'
                        });

                        // Modifications must come through to the store update event
                        expect(updateSpy.callCount).toBe(2);
                        expect(updateSpy.calls[1].args[1]).toBe(record);
                        expect(updateSpy.calls[1].args[2]).toBe(Ext.data.Model.EDIT);
                        expect(updateSpy.calls[1].args[3]).toEqual([ 'isLast', 'parentId', 'depth', 'index' ]);
                    });

                    it("should not add any records to getUpdatedRecords", function() {
                        expect(store.getUpdatedRecords().length).toBe(0);
                    });
                });

                describe("inserting a single node", function() {
                    beforeEach(function() {
                        record = new NodeModel({ name: 'Phil' });
                        store.getNodeById(2).insertBefore(record, store.getNodeById(3));
                    });

                    it("should add the node to getNewRecords", function() {
                        records = store.getNewRecords();
                        expect(records.length).toBe(1);
                        expect(records[0]).toBe(record);
                    });

                    it("should add all of its sibling nodes that come after the insertion point to getUpdatedRecords", function() {
                        records = store.getUpdatedRecords();
                        expect(records.length).toBe(2);
                        expect(Ext.Array.contains(records, store.getNodeById(3))).toBe(true);
                        expect(Ext.Array.contains(records, store.getNodeById(4))).toBe(true);
                    });
                });
            });

            describe("when autoSync is true", function() {
                beforeEach(function() {
                    store.autoSync = true;
                });

                describe("appending a single node", function() {
                    beforeEach(function() {
                        record = new NodeModel({ name: 'Phil' });
                        store.getRootNode().appendChild(record);
                    });

                    it("should sync the store", function() {
                        expect(syncSpy.callCount).toBe(1);
                    });
                });

                describe("inserting a single node", function() {
                    beforeEach(function() {
                        record = new NodeModel({ name: 'Phil' });
                        store.getNodeById(2).insertBefore(record, store.getNodeById(4));
                    });

                    it("should sync the store", function() {
                        expect(syncSpy.callCount).toBe(1);
                    });
                });
            });
        });

        describe("updating records", function() {
            describe("updating multiple records", function() {
                beforeEach(function() {
                    store.getNodeById(2).set('name', '222');
                    store.getNodeById(3).set('name', '333');
                });

                it("should add the nodes to getUpdatedRecords", function() {
                    records = store.getUpdatedRecords();
                    expect(records.length).toBe(2);
                    expect(Ext.Array.contains(records, store.getNodeById(2))).toBe(true);
                    expect(Ext.Array.contains(records, store.getNodeById(3))).toBe(true);
                });

                it("should not sync the store", function() {
                    expect(syncSpy).not.toHaveBeenCalled();
                });
            });

            describe("moving records", function() {
                describe("within the same parent node", function() {
                    beforeEach(function() {
                        store.getRootNode().insertBefore(store.getNodeById(6), store.getNodeById(1));
                    });

                    it("should not add any records to getUpdatedRecords", function() {
                        expect(store.getUpdatedRecords().length).toBe(0);
                    });

                    it("should not sync the store", function() {
                        expect(syncSpy).not.toHaveBeenCalled();
                    });
                });

                describe("to a different parent node", function() {
                    beforeEach(function() {
                        store.getNodeById(4).insertBefore(store.getNodeById(1), store.getNodeById(5));
                    });

                    it("should add the node to getUpdatedRecords", function() {
                        records = store.getUpdatedRecords();
                        expect(records.length).toBe(1);
                        expect(records[0]).toBe(store.getNodeById(1));
                    });

                    it("should not sync the store", function() {
                        expect(syncSpy).not.toHaveBeenCalled();
                    });
                });
            });

            describe("moving records when the index field is persistent", function() {
                var updateSpy,
                    updateRec;

                beforeEach(function() {
                    NodeModel.getField('index').persist = true;
                });
                afterEach(function() {
                    NodeModel.getField('index').persist = false;
                });

                describe("within the same parent node", function() {
                    beforeEach(function() {
                        updateSpy = spyOnEvent(store, 'update');
                        store.getRootNode().insertBefore(store.getNodeById(6), store.getNodeById(1));
                    });

                    it("should add the node and all sibling nodes after it to getUpdatedRecords", function() {
                        expect(updateSpy.callCount).toBe(4);

                        // Second call is when node 1 gets its index bumped
                        updateRec = updateSpy.calls[1].args[1];
                        expect(updateRec.modified).toEqual({
                            index: 0
                        });
                        expect(updateRec.getChanges()).toEqual({
                            index: 1
                        });
                        expect(updateRec).toBe(store.getNodeById(1));
                        expect(updateSpy.calls[1].args[2]).toBe(Ext.data.Model.EDIT);
                        expect(updateSpy.calls[1].args[3]).toEqual(['index']);

                        // Then node 2
                        updateRec = updateSpy.calls[2].args[1];
                        expect(updateRec.modified).toEqual({
                            index: 1
                        });
                        expect(updateRec.getChanges()).toEqual({
                            index: 2
                        });
                        expect(updateRec).toBe(store.getNodeById(2));
                        expect(updateSpy.calls[2].args[2]).toBe(Ext.data.Model.EDIT);
                        expect(updateSpy.calls[2].args[3]).toEqual(['index']);

                        // Then node 6
                        updateRec = updateSpy.calls[3].args[1];
                        expect(updateRec.modified).toEqual({
                            index: 2
                        });
                        expect(updateRec.getChanges()).toEqual({
                            index: 0
                        });
                        expect(updateRec).toBe(store.getNodeById(6));
                        expect(updateSpy.calls[3].args[2]).toBe(Ext.data.Model.EDIT);
                        expect(updateSpy.calls[3].args[3]).toEqual([ 'index', 'isFirst', 'isLast' ]);

                        records = store.getUpdatedRecords();
                        expect(records.length).toBe(3);
                        expect(Ext.Array.contains(records, store.getNodeById(1))).toBe(true);
                        expect(Ext.Array.contains(records, store.getNodeById(2))).toBe(true);
                        expect(Ext.Array.contains(records, store.getNodeById(6))).toBe(true);
                    });
                });

                describe("to a different parent node", function() {
                    beforeEach(function() {
                        store.getNodeById(4).insertBefore(store.getNodeById(1), store.getNodeById(5));
                    });

                    it("should add the node, all sibling nodes after it's insertion point, and all siblings after its removal point to getUpdatedRecords", function() {
                        records = store.getUpdatedRecords();
                        expect(records.length).toBe(4);
                        expect(Ext.Array.contains(records, store.getNodeById(1))).toBe(true);
                        expect(Ext.Array.contains(records, store.getNodeById(2))).toBe(true);
                        expect(Ext.Array.contains(records, store.getNodeById(5))).toBe(true);
                        expect(Ext.Array.contains(records, store.getNodeById(6))).toBe(true);
                    });
                });

                describe("to a different parent but reverting back to the original index", function() {
                    it("should add the node, all sibling nodes after it's insertion point, and all siblings after its removal point to getUpdatedRecords", function() {
                        store.getNodeById(2).appendChild(store.getNodeById(1));
                        expect(store.getNodeById(1).modified).toEqual({
                            parentId: 'root',
                            index: 0
                        });
                        expect(store.getNodeById(1).getChanges()).toEqual({
                            parentId: 2,
                            index: 2
                        });

                        // This will put node 1 back to index 0 so only the parentId is changed.
                        store.getNodeById(7).appendChild(store.getNodeById(1));
                        expect(store.getNodeById(1).modified).toEqual({
                            parentId: 'root'
                        });
                        expect(store.getNodeById(1).getChanges()).toEqual({
                            parentId: 7
                        });

                        // Now move back to its original position.
                        // All changes must be wiped.
                        store.getRoot().insertBefore(store.getNodeById(1), store.getRoot().firstChild);
                        expect(store.getNodeById(1).modified).toEqual({});
                        expect(store.getNodeById(1).getChanges()).toEqual({});
                    });
                });
            });

            describe("moving records when autoSync is true", function() {
                beforeEach(function() {
                    store.autoSync = true;
                });

                describe("within the same parent node", function() {
                    beforeEach(function() {
                        store.getRootNode().insertBefore(store.getNodeById(6), store.getNodeById(1));
                    });

                    // The parentId field is persistent. Has not been changed in this case.
                    it("should not sync the store", function() {
                        expect(syncSpy).not.toHaveBeenCalled();
                    });
                });

                describe("to a different parent node", function() {
                    beforeEach(function() {
                        store.getNodeById(4).insertBefore(store.getNodeById(1), store.getNodeById(5));
                    });

                    // The parentId field is persistent. Has been changed, so store is dirty
                    it("should sync the store", function() {
                        expect(syncSpy.callCount).toBe(1);
                    });
                });

                describe("to a different TreeStore", function() {
                    var otherStore,
                        otherSyncSpy;

                    beforeEach(function() {
                        otherStore = new Ext.data.TreeStore({
                            model: NodeModel,
                            root: {
                                expanded: true,
                                name: 'Root Node'
                            },
                            autoSync: true
                        });
                        otherSyncSpy = spyOn(otherStore, 'sync').andCallFake(function() {
                            this.needsSync = false;
                        });
                        otherStore.getRootNode().appendChild(store.getNodeById(1));
                    });
                    afterEach(function() {
                        otherStore.destroy();
                    });

                    it("should sync both the stores", function() {
                        expect(syncSpy.callCount).toBe(1);
                        expect(otherSyncSpy.callCount).toBe(1);
                    });
                });

            });
        });

        describe("removing records", function() {
            describe("removing a single record", function() {
                beforeEach(function() {
                    record = store.getNodeById(1).remove();
                });

                it("should add the node to getRemovedRecords", function() {
                    records = store.getRemovedRecords();
                    expect(records.length).toBe(1);
                    expect(records[0]).toBe(record);
                });

                it("should not add any records to getUpdatedRecords", function() {
                    expect(store.getUpdatedRecords().length).toBe(0);
                });

                it("should not sync the store", function() {
                    expect(syncSpy).not.toHaveBeenCalled();
                });

                it("should not add phantom records to the removed collection", function() {
                    var node = new NodeModel(),
                        root = store.getRootNode();

                    root.appendChild(node);
                    root.removeChild(node);
                    expect(Ext.Array.contains(store.getRemovedRecords(), node)).toBe(false);
                });
            });

            describe("removing multiple records", function() {
                var record2;

                beforeEach(function() {
                    record = store.getNodeById(1).remove();
                    record2 = store.getNodeById(4).remove();
                });

                it("should add the nodes to getRemovedRecords", function() {
                    records = store.getRemovedRecords();

                    // 1, 4, and 4's sole child 5 should be in the removed list.
                    expect(records.length).toBe(3);
                    expect(Ext.Array.contains(records, record)).toBe(true);
                    expect(Ext.Array.contains(records, record2)).toBe(true);
                });

                it("should not add any records to getUpdatedRecords", function() {
                    expect(store.getUpdatedRecords().length).toBe(0);
                });

                it("should not sync the store", function() {
                    expect(syncSpy).not.toHaveBeenCalled();
                });
            });

            describe("when the index field is persistent", function() {
                beforeEach(function() {
                    NodeModel.getField('index').persist = true;
                });
                afterEach(function() {
                    NodeModel.getField('index').persist = false;
                });

                describe("removing a single record", function() {
                    beforeEach(function() {
                        record = store.getNodeById(1).remove();
                    });

                    it("should add the node to getRemovedRecords", function() {
                        records = store.getRemovedRecords();
                        expect(records.length).toBe(1);
                        expect(records[0]).toBe(record);
                    });

                    it("should add all siblings after the node's removal point to getUpdatedRecords", function() {
                        records = store.getUpdatedRecords();
                        expect(records.length).toBe(2);
                        expect(Ext.Array.contains(records, store.getNodeById(2))).toBe(true);
                        expect(Ext.Array.contains(records, store.getNodeById(6))).toBe(true);
                    });
                });
            });

            describe("when autoSync is true", function() {
                beforeEach(function() {
                    store.autoSync = true;
                });

                describe("removing a single record", function() {
                    beforeEach(function() {
                        store.getNodeById(1).remove();
                    });

                    it("should sync the store", function() {
                        expect(syncSpy.callCount).toBe(1);
                    });
                });
            });
        });

        describe("sorting", function() {
            var sortByNameDesc = function(node1, node2) {
                var name1 = node1.data.name,
                    name2 = node2.data.name;

                return name1 < name2 ? 1 : node1 === node2 ? 0 : -1;
            };

            describe("when sorting the TreeStore", function() {
                var beforeSortSpy,
                    sortSpy;

                beforeEach(function() {
                    beforeSortSpy = spyOnEvent(store, 'beforesort');
                    sortSpy = spyOnEvent(store, 'sort');
                    store.sort(sortByNameDesc);
                });

                it("should not add any records to getUpdatedRecords", function() {
                    expect(store.getUpdatedRecords().length).toBe(0);

                    // Expected events must have fired.
                    expect(beforeSortSpy.callCount).toBe(1);
                    expect(sortSpy.callCount).toBe(1);
                });
            });

            describe("when sorting recursively", function() {
                beforeEach(function() {
                    store.getRootNode().sort(sortByNameDesc, true);
                });

                it("should not add any records to getUpdatedRecords", function() {
                    expect(store.getUpdatedRecords().length).toBe(0);
                });
            });

            describe("when sorting non-recursively", function() {
                beforeEach(function() {
                    store.getRootNode().sort(sortByNameDesc);
                });

                it("should not add any records to getUpdatedRecords", function() {
                    expect(store.getUpdatedRecords().length).toBe(0);
                });
            });

            describe("when the index field is persistent and autoSync is true", function() {
                beforeEach(function() {
                    NodeModel.getField('index').persist = true;
                    store.autoSync = true;
                });
                afterEach(function() {
                    NodeModel.getField('index').persist = false;
                });

                describe("when sorting recursively", function() {
                    beforeEach(function() {
                        store.getRootNode().sort(sortByNameDesc, true);
                    });

                    it("should add all nodes at all levels that had an index change to getUpdatedRecords", function() {
                        records = store.getUpdatedRecords();
                        expect(records.length).toBe(4);
                        expect(Ext.Array.contains(records, store.getNodeById(1))).toBe(true);
                        expect(Ext.Array.contains(records, store.getNodeById(3))).toBe(true);
                        expect(Ext.Array.contains(records, store.getNodeById(4))).toBe(true);
                        expect(Ext.Array.contains(records, store.getNodeById(6))).toBe(true);
                    });

                    it("should sync the store", function() {
                        expect(syncSpy.callCount).toBe(1);
                    });
                });

                describe("when sorting non-recursively", function() {
                    beforeEach(function() {
                        store.getRootNode().sort(sortByNameDesc);
                    });

                    it("should add all nodes at depth 1 that had an index change to getUpdatedRecords", function() {
                        records = store.getUpdatedRecords();
                        expect(records.length).toBe(2);
                        expect(Ext.Array.contains(records, store.getNodeById(1))).toBe(true);
                        expect(Ext.Array.contains(records, store.getNodeById(6))).toBe(true);
                    });

                    it("should sync the store", function() {
                        expect(syncSpy.callCount).toBe(1);
                    });
                });
            });
        });
    });

    describe('Loading TreeStore using root config', function() {
        it('should load the root nodes children using Proxy\'s "root" config', function() {
            // Suppress console error
            spyOn(Ext.log, 'error');
            var store = new Ext.data.TreeStore({
                root: {
                    expanded: true,
                    CHILDREN: [
                        { text: "detention", leaf: true },
                        { text: "homework", expanded: true, CHILDREN: [
                            { text: "book report", leaf: true },
                            { text: "alegrbra", leaf: true }
                        ] },
                        { text: "buy lottery tickets", leaf: true }
                    ]
                },
                proxy: {
                    type: "memory",
                    reader: {
                        type: "json",
                        rootProperty: "CHILDREN"
                    }
                }
            });

            var cn = store.getRootNode().childNodes;

            expect(cn.length).toBe(3);
            expect(cn[0].childNodes.length).toBe(0);
            expect(cn[1].childNodes.length).toBe(2);
            expect(cn[2].childNodes.length).toBe(0);
        });
    });

    describe("default node id", function() {
        it('Should use generate an ID if the idProperty is null in the incoming data', function() {
            store = new Ext.data.TreeStore({
                model: TaskModel,
                defaultRootId: null,
                root: {
                }
            });
            expect(store.getRootNode().getId()).not.toBeNull();
        });
        it('Should use "root" as the defaultRootId, and parse that according to the idProperty field type', function() {
            // The idProperty field is an int, so this should raise an error
            expect(function() {
                store = new Ext.data.TreeStore({
                    model: TaskModel,
                    root: {
                    }
                });
            }).toThrow();
        });

        it('Should use the configured defaultRootId, and parse that according to the idProperty field type', function() {
            store = new Ext.data.TreeStore({
                model: TaskModel,
                defaultRootId: -1,
                root: {
                }
            });
            expect(store.getRootNode().getId()).toBe(-1);
        });
    });

    describe('moving root node between trees', function() {
        it('should move root and all descendants from source tree into destination tree', function() {
            store = new Ext.data.TreeStore({
                root: {
                    expanded: true,
                    children: [{
                        text: "Test",
                        leaf: true,
                        id: 'testId'
                    }]
                },
                listeners: {
                    rootchange: function(newRoot, oldRoot) {
                        oldStoreRootChangeArgs = [newRoot, oldRoot];
                    },
                    refresh: function() {
                        storeRefreshed++;
                    },
                    add: function() {
                        added++;
                    },
                    remove: function() {
                        removed++;
                    }
                }
            });

            var rootNode = store.getRootNode(),
                childNode = rootNode.firstChild,
                store2 = new Ext.data.TreeStore({
                    listeners: {
                        rootchange: function(newRoot, oldRoot) {
                            newStoreRootChangeArgs = [newRoot, oldRoot];
                        },
                        refresh: function() {
                            store2Refreshed++;
                        },
                        add: function() {
                            added++;
                        },
                        remove: function() {
                            removed++;
                        }
                    },
                    root: {
                    }
                }),
                storeRefreshed = 0,
                store2Refreshed = 0,
                added = 0,
                removed = 0,
                store2Root = store2.getRootNode(),
                oldStoreRootChangeArgs = [],
                newStoreRootChangeArgs = [];

            // TreeStore set up as expected
            expect(rootNode.rootOf === store.tree).toBe(true);
            expect(store.getNodeById('testId') === childNode).toBe(true);

            // Move the root to a new TreeStore and check it's set up as expected.
            store2.setRootNode(rootNode);

            // Old store has gone from rootNode to null
            expect(oldStoreRootChangeArgs[0]).toEqual(null);
            expect(oldStoreRootChangeArgs[1]).toEqual(rootNode);

            // Second store has gone from store2Root to rootNode
            expect(newStoreRootChangeArgs[0]).toEqual(rootNode);
            expect(newStoreRootChangeArgs[1]).toEqual(store2Root);

            // Both stores should fire a refresh event
            expect(storeRefreshed).toBe(1);
            expect(store2Refreshed).toBe(1);

            // Add and remove events should be suspended for the root change operation
            expect(added).toBe(0);
            expect(removed).toBe(0);

            expect(rootNode.rootOf === store2.tree).toBe(true);
            expect(store2.getRootNode() === rootNode).toBe(true);
            expect(store2.getNodeById('testId') === childNode).toBe(true);

            // Child node must not be registered with the old TreeStore
            expect(store.getNodeById('testId')).toBeFalsy();

            // Old TreeStore must not have a root
            expect(store.getRootNode()).toBeFalsy();
            store2.destroy();
        });
    });

    describe('Node events bubbled to the root node', function() {

        var spy,
            root,
            newNode,
            removedNode,
            firstChild,
            spyArgs;

        beforeEach(function() {
            store = new Ext.data.TreeStore({
                root: {
                    text: 'Root 1',
                    expanded: true,
                    children: [{
                        text: 'Child 1',
                        leaf: true
                    }, {
                        text: 'Child 2',
                        leaf: true
                    }, {
                        text: 'Child 3',
                        leaf: true
                    }, {
                        text: 'Child 4',
                        leaf: true
                    }]
                }
            });
            root = store.getRootNode();
        });

        it('should fire insert event', function() {

            // Node events are NOT bubbled up to the TreeStore level, only as far as the root
            spy = spyOnEvent(root, "insert").andCallThrough();
            firstChild = root.firstChild;
            newNode = root.insertBefore({
                text: 'New First'
            }, firstChild);
            spyArgs = spy.calls[0].args;
            expect(spy.calls.length).toBe(1);
            expect(spyArgs[0]).toBe(root);
            expect(spyArgs[1]).toBe(newNode);
            expect(spyArgs[2]).toBe(firstChild);
        });

        it('should fire append event', function() {

            // Node events are NOT bubbled up to the TreeStore level, only as far as the root
            spy = spyOnEvent(root, "append").andCallThrough();
            newNode = root.appendChild({
                text: 'New Last'
            });
            spyArgs = spy.calls[0].args;
            expect(spy.calls.length).toBe(1);
            expect(spyArgs[0]).toBe(root);
            expect(spyArgs[1]).toBe(newNode);
            expect(spyArgs[2]).toBe(4);
        });

        it('should fire remove event', function() {
            var context;

            // Node events are NOT bubbled up to the TreeStore level, only as far as the root
            spy = spyOnEvent(root, "remove").andCallThrough();
            removedNode = root.removeChild(root.childNodes[1]);
            spyArgs = spy.calls[0].args;
            expect(spy.calls.length).toBe(1);
            expect(spyArgs[0]).toBe(root);
            expect(spyArgs[1]).toBe(removedNode);
            expect(spyArgs[2]).toBe(false);

            // Context arguments: where the removed node came from
            context = spyArgs[3];
            expect(context.parentNode).toBe(root);
            expect(context.previousSibling).toBe(root.childNodes[0]);
            expect(context.nextSibling).toBe(root.childNodes[1]);
        });

        it('should fire update event', function() {
            spy = spyOnEvent(store, "update").andCallThrough();
            root.firstChild.set('text', 'New Text');
            spyArgs = spy.calls[0].args;
            expect(spy.calls.length).toBe(1);
            expect(spyArgs[0]).toBe(store);
            expect(spyArgs[1]).toBe(root.firstChild);
            expect(spyArgs[2]).toBe("edit");
            expect(spyArgs[3]).toEqual(["text"]);
        });

        it('should fire "load" event with valid 5-argument signature', function() {
            spy = spyOnEvent(store, "load").andCallThrough();
            store.load();
            spyArgs = spy.calls[0].args;
            expect(spy.calls.length).toBe(1);
            expect(spyArgs.length).toBe(5);

            // validating args: [ store, records[], success, operation, node]
            expect(spyArgs[0]).toBe(store);
            expect(Ext.isArray(spyArgs[1])).toBe(true);
            expect(typeof spyArgs[2]).toBe('boolean');
            expect(spyArgs[3].isReadOperation).toBe(true);
            expect(spyArgs[4]).toBe(root);

        });

        it('should fire "beforeload" event with valid 2-argument signature', function() {
            spy = spyOnEvent(store, "beforeload").andCallThrough();
            store.load();
            spyArgs = spy.calls[0].args;
            expect(spy.calls.length).toBe(1);
            expect(spyArgs.length).toBe(2);

            // validating args: [ store, data.Operation, object, eOptsObject ]
            expect(spyArgs[0]).toBe(store);
            expect(spyArgs[1] && spyArgs[1].isReadOperation).toBe(true);
        });

        describe('event ordering', function() {
            it('should fire events in the correct order', function() {
                store = new Ext.data.TreeStore({
                    root: {
                        text: 'Root 1',
                        expanded: true,
                        children: []
                    }
                });
                root = store.getRoot();

                var result = [],
                    nodeData = {
                        id: 'A',
                        leaf: false,
                        expanded: true,
                        children: [{
                            id: 'A.A',
                            leaf: true
                        }, {
                            id: 'A.B',
                            leaf: true
                        }, {
                            id: 'A.C',
                            leaf: false,
                            expanded: true,
                            children: [{
                                id: 'A.C.A',
                                leaf: true
                            }, {
                                id: 'A.C.B',
                                leaf: true
                            }]
                        }, {
                            id: 'A.D',
                            leaf: true
                        }]
                    };

                // Node events are NOT bubbled up to the TreeStore level, only as far as the root
                root.on('append', function(thisNode, newChildNode, index) {
                    result.push(newChildNode.getPath() + " | " + thisNode.getPath());
                });
                root.appendChild(nodeData);
                result = result.join(', ');
                expect(result).toBe("/root/A | /root, /root/A/A.A | /root/A, /root/A/A.B | /root/A, /root/A/A.C | /root/A, /root/A/A.C/A.C.A | /root/A/A.C, /root/A/A.C/A.C.B | /root/A/A.C, /root/A/A.D | /root/A");
                store.destroy();
            });
        });
    });

    describe('Node events bubbled to the TreeStore', function() {

        var spy,
            root,
            newNode,
            removedNode,
            firstChild,
            spyArgs;

        beforeEach(function() {
            store = new Ext.data.TreeStore({
                root: {
                    text: 'Root 1',
                    expanded: true,
                    children: [{
                        text: 'Child 1',
                        leaf: true
                    }, {
                        text: 'Child 2',
                        leaf: true
                    }, {
                        text: 'Child 3',
                        leaf: true
                    }, {
                        text: 'Child 4',
                        leaf: true
                    }]
                }
            });
            root = store.getRootNode();
        });

        // Node events fired through the TreeStore are prepended with "node"
        it('should fire insert event', function() {

            spy = spyOnEvent(store, "nodeinsert").andCallThrough();
            firstChild = root.firstChild;
            newNode = root.insertBefore({
                text: 'New First'
            }, firstChild);
            spyArgs = spy.calls[0].args;
            expect(spy.calls.length).toBe(1);
            expect(spyArgs[0]).toBe(root);
            expect(spyArgs[1]).toBe(newNode);
            expect(spyArgs[2]).toBe(firstChild);
        });

        // Node events fired through the TreeStore are prepended with "node"
        it('should fire append event', function() {

            spy = spyOnEvent(store, "nodeappend").andCallThrough();
            newNode = root.appendChild({
                text: 'New Last'
            });
            spyArgs = spy.calls[0].args;
            expect(spy.calls.length).toBe(1);
            expect(spyArgs[0]).toBe(root);
            expect(spyArgs[1]).toBe(newNode);
            expect(spyArgs[2]).toBe(4);
        });

        // Node events fired through the TreeStore are prepended with "node"
        it('should fire remove event', function() {

            spy = spyOnEvent(store, "noderemove").andCallThrough();
            removedNode = root.removeChild(root.firstChild);
            spyArgs = spy.calls[0].args;
            expect(spy.calls.length).toBe(1);
            expect(spyArgs[0]).toBe(root);
            expect(spyArgs[1]).toBe(removedNode);
            expect(spyArgs[2]).toBe(false);
        });

        describe('event ordering', function() {
            it('should fire events in the correct order', function() {

                store = new Ext.data.TreeStore({
                    root: {
                        text: 'Root 1',
                        expanded: true,
                        children: []
                    }
                });
                root = store.getRoot();

                var result = [],
                    nodeData = {
                        id: 'A',
                        leaf: false,
                        expanded: true,
                        children: [{
                            id: 'A.A',
                            leaf: true
                        }, {
                            id: 'A.B',
                            leaf: true
                        }, {
                            id: 'A.C',
                            leaf: false,
                            expanded: true,
                            children: [{
                                id: 'A.C.A',
                                leaf: true
                            }, {
                                id: 'A.C.B',
                                leaf: true
                            }]
                        }, {
                            id: 'A.D',
                            leaf: true
                        }]
                    };

                // Node events fired through the TreeStore are prepended with "node"
                store.on('nodeappend', function(thisNode, newChildNode, index) {
                    result.push(newChildNode.getPath() + " | " + thisNode.getPath());
                });

                root.appendChild(nodeData);
                result = result.join(', ');
                expect(result).toBe("/root/A | /root, /root/A/A.A | /root/A, /root/A/A.B | /root/A, /root/A/A.C | /root/A, /root/A/A.C/A.C.A | /root/A/A.C, /root/A/A.C/A.C.B | /root/A/A.C, /root/A/A.D | /root/A");
                store.destroy();
            });
        });
    });

    describe('events from descendants of collapsed nodes', function() {
        beforeEach(function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: false,
                    id: 0,
                    name: 'Root Node',
                    autoLoad: true,
                    children: dummyData.children
                }
            });
        });
        it('should fire update events from descendants of collapsed nodes', function() {
            var updateSpy = spyOnEvent(store, 'update');

            waitsFor(function() {
                return !!store.getNodeById(5);
            });
            runs(function() {
                store.getNodeById(5).set('name', 'modified');

                // Data notifications take precedance over filering
                expect(updateSpy).toHaveBeenCalled();
            });
        });
    });

    describe('beforeload', function() {

        it('should not clear node descendants if a function bound to beforeload returns false', function() {
            var beforeLoadComplete = false;

            store = new Ext.data.TreeStore({
                model: NodeModel,
                autoLoad: false,
                root: {
                    expanded: false,
                    id: 0,
                    name: 'Root Node',
                    children: [{
                        id: 1
                    }]
                }
             });

             store.on('beforeload', function(store) {
                 expect(store.getRootNode().firstChild).not.toBeNull();
                 beforeLoadComplete = true;

                 return false;
             });

             store.load();

             waitsFor(function() {
                 return beforeLoadComplete;
             });
        });
    });

    describe('appending to leaf nodes', function() {
        beforeEach(function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    id: 0,
                    name: 'Root Node'
                }
            });
            store.fillNode(store.getRootNode(), store.getProxy().getReader().readRecords(dummyData.children).records);
        });
        it('should convert leaf nodes to branch nodes.', function() {
            var leaf = store.getNodeById(5);

            expect(leaf.isLeaf()).toBe(true);
            leaf.appendChild({
                name: 'eee-child'
            });
            expect(leaf.isLeaf()).toBe(false);
        });
    });

    describe("filtering", function() {
        function vis(node) {
            if (Ext.isNumber(node)) {
                node = byId(node);
            }

            return store.isVisible(node);
        }

        function has(nodeId) {
            if (nodeId.isEntity) {
                nodeId = nodeId.getId();
            }

            return store.getById(nodeId) != null;
        }

        function idFilter(ids) {
            store.filter({
                filterFn: function(node) {
                    return Ext.Array.indexOf(ids, node.id) > -1;
                }
            });
        }

        describe("basic filtering", function() {
            it("should be able to append to a node who's current children are all filtered out", function() {
                makeStore([{
                    id: 1
                }, {
                    id: 2
                }], {
                    filters: [{
                        fn: function(rec) {
                            return rec.get('id') > 2;
                        }
                    }]
                });

                // rootVisible is false by default in TreeStore.
                // And all the nodes are filtered out.
                expect(store.getCount()).toBe(0);

                store.getRoot().appendChild({
                    id: 3
                });

                // We've added the first node which passes the filter.
                expect(store.getCount()).toBe(1);
            });

            it("should be able to append to a node with filtered out trailing descendants", function() {
                makeStore([{
                    text: "parent 1",
                    filterMeIn: true
                }, {
                    text: 'parent 2',
                    filterMeIn: true,
                    expanded: true,
                    children: [{
                        text: 'p2/child 1',
                        filterMeIn: true,
                        children: [{
                            text: 'p2/1/1',
                            filterMeIn: true
                        }]
                    }, {
                        text: 'p2/child 2',
                        children: [{
                            text: 'p2/2/1'
                        }, {
                            text: 'p2/2/3'
                        }]
                    }]
                }, {
                    text: 'parent 3',
                    filterMeIn: true
                }], {
                    filters: [{
                        fn: function(rec) {
                            return rec.get('filterMeIn');
                        }
                    }]
                });
                var p2 = store.getAt(1);

                expect(p2.get('text')).toBe('parent 2');

                // rootVisible is false by default in TreeStore.
                // Only 5 nodes should show.
                expect(store.getCount()).toBe(5);

                var newNode = p2.appendChild({
                    text: 'p2/child 3',
                    filterMeIn: true
                });

                // Node has been added to the flat store
                expect(store.getCount()).toBe(6);

                // We've added the first node which passes the filter.
                // It should be inserted after 'p2/1/1' because all nodes
                // after that in the 'parent 2' branch are filtered out
                expect(store.getAt(4)).toBe(newNode);
            });

            it("should be able to provide a filter in the constructor", function() {
                makeStore([{
                    id: 1
                }, {
                    id: 2
                }], {
                    filters: [{
                        fn: function(rec) {
                            return rec.get('id') === 1;
                        }
                    }]
                });
                expect(vis(1)).toBe(true);
                expect(vis(2)).toBe(false);
            });

            it("should not show children of non matching nodes", function() {
                makeStore([{
                    id: 1,
                    children: [2, 3]
                }, {
                    id: 4,
                    children: [5, 6]
                }]);
                idFilter([2, 3, 4, 5, 6]);
                expect(vis(1)).toBe(false);
                expect(vis(2)).toBe(false);
                expect(vis(3)).toBe(false);
                expect(vis(4)).toBe(true);
                expect(vis(5)).toBe(true);
                expect(vis(6)).toBe(true);
            });

            it("should hide non-matching leaves", function() {
                makeStore([{
                    id: 1,
                    children: [2, 3]
                }, {
                    id: 4,
                    children: [5, 6]
                }]);
                idFilter([1, 4]);
                expect(vis(1)).toBe(true);
                expect(vis(2)).toBe(false);
                expect(vis(3)).toBe(false);
                expect(vis(4)).toBe(true);
                expect(vis(5)).toBe(false);
                expect(vis(6)).toBe(false);
            });

            it("should hide non-matching nodes at all levels", function() {
                makeStore([{
                    id: 1,
                    children: [{
                        id: 2,
                        children: [{
                            id: 3,
                            children: [{
                                id: 4,
                                children: [{
                                    id: 5
                                }]
                            }]
                        }]
                    }]
                }]);
                idFilter([1, 2]);
                expect(vis(1)).toBe(true);
                expect(vis(2)).toBe(true);
                expect(vis(3)).toBe(false);
                expect(vis(4)).toBe(false);
                expect(vis(5)).toBe(false);
            });

            it("should run the filters on all nodes (even if the parent is not visible)", function() {
                makeStore([{
                    id: 'n',
                    children: [{
                        id: 'h',
                        children: [{
                            id: 'c',
                            children: [{
                                id: 'a'
                            }, {
                                id: 'b'
                            }]
                        }, {
                            id: 'f',
                            children: [{
                                id: 'd'
                            }, {
                                id: 'e'
                            }]
                        }, {
                            id: 'g'
                        }]
                    }, {
                        id: 'm',
                        children: [{
                            id: 'i'
                        }, {
                            id: 'l',
                            children: [{
                                id: 'j'
                            }, {
                                id: 'k'
                            }]
                        }]
                    }]
                }, {
                    id: 'v',
                    children: [{
                        id: 'r',
                        children: [{
                            id: 'p',
                            children: [{
                                id: 'o'
                            }]
                        }, {
                            id: 'q'
                        }]
                    }, {
                        id: 'u',
                        children: [{
                            id: 's'
                        }, {
                            id: 't'
                        }]
                    }]
                }, {
                    id: 'z',
                    children: [{
                        id: 'x',
                        children: [{
                            id: 'w'
                        }]
                    }, {
                        id: 'y'
                    }]
                }]);

                var order = [];

                store.getFilters().add({
                    filterFn: function(node) {
                        if (!node.isRoot()) {
                            order.push(node.id);
                        }

                        return node.id !== 'h';
                    }
                });
                expect(order.join('')).toBe('nhcabfdegmiljkvrpoqustzxwy');
            });
        });

        describe("clearing filters", function() {
            it("should reset node visibility after clearing filters", function() {
                makeStore([{
                    id: 1,
                    children: [{
                        id: 2,
                        children: [3, 4]
                    }, {
                        id: 5
                    }, {
                        id: 6,
                        children: [{
                            id: 7,
                            children: [8, 9]
                        }]
                    }]
                }]);
                idFilter([1, 6]);
                expect(vis(1)).toBe(true);
                expect(vis(2)).toBe(false);
                expect(vis(3)).toBe(false);
                expect(vis(4)).toBe(false);
                expect(vis(5)).toBe(false);
                expect(vis(6)).toBe(true);
                expect(vis(7)).toBe(false);
                expect(vis(8)).toBe(false);
                expect(vis(9)).toBe(false);
                store.getFilters().removeAll();
                expect(vis(1)).toBe(true);
                expect(vis(2)).toBe(true);
                expect(vis(3)).toBe(true);
                expect(vis(4)).toBe(true);
                expect(vis(5)).toBe(true);
                expect(vis(6)).toBe(true);
                expect(vis(7)).toBe(true);
                expect(vis(8)).toBe(true);
                expect(vis(9)).toBe(true);
            });

            it("should not fire refresh or datachanged when passing suppressEvent", function() {
                makeStore([{
                    id: 1,
                    children: [{
                        id: 2,
                        children: [3, 4]
                    }, {
                        id: 5
                    }, {
                        id: 6,
                        children: [{
                            id: 7,
                            children: [8, 9]
                        }]
                    }]
                }]);
                idFilter([1, 6]);
                var spy = jasmine.createSpy();

                store.on('refresh', spy);
                store.on('datachanged', spy);
                store.clearFilter(true);
                expect(spy).not.toHaveBeenCalled();
            });
        });

        describe("root visibility with filterer: 'bottomup'", function() {
            describe("with rootVisible: true", function() {
                it("should show the root if any root childNodes are visible", function() {
                    makeStore([{
                        id: 1
                    }, {
                        id: 2
                    }, {
                        id: 3
                    }], {
                        rootVisible: true,
                        filterer: 'bottomup'
                    });
                    idFilter([2]);
                    expect(vis(store.getRoot())).toBe(true);
                });

                it("should not show the root if no children match", function() {
                    makeStore([{
                        id: 1
                    }, {
                        id: 2
                    }], {
                        rootVisible: true,
                        filterer: 'bottomup'
                    });
                    idFilter([3]);
                    expect(vis(store.getRoot())).toBe(false);
                });
            });
        });

        describe("dynamic manipulation", function() {
            describe("adding", function() {
                it("should not show nodes that are added to a filtered out node", function() {
                    makeStore([{
                        id: 1,
                        leaf: false
                    }]);
                    idFilter([2]);
                    byId(1).appendChild({
                        id: 2
                    });
                    expect(vis(2)).toBe(false);
                });

                it("should not show a node that does match the filter", function() {
                    makeStore([{
                        id: 1,
                        leaf: false
                    }]);
                    idFilter([1]);
                    byId(1).appendChild({
                        id: 2
                    });
                    expect(vis(2)).toBe(false);
                });

                it("should show if the added node matches the filter", function() {
                    makeStore([{
                        id: 1,
                        leaf: false
                    }]);
                    idFilter([1, 2]);
                    byId(1).appendChild({
                        id: 2
                    });
                    expect(vis(2)).toBe(true);
                });

                it("should filter out deep nodes that do not match", function() {
                    makeStore([{
                        id: 1,
                        leaf: false
                    }]);
                    idFilter([1, 2, 3, 4]);

                    var main = new Ext.data.TreeModel({
                        id: 2,
                        leaf: false,
                        expanded: true,
                        children: []
                    });

                    main.appendChild({
                        id: 3,
                        leaf: false,
                        expanded: true,
                        children: []
                    }).appendChild({
                        id: 4,
                        leaf: false,
                        expanded: true,
                        children: []
                    }).appendChild({
                        id: 5,
                        leaf: true
                    });

                    byId(1).appendChild(main);
                    expect(vis(2)).toBe(true);
                    expect(vis(3)).toBe(true);
                    expect(vis(4)).toBe(true);
                    expect(vis(5)).toBe(false);
                });
            });

            describe("updating", function() {
                it("should exclude a node when modifying it to not match the filter", function() {
                    makeStore([{
                        id: 1,
                        text: 'Foo'
                    }]);
                    store.getFilters().add({
                        property: 'text',
                        value: 'Foo'
                    });
                    var storeCount = store.getCount();

                    byId(1).set('text', 'Bar');
                    expect(vis(1)).toBe(false);

                    // The node must have been evicted from the flat store
                    expect(store.getCount()).toBe(storeCount - 1);
                });

                it("should exclude children when the parent is filtered out", function() {
                    makeStore([{
                        id: 1,
                        text: 'Foo',
                        children: [{
                            id: 2,
                            text: 'Leaf'
                        }]
                    }]);
                    store.getFilters().add({
                        filterFn: function(node) {
                            if (node.isLeaf()) {
                                return true;
                            }
                            else {
                                return node.data.text === 'Foo';
                            }
                        }
                    });

                    var storeCount = store.getCount();

                    byId(1).set('text', 'Bar');
                    expect(vis(1)).toBe(false);
                    expect(vis(2)).toBe(false);

                    // The node and its child must have been evicted from the flat store
                    expect(store.getCount()).toBe(storeCount - 2);
                });

                it("should include a node when modifying it to match the filter", function() {
                    makeStore([{
                        id: 1,
                        text: 'Foo'
                    }]);
                    store.getFilters().add({
                        property: 'text',
                        value: 'Bar'
                    });
                    var storeCount = store.getCount();

                    byId(1).set('text', 'Bar');
                    expect(vis(1)).toBe(true);

                    // The node must have been added to the flat store
                    expect(store.getCount()).toBe(storeCount + 1);
                });

                it("should include children when the parent is filtered in", function() {
                    makeStore([{
                        id: 1,
                        text: 'Bar',
                        children: [{
                            id: 2,
                            text: 'Leaf'
                        }]
                    }]);

                    store.getFilters().add({
                        filterFn: function(node) {
                            if (node.isLeaf()) {
                                return true;
                            }
                            else {
                                return node.data.text === 'Foo';
                            }
                        }
                    });

                    var storeCount = store.getCount();

                    byId(1).set('text', 'Foo');
                    expect(vis(1)).toBe(true);
                    expect(vis(2)).toBe(true);

                    // The node and its child must have been added to the flat store
                    expect(store.getCount()).toBe(storeCount + 2);
                });
            });
        });

        describe('Programmatic filtering', function() {
            describe('rootVisible: true', function() {
                it("should hide the filtered out node", function() {
                    makeStore([{
                        id: 1,
                        expanded: true,
                        children: [{
                            id: 2,
                            expanded: true,
                            children: [{
                                id: 3,
                                expanded: true,
                                children: [{
                                    id: 4,
                                    expanded: true,
                                    children: [{
                                        id: 5
                                    }]
                                }]
                            }]
                        }]
                    }], {
                        // We plan to hide/show it, so it
                        // must be visible.
                        rootVisible: true
                    });

                    expect(has(1)).toBe(true);
                    expect(has(2)).toBe(true);
                    expect(has(3)).toBe(true);
                    expect(has(4)).toBe(true);
                    expect(has(5)).toBe(true);

                    // Filtering out the node should hide it and its descendants
                    byId(3).set('visible', false);
                    expect(has(3)).toBe(false);
                    expect(has(4)).toBe(false);
                    expect(has(5)).toBe(false);

                    byId(2).collapse();

                    // Filtering back in when an ancestor is collapsed should not re-add it.
                    byId(3).set('visible', true);
                    expect(has(3)).toBe(false);
                    expect(has(4)).toBe(false);
                    expect(has(5)).toBe(false);

                    // When parent is expanded, node 3 and its descendants should be visible again
                    byId(2).expand();
                    expect(has(3)).toBe(true);
                    expect(has(4)).toBe(true);
                    expect(has(5)).toBe(true);

                    // Only expanded descendants should be re-inserted when an ancestor becomes visible
                    byId(3).set('visible', false);
                    byId(4).collapse();
                    byId(3).set('visible', true);
                    expect(has(3)).toBe(true);
                    expect(has(4)).toBe(true);
                    expect(has(5)).toBe(false);

                    byId(4).expand();
                    expect(store.getCount()).toBe(6);

                    // When rootVisible is true, we must be able to hide and show the whole lot
                    // using the root node
                    store.getRootNode().set('visible', false);
                    expect(store.getCount()).toBe(0);
                    store.getRootNode().set('visible', true);
                    expect(store.getCount()).toBe(6);
                });
            });

            describe('rootVisible: false', function() {
                it("should hide the filtered out node", function() {
                    makeStore([{
                        id: 1,
                        expanded: true,
                        children: [{
                            id: 2,
                            expanded: true,
                            children: [{
                                id: 3,
                                expanded: true,
                                children: [{
                                    id: 4,
                                    expanded: true,
                                    children: [{
                                        id: 5
                                    }]
                                }]
                            }]
                        }]
                    }], {
                        rootVisible: false
                    });
                    expect(has(1)).toBe(true);
                    expect(has(2)).toBe(true);
                    expect(has(3)).toBe(true);
                    expect(has(4)).toBe(true);
                    expect(has(5)).toBe(true);

                    byId(1).set('visible', false);
                    expect(store.getCount()).toBe(0);
                    byId(2).set('visible', false);
                    byId(3).set('visible', false);
                    byId(4).set('visible', false);
                    byId(5).set('visible', false);
                    expect(store.getCount()).toBe(0);

                    // Ancestors are hidden. Should not show
                    byId(5).set('visible', true);
                    expect(store.getCount()).toBe(0);
                    byId(4).set('visible', true);
                    expect(store.getCount()).toBe(0);
                    byId(3).set('visible', true);
                    expect(store.getCount()).toBe(0);
                    byId(2).set('visible', true);
                    expect(store.getCount()).toBe(0);

                    // Ancestor of all those shows, suddenly all should show
                    byId(1).set('visible', true);
                    expect(store.getCount()).toBe(5);

                    // Filtering out the node should hide it and its descendants
                    byId(3).set('visible', false);
                    expect(has(3)).toBe(false);
                    expect(has(4)).toBe(false);
                    expect(has(5)).toBe(false);

                    byId(2).collapse();

                    // Filtering back in when an ancestor is collapsed should not re-add it.
                    byId(3).set('visible', true);
                    expect(has(3)).toBe(false);
                    expect(has(4)).toBe(false);
                    expect(has(5)).toBe(false);

                    // When parent is expanded, node 3 and its descendants should be visible again
                    byId(2).expand();
                    expect(has(3)).toBe(true);
                    expect(has(4)).toBe(true);
                    expect(has(5)).toBe(true);

                    // Only expanded descendants should be re-inserted when an ancestor becomes visible
                    byId(3).set('visible', false);
                    byId(4).collapse();
                    byId(3).set('visible', true);
                    expect(has(3)).toBe(true);
                    expect(has(4)).toBe(true);
                    expect(has(5)).toBe(false);

                    byId(4).expand();
                    expect(store.getCount()).toBe(5);
                });
            });
        });
    });

    describe('heterogeneous TreeStores', function() {
        var treeData,
            schema;

        beforeEach(function() {
            schema = Ext.data.Model.schema;
            schema.setNamespace('spec');

            Ext.define('spec.Territory', {
                extend: 'Ext.data.TreeModel',
                idProperty: 'territoryName',
                fields: [{
                    name: 'territoryName',
                    mapping: 'territoryName',
                    convert: undefined
                }]
            });
            Ext.define('spec.Country', {
                extend: 'Ext.data.TreeModel',
                idProperty: 'countryName',
                fields: [{
                    name: 'countryName',
                    mapping: 'countryName',
                    convert: undefined
                }]
            });
            Ext.define('spec.City', {
                extend: 'Ext.data.TreeModel',
                idProperty: 'cityName',
                fields: [{
                    name: 'cityName',
                    mapping: 'cityName',
                    convert: undefined
                }]
            });

            // Must renew the data each time. Because TreeStore mutates input data object by deleting
            // the childNodes in onBeforeNodeExpand and onNodeAdded. TODO: it shouldn't do that.
            // The heterogeneous models MUST have disparate, non-overlapping field names
            // so that we test that a correct, record-specific data extraction function
            // has been run on the different mtypes on the dataset.
            treeData = {
                children: [{
                    mtype: 'Territory',
                    territoryName: 'North America',
                    children: [{
                        mtype: 'Country',
                        countryName: 'USA',

                        // Test using both forms of classname, defaultNamespaced "City".
                        children: [{
                            mtype: 'spec.City',
                            cityName: 'Redwood City',
                            leaf: true
                        }, {
                            mtype: 'City',
                            cityName: 'Frederick, MD',
                            leaf: true
                        }]
                    }, {
                        mtype: 'Country',
                        countryName: 'Canada',
                        children: [{
                            mtype: 'spec.City',
                            cityName: 'Vancouver',
                            leaf: true
                        }, {
                            mtype: 'City',
                            cityName: 'Toronto',
                            leaf: true
                        }]
                    }]
                }, {
                    mtype: 'Territory',
                    territoryName: 'Europe, ME, Africa',
                    expanded: true,
                    children: [{
                        mtype: 'Country',
                        countryName: 'England',
                        children: [{
                            mtype: 'spec.City',
                            cityName: 'Nottingham',
                            leaf: true
                        }, {
                            mtype: 'City',
                            cityName: 'London',
                            leaf: true
                        }]
                    }, {
                        mtype: 'Country',
                        countryName: 'Netherlands',
                        children: [{
                            mtype: 'spec.City',
                            cityName: 'Amsterdam',
                            leaf: true
                        }, {
                            mtype: 'City',
                            cityName: 'Haaksbergen',
                            leaf: true
                        }]
                    }]
                }]
            };
        });
        afterEach(function() {
            Ext.undefine('spec.Territory');
            Ext.undefine('spec.Country');
            Ext.undefine('spec.City');
            schema.clear(true);
        });

        it("should use the parentNode's childType to resolve child node models if no typeProperty is used on Reader", function() {

            // Need a special root type which knows about the first level
            Ext.define('spec.World', {
                extend: 'Ext.data.TreeModel',
                childType: 'Territory'
            });
            // Set the childType on the prototypes.
            // So Territory chould always produce Country childNodes and Country should always produce City childNodes.
            spec.Territory.prototype.childType = 'Country';
            spec.Country.prototype.childType = 'City';

            store = new Ext.data.TreeStore({
                root: treeData,
                model: 'spec.World',
                proxy: {
                    type: 'memory'
                }
            });
            var root = store.getRootNode(),
                na = root.childNodes[0],
                emea = root.childNodes[1],
                spain,
                madrid,
                usa = na.childNodes[0],
                rwc = usa.childNodes[0],
                frederick = usa.childNodes[1],
                canada = na.childNodes[1],
                vancouver = canada.childNodes[0],
                toronto = canada.childNodes[1],
                sacramento = usa.appendChild({
                    cityName: 'Sacramento',
                    leaf: true
                });

            // Two top level nodes are North America and Europe, ME, Africa"
            expect(na instanceof spec.Territory).toBe(true);
            expect(emea instanceof spec.Territory).toBe(true);
            expect(na.get('territoryName')).toBe('North America');
            expect(emea.get('territoryName')).toBe('Europe, ME, Africa');

            expect(usa instanceof spec.Country).toBe(true);
            expect(canada instanceof spec.Country).toBe(true);
            expect(usa.get('countryName')).toBe('USA');
            expect(canada.get('countryName')).toBe('Canada');

            expect(rwc instanceof spec.City).toBe(true);
            expect(frederick instanceof spec.City).toBe(true);
            expect(sacramento instanceof spec.City).toBe(true);
            expect(vancouver instanceof spec.City).toBe(true);
            expect(toronto instanceof spec.City).toBe(true);
            expect(rwc.get('cityName')).toBe('Redwood City');
            expect(frederick.get('cityName')).toBe('Frederick, MD');
            expect(sacramento.get('cityName')).toBe('Sacramento');
            expect(vancouver.get('cityName')).toBe('Vancouver');
            expect(toronto.get('cityName')).toBe('Toronto');

            // Check that the Model converts raw configs correctly according to the
            // typeProperty in the TreeStore
            spain = emea.appendChild({
                mtype: 'Country',
                countryName: 'Spain'
            });
            expect(spain instanceof spec.Country).toBe(true);
            expect(spain.get('countryName')).toBe('Spain');

            madrid = spain.appendChild({
                mtype: 'City',
                cityName: 'Madrid'
            });
            expect(madrid instanceof spec.City).toBe(true);
            expect(madrid.get('cityName')).toBe('Madrid');
        });

        it("should use the store's model namespace to resolve child node models if short form typeProperty is used", function() {
            store = new Ext.data.TreeStore({
                model: 'spec.Territory',
                root: treeData,
                proxy: {
                    type: 'memory',
                    reader: {
                        typeProperty: 'mtype'
                    }
                }
            });
            var root = store.getRootNode(),
                na = root.childNodes[0],
                emea = root.childNodes[1],
                spain,
                madrid,
                usa = na.childNodes[0],
                rwc = usa.childNodes[0],
                frederick = usa.childNodes[1],
                canada = na.childNodes[1],
                vancouver = canada.childNodes[0],
                toronto = canada.childNodes[1];

            // Two top level nodes are North America and Europe, ME, Africa"
            expect(na instanceof spec.Territory).toBe(true);
            expect(emea instanceof spec.Territory).toBe(true);
            expect(na.get('territoryName')).toBe('North America');
            expect(emea.get('territoryName')).toBe('Europe, ME, Africa');

            expect(usa instanceof spec.Country).toBe(true);
            expect(canada instanceof spec.Country).toBe(true);
            expect(usa.get('countryName')).toBe('USA');
            expect(canada.get('countryName')).toBe('Canada');

            expect(rwc instanceof spec.City).toBe(true);
            expect(frederick instanceof spec.City).toBe(true);
            expect(vancouver instanceof spec.City).toBe(true);
            expect(toronto instanceof spec.City).toBe(true);
            expect(rwc.get('cityName')).toBe('Redwood City');
            expect(frederick.get('cityName')).toBe('Frederick, MD');
            expect(vancouver.get('cityName')).toBe('Vancouver');
            expect(toronto.get('cityName')).toBe('Toronto');

            // Check that the Model converts raw configs correctly according to the
            // typeProperty in the TreeStore
            spain = emea.appendChild({
                mtype: 'Country',
                countryName: 'Spain'
            });
            expect(spain instanceof spec.Country).toBe(true);
            expect(spain.get('countryName')).toBe('Spain');

            madrid = spain.appendChild({
                mtype: 'City',
                cityName: 'Madrid'
            });
            expect(madrid instanceof spec.City).toBe(true);
            expect(madrid.get('cityName')).toBe('Madrid');
        });

        it("should use the typeProperty's namespace property to resolve model class names", function() {
            var data = Ext.clone(treeData);

            // Remove all usages of namespace.
            // It gets added.
            data.children[0].children[0].children[0].mtype = 'City';
            data.children[0].children[1].children[0].mtype = 'City';
            data.children[1].children[0].children[0].mtype = 'City';
            data.children[1].children[1].children[0].mtype = 'City';

            store = new Ext.data.TreeStore({
                root: data,
                proxy: {
                    type: 'memory',
                    reader: {
                        typeProperty: {
                            name: 'mtype',
                            namespace: 'spec'
                        }
                    }
                }
            });
            var root = store.getRootNode(),
                na = root.childNodes[0],
                emea = root.childNodes[1],
                spain,
                madrid,
                usa = na.childNodes[0],
                rwc = usa.childNodes[0],
                frederick = usa.childNodes[1],
                canada = na.childNodes[1],
                vancouver = canada.childNodes[0],
                toronto = canada.childNodes[1];

            expect(na instanceof spec.Territory).toBe(true);
            expect(emea instanceof spec.Territory).toBe(true);
            expect(na.get('territoryName')).toBe('North America');
            expect(emea.get('territoryName')).toBe('Europe, ME, Africa');

            expect(usa instanceof spec.Country).toBe(true);
            expect(canada instanceof spec.Country).toBe(true);
            expect(usa.get('countryName')).toBe('USA');
            expect(canada.get('countryName')).toBe('Canada');

            expect(rwc instanceof spec.City).toBe(true);
            expect(frederick instanceof spec.City).toBe(true);
            expect(vancouver instanceof spec.City).toBe(true);
            expect(toronto instanceof spec.City).toBe(true);
            expect(rwc.get('cityName')).toBe('Redwood City');
            expect(frederick.get('cityName')).toBe('Frederick, MD');
            expect(vancouver.get('cityName')).toBe('Vancouver');
            expect(toronto.get('cityName')).toBe('Toronto');

            // Check that the Model converts raw configs correctly according to the
            // typeProperty in the TreeStore
            spain = emea.appendChild({
                mtype: 'Country',
                countryName: 'Spain'
            });
            expect(spain instanceof spec.Country).toBe(true);
            expect(spain.get('countryName')).toBe('Spain');

            madrid = spain.appendChild({
                mtype: 'City',
                cityName: 'Madrid'
            });
            expect(madrid instanceof spec.City).toBe(true);
            expect(madrid.get('cityName')).toBe('Madrid');
        });

        it("should use the typeProperty's map property to resolve model class names", function() {
            store = new Ext.data.TreeStore({
                root: treeData,
                proxy: {
                    type: 'memory',
                    reader: {
                        typeProperty: {
                            name: 'mtype',
                            map: {
                                Territory: 'Territory',
                                Country: 'Country',
                                City: 'City'
                            }
                        }
                    }
                }
            });
            var root = store.getRootNode(),
                na = root.childNodes[0],
                emea = root.childNodes[1],
                spain,
                madrid,
                usa = na.childNodes[0],
                rwc = usa.childNodes[0],
                frederick = usa.childNodes[1],
                canada = na.childNodes[1],
                vancouver = canada.childNodes[0],
                toronto = canada.childNodes[1];

            expect(na instanceof spec.Territory).toBe(true);
            expect(emea instanceof spec.Territory).toBe(true);
            expect(na.get('territoryName')).toBe('North America');
            expect(emea.get('territoryName')).toBe('Europe, ME, Africa');

            expect(usa instanceof spec.Country).toBe(true);
            expect(canada instanceof spec.Country).toBe(true);
            expect(usa.get('countryName')).toBe('USA');
            expect(canada.get('countryName')).toBe('Canada');

            expect(rwc instanceof spec.City).toBe(true);
            expect(frederick instanceof spec.City).toBe(true);
            expect(vancouver instanceof spec.City).toBe(true);
            expect(toronto instanceof spec.City).toBe(true);
            expect(rwc.get('cityName')).toBe('Redwood City');
            expect(frederick.get('cityName')).toBe('Frederick, MD');
            expect(vancouver.get('cityName')).toBe('Vancouver');
            expect(toronto.get('cityName')).toBe('Toronto');

            // Check that the Model converts raw configs correctly according to the
            // typeProperty in the TreeStore
            spain = emea.appendChild({
                mtype: 'Country',
                countryName: 'Spain'
            });
            expect(spain instanceof spec.Country).toBe(true);
            expect(spain.get('countryName')).toBe('Spain');

            madrid = spain.appendChild({
                mtype: 'City',
                cityName: 'Madrid'
            });
            expect(madrid instanceof spec.City).toBe(true);
            expect(madrid.get('cityName')).toBe('Madrid');
        });

        it("should CALL the typeProperty to resolve model class names if it is a function", function() {
            var typePropertyScope;

            store = new Ext.data.TreeStore({
                root: treeData,
                proxy: {
                    type: 'memory',
                    reader: {
                        typeProperty: function(rawData) {
                            typePropertyScope = this;

                            return Ext.String.startsWith(rawData.mtype, 'spec.') ? rawData.mtype : 'spec.' + rawData.mtype;
                        }
                    }
                }
            });
            var root = store.getRootNode(),
                na = root.childNodes[0],
                emea = root.childNodes[1],
                spain,
                madrid,
                usa = na.childNodes[0],
                rwc = usa.childNodes[0],
                frederick = usa.childNodes[1],
                canada = na.childNodes[1],
                vancouver = canada.childNodes[0],
                toronto = canada.childNodes[1];

            // The typeProperty function must be called in the scope of the Reader
            expect(typePropertyScope === store.getProxy().getReader());

            expect(na instanceof spec.Territory).toBe(true);
            expect(emea instanceof spec.Territory).toBe(true);
            expect(na.get('territoryName')).toBe('North America');
            expect(emea.get('territoryName')).toBe('Europe, ME, Africa');

            expect(usa instanceof spec.Country).toBe(true);
            expect(canada instanceof spec.Country).toBe(true);
            expect(usa.get('countryName')).toBe('USA');
            expect(canada.get('countryName')).toBe('Canada');

            expect(rwc instanceof spec.City).toBe(true);
            expect(frederick instanceof spec.City).toBe(true);
            expect(vancouver instanceof spec.City).toBe(true);
            expect(toronto instanceof spec.City).toBe(true);
            expect(rwc.get('cityName')).toBe('Redwood City');
            expect(frederick.get('cityName')).toBe('Frederick, MD');
            expect(vancouver.get('cityName')).toBe('Vancouver');
            expect(toronto.get('cityName')).toBe('Toronto');

            // Check that the Model converts raw configs correctly according to the
            // typeProperty in the TreeStore
            spain = emea.appendChild({
                mtype: 'Country',
                countryName: 'Spain'
            });
            expect(spain instanceof spec.Country).toBe(true);
            expect(spain.get('countryName')).toBe('Spain');

            madrid = spain.appendChild({
                mtype: 'City',
                cityName: 'Madrid'
            });
            expect(madrid instanceof spec.City).toBe(true);
            expect(madrid.get('cityName')).toBe('Madrid');
        });
    });

    describe('heterogeneous TreeStores with different proxy for each type', function() {
        var schema;

        beforeEach(function() {
            schema = Ext.data.Model.schema;
            schema.setNamespace('spec');

            Ext.define('spec.Root', {
                extend: 'Ext.data.TreeModel',
                proxy: {
                    model: 'spec.Territory',
                    type: 'ajax',
                    url: 'territories'
                }
            });
            Ext.define('spec.Territory', {
                extend: 'Ext.data.TreeModel',
                idProperty: 'territoryName',
                fields: [{
                    name: 'territoryName',
                    mapping: 'territoryName',
                    convert: undefined
                }],
                proxy: {
                    model: 'spec.Country',
                    type: 'ajax',
                    url: 'countries'
                }
            });
            Ext.define('spec.Country', {
                extend: 'Ext.data.TreeModel',
                idProperty: 'countryName',
                fields: [{
                    name: 'countryName',
                    mapping: 'countryName',
                    convert: undefined
                }],
                proxy: {
                    model: 'spec.City',
                    type: 'ajax',
                    url: 'cities'
                }
            });
            Ext.define('spec.City', {
                extend: 'Ext.data.TreeModel',
                idProperty: 'cityName',
                fields: [{
                    name: 'cityName',
                    mapping: 'cityName',
                    convert: undefined
                }],
                proxy: null
            });
        });
        afterEach(function() {
            Ext.undefine('spec.Territory');
            Ext.undefine('spec.Country');
            Ext.undefine('spec.City');
            Ext.undefine('spec.Root');
            schema.clear(true);
        });

        it("should load the nodes from different URLs", function() {
            // Use generic TreeModel as root.
            store = new Ext.data.TreeStore({
                root: {
                    expanded: true
                },
                model: 'spec.Root'
            });
            var root = store.getRootNode(),
                northAmerica,
                usa;

            // The latest Ajax request url must be 'territories'
            expect(Ext.String.startsWith(Ext.Ajax.requests[Ext.Ajax.latestId].url, 'territories')).toBe(true);

            // Respond to root node's expansion request
            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode([{
                    territoryName: 'North America'
                }, {
                    territoryName: 'Europe, ME, Africa'
                }])
            });

            expect((northAmerica = root.childNodes[0]) instanceof spec.Territory).toBe(true);
            expect(root.childNodes[1] instanceof spec.Territory).toBe(true);

            // Expand North America
            northAmerica.expand();

            // The latest Ajax request url must be 'countries'
            expect(Ext.String.startsWith(Ext.Ajax.requests[Ext.Ajax.latestId].url, 'countries')).toBe(true);

            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode([{
                    countryName: 'U.S.A.'
                }, {
                    countryName: 'Canada'
                }])
            });
            expect((usa = northAmerica.childNodes[0]) instanceof spec.Country).toBe(true);
            expect(northAmerica.childNodes[1] instanceof spec.Country).toBe(true);

            // Expand USA
            usa.expand();

            // The latest Ajax request url must be 'cities'
            expect(Ext.String.startsWith(Ext.Ajax.requests[Ext.Ajax.latestId].url, 'cities')).toBe(true);

            Ext.Ajax.mockComplete({
                status: 200,
                responseText: Ext.encode([{
                    cityName: 'Redwood City',
                    leaf: true
                }, {
                    cityName: 'Frederick, MD',
                    leaf: true
                }])
            });
            expect(usa.childNodes[0] instanceof spec.City).toBe(true);
            expect(usa.childNodes[1] instanceof spec.City).toBe(true);
        });
    });

    describe('Filtering, and isLastVisible status', function() {
        var rec0, rec1, rec2;

        beforeEach(function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    id: 0,
                    name: 'Root Node',
                    children: [{
                        name: 'Foo'
                    }, {
                        name: 'Bar'
                    }, {
                        name: 'Bletch'
                    }]
                }
            });
            rec0 = store.getAt(0);
            rec1 = store.getAt(1);
            rec2 = store.getAt(2);

        });
        it('should correctly ascertain whether a node is the last visible node.', function() {

            // Verify initial conditions
            expect(store.getCount()).toEqual(3);
            expect(rec0.isLastVisible()).toBe(false);
            expect(rec1.isLastVisible()).toBe(false);
            expect(rec2.isLastVisible()).toBe(true);

            // Only first node should now be visible
            store.filter({
                property: 'name',
                value: 'Foo'
            });

            // Now there's only 1, and it should report that it is the last visible
            expect(store.getCount()).toEqual(1);
            expect(rec0.isLastVisible()).toBe(true);
        });
    });

    describe('TreeNode drop with locally created (phantom) nodes', function() {
        var n1, n2, n3;

        beforeEach(function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    id: 0,
                    name: 'Root Node',
                    children: [{
                        name: 'Foo',
                        expanded: true,
                        children: []
                    }, {
                        name: 'Bar'
                    }, {
                        name: 'Bletch'
                    }]
                }
            });

            n1 = store.getAt(0);
        });

        it('should remove all descendants. All nodes are phantom, so there should be an empty removed list', function() {
            var records;

            // "Foo", "Bar" and "Bletch" present
            expect(store.getCount()).toBe(3);

            // Append to expanded node "Foo"
            n2 = n1.appendChild({
                name: 'Zarg',
                expanded: true
            });
            n3 = n2.appendChild({
                name: 'Blivit',
                leaf: true
            });

            // The added nodes should be in the store; they are added to expanded nodes.
            expect(store.getCount()).toBe(5);

            // n1, its child n2("zarg"), and grandchild n3("blivit") will all be removed by this operation.
            n1.drop();

            records = store.getRemovedRecords();

            // NO records should appear in the removed list because they are all phantom
            // having been defined using client-side data.
            expect(records.length).toBe(0);

            // n1("Foo") and its descendants, "Zarg" and "Blivit" should be removed.
            // Only "Bar" and "Bletch" present now.
            expect(store.getCount()).toBe(2);
        });
    });

    describe('TreeNode drop', function() {
        var n1, n2, n3;

        beforeEach(function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    id: 0,
                    name: 'Root Node'
                },
                // Read these through a Proxy because we are expecting them NOT to be phantom
                proxy: {
                    type: 'memory',
                    data: [{
                        name: 'Foo',
                        expanded: true,
                        children: []
                    }, {
                        name: 'Bar'
                    }, {
                        name: 'Bletch'
                    }]
                }
            });

            n1 = store.getAt(0);
        });

        it('should remove all descendants, and add non-phantom descendants to removed list', function() {
            var records;

            // "Foo", "Bar" and "Bletch" present
            expect(store.getCount()).toBe(3);

            // Append to expanded node "Foo"
            n2 = n1.appendChild({
                name: 'Zarg',
                expanded: true
            });
            n3 = n2.appendChild({
                name: 'Blivit',
                leaf: true
            });

            // The added nodes should be in the store; they are added to expanded nodes.
            expect(store.getCount()).toBe(5);

            // n1, its child n2("zarg"), and grandchild n3("blivit") will all be removed by this operation.
            n1.drop();

            records = store.getRemovedRecords();

            // Only the non-phantom node "Foo" should be in the removed list.
            // The two newly added phantoms just disappear.
            // Only "Bar" and "Bletch" present now.
            expect(records.length).toBe(1);
            expect(records[0] === n1).toBe(true);

            // n1("Foo") and its descendants, "Zarg" and "Blivit" should be removed.
            // Only "Bar" and "Bletch" present now.
            expect(store.getCount()).toBe(2);
        });

        it('should remove deleted records from removed list if they get added back', function() {
            var bletchNode = store.findNode('name', 'Bletch'),
                bletchParent = bletchNode.parentNode;

            // Queue the node for destruction upon the next store sync.
            bletchNode.drop();

            // Should be in destruction queue
            expect(Ext.Array.contains(store.getRemovedRecords(), bletchNode)).toBe(true);

            // Change ourt mind, add it back
            bletchParent.appendChild(bletchNode);

            // Should NOT be in destruction queue
            expect(Ext.Array.contains(store.getRemovedRecords(), bletchNode)).toBe(false);
        });
    });

    describe("parentIdProperty", function() {
        var root;

        beforeEach(function() {
            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {},
                parentIdProperty: 'foo'
            });
            root = store.getRoot();
        });

        afterEach(function() {
            root = null;
        });

        function makeNode(id, parent) {
            var o = {
                id: id,
                expanded: true
            };

            if (arguments.length > 1) {
                o.foo = parent;
            }

            return o;
        }

        it("should append items without a parentId to the loaded item", function() {
            root.expand();
            completeWithData([
                makeNode(1),
                makeNode(2),
                makeNode(3)
             ]);

            var childNodes = root.childNodes;

            expect(byId(1)).toBe(childNodes[0]);
            expect(byId(2)).toBe(childNodes[1]);
            expect(byId(3)).toBe(childNodes[2]);
        });

        it("should allow a parentId of 0", function() {
            root.expand();
            completeWithData([
                makeNode(0),
                makeNode(1, 0)
             ]);

            expect(byId(1)).toBe(byId(0).childNodes[0]);
        });

        it("should throw an exception if a matching parent is not found", function() {
            root.expand();
            expect(function() {
                completeWithData([
                    makeNode(1),
                    makeNode(2, 100)
                ]);
            }).toThrow();
        });

        it("should add children to their parent nodes, retaining any implied order", function() {
            root.expand();
            completeWithData([
                makeNode('c21', 'c2'),
                makeNode('a'),
                makeNode('c2', 'c'),
                makeNode('a1', 'a'),
                makeNode('c'),
                makeNode('b'),
                makeNode('b1', 'b'),
                makeNode('a2', 'a'),
                makeNode('c1', 'c'),
                makeNode('c22', 'c2'),
                makeNode('a32', 'a3'),
                makeNode('a31', 'a3'),
                makeNode('a21', 'a2'),
                makeNode('b12', 'b1'),
                makeNode('b11', 'b1'),
                makeNode('a3', 'a'),
                makeNode('a211', 'a21')
            ]);

            expectOrder(root, ['a', 'c', 'b']);

            expectOrder(byId('a'), ['a1', 'a2', 'a3']);
            expectOrder(byId('a2'), ['a21']);
            expectOrder(byId('a21'), ['a211']);

            expectOrder(byId('b'), ['b1']);
            expectOrder(byId('b1'), ['b12', 'b11']);

            expectOrder(byId('c'), ['c2', 'c1']);
            expectOrder(byId('c2'), ['c21', 'c22']);
        });

        describe("sorting", function() {
            it("should sort nodes via sorter", function() {
                store.getSorters().add('id');
                root.expand();
                completeWithData([
                    makeNode('c'),
                    makeNode('a'),
                    makeNode('b'),
                    makeNode('c3', 'c'),
                    makeNode('b3', 'b'),
                    makeNode('a3', 'a'),
                    makeNode('c2', 'c'),
                    makeNode('b2', 'b'),
                    makeNode('a2', 'a'),
                    makeNode('c1', 'c'),
                    makeNode('b1', 'b'),
                    makeNode('a1', 'a')
                ]);

                expectOrder(root, ['a', 'b', 'c']);

                expectOrder(byId('a'), ['a1', 'a2', 'a3']);
                expectOrder(byId('b'), ['b1', 'b2', 'b3']);
                expectOrder(byId('c'), ['c1', 'c2', 'c3']);
            });

            it("should do an index sort if required", function() {
                root.expand();
                completeWithData([
                    { id: 'a', index: 2 },
                    { id: 'b', index: 1 },
                    { id: 'c', index: 0 },
                    { id: 'a1', foo: 'a', index: 2 },
                    { id: 'a2', foo: 'a', index: 1 },
                    { id: 'a3', foo: 'a', index: 0 }
                ]);

                expectOrder(root, ['c', 'b', 'a']);
                expectOrder(byId('a'), ['a3', 'a2', 'a1']);
            });
        });

        describe("filtering", function() {
            it("should apply filters", function() {
                var allowed = ['a', 'c', 'a2', 'c1', 'c11', 'c13'];

                store.getFilters().add({
                    filterFn: function(node) {
                        return Ext.Array.indexOf(allowed, node.id) > -1;
                    }
                });

                root.expand();
                completeWithData([
                    makeNode('a'),
                    makeNode('b'),
                    makeNode('c'),
                    makeNode('a1', 'a'),
                    makeNode('a2', 'a'),
                    makeNode('a3', 'a'),
                    makeNode('b1', 'b'),
                    makeNode('b2', 'b'),
                    makeNode('b3', 'b'),
                    makeNode('c1', 'c'),
                    makeNode('c11', 'c1'),
                    makeNode('c12', 'c1'),
                    makeNode('c13', 'c1'),
                    makeNode('c2', 'c'),
                    makeNode('c3', 'c')
                ]);

                expect(store.isVisible(byId('a'))).toBe(true);
                expect(store.isVisible(byId('a1'))).toBe(false);
                expect(store.isVisible(byId('a2'))).toBe(true);
                expect(store.isVisible(byId('a3'))).toBe(false);

                expect(store.isVisible(byId('b'))).toBe(false);
                expect(store.isVisible(byId('b1'))).toBe(false);
                expect(store.isVisible(byId('b2'))).toBe(false);
                expect(store.isVisible(byId('b3'))).toBe(false);

                expect(store.isVisible(byId('c'))).toBe(true);
                expect(store.isVisible(byId('c1'))).toBe(true);
                expect(store.isVisible(byId('c11'))).toBe(true);
                expect(store.isVisible(byId('c12'))).toBe(false);
                expect(store.isVisible(byId('c13'))).toBe(true);
                expect(store.isVisible(byId('c2'))).toBe(false);
                expect(store.isVisible(byId('c3'))).toBe(false);
            });
        });
    });

    describe('loading inline data with no configured root node', function() {
        it('should run without throwing an error', function() {
            expect(function() {
                new Ext.data.TreeStore({
                    fields: ['name', 'text', 'id', 'parentId'],
                    parentIdProperty: 'parentId',
                    data: [{
                        id: 1,
                        name: 'A',
                        value: 10,
                        parentId: null
                    }, {
                        id: 2,
                        name: 'B',
                        value: 12,
                        parentId: 1,
                        leaf: true
                    }]
                }).load();
            }).not.toThrow();
        });
    });

    describe("setting the root", function() {
        describe("via configuration", function() {
            describe("with a model config", function() {
                beforeEach(function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        root: {}
                    });
                });

                it("should set the root property", function() {
                    expect(store.getRoot().get('root')).toBe(true);
                });

                it("should have the treeStore available", function() {
                    var root = store.getRoot();

                    expect(root.getTreeStore()).toBe(store);
                });
            });

            describe("with a model instance", function() {
                var root;

                beforeEach(function() {
                    root = new NodeModel();
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        root: root
                    });
                });

                afterEach(function() {
                    root = null;
                });

                it("should set the root property", function() {
                    expect(root.get('root')).toBe(true);
                });

                it("should have the treeStore available", function() {
                    expect(root.getTreeStore()).toBe(store);
                });
            });
        });

        describe("after creation", function() {
            describe("with a model config", function() {
                beforeEach(function() {
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        root: {}
                    });
                });

                it("should set the root property", function() {
                    var oldRoot = store.getRoot();

                    store.setRoot({
                        id: 'foo'
                    });
                    expect(oldRoot.get('root')).toBe(false);
                    expect(store.getRoot().get('root')).toBe(true);
                    expect(store.getRoot().id).toBe('foo');
                });

                it("should have the treeStore available", function() {
                    var oldRoot = store.getRoot();

                    store.setRoot({
                        id: 'foo'
                    });
                    expect(oldRoot.getTreeStore()).toBeNull();
                    expect(store.getRoot().getTreeStore()).toBe(store);
                });
            });

            describe("with a model instance", function() {
                var root, oldRoot;

                beforeEach(function() {
                    root = new NodeModel();
                    store = new Ext.data.TreeStore({
                        model: NodeModel,
                        root: {}
                    });
                    oldRoot = store.getRoot();
                });

                afterEach(function() {
                    oldRoot = root = null;
                });

                it("should set the root property", function() {
                    store.setRoot(root);
                    expect(oldRoot.get('root')).toBe(false);
                    expect(store.getRoot().get('root')).toBe(true);
                    expect(store.getRoot()).toBe(root);
                });

                it("should have the treeStore available", function() {
                    store.setRoot(root);
                    expect(oldRoot.getTreeStore()).toBeNull();
                    expect(store.getRoot().getTreeStore()).toBe(store);
                });
            });
        });
    });

    describe('Changing root node', function() {
        it('should clear the root property', function() {
            store = new Ext.data.TreeStore({
                root: {
                    text: 'Root',
                    expanded: true,
                    children: [{
                        text: 'A',
                        leaf: true
                    }, {
                        text: 'B',
                        leaf: true
                    }]
                }
            });

            var oldRoot = store.getRootNode();

            expect(oldRoot.get('root')).toBe(true);

            store.setRoot({
                text: 'NewRoot',
                expanded: true,
                children: [{
                    text: 'New A',
                    leaf: true
                }, {
                    text: 'New B',
                    leaf: true
                }]
            });

            expect(oldRoot.get('root')).toBe(false);

        });
    });

    describe('commitChanges', function() {
        beforeEach(function() {
            makeStore([{
                text: 'Foo',
                leaf: true
            }]);
        });

        it('should clear the removed collection', function() {
            var root = store.getRoot();

            root.removeChild(root.getChildAt(0));

            store.commitChanges();

            expect(store.removedNodes.length).toBe(0);
        });
    });

    describe("proxy", function() {
        it("should use the model's memory proxy when no proxy is defined on the store", function() {
            store = new Ext.data.TreeStore({
                root: { text: 'Foo' }
            });
            expect(store.getProxy().isMemoryProxy).toBe(true);
            expect(store.getProxy()).toBe(store.model.getProxy());
        });

        it("should set the store's proxy on the model", function() {
            store = new Ext.data.TreeStore({
                root: { text: 'Foo' },
                proxy: {
                    type: 'ajax',
                    url: 'foo'
                }
            });
            expect(store.getProxy().isAjaxProxy).toBe(true);
            expect(store.getProxy().url).toBe('foo');
            expect(store.getProxy()).toBe(store.model.getProxy());
        });
    });

    describe('rejected changes', function() {
        // Note that we don't actually need to remove a node to test this.
        function doTests(rootVisible) {
            describe('rootVisible = ' + rootVisible, function() {
                it('should not include the root node', function() {
                    makeStore([{
                        children: [2, 3]
                    }], {
                        rootVisible: rootVisible
                    });

                    expect(Ext.Array.contains(store.getRejectRecords(), store.getRoot())).toBe(false);
                });
            });
        }

        doTests(true);
        doTests(false);
    });

    describe('collect', function() {
        beforeEach(function() {
            store = new Ext.data.TreeStore({
                filterer: 'bottomup',
                fields: ['name'],
                asynchronousLoad: false,
                root: {
                    expanded: true,
                    children: dummyData.children
                }
            });
        });

        describe('with collapse option', function() {
            it('should collect values', function() {
                var result = store.collect('name', {
                    collapsed: true
                });

                expect(result).toEqual(["aaa", "bbb", "ccc", "ddd", "eee", "fff", "ggg"]);
            });
            it('should honour filters if bypassFilters not passed', function() {
                store.filter({
                    property: 'name',
                    operator: '=',
                    value: 'ddd'
                });
                expect(store.getCount()).toBe(1);
                var result = store.collect('name', {
                    collapsed: true
                });

                // bottom up filtering. Path nodes are visible.
                expect(result).toEqual(["bbb", "ddd"]);
            });
            it('should collect values regardless of filter if filtered:true', function() {
                store.filter({
                    property: 'name',
                    operator: '=',
                    value: 'ddd'
                });
                expect(store.getCount()).toBe(1);
                var result = store.collect('name', {
                    filtered: true,
                    collapsed: true
                });

                // Filters bypassed, all nodes collected
                expect(result).toEqual(["aaa", "bbb", "ccc", "ddd", "eee", "fff", "ggg"]);
            });
        });

        describe('without collapse option', function() {
            it('should collect values', function() {
                var result = store.collect('name');

                expect(result).toEqual(["aaa", "bbb", "fff"]);
            });
            it('should honour filters if bypassFilters not passed', function() {
                store.filter({
                    property: 'name',
                    operator: '=',
                    value: 'ddd'
                });
                expect(store.getCount()).toBe(1);
                var result = store.collect('name');

                // bottom up filtering. Path nodes are visible if path is expanded.
                expect(result).toEqual(["bbb"]);
            });
            it('should collect values regardless of filter if filtered:true', function() {
                store.filter({
                    property: 'name',
                    operator: '=',
                    value: 'ddd'
                });
                expect(store.getCount()).toBe(1);
                var result = store.collect('name', {
                    filtered: true
                });

                // Filters bypassed, all nodes collected if path to them is expanded
                expect(result).toEqual(["aaa", "bbb", "fff"]);
            });
        });
    });

    describe('each', function() {
        var result,
            collect = function(bypassFilters, bypassCollapsed) {
                store.each(function(node) {
                    result.push(node.get('name'));
                }, null, {
                    filtered: bypassFilters,
                    collapsed: bypassCollapsed
                });
            };

        beforeEach(function() {
            store = new Ext.data.TreeStore({
                filterer: 'bottomup',
                fields: ['name'],
                asynchronousLoad: false,
                root: {
                    expanded: true,
                    children: dummyData.children,
                    name: 'root'
                }
            });
            result = [];
        });

        describe('without bypassCollapsed', function() {
            it('should visit all nodes which are below expanded ancestors', function() {
                collect();
                expect(result).toEqual(["aaa", "bbb", "fff"]);
            });
            it('should honour filters which are below expanded ancestors if bypassFilters not passed', function() {
                store.filter({
                    property: 'name',
                    operator: '=',
                    value: 'ddd'
                });
                collect();

                // bottom up filtering. Path nodes are visible. "bbb" is collapsed, so the filtered in "ddd" is not present.
                expect(result).toEqual(["bbb"]);
            });
            it('should collect values which are below expanded ancestors regardless of filter if bypassFilters passed', function() {
                store.filter({
                    property: 'name',
                    operator: '=',
                    value: 'ddd'
                });
                collect(true);

                // Filters bypassed, only "bbb" is visible because its child, "ddd" is filtered in, but "bbb" is collapsed, do "ddd" is not present.
                expect(result).toEqual(["bbb"]);
            });
        });

        describe('with bypassCollapsed', function() {
            it('should visit all nodes', function() {
                collect(false, true);
                expect(result).toEqual(["root", "aaa", "bbb", "ccc", "ddd", "eee", "fff", "ggg"]);
            });
            it('should honour filters if bypassFilters not passed', function() {
                store.filter({
                    property: 'name',
                    operator: '=',
                    value: 'ddd'
                });
                collect(false, true);

                // bottom up filtering. Path nodes are visible.
                expect(result).toEqual(["root", "bbb", "ddd"]);
            });
            it('should collect values regardless of filter if bypassFilters passed', function() {
                store.filter({
                    property: 'name',
                    operator: '=',
                    value: 'ddd'
                });
                collect(true, true);

                // Filters bypassed, all nodes visited
                expect(result).toEqual(["root", "aaa", "bbb", "ccc", "ddd", "eee", "fff", "ggg"]);
            });
        });
    });

    describe('loading locally filtered TreeStore', function() {
        it('should not throw an error if a local function filter is applied', function() {
            var opFilters;

            store = new Ext.data.TreeStore({
                root: {
                    text: 'Root'
                },
                listeners: {
                    beforeload: function(store, operation) {
                        opFilters = operation.getFilters();
                    }
                }
            });
            loadStore(store);
            expect(store.loadCount).toBe(1);
            expect(store.isLoaded()).toBe(true);

            store.filterBy(function(record, id) { // Simple filter that includes all
                return true;
            });

            // Should run without error.
            store.reload();
            completeWithData(dummyData);
            expect(store.loadCount).toBe(2);

            // Filter should not be part of the operation
            expect(opFilters).toBeUndefined();
        });
    });

    describe('Loading', function() {
        it('should not pass paging parameters', function() {
            store = new Ext.data.TreeStore({
                root: {
                    text: 'Root'
                },
                listeners: {
                    beforeload: function(store, operation) {
                        expect(operation.getStart()).toBeUndefined();
                        expect(operation.getLimit()).toBeUndefined();
                        expect(operation.getPage()).toBeUndefined();
                    }
                }
            });
            loadStore(store);
        });
    });

    describe('node traversal with intervening filtered nodes', function() {
        function getByText(text) {
            return store.findNode('text', text);
        }

        function getPreviousVisibleNode(text) {
            return store.getAt(store.indexOfPreviousVisibleNode(getByText(text).previousSibling));
        }

        beforeEach(function() {
//      Make a tree like this:
//            Root
//            ├top1
//            │ ├top1/1
//            │ │ └top/1/1/2
//            │ │  ├top1/1/2/1
//            │ │  ├top1/1/2/2
//            │ │  └top1/1/2/3<filtered out>
//            ├top2<filtered out>
//            │ ├This will be invisible1
//            │ ├This will be invisible2
//            │ ├This will be invisible3
//            │ ├This will be invisible4
//            │ ├This will be invisible5
//            │ └This will be invisible6
//            └top3
            store = new Ext.data.TreeStore({
                rootVisible: true,
                root: {
                    text: 'Root',
                    expanded: true,
                    children: [{
                        text: 'top1',
                        expanded: true,
                        children: [{
                            text: 'top1/1',
                            expanded: true,
                            children: [{
                                text: 'top1/1/2',
                                expanded: true,
                                children: [{
                                    text: 'top1/1/2/1'
                                }, {
                                    text: 'top1/1/2/2'
                                }, {
                                    text: 'top1/1/2/3',
                                    exclude: true
                                }]
                            }]
                        }]
                    }, {
                        text: 'top2',
                        expanded: true,
                        exclude: true,
                        children: [{
                            text: 'This will be invisible1'
                        }, {
                            text: 'This will be invisible2'
                        }, {
                            text: 'This will be invisible3'
                        }, {
                            text: 'This will be invisible4'
                        }, {
                            text: 'This will be invisible5'
                        }, {
                            text: 'This will be invisible6'
                        }]
                    }, {
                        text: 'top3'
                    }]
                },
                filters: function(rec) {
                    return rec.get('exclude') !== true;
                }
            });
        });

        it('should correctly find the store index of the previous visible node', function() {
            // Simplest case of it being the actual previous sibling
            expect(getPreviousVisibleNode('top1/1/2/3')).toBe(getByText('top1/1/2/2'));

            expect(getPreviousVisibleNode('top3')).toBe(getByText('top1/1/2/2'));

            // Restore top2 and its first child to visibility
            getByText('top2').set('exclude', false);
            getByText('This will be invisible2').set('exclude', true);
            getByText('This will be invisible3').set('exclude', true);
            getByText('This will be invisible4').set('exclude', true);
            getByText('This will be invisible5').set('exclude', true);
            getByText('This will be invisible6').set('exclude', true);

            expect(getPreviousVisibleNode('top3')).toBe(getByText('This will be invisible1'));
            expect(getPreviousVisibleNode('This will be invisible6')).toBe(getByText('This will be invisible1'));

            getByText('top1').set('exclude', true);
            getByText('top2').set('exclude', true);

            expect(getPreviousVisibleNode('top3')).toBe(getByText('Root'));
        });
    });

    // https://sencha.jira.com/browse/EXTJS-17902
    describe("reloading from empty dataset", function() {
        var root;

        beforeEach(function() {
            store = new Ext.data.TreeStore({
                fields: ['id', 'name'],
                proxy: 'memory',
                root: {
                    expanded: true,
                    children: [
                        { id: 1, name: 'child 1' },
                        { id: 2, name: 'child 2' }
                    ]
                }
            });

            store.getProxy().data = [];
            store.load();

            root = store.getRootNode();
        });

        afterEach(function() {
            root = null;
        });

        it("should clear firstChild on the root node", function() {
            expect(root.firstChild).toBe(null);
        });

        it("should clear lastChild on the root node", function() {
            expect(root.lastChild).toBe(null);
        });

        it("should clear childNodes", function() {
            expect(root.childNodes.length).toBe(0);
        });

        it("should clear internal data children", function() {
            expect(root.getData().children).toBe(null);
        });
    });

    describe("findNode", function() {
        it("should be able to find a node by id", function() {
            makeStore([{ id: 1 }, { id: 2 }, { id: 3 }]);

            var root = store.getRoot();

            expect(store.findNode('id', 1)).toBe(root.childNodes[0]);
            expect(store.findNode('id', 2)).toBe(root.childNodes[1]);
            expect(store.findNode('id', 3)).toBe(root.childNodes[2]);
        });
    });

    describe('Model register/unregister methods', function() {
        var RegisteredNode = Ext.define(null, {
            extend: 'Ext.data.TreeModel',

            onRegisterTreeNode: function() {
                registeredNodeCount++;
                registerCount++;
            },
            onUnregisterTreeNode: function() {
                registeredNodeCount--;
                unregisterCount++;
            }
        }),
        registeredNodeCount = 0,
        registerCount = 0,
        unregisterCount = 0;

        it('should call register/unregister methods', function() {
            makeStore([], {
                model: RegisteredNode
            });

            // Just the root node registered
            expect(registeredNodeCount).toBe(1);
            expect(registerCount).toBe(1);
            expect(unregisterCount).toBe(0);

            root.appendChild({
                children: [{
                    children: [{
                        id: 'removeMe'
                    }]
                }]
            });

            // Now three descendants in addition to the root
            expect(registeredNodeCount).toBe(4);
            expect(registerCount).toBe(4);
            expect(unregisterCount).toBe(0);

            // Drop one
            store.getNodeById('removeMe').drop();

            // Only three now registered and the unregister count sohuld have gone up
            expect(registeredNodeCount).toBe(3);
            expect(registerCount).toBe(4);
            expect(unregisterCount).toBe(1);

            // Remove all three remaining nodes and add a new root
            store.setRoot({

            });

            // Only one node should be registered.
            // Register count should be up by one, unregister count should be up by three
            expect(registeredNodeCount).toBe(1);
            expect(registerCount).toBe(5);
            expect(unregisterCount).toBe(4);
        });
    });

    describe('datachanged event', function() {
        it('should not fire events while constructing', function() {
            var spy = jasmine.createSpy();

            store = new Ext.data.TreeStore({
                model: NodeModel,
                root: {
                    expanded: true,
                    children: [{
                        expanded: true,
                        children: [{
                            expanded: true,
                            children: [{
                                expanded: true,
                                children: [{
                                    id: 'deep'
                                }]
                            }]
                        }]
                    }]
                },
                listeners: {
                    add: spy,
                    remove: spy,
                    datachanged: spy,
                    rootchange: spy,
                    refresh: spy
                }
            });
            expect(spy).not.toHaveBeenCalled();
        });
    });

    describe('linear data', function() {
        it('should next correctly with depth values set', function() {
            store = new Ext.data.TreeStore({
                parentIdProperty: 'parent',
                proxy: {
                    type: 'memory',
                    data: [
                        { text: 'Aardvark', id: 'a' },
                        { text: 'Bandicoot', id: 'b', parent: 'a' },
                        { text: 'Crocodile', id: 'c', parent: 'b' }
                    ]
                },
                root: {
                    expanded: true
                }
            });
            root = store.getRoot();
            expect(root.data.depth).toBe(0);
            expect(root.childNodes[0].data.depth).toBe(1);
            expect(root.childNodes[0].childNodes[0].data.depth).toBe(2);
            expect(root.childNodes[0].childNodes[0].childNodes[0].data.depth).toBe(3);
        });
    });

    describe('changing the root node from null to non-null', function() {
        it('should fire the rootchange event', function() {
            var eventFired = false;

            store = new Ext.data.TreeStore({
                root: {
                    text: 'Foo'
                }
            });

            store.setRoot(null);

            store.on('rootchange', function() {
                eventFired = true;
            });

            store.setRoot({
                text: 'Bar'
            });

            expect(eventFired).toBe(true);
        });
    });
});
