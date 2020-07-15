topSuite("Ext.view.NavigationModel", ['Ext.data.ArrayStore', 'Ext.view.View'], function() {
    var view, navModel, store;

    var Model = Ext.define(null, {
        extend: 'Ext.data.Model',
        fields: ['id', 'name']
    });

    function makeData(len) {
        var data = [];

        for (var i = 1; i <= len; ++i) {
            data.push({
                id: i,
                name: 'Item' + i
            });
        }

        return data;
    }

    function makeView(cfg, navCfg, data) {
        if (data) {
            if (typeof data === 'number') {
                makeData(data);
            }
        }
        else {
            data = makeData(20);
        }

        store = new Ext.data.Store({
            model: Model,
            data: data
        });

        view = new Ext.view.View({
            renderTo: Ext.getBody(),
            store: store,
            itemTpl: '{name}'
        });
        navModel = view.getNavigationModel();
    }

    afterEach(function() {
        view = navModel = Ext.destroy(view);
    });

    describe("filter changes", function() {
        it("should focus the item correctly when making the dataset smaller", function() {
            makeView();
            var rec = store.getById(10);

            navModel.setPosition(rec);
            expect(navModel.getPosition()).toBe(9);
            store.filterBy(function(rec) {
                return rec.id % 2 === 0;
            });

            var node = view.getNode(rec);

            expect(Ext.dom.Element.getActiveElement()).toBe(node);
            expect(node).toHaveCls(navModel.focusCls);
            expect(navModel.getPosition()).toBe(4);
        });

        it("should focus the item correctly when making the dataset larger", function() {
            makeView();
            var rec = store.getById(10);

            store.filterBy(function(rec) {
                return rec.id % 2 === 0;
            });
            navModel.setPosition(rec);
            expect(navModel.getPosition()).toBe(4);

            store.getFilters().removeAll();

            var node = view.getNode(rec);

            expect(Ext.dom.Element.getActiveElement()).toBe(node);
            expect(node).toHaveCls(navModel.focusCls);
            expect(navModel.getPosition()).toBe(9);
        });
    });
});
