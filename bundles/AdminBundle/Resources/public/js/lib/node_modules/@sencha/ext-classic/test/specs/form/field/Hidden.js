topSuite("Ext.form.field.Hidden", function() {
    var component, makeComponent, render;

    beforeEach(function() {
        makeComponent = function(config) {
            config = config || {};
            component = new Ext.form.field.Hidden(config);

            return component;
        };
    });

    afterEach(function() {
        if (component) {
            component.destroy();
        }

        component = makeComponent = null;
    });

    it("should not take up any height", function() {
        var ct = new Ext.container.Container({
            renderTo: Ext.getBody(),
            items: makeComponent()
        });

        expect(ct.getHeight()).toBe(0);
        ct.destroy();
    });

    it("should be registered with the 'hiddenfield' xtype", function() {
        component = Ext.create("Ext.form.field.Hidden", { name: 'test' });
        expect(component instanceof Ext.form.field.Hidden).toBe(true);
        expect(Ext.getClass(component).xtype).toBe("hiddenfield");
    });

    it("should render as input hidden", function() {
        makeComponent({
            name: 'test',
            renderTo: Ext.getBody()
        });
        expect(component.inputEl.dom.type).toEqual('hidden');
    });

    describe('getSubmitData', function() {
        it("should return the field's value", function() {
            makeComponent({ name: 'myname', value: 'myvalue' });
            expect(component.getSubmitData()).toEqual({ myname: 'myvalue' });
        });
        it("should return empty string for an empty value", function() {
            makeComponent({ name: 'myname', value: '' });
            expect(component.getSubmitData()).toEqual({ myname: '' });
        });
    });

    describe('getModelData', function() {
        it("should return the field's value", function() {
            makeComponent({ name: 'myname', value: 'myvalue' });
            expect(component.getModelData()).toEqual({ myname: 'myvalue' });
        });
        it("should return empty string for an empty value", function() {
            makeComponent({ name: 'myname', value: '' });
            expect(component.getModelData()).toEqual({ myname: '' });
        });
    });

});
