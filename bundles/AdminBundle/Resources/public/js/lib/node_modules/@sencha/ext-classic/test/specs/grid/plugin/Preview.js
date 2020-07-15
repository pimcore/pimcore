topSuite("Ext.ux.PreviewPlugin", ['Ext.grid.Panel'], function() {
    it('should not throw an error', function() {
        // See EXTJSIV-12783.
        var grid;

        expect(function() {
            grid = new Ext.grid.Panel({
                renderTo: Ext.getBody(),
                height: 400,
                width: 600,
                title: 'Preview Test',
                store: {
                    data: [
                        { name: 'foo', description: 'foo description' }
                    ],
                    fields: ['name', 'description']
                },
                columns: [{
                    text: 'Name',
                    dataIndex: 'name'
                }],
                plugins: [{
                    ptype: 'preview',
                    bodyField: 'description',
                    expanded: true,
                    pluginId: 'preview'
                }]
            });
        }).not.toThrow();

        grid.destroy();
    });
});
