topSuite("Ext.layout.component.Component", ['Ext.Window'], function() {
    var c;

    afterEach(function() {
        c = Ext.destroy(c);
    });

    describe("retaining dimensions between layout runs", function() {
        it("should use the last calculated width when running a top level layout & previously had a value", function() {
            c = new Ext.container.Container({
                layout: {
                    type: 'vbox',
                    align: 'stretchmax'
                },
                renderTo: Ext.getBody(),
                items: [{
                    xtype: 'panel',
                    border: false,
                    width: 50,
                    flex: 1,
                    dockedItems: [{
                        dock: 'bottom',
                        html: 'X'
                    }]

                }, {
                    xtype: 'panel',
                    border: false,
                    html: '<div style="width: 150px;">asdf</div>',
                    flex: 1
                }]
            });

            var child = c.items.first();

            expect(child.getWidth()).toBe(150);

            // Changing the html of the docked item will never change the size
            // of "child", so child will run as a top level layout. Ensure that
            // we retain the width previously calculated.
            child.getDockedItems()[0].setHtml('Foo<br>bar');
            expect(child.getWidth()).toBe(150);
        });

        it("should use the last calculated height when running a top level layout & previously had a value", function() {
            c = new Ext.container.Container({
                layout: {
                    type: 'hbox',
                    align: 'stretchmax'
                },
                renderTo: Ext.getBody(),
                items: [{
                    xtype: 'panel',
                    border: false,
                    height: 50,
                    flex: 1,
                    dockedItems: [{
                        dock: 'right',
                        html: 'X'
                    }]

                }, {
                    xtype: 'panel',
                    border: false,
                    html: '<div style="height: 150px;">asdf</div>',
                    flex: 1
                }]
            });

            var child = c.items.first();

            expect(child.getHeight()).toBe(150);

            // Changing the html of the docked item will never change the size
            // of "child", so child will run as a top level layout. Ensure that
            // we retain the height previously calculated.
            child.getDockedItems()[0].setHtml('Foo<br>bar');
            expect(child.getHeight()).toBe(150);
        });
    });

    describe("framed windows with percentage dimensions", function() {
        function makeWindow(cfg) {
            cfg = Ext.apply({
                autoShow: true,
                header: false
            }, cfg);

            c = new Ext.window.Window(cfg);
        }

        it("should accept percentage width and height", function() {
            makeWindow({
                width: "50%",
                height: "50%"
            });

            expect(c.getWidth()).toBe(Ext.Element.getViewportWidth() / 2);
            expect(c.getHeight()).toBe(Ext.Element.getViewportHeight() / 2);
        });

        it("should accept percentage width and configured height", function() {
            makeWindow({
                width: "50%",
                height: 200
            });

            expect(c.getWidth()).toBe(Ext.Element.getViewportWidth() / 2);
            expect(c.getHeight()).toBe(200);
        });

        it("should accept percentage height and configured width", function() {
            makeWindow({
                width: 200,
                height: "50%"
            });

            expect(c.getWidth()).toBe(200);
            expect(c.getHeight()).toBe(Ext.Element.getViewportHeight() / 2);
        });
    });
});
