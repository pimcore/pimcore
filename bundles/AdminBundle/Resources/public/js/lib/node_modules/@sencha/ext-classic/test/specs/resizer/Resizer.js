topSuite("Ext.resizer.Resizer", ['Ext.window.Window'], function() {
    var resizer, target,
        testIt = Ext.isWebKit ? it : xit;

    function makeResizer(cfg) {
        target = new Ext.Component(Ext.apply({
            renderTo: Ext.getBody()
        }, cfg));

        resizer = new Ext.resizer.Resizer({
            target: target,
            handles: 'all'
        });
    }

    afterEach(function() {
        Ext.destroy(resizer, target);
        resizer = target = null;
    });

    describe('init', function() {
        describe('when target el needs to be wrapped', function() {
            beforeEach(function() {
                makeResizer({
                    autoEl: {
                        tag: 'textarea',
                        html: 'And any fool knows a dog needs a home, A shelter from pigs on the wing.'
                    }
                });
            });

            it('should be given an `originalTarget` property', function() {
                expect(resizer.originalTarget).toBeDefined();
            });

            it('should redefine the target to be an element', function() {
                expect(resizer.target.isElement).toBe(true);
            });

            it('should not set originalTarget equalTo target', function() {
                expect(resizer.originalTarget).not.toBe(resizer.target);
            });
        });
    });

    describe('constraining', function() {
        // Synthetic event resizing only works on good browsers.
        // Code tested is not browser dependent however.
        testIt('should not constrain if constrain config !== true', function() {
            var window,
                panel = new Ext.panel.Panel({
                height: 200,
                width: 200,
                renderTo: document.body,
                items: [window = new Ext.window.Window({
                    height: 100,
                    width: 100,
                    title: 'Child Window'
                })]
            });

            window.show();
            jasmine.fireMouseEvent(window.resizer.east, 'mousedown');
            jasmine.fireMouseEvent(document.body, 'mousemove', '+200', 150);
            jasmine.fireMouseEvent(document.body, 'mouseup');

            // Window must be allowed to resize outside its owning Panel's bounds
            expect(window.getWidth()).toBe(300);
            Ext.destroy(panel, window);
        });
        testIt("should constrain to floatParent's targetEl if constrain config == true", function() {
            var window,
                panel = new Ext.panel.Panel({
                height: 200,
                width: 200,
                renderTo: document.body,
                items: [window = new Ext.window.Window({
                    constrain: true,
                    height: 100,
                    width: 100,
                    title: 'Child Window'
                })]
            });

            window.show();
            jasmine.fireMouseEvent(window.resizer.east, 'mousedown');
            jasmine.fireMouseEvent(document.body, 'mousemove', '+200', 150);
            jasmine.fireMouseEvent(document.body, 'mouseup');

            // Window must NOT be allowed to resize outside its owning Panel's bounds
            expect(window.getWidth()).toBe(150);
            Ext.destroy(panel, window);
        });
    });

    describe('resizing in a layout', function() {
        testIt('Should allow layout last word on positioning when sizing using top handle', function() {
            var i,
                panels = [],
                macBeth = [
                    "The Tragedy of Macbeth - Shakespeare <br>ACT I<br>SCENE I. A desert place.<br><br>Thunder and lightning. Enter three Witches",
                    "First Witch: When shall we three meet again In thunder, lightning, or in rain?",
                    "Second Witch: When the hurlyburly's done, When the battle's lost and won.",
                    "Third Witch: That will be ere the set of sun."
                ],
                outerPanel;

            for (i = 0; i < macBeth.length; i++) {
                panels.push(new Ext.panel.Panel({
                    margin: "0, 0 10 0",
                    title: "I'm Panel #" + (i + 1),
                    titleAlign: 'center',
                    html: macBeth[i],
                    frame: true,
                    resizable: true,
                    resizeHandles: 's,n'
                }));
            }

            outerPanel = new Ext.panel.Panel({
                height: 600,
                width: 800,
                layout: {
                    type: 'vbox'
                },
                items: panels,
                renderTo: document.body
            });

            var panel1Top = panels[1].getY();

            jasmine.fireMouseEvent(panels[1].resizer.north, 'mousedown');
            jasmine.fireMouseEvent(document.body, 'mousemove', 0, '-50');
            jasmine.fireMouseEvent(document.body, 'mouseup');

            // Layout should have correctred the top
            expect(panels[1].getY()).toBe(panel1Top);
            outerPanel.destroy();
        });
    });
});
