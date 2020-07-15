topSuite("Ext.dashboard.Dashboard", function() {
    var panel;

    function createPanel(config) {
        panel = Ext.create('Ext.dashboard.Dashboard', Ext.apply({
            renderTo: Ext.getBody(),
            parts: {
                foo: {
                    viewTemplate: {
                        title: 'Foo',
                        items: [{
                            html: 'Foo bar baz'
                        }]
                    }
                }
            },
            width: 500,

            columnWidths: [0.5, 0.5],
            maxColumns: 2
        }, config));
    }

    afterEach(function() {
        panel = Ext.destroy(panel);
    });

    describe('stateful', function() {
        beforeEach(function() {
            createPanel({
                stateful: true,
                stateId: 'dashboard'
            });
        });

        describe('columns', function() {
            it('should not ignore column widths', function() {
                var r1, r2;

                // add view
                waits(100);
                runs(function() {
                    panel.addView({ type: 'foo' });
                    r1 = panel.getRegion().right;
                    r2 = panel.items.getAt(0).getRegion().right;

                    // should fill half the dashboard
                    expect(r2).toEqual(r1 / 2);
                });
            });
        });
    });
});
