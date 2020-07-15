topSuite("Ext.layout.component.field.FieldContainer", ['Ext.form.field.*'], function() {
    var fc;

    afterEach(function() {
        fc = Ext.destroy(fc);
    });

    describe("fixed size", function() {
        describe("padding", function() {
            it("should account for horizontal padding supplied by the fieldBodyCls", function() {
                fc = new Ext.form.FieldContainer({
                    renderTo: Ext.getBody(),
                    hideLabel: true,
                    layout: {
                        type: 'hbox',
                        align: 'stretch'
                    },
                    width: 300,
                    height: 50,
                    items: {
                        xtype: 'component',
                        flex: 1
                    }
                });
                // Simulate bodyCls setting padding: 1px
                fc.bodyEl.setStyle('padding', '1px');
                fc.updateLayout();

                expect(fc.items.first().getWidth()).toBe(298);
            });

            it("should account for vertical padding supplied by the fieldBodyCls", function() {
                fc = new Ext.form.FieldContainer({
                    renderTo: Ext.getBody(),
                    hideLabel: true,
                    layout: {
                        type: 'hbox',
                        align: 'stretch'
                    },
                    width: 50,
                    height: 300,
                    items: {
                        xtype: 'component',
                        flex: 1
                    }
                });
                // Simulate bodyCls setting padding: 1px
                fc.bodyEl.setStyle('padding', '1px');
                fc.updateLayout();
                expect(fc.items.first().getHeight()).toBe(298);
            });
        });

        describe("errors", function() {
            it("should account for horizontal errors", function() {
                fc = new Ext.form.FieldContainer({
                    renderTo: document.body,
                    width: 500,
                    hideLabel: true,
                    msgTarget: 'side',
                    layout: 'hbox',
                    items: {
                        flex: 1,
                        xtype: 'component'
                    }
                });

                expect(fc.getWidth()).toBe(500);
                expect(fc.bodyEl.getWidth()).toBe(500);
                expect(fc.items.getAt(0).getWidth()).toBe(500);

                // make sure the child gets resized when side error is shown.
                fc.setActiveError('Error');

                var errorWidth = fc.errorWrapEl.getWidth();

                expect(fc.getWidth()).toBe(500);
                expect(fc.bodyEl.getWidth()).toBe(500 - errorWidth);
                expect(fc.items.getAt(0).getWidth()).toBe(500 - errorWidth);
            });

            it("should account for vertical errors", function() {
                fc = new Ext.form.FieldContainer({
                    renderTo: document.body,
                    hideLabel: true,
                    width: 200,
                    height: 200,
                    msgTarget: 'under',
                    layout: 'vbox',
                    items: {
                        flex: 1,
                        xtype: 'component'
                    }
                });

                expect(fc.getHeight()).toBe(200);
                expect(fc.bodyEl.getHeight()).toBe(200);
                expect(fc.items.getAt(0).getHeight()).toBe(200);

                // make sure the child gets resized when side error is shown.
                fc.setActiveError('Error');

                var errorHeight = fc.errorWrapEl.getHeight();

                expect(fc.getHeight()).toBe(200);
                expect(fc.bodyEl.getHeight()).toBe(200 - errorHeight);
                expect(fc.items.getAt(0).getHeight()).toBe(200 - errorHeight);
            });
        });

        describe("labels", function() {
            it("should account for horizontal labels", function() {
                fc = new Ext.form.FieldContainer({
                    renderTo: document.body,
                    width: 500,
                    height: 200,
                    fieldLabel: 'Label',
                    layout: 'hbox',
                    items: {
                        flex: 1,
                        xtype: 'component'
                    }
                });

                var labelWidth = fc.labelWidth + fc.labelPad;

                expect(fc.getWidth()).toBe(500);
                expect(fc.bodyEl.getWidth()).toBe(500 - labelWidth);
                expect(fc.items.getAt(0).getWidth()).toBe(500 - labelWidth);
            });

            it("should account for vertical labels", function() {
                fc = new Ext.form.FieldContainer({
                    renderTo: document.body,
                    width: 200,
                    height: 500,
                    fieldLabel: 'Label',
                    labelAlign: 'top',
                    layout: 'vbox',
                    items: {
                        flex: 1,
                        xtype: 'component'
                    }
                });

                var labelHeight = fc.labelEl.getHeight();

                expect(fc.getHeight()).toBe(500);
                expect(fc.bodyEl.getHeight()).toBe(500 - labelHeight);
                expect(fc.items.getAt(0).getHeight()).toBe(500 - labelHeight);
            });
        });
    });

    describe("auto size", function() {
        describe("padding", function() {
            it("should account for horizontal padding supplied by the fieldBodyCls", function() {
                var ct = new Ext.container.Container({
                    floating: true,
                    renderTo: Ext.getBody(),
                    layout: 'hbox',
                    items: [{
                        xtype: 'component',
                        width: 50
                    }, {
                        xtype: 'fieldcontainer',
                        hideLabel: true,
                        margin: 0,
                        items: {
                            xtype: 'component',
                            width: 48
                        }
                    }, {
                        xtype: 'component',
                        width: 50
                    }]
                });

                fc = ct.down('fieldcontainer');
                // Simulate bodyCls setting padding: 1px
                fc.bodyEl.setStyle('padding', '1px');
                fc.updateLayout();

                expect(ct.getWidth()).toBe(150);
                expect(ct.items.last().el.getLeft(true)).toBe(100);

                ct.destroy();
            });

            it("should account for vertical padding supplied by the fieldBodyCls", function() {
                var ct = new Ext.container.Container({
                    floating: true,
                    renderTo: Ext.getBody(),
                    layout: 'vbox',
                    items: [{
                        xtype: 'component',
                        height: 50
                    }, {
                        xtype: 'fieldcontainer',
                        hideLabel: true,
                        margin: 0,
                        items: {
                            xtype: 'component',
                            height: 48
                        }
                    }, {
                        xtype: 'component',
                        height: 50
                    }]
                });

                fc = ct.down('fieldcontainer');
                // Simulate bodyCls setting padding: 1px
                fc.bodyEl.setStyle('padding', '1px');
                fc.updateLayout();

                expect(ct.getHeight()).toBe(150);
                expect(ct.items.last().el.getTop(true)).toBe(100);

                ct.destroy();
            });
        });

        describe("errors", function() {
            it("should account for horizontal errors", function() {
                var ct = new Ext.container.Container({
                    floating: true,
                    renderTo: Ext.getBody(),
                    layout: 'hbox',
                    items: [{
                        xtype: 'component',
                        width: 50
                    }, {
                        xtype: 'fieldcontainer',
                        hideLabel: true,
                        margin: 0,
                        msgTarget: 'side',
                        items: {
                            xtype: 'component',
                            width: 50
                        }
                    }, {
                        xtype: 'component',
                        width: 50
                    }]
                });

                expect(ct.getWidth()).toBe(150);

                fc = ct.down('fieldcontainer');

                // make sure the child gets resized when side error is shown.
                fc.setActiveError('Error');

                var errorWidth = fc.errorWrapEl.getWidth();

                expect(ct.getWidth()).toBe(150 + errorWidth);
                expect(ct.items.last().el.getLeft(true)).toBe(100 + errorWidth);

                ct.destroy();
            });

            it("should account for vertical errors", function() {
                var ct = new Ext.container.Container({
                    floating: true,
                    renderTo: Ext.getBody(),
                    layout: 'vbox',
                    items: [{
                        xtype: 'component',
                        height: 50
                    }, {
                        xtype: 'fieldcontainer',
                        hideLabel: true,
                        margin: 0,
                        msgTarget: 'under',
                        items: {
                            xtype: 'component',
                            height: 50
                        }
                    }, {
                        xtype: 'component',
                        height: 50
                    }]
                });

                expect(ct.getHeight()).toBe(150);

                fc = ct.down('fieldcontainer');

                // make sure the child gets resized when side error is shown.
                fc.setActiveError('Error');

                var errorWidth = fc.errorWrapEl.getHeight();

                expect(ct.getHeight()).toBe(150 + errorWidth);
                expect(ct.items.last().el.getTop(true)).toBe(100 + errorWidth);

                ct.destroy();
            });
        });

        describe("labels", function() {
            it("should account for horizontal labels", function() {
                var ct = new Ext.container.Container({
                    floating: true,
                    renderTo: Ext.getBody(),
                    layout: 'hbox',
                    items: [{
                        xtype: 'component',
                        width: 50
                    }, {
                        xtype: 'fieldcontainer',
                        fieldLabel: 'Label',
                        margin: 0,
                        items: {
                            xtype: 'component',
                            width: 50
                        }
                    }, {
                        xtype: 'component',
                        width: 50
                    }]
                });

                fc = ct.down('fieldcontainer');
                var labelWidth = fc.labelWidth + fc.labelPad;

                expect(ct.getWidth()).toBe(150 + labelWidth);
                expect(ct.items.last().el.getLeft(true)).toBe(100 + labelWidth);

                ct.destroy();
            });

            it("should account for vertical labels", function() {
                var ct = new Ext.container.Container({
                    floating: true,
                    renderTo: Ext.getBody(),
                    layout: 'vbox',
                    items: [{
                        xtype: 'component',
                        height: 50
                    }, {
                        xtype: 'fieldcontainer',
                        fieldLabel: 'Label',
                        labelAlign: 'top',
                        margin: 0,
                        items: {
                            xtype: 'component',
                            height: 50
                        }
                    }, {
                        xtype: 'component',
                        height: 50
                    }]
                });

                fc = ct.down('fieldcontainer');
                var labelHeight = fc.labelEl.getHeight();

                expect(ct.getHeight()).toBe(150 + labelHeight);
                expect(ct.items.last().el.getTop(true)).toBe(100 + labelHeight);

                ct.destroy();
            });
        });
    });

    it("should shrink wrap liquid layout children when using a box layout", function() {
        fc = new Ext.form.FieldContainer({
            renderTo: document.body,
            layout: 'hbox',
            items: [{
                xtype: 'textfield',
                width: 300,
                value: 'foo'
            }]
        });

        expect(fc.getHeight()).toBe(22);
        expect(fc.getWidth()).toBe(300);
    });
});
