// Tests only the behaviours specific to our default implementation of the list item
topSuite("Ext.list.TreeItem", ['Ext.data.TreeStore', 'Ext.list.Tree'], function() {
    var root, list, store, sampleData;

    var Model = Ext.define(null, {
        extend: 'Ext.data.TreeModel',
        fields: ['customField']
    });

    function makeList(cfg, noStore) {
        if (!store && !noStore) {
            store = new Ext.data.TreeStore({
                model: Model,
                root: {
                    expanded: true,
                    children: sampleData
                }
            });
        }

        list = new Ext.list.Tree(Ext.apply({
            store: store,
            animation: false
        }, cfg));
        list.render(Ext.getBody());

        if (list.getStore()) {
            root = list.getStore().getRoot();
        }
    }

    beforeEach(function() {
        sampleData = [{
            id: 'i1',
            text: 'Item 1',
            iconCls: 'iconA',
            children: [{
                id: 'i11',
                text: 'Item 1.1',
                leaf: true
            }, {
                id: 'i12',
                text: 'Item 1.2',
                leaf: true
            }]
        }, {
            id: 'i2',
            text: 'Item 2',
            expandable: false
        }, {
            id: 'i3',
            iconCls: 'iconB',
            text: 'Item 3',
            children: []
        }, {
            id: 'i4',
            text: 'Item 4',
            expanded: true,
            children: [{
                id: 'i41',
                iconCls: 'iconC',
                text: 'Item 4.1'
            }, {
                id: 'i42',
                text: 'Item 4.2',
                iconCls: null
            }, {
                id: 'i43',
                text: 'Item 4.3'
            }]
        }];
    });

    afterEach(function() {
        root = sampleData = store = list = Ext.destroy(list, store);
    });

    function byId(id) {
        return store.getNodeById(id);
    }

    function getItem(id) {
        return list.getItem(byId(id));
    }

    describe("items", function() {
        beforeEach(function() {
            makeList();
        });

        describe("at creation", function() {
            it("should render child items in order", function() {
                var item = getItem('i1'),
                    itemContainer = item.itemContainer;

                expect(itemContainer.dom.childNodes[0]).toBe(getItem('i11').el.dom);
                expect(itemContainer.dom.childNodes[1]).toBe(getItem('i12').el.dom);

                item = getItem('i4');
                itemContainer = item.itemContainer;

                expect(itemContainer.dom.childNodes[0]).toBe(getItem('i41').el.dom);
                expect(itemContainer.dom.childNodes[1]).toBe(getItem('i42').el.dom);
                expect(itemContainer.dom.childNodes[2]).toBe(getItem('i43').el.dom);
            });

            it("should set the text on the textElement", function() {
                expect(getItem('i1').textElement.dom).hasHTML('Item 1');
                expect(getItem('i11').textElement.dom).hasHTML('Item 1.1');
                expect(getItem('i12').textElement.dom).hasHTML('Item 1.2');

                expect(getItem('i2').textElement.dom).hasHTML('Item 2');

                expect(getItem('i3').textElement.dom).hasHTML('Item 3');

                expect(getItem('i4').textElement.dom).hasHTML('Item 4');
                expect(getItem('i41').textElement.dom).hasHTML('Item 4.1');
                expect(getItem('i42').textElement.dom).hasHTML('Item 4.2');
                expect(getItem('i43').textElement.dom).hasHTML('Item 4.3');
            });

            describe("leafCls", function() {
                it("should have the leafCls if an item is a leaf", function() {
                    Ext.Array.forEach(['i11', 'i12'], function(id) {
                        var item = getItem(id);

                        expect(item.element).toHaveCls(item.leafCls);
                    });

                    Ext.Array.forEach(['i1', 'i2', 'i3', 'i4', 'i41', 'i42', 'i43'], function(id) {
                        var item = getItem(id);

                        expect(item.element).not.toHaveCls(item.leafCls);
                    });
                });
            });

            describe("iconCls", function() {
                it("should add the withIconCls and set the iconCls if there is an iconCls", function() {
                    var i1 = getItem('i1'),
                        i3 = getItem('i3'),
                        i41 = getItem('i41');

                    expect(i1.iconElement).toHaveCls('iconA');
                    expect(i3.iconElement).toHaveCls('iconB');
                    expect(i41.iconElement).toHaveCls('iconC');

                    expect(i1.element).toHaveCls(i1.withIconCls);
                    expect(i3.element).toHaveCls(i3.withIconCls);
                    expect(i41.element).toHaveCls(i41.withIconCls);
                });

                it("should not have the withIconCls if there is no iconCls", function() {
                    Ext.Array.forEach(['i11', 'i12', 'i2', 'i4', 'i42', 'i43'], function(id) {
                        var item = getItem(id);

                        expect(item.element).not.toHaveCls(item.withIconCls);
                    });
                });

                it("should have the hideIconCls if the iconCls is null", function() {
                    var item = getItem('i42');

                    expect(item.element).toHaveCls(item.hideIconCls);
                });

                it("should not have the hideIconCls if the iconCls is not null", function() {
                    Ext.Array.forEach(['i1', 'i11', 'i12', 'i2', 'i3', 'i4', 'i41', 'i43'], function(id) {
                        var item = getItem(id);

                        expect(item.element).not.toHaveCls(item.hideIconCls);
                    });
                });
            });

            describe("expanded/collapsed", function() {
                it("should add the collapsedCls if the item is collapsed and expandable", function() {
                    Ext.Array.forEach(['i1', 'i41', 'i42', 'i43'], function(id) {
                        var item = getItem(id);

                        expect(item.element).toHaveCls(item.collapsedCls);
                    });

                    Ext.Array.forEach(['i11', 'i12', 'i2', 'i3', 'i4'], function(id) {
                        var item = getItem(id);

                        expect(item.element).not.toHaveCls(item.collapsedCls);
                    });
                });

                it("should add the expandedCls if the item is expanded and expandable", function() {
                    Ext.Array.forEach(['i4'], function(id) {
                        var item = getItem(id);

                        expect(item.element).toHaveCls(item.expandedCls);
                    });

                    Ext.Array.forEach(['i1', 'i11', 'i12', 'i2', 'i3', 'i41', 'i42', 'i43'], function(id) {
                        var item = getItem(id);

                        expect(item.element).not.toHaveCls(item.expandedCls);
                    });
                });
            });

            describe("expandable", function() {
                it("should add the expandableCls if the item is expandable", function() {
                    Ext.Array.forEach(['i1', 'i4', 'i41', 'i42', 'i43'], function(id) {
                        var item = getItem(id);

                        expect(item.element).toHaveCls(item.expandableCls);
                    });

                    Ext.Array.forEach(['i11', 'i12', 'i2', 'i3'], function(id) {
                        var item = getItem(id);

                        expect(item.element).not.toHaveCls(item.expandableCls);
                    });
                });
            });
        });

        describe("dynamic store modifications", function() {
            describe("adding nodes", function() {
                it("should insert nodes in the right position", function() {
                    byId('i1').insertBefore({
                        id: 'i9'
                    }, byId('i12'));

                    var childNodes = getItem('i1').itemContainer.dom.childNodes;

                    expect(childNodes[0]).toBe(getItem('i11').el.dom);
                    expect(childNodes[1]).toBe(getItem('i9').el.dom);
                    expect(childNodes[2]).toBe(getItem('i12').el.dom);
                });

                it("should append nodes to the right position", function() {
                    byId('i1').appendChild({
                        id: 'i9'
                    });

                    var childNodes = getItem('i1').itemContainer.dom.childNodes;

                    expect(childNodes[0]).toBe(getItem('i11').el.dom);
                    expect(childNodes[1]).toBe(getItem('i12').el.dom);
                    expect(childNodes[2]).toBe(getItem('i9').el.dom);
                });

                it("should add the expandableCls when adding the first item", function() {
                    var item = getItem('i3'),
                        el = item.element;

                    expect(el).not.toHaveCls(item.expandableCls);
                    byId('i3').appendChild({
                        id: 'i9'
                    });
                    expect(el).toHaveCls(item.expandableCls);
                });

                it("should update the expandableCls when removing and adding childs", function() {
                    var item = getItem('i1'),
                        el = item.element;

                    // item is expandable initially
                    expect(el).toHaveCls(item.expandableCls);
                    expect(item.getExpandable()).toBe(true);

                    // Remove all child
                    byId('i1').removeChild(byId('i11'));
                    byId('i1').removeChild(byId('i12'));

                    // Now items should not be expandable
                    expect(el).not.toHaveCls(item.expandableCls);
                    expect(item.getExpandable()).toBe(false);

                    // Append one child
                    byId('i1').appendChild({
                        id: 'i11'
                    });

                    // Items should be expandable again
                    expect(el).toHaveCls(item.expandableCls);
                    expect(item.getExpandable()).toBe(true);
                });
            });

            describe("removing nodes", function() {
                it("should remove the element from the DOM", function() {
                    var item = getItem('i42'),
                        itemContainer = getItem('i4').itemContainer;

                    expect(itemContainer.contains(item.el)).toBe(true);
                    expect(itemContainer.child('#' + item.id)).toBe(item.el);
                    byId('i4').removeChild(byId('i42'));
                    expect(item.destroyed).toBe(true);
                    expect(itemContainer.child('#' + item.id)).toBeNull();
                });

                it("should remove the expandableCls when removing the last item", function() {
                    var item = getItem('i1'),
                        el = item.element;

                    byId('i1').removeChild(byId('i12'));
                    expect(el).toHaveCls(item.expandableCls);
                    byId('i1').removeChild(byId('i11'));
                    expect(el).not.toHaveCls(item.expandableCls);
                });
            });

            describe("collapse", function() {
                describe("when expanded", function() {
                    it("should have the collapsedCls", function() {
                        var item = getItem('i4');

                        byId('i4').collapse();
                        expect(item.element).toHaveCls(item.collapsedCls);
                    });

                    it("should not have the expandedCls", function() {
                        var item = getItem('i4');

                        byId('i4').collapse();
                        expect(item.element).not.toHaveCls(item.expandedCls);
                    });

                    it("should hide the itemContainer", function() {
                        var item = getItem('i4');

                        byId('i4').collapse();
                        expect(item.itemContainer.isVisible()).toBe(false);
                    });
                });

                describe("when collapsed", function() {
                    it("should have the collapsedCls", function() {
                        var item = getItem('i1');

                        byId('i1').collapse();
                        expect(item.element).toHaveCls(item.collapsedCls);
                    });

                    it("should not have the expandedCls", function() {
                        var item = getItem('i1');

                        byId('i1').collapse();
                        expect(item.element).not.toHaveCls(item.expandedCls);
                    });

                    it("should hide the itemContainer", function() {
                        var item = getItem('i1');

                        byId('i1').collapse();
                        expect(item.itemContainer.isVisible()).toBe(false);
                    });
                });
            });

            describe("expand", function() {
                describe("when collapsed", function() {
                    it("should have the expandedCls", function() {
                        var item = getItem('i1');

                        byId('i1').expand();
                        expect(item.element).toHaveCls(item.expandedCls);
                    });

                    it("should not have the collapsedCls", function() {
                        var item = getItem('i1');

                        byId('i1').expand();
                        expect(item.element).not.toHaveCls(item.collapsedCls);
                    });

                    it("should show the itemContainer", function() {
                        var item = getItem('i1');

                        byId('i1').expand();
                        expect(item.itemContainer.isVisible()).toBe(true);
                    });

                    describe("loading", function() {
                        it("should have the loadingCls while loading", function() {
                            MockAjaxManager.addMethods();
                            var item = getItem('i41');

                            expect(item.element).not.toHaveCls(item.loadingCls);
                            item.expand();
                            expect(item.element).toHaveCls(item.loadingCls);
                            Ext.Ajax.mockComplete({
                                status: 200,
                                responseText: '[]'
                            });
                            expect(item.element).not.toHaveCls(item.loadingCls);
                            MockAjaxManager.removeMethods();
                        });
                    });
                });

                describe("when expanded", function() {
                    it("should have the expandedCls", function() {
                        var item = getItem('i4');

                        byId('i4').expand();
                        expect(item.element).toHaveCls(item.expandedCls);
                    });

                    it("should not have the collapsedCls", function() {
                        var item = getItem('i4');

                        byId('i4').expand();
                        expect(item.element).not.toHaveCls(item.collapsedCls);
                    });

                    it("should hide the itemContainer", function() {
                        var item = getItem('i4');

                        byId('i4').expand();
                        expect(item.itemContainer.isVisible()).toBe(true);
                    });
                });
            });

            describe("updating node fields", function() {
                it("should update the text when the text is changed", function() {
                    byId('i1').set('text', 'Foo');
                    expect(getItem('i1').textElement.dom).hasHTML('Foo');
                });

                describe("with iconCls: null", function() {
                    it("should remove hideIconCls when setting to empty", function() {
                        var item = getItem('i42');

                        expect(item.element).toHaveCls(item.hideIconCls);
                        expect(item.element).not.toHaveCls(item.withIconCls);

                        byId('i42').set('iconCls', '');
                        expect(item.element).not.toHaveCls(item.hideIconCls);
                        expect(item.element).not.toHaveCls(item.withIconCls);
                    });

                    it("should remove hideIconCls, add iconCls and add withIconCls when setting a class", function() {
                        var item = getItem('i42');

                        expect(item.element).toHaveCls(item.hideIconCls);
                        expect(item.element).not.toHaveCls(item.withIconCls);

                        byId('i42').set('iconCls', 'foo');
                        expect(item.element).not.toHaveCls(item.hideIconCls);
                        expect(item.element).toHaveCls(item.withIconCls);
                        expect(item.iconElement).toHaveCls('foo');
                    });
                });

                describe("with an empty iconCls", function() {
                    it("should add hideIconCls when setting to null", function() {
                        var item = getItem('i43');

                        expect(item.element).not.toHaveCls(item.hideIconCls);
                        expect(item.element).not.toHaveCls(item.withIconCls);

                        byId('i43').set('iconCls', null);
                        expect(item.element).toHaveCls(item.hideIconCls);
                        expect(item.element).not.toHaveCls(item.withIconCls);
                    });

                    it("should add withIconCls and set the icon when setting a class", function() {
                        var item = getItem('i43');

                        expect(item.element).not.toHaveCls(item.hideIconCls);
                        expect(item.element).not.toHaveCls(item.withIconCls);

                        byId('i43').set('iconCls', 'foo');
                        expect(item.element).not.toHaveCls(item.hideIconCls);
                        expect(item.element).toHaveCls(item.withIconCls);
                        expect(item.iconElement).toHaveCls('foo');
                    });
                });

                describe("with an iconCls specified", function() {
                    it("should remove withIconCls, clear iconCls and add hideIconCls when setting to null", function() {
                        var item = getItem('i41');

                        expect(item.element).not.toHaveCls(item.hideIconCls);
                        expect(item.element).toHaveCls(item.withIconCls);
                        expect(item.iconElement).toHaveCls('iconC');

                        byId('i41').set('iconCls', null);
                        expect(item.element).toHaveCls(item.hideIconCls);
                        expect(item.element).not.toHaveCls(item.withIconCls);
                        expect(item.iconElement).not.toHaveCls('iconC');
                    });

                    it("should remove withIconCls and clear iconCls when setting to empty", function() {
                        var item = getItem('i41');

                        expect(item.element).not.toHaveCls(item.hideIconCls);
                        expect(item.element).toHaveCls(item.withIconCls);
                        expect(item.iconElement).toHaveCls('iconC');

                        byId('i41').set('iconCls', '');
                        expect(item.element).not.toHaveCls(item.hideIconCls);
                        expect(item.element).not.toHaveCls(item.withIconCls);
                        expect(item.iconElement).not.toHaveCls('iconC');
                    });

                    it("should change the iconCls", function() {
                        var item = getItem('i1');

                        expect(item.element).toHaveCls(item.withIconCls);
                        byId('i1').set('iconCls', 'iconZ');
                        expect(item.iconElement).not.toHaveCls('iconA');
                        expect(item.iconElement).toHaveCls('iconZ');
                        expect(item.element).toHaveCls(item.withIconCls);
                    });
                });

                it("should set the cls and set the withIconCls when setting a iconCls", function() {
                    var item = getItem('i2'),
                        el = item.iconElement;

                    expect(item.element).not.toHaveCls(item.withIconCls);
                    byId('i2').set('iconCls', 'iconZ');
                    expect(el).toHaveCls('iconZ');
                    expect(item.element).toHaveCls(item.withIconCls);
                });

                it("should remove the cls and hide the iconElement when clearing a iconCls", function() {
                    var item = getItem('i1'),
                        el = item.iconElement;

                    expect(item.element).toHaveCls(item.withIconCls);
                    byId('i1').set('iconCls', '');
                    expect(el).not.toHaveCls('iconA');
                    expect(item.element).not.toHaveCls(item.withIconCls);
                });

                it("should change the class when modifying the iconCls", function() {
                    var item = getItem('i1'),
                        el = item.iconElement;

                    expect(item.element).toHaveCls(item.withIconCls);
                    byId('i1').set('iconCls', 'iconZ');
                    expect(el).not.toHaveCls('iconA');
                    expect(el).toHaveCls('iconZ');
                    expect(item.element).toHaveCls(item.withIconCls);
                });
            });
        });
    });

    // Disable these for now, they fail on device simulators
    xdescribe("expand/collapse via UI", function() {
        describe("with expanderOnly: false", function() {
            beforeEach(function() {
                sampleData[3].children[0].leaf = true;
                makeList({
                    expanderOnly: false
                });
            });

            it("should expand when clicking the expander of a collapsed item", function() {
                var item = getItem('i1');

                expect(item.isExpanded()).toBe(false);
                jasmine.fireMouseEvent(item.expanderElement.dom, 'click');
                expect(item.isExpanded()).toBe(true);
            });

            it("should collapse when clicking the expander of a expanded item", function() {
                var item = getItem('i4');

                expect(item.isExpanded()).toBe(true);
                jasmine.fireMouseEvent(item.expanderElement.dom, 'click');
                expect(item.isExpanded()).toBe(false);
            });

            it("should expand when clicking on the element of a collapsed item", function() {
                var item = getItem('i1');

                expect(item.isExpanded()).toBe(false);
                jasmine.fireMouseEvent(item.textElement.dom, 'click');
                expect(item.isExpanded()).toBe(true);
            });

            it("should collapse when clicking on the element of an expanded item", function() {
                var item = getItem('i4');

                expect(item.isExpanded()).toBe(true);
                jasmine.fireMouseEvent(item.textElement.dom, 'click');
                expect(item.isExpanded()).toBe(false);
            });

            it("should not collapse when clicking inside the itemContainer", function() {
                var item = getItem('i4');

                expect(item.isExpanded()).toBe(true);
                jasmine.fireMouseEvent(getItem('i41').textElement.dom, 'click');
                expect(item.isExpanded()).toBe(true);
            });
        });

        describe("with expanderOnly: true", function() {
            beforeEach(function() {
                makeList({
                    expanderOnly: true
                });
            });

            it("should expand when clicking the expander of a collapsed item", function() {
                var item = getItem('i1');

                expect(item.isExpanded()).toBe(false);
                jasmine.fireMouseEvent(item.expanderElement.dom, 'click');
                expect(item.isExpanded()).toBe(true);
            });

            it("should collapse when clicking the expander of an expanded item", function() {
                var item = getItem('i4');

                expect(item.isExpanded()).toBe(true);
                jasmine.fireMouseEvent(item.expanderElement.dom, 'click');
                expect(item.isExpanded()).toBe(false);
            });

            it("should not expand when clicking on the element of a collapsed item", function() {
                var item = getItem('i1');

                expect(item.isExpanded()).toBe(false);
                jasmine.fireMouseEvent(item.iconElement.dom, 'click');
                jasmine.fireMouseEvent(item.textElement.dom, 'click');
                jasmine.fireMouseEvent(item.element.dom, 'click');
                expect(item.isExpanded()).toBe(false);
            });

            it("should not collapse when clicking on the element of an expanded item", function() {
                var item = getItem('i4');

                expect(item.isExpanded()).toBe(true);
                jasmine.fireMouseEvent(item.iconElement.dom, 'click');
                jasmine.fireMouseEvent(item.textElement.dom, 'click');
                jasmine.fireMouseEvent(item.element.dom, 'click');
                expect(item.isExpanded()).toBe(true);
            });
        });
    });

});
