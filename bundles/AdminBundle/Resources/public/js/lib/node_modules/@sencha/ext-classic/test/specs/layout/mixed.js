topSuite("Ext.layout.mixed",
    [false, 'Ext.container.Viewport', 'Ext.layout.*', 'Ext.tab.Panel'],
function() {
    it("mixed test 1 - no failure", function() {
        var vp;

        expect(function() {
            vp = new Ext.container.Viewport({
                renderTo: Ext.getBody(),
                layout: "fit",
                items: [{
                    autoScroll: true,
                    layout: {
                        type: "vbox",
                        align: "stretch"
                    },
                    items: [{
                        heigt: 400,
                        layout: {
                            type: "hbox",
                            align: "stretch"
                        },
                        items: [{
                            flex: 1,
                            layout: {
                                type: "hbox",
                                align: "stretch"
                            },
                            items: [{
                                flex: 1,
                                layout: {
                                    type: "vbox",
                                    align: "stretch"
                                },
                                items: [{
                                    flex: 1,
                                    items: [{
                                        title: "Title"
                                    }]
                                }]
                            }, {
                                flex: 1,
                                layout: {
                                    type: "vbox",
                                    align: "stretch"
                                },
                                items: [{
                                    height: 3000,
                                    layout: {
                                        type: "vbox",
                                        align: "stretch"
                                    },
                                    items: [{
                                        xtype: "tabpanel",
                                        flex: 1,
                                        items: [{
                                            xtype: "panel",
                                            title: "Music Oriented",
                                            layout: "column",
                                            autoScroll: true,
                                            items: [{
                                                columnWidth: 1,
                                                items: [{
                                                    xtype: "panel",
                                                    layout: "fit",
                                                    items: [{
                                                        title: "Title"
                                                    }]
                                                }]
                                            }]
                                        }]
                                    }]
                                }]
                            }]
                        }]
                    }]
                }]
            });
        }).not.toThrow();

        vp.destroy();
    });
});
