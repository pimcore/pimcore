topSuite("Ext.app.bind.Binding", ['Ext.app.ViewModel', 'Ext.panel.Panel', 'Ext.form.field.Text'], function() {
    var component, viewModel;

    afterEach(function() {
        Ext.destroy(component);
    });

    // EXTJS-25304
    // The null initial value was published upon spin up of the binding
    // which then contaminated the supposedly incoming value from the VM
    // resulting in the field being empty.
    it("should not push a non-confgured null initial value out to a two way bind", function() {
        component = Ext.create('Ext.panel.Panel', {
            renderTo: document.body,
            viewModel: {
                data: {
                    testValue: 'test value'
                }
            },
            title: 'Test textfield bind',
            items: [{
                id: 'test-textfield',
                xtype: 'textfield',
                fieldLabel: 'Textfield',
                publishes: 'value',
                bind: '{testValue}'
            }]
        });
        viewModel = component.getViewModel();
        viewModel.notify();

        var textfield = Ext.getCmp('test-textfield');

        expect(textfield.getValue()).toBe('test value');
    });
});
