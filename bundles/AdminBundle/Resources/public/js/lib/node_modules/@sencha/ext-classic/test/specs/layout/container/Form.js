topSuite('Ext.layout.container.Form', ['Ext.form.Panel', 'Ext.form.field.Text'], function() {

    // TODO: form layout specs
    xdescribe('child items manipulation', function() {

        it('should convert child items tables to tbody nodes', function() {
            var panel = Ext.create('Ext.form.Panel', {
                    layout: 'form',
                    renderTo: Ext.getBody(),
                    items: [{
                        id: 'textfield0',
                        xtype: 'textfield'
                    }, {
                        id: 'textfield1',
                        xtype: 'textfield'
                    }]
                }),
                table = panel.el.down('table').dom;

            // both original and dynamically added children
            panel.add({
                id: 'textfield2',
                xtype: 'textfield'
            });

            expect(table.childNodes.length).toBe(3);
            expect(table.childNodes[0].tagName).toBe('TBODY');
            expect(table.childNodes[0]).toBe(Ext.getCmp('textfield0').el.dom);
            expect(table.childNodes[1].tagName).toBe('TBODY');
            expect(table.childNodes[1]).toBe(Ext.getCmp('textfield1').el.dom);
            expect(table.childNodes[2].tagName).toBe('TBODY');
            expect(table.childNodes[2]).toBe(Ext.getCmp('textfield2').el.dom);

            panel.destroy();
        });

    });

    it("should shrinkwrap auto-width items", function() {
        var panel = Ext.widget({
            xtype: 'panel',
            renderTo: document.body,
            shrinkWrap: true,
            layout: {
                type: 'form',
                labelWidth: 100
            },
            items: [{
                fieldLabel: 'Label',
                xtype: 'textfield'
            }]
        });

        expect(panel.getWidth()).toBe(267);
        panel.destroy();
    });
});
