topSuite("Ext.view.BoundList", ['Ext.data.ArrayStore'], function() {
    var boundList, store;

    function createBoundList(cfg, data) {
        cfg = cfg || {};
        cfg.displayField = 'name';
        cfg.renderTo = document.body;
        store = cfg.store = new Ext.data.Store({
            autoDestroy: true,
            model: 'spec.View',
            data: data || [{
                name: 'Item1'
            }]
        });
        boundList = new Ext.view.BoundList(cfg);
    }

    beforeEach(function() {
        Ext.define('spec.View', {
            extend: 'Ext.data.Model',
            fields: ['name']
        });
    });

    afterEach(function() {
        Ext.undefine('spec.View');
        Ext.data.Model.schema.clear();
        Ext.destroy(boundList);
        boundList = store = null;
    });

    describe("alternate class name", function() {
        it("should have Ext.BoundList as the alternate class name", function() {
            expect(Ext.view.BoundList.prototype.alternateClassName).toEqual("Ext.BoundList");
        });

        it("should allow the use of Ext.BoundList", function() {
            expect(Ext.BoundList).toBeDefined();
        });
    });

    describe("custom tpl", function() {
        it("should clear the view when using a custom node outside the tpl", function() {
            createBoundList({
                tpl: [
                    '<div class="header">header</div>',
                    '<tpl for=".">',
                        '<li class="x-boundlist-item">{name}</li>',
                    '</tpl>'
                ]
            });
            boundList.refresh();
            boundList.refresh();
            boundList.refresh();
            expect(boundList.getEl().select('.header').getCount()).toBe(1);
        });
    });

    describe("default tpl", function() {
        it("should be an XTemplate", function() {
            createBoundList();

            expect(boundList.tpl.isTemplate).toBe(true);
        });

        it("should generate the correct default tpl", function() {
            createBoundList();

            expect(boundList.tpl.html).toBe('<tpl for="."><li role="option" unselectable="on" class="x-boundlist-item">{name}</li></tpl>');
        });

        it("should correctly render items using the tpl", function() {
            createBoundList({}, [{
                name: 'Item1',
                id: 'itemone'
            }]);

            var nodes = boundList.getNodes(),
                node = nodes[0];

            expect(Ext.fly(node)).toHaveCls('x-boundlist-item');
            expect(node.innerHTML).toBe('Item1');
        });
    });

    describe("modifying the store", function() {
        describe("adding", function() {
            it("should be able to add to an empty BoundList", function() {
                createBoundList({
                }, []);

                // The <li> items should go indide the <ul>
                expect(boundList.getNodeContainer().dom.childNodes.length).toBe(0);
                store.add({
                    name: 'Item1'
                });

                // The <li> items should go indide the <ul>
                expect(boundList.getNodeContainer().dom.childNodes.length).toBe(1);
                var nodes = boundList.getNodes();

                expect(nodes.length).toBe(1);
                expect(nodes[0].innerHTML).toBe('Item1');
            });

            it("should be able to add to the end of a BoundList", function() {
                createBoundList({
                });

                // The <li> items should go indide the <ul>
                expect(boundList.getNodeContainer().dom.childNodes.length).toBe(1);
                store.add({
                    name: 'Item2'
                });

                // The <li> items should go indide the <ul>
                expect(boundList.getNodeContainer().dom.childNodes.length).toBe(2);
                var nodes = boundList.getNodes();

                expect(nodes.length).toBe(2);
                expect(nodes[1].innerHTML).toBe('Item2');
            });

            it("should be able to insert a node at the start of the BoundList", function() {
                createBoundList({
                });

                // The <li> items should go indide the <ul>
                expect(boundList.getNodeContainer().dom.childNodes.length).toBe(1);
                store.insert(0, {
                    name: 'Item2'
                });

                // The <li> items should go indide the <ul>
                expect(boundList.getNodeContainer().dom.childNodes.length).toBe(2);
                var nodes = boundList.getNodes();

                expect(nodes.length).toBe(2);
                expect(nodes[0].innerHTML).toBe('Item2');
            });

            it("should be able to insert a node in the middle of the BoundList", function() {
                createBoundList({
                }, [{
                    name: 'Item1'
                }, {
                    name: 'Item2'
                }, {
                    name: 'Item3'
                }, {
                    name: 'Item4'
                }]);

                // The <li> items should go indide the <ul>
                expect(boundList.getNodeContainer().dom.childNodes.length).toBe(4);
                store.insert(2, {
                    name: 'new'
                });

                // The <li> items should go indide the <ul>
                expect(boundList.getNodeContainer().dom.childNodes.length).toBe(5);
                var nodes = boundList.getNodes();

                expect(nodes.length).toBe(5);
                expect(nodes[2].innerHTML).toBe('new');
            });
        });

        describe("updating", function() {
            it("should update the node content", function() {
                createBoundList({
                });
                store.first().set('name', 'foo');
                var nodes = boundList.getNodes();

                expect(nodes.length).toBe(1);
                expect(nodes[0].innerHTML).toBe('foo');
            });
        });

        describe("removing", function() {
            it("should remove a node from the BoundList", function() {
                createBoundList({
                });
                store.removeAt(0);
                var nodes = boundList.getNodes();

                expect(nodes.length).toBe(0);
            });
        });

        describe("ARIA attributes", function() {
            var nodes;

            beforeEach(function() {
                createBoundList({
                }, []);

                spyOn(boundList, 'refreshAriaAttributes').andCallThrough();

                store.add([{
                    name: 'Item1'
                }, {
                    name: 'Item2'
                }]);

                nodes = boundList.getNodes();
            });

            afterEach(function() {
                nodes = null;
            });

            it("should call refreshAriaAttributes on refresh", function() {
                expect(boundList.refreshAriaAttributes).toHaveBeenCalled();
            });

            it("should not set aria-selected by default", function() {
                expect(nodes[0]).not.toHaveAttr('aria-selected');
            });

            it("should not set aria-setsize by default", function() {
                expect(nodes[0]).not.toHaveAttr('aria-setsize');
            });

            it("should not set aria-posinset by default", function() {
                expect(nodes[0]).not.toHaveAttr('aria-posinset');
            });

            it("should set aria-selected when pickerField is multiSelect", function() {
                boundList.pickerField = { multiSelect: true };

                boundList.refresh();
                nodes = boundList.getNodes();

                expect(nodes[0]).toHaveAttr('aria-selected', 'false');
            });

            describe("paged store", function() {
                beforeEach(function() {
                    spyOn(store, 'getTotalCount').andCallFake(function() { return 42; });

                    boundList.refresh();
                    nodes = boundList.getNodes();
                });

                it("should set aria-setsize", function() {
                    expect(nodes[0]).toHaveAttr('aria-setsize', '42');
                    expect(nodes[1]).toHaveAttr('aria-setsize', '42');
                });

                it("should set aria-posinset", function() {
                    expect(nodes[0]).toHaveAttr('aria-posinset', '0');
                    expect(nodes[1]).toHaveAttr('aria-posinset', '1');
                });
            });

            describe("filtered store", function() {
                beforeEach(function() {
                    spyOn(store, 'isFiltered').andCallFake(function() { return true; });
                    spyOn(store, 'getCount').andCallFake(function() { return 42; });

                    boundList.refresh();
                    nodes = boundList.getNodes();
                });

                it("should set aria-setsize", function() {
                    expect(nodes[0]).toHaveAttr('aria-setsize', '42');
                    expect(nodes[1]).toHaveAttr('aria-setsize', '42');
                });

                it("should set aria-posinset", function() {
                    expect(nodes[0]).toHaveAttr('aria-posinset', '0');
                    expect(nodes[1]).toHaveAttr('aria-posinset', '1');
                });
            });
        });
    });

    describe("highlighting", function() {
        beforeEach(function() {
            var nodes = [],
                i = 1;

            for (; i <= 10; ++i) {
                nodes.push({
                    name: 'Item ' + i
                });
            }

            createBoundList({
                itemCls: 'foo',
                renderTo: Ext.getBody(),
                itemTpl: '{name}',
                overItemCls: 'over'
            }, nodes);
        });

        it("should apply the highlight class to a node", function() {
            boundList.highlightItem(boundList.getNode(0));
            var nodes = boundList.getEl().select('.foo');

            expect(nodes.item(0).hasCls(boundList.overItemCls)).toBe(true);
        });

        it("should remove the highlight on an item", function() {
            boundList.highlightItem(boundList.getNode(0));
            boundList.clearHighlight(boundList.getNode(0));
            var nodes = boundList.getEl().select('.foo');

            expect(nodes.item(0).hasCls(boundList.overItemCls)).toBe(false);
        });

        it("should only have at most one item highlighted", function() {
            boundList.highlightItem(boundList.getNode(0));
            boundList.highlightItem(boundList.getNode(1));
            var nodes = boundList.getEl().select('.foo');

            expect(nodes.item(0).hasCls(boundList.overItemCls)).toBe(false);
            expect(nodes.item(1).hasCls(boundList.overItemCls)).toBe(true);
        });

        it("should keep highlight on an item when updated", function() {
            boundList.highlightItem(boundList.getNode(0));
            boundList.getStore().getAt(0).set('name', 'New');
            var nodes = boundList.getEl().select('.foo');

            expect(nodes.item(0).hasCls(boundList.overItemCls)).toBe(true);
        });

        it("should clear all highlights on refresh", function() {
            boundList.highlightItem(boundList.getNode(0));
            boundList.refresh();
            var nodes = boundList.getEl().select('.foo');

            expect(nodes.item(0).hasCls(boundList.overItemCls)).toBe(false);
        });
    });

    describe("selecting", function() {
        beforeEach(function() {
            createBoundList();
        });

        describe("ARIA attributes", function() {
            describe("enableAutoSelect == false", function() {
                beforeEach(function() {
                    boundList.selModel.select(0);
                });

                it("should not set aria-selected by default", function() {
                    expect(boundList.getNodes()[0]).not.toHaveAttr('aria-selected');
                });
            });

            describe("ariaSelectable == true", function() {
                beforeEach(function() {
                    boundList.ariaSelectable = true;
                    boundList.selModel.select(0);
                });

                it("should set aria-selected", function() {
                    expect(boundList.getNodes()[0]).toHaveAttr('aria-selected', 'true');
                });

                it("should remove aria-selected", function() {
                    boundList.selModel.deselectAll();

                    expect(boundList.getNodes()[0]).not.toHaveAttr('aria-selected');
                });

                it("should reset aria-selected with multiSelect pickerField", function() {
                    boundList.pickerField = { multiSelect: true };
                    boundList.selModel.deselectAll();

                    expect(boundList.getNodes()[0]).toHaveAttr('aria-selected', 'false');
                });
            });
        });
    });

    describe('setDisplayField', function() {
        it('should update the displayField', function() {
            createBoundList({}, [{
                name: 'Item1',
                id: 'itemone'
            }]);

            expect(boundList.displayField).toBe('name');

            boundList.setDisplayField('id');

            expect(boundList.displayField).toBe('id');
        });

        it('should update the tpl', function() {
            createBoundList();

            // update boundlist displayField
            boundList.setDisplayField('id');

            expect(boundList.tpl.isTemplate).toBe(true);
            expect(boundList.tpl.html).toBe('<tpl for="."><li role="option" unselectable="on" class="x-boundlist-item">{id}</li></tpl>');
        });

        it('should correctly render items using the updated tpl', function() {
            var nodes;

            createBoundList({}, [{
                name: 'Item1',
                id: 'itemone'
            }]);

            // update boundlist displayField
            boundList.setDisplayField('id');
            // refresh so tpl is rerun
            boundList.refresh();
            nodes = boundList.getNodes();
            expect(nodes[0].innerHTML).toBe('itemone');
        });
    });

    describe('masking', function() {
        describe('disabling the boundlist', function() {
            it('should mark the boundlist as disabled', function() {
                createBoundList();

                boundList.setDisabled(true);

                expect(boundList.disabled).toBe(true);
            });

            it('should call Element.mask', function() {
                // This tests to make sure that the element is being masked by Element.mask and not by the LoadMask component.
                // See EXTJSIV-11838.
                createBoundList();

                spyOn(Ext.dom.Element.prototype, 'mask');

                boundList.setDisabled(true);

                expect(Ext.dom.Element.prototype.mask).toHaveBeenCalled();
            });
        });

        describe('enabling the boundlist', function() {
            beforeEach(function() {
                createBoundList({
                    disabled: true
                });

                spyOn(Ext.dom.Element.prototype, 'unmask');

                boundList.setDisabled(false);
            });

            it('should mark the boundlist as enabled', function() {
                expect(boundList.disabled).toBe(false);
            });

            it('should call Element.unmask', function() {
                // This tests to make sure that the element is being unmasked by Element.mask and not by the LoadMask component.
                // See EXTJSIV-11838.
                expect(Ext.dom.Element.prototype.unmask).toHaveBeenCalled();
            });
        });
    });

    describe("deselectOnContainerClick", function() {
        var ieIt = (Ext.isIE10 || Ext.isIE11) ? it : xit;

        beforeEach(function() {
            createBoundList({
                deselectOnContainerClick: true,
                width: 200,
                height: 100
            }, [
                { name: 'Item 1' },
                { name: 'Item 2' },
                { name: 'Item 3' },
                { name: 'Item 4' },
                { name: 'Item 5' },
                { name: 'Item 6' },
                { name: 'Item 7' },
                { name: 'Item 8' },
                { name: 'Item 9' }
            ]);
        });

        // https://sencha.jira.com/browse/EXTJS-18847
        ieIt("should not deselect when clicking on scrollbar", function() {
            var el = boundList.el,
                xy = el.getXY(),
                width = el.getWidth(),
                x = xy[0] + width - Math.ceil(Ext.getScrollbarSize().width / 2);

            boundList.getSelectionModel().select(1);

            jasmine.fireMouseEvent(el, 'click', x, xy[1] + 1);

            expect(boundList.getSelectionModel().getSelection().length).toBe(1);
        });
    });
});
