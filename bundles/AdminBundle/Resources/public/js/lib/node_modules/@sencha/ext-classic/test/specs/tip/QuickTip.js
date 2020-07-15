topSuite("Ext.tip.QuickTip", function() {
    var target, tip;

    function createTargetEl(attrString) {
        target = Ext.getBody().insertHtml('beforeEnd', '<a href="#" ' + attrString + '>x</a>', true);
    }

    function mouseoverTarget(theTarget) {
        theTarget = theTarget || target;

        if (jasmine.supportsTouch && !Ext.os.is.Desktop) {
            jasmine.fireMouseEvent(theTarget, 'click');
        }
        else {
            jasmine.fireMouseEvent(theTarget, 'mouseover');
        }
    }

    function createTip(cfg) {
        tip = new Ext.tip.QuickTip(Ext.apply({
            showOnTap: jasmine.supportsTouch
        }, cfg, { showDelay: 1 }));
    }

    beforeEach(function() {
        // We test a private instance.
        // Do not disturb the system QuickTip
        Ext.QuickTips.disable();
    });

    afterEach(function() {
        Ext.QuickTips.enable();

        if (target) {
            target.destroy();
        }

        if (tip) {
            tip.destroy();
        }
    });

    describe("element attributes", function() {
        function setup(attrs) {
            runs(function() {
                createTargetEl(attrs);
                createTip();
                mouseoverTarget();
            });
            waitsFor(function() {
                return tip.isVisible();
            }, "QuickTip never showed");
        }

        it("should display a tooltip containing the ext:qtip attribute's value", function() {
            setup('data-qtip="tip text"');
            runs(function() {
                expect(tip.layout.innerCt.dom).hasHTML('tip text');
            });
        });

        it("should display the ext:qtitle attribute as the tooltip title", function() {
            setup('data-qtip="tip text" data-qtitle="tip title"');
            runs(function() {
                expect(tip.title).toEqual('tip title');
            });
        });

        it("should use the ext:qwidth attribute as the tooltip width", function() {
            setup('data-qtip="tip text" data-qwidth="234"');
            runs(function() {
                expect(tip.el.getWidth()).toEqual(234);
            });
        });

        it("should add the ext:qclass attribute as a className on the tooltip element", function() {
            setup('data-qtip="tip text" data-qclass="test-class"');
            runs(function() {
                expect(tip.el.hasCls('test-class')).toBeTruthy();
            });
        });

        it("should add the ext:qshowDelay attribute on the tooltip element", function() {
            setup('data-qtip="tip text" data-qshowDelay="300"');
            runs(function() {
                expect(tip.activeTarget.el.getAttribute('data-qshowDelay')).toBe('300');
            });
        });

        it("should use the ext:hide attribute as an autoHide switch for the tooltip", function() {
            setup('data-qtip="tip text" data-hide="user"');
            runs(function() {
                expect(tip.autoHide).toBeFalsy();
            });
        });
    });

    describe("register", function() {
        function setup(registerConfig, targ, attrString) {
            runs(function() {
                createTargetEl(attrString || '');
                createTip({ maxWidth: 400 });
                tip.register(Ext.apply({}, registerConfig || {}, { target: targ || target, text: 'tip text' }));
                mouseoverTarget();
            });
            waitsFor(function() {
                return tip.isVisible();
            }, "QuickTip never showed");
        }

        it("should use the 'target' parameter as a new target", function() {
            setup();
            // the expectation is just that setup's waitsFor completed
        });

        it("should show when registering tooltip as string", function() {
            setup({ text: 'test text' }, 'foobar', 'id="foobar"');
            runs(function() {
                expect(tip.isVisible()).toBe(true);
            });
        });

        it("should show when registering tooltip as HTMLElement", function() {
            setup({ text: 'test text' }, target.dom);
            runs(function() {
                expect(tip.isVisible()).toBe(true);
            });
        });

        it("should show when registering tooltip as Ext.Element", function() {
            setup({ text: 'test text' });

            runs(function() {
                expect(tip.isVisible()).toBe(true);
            });
        });

        it("should use the 'text' parameter as the tooltip content", function() {
            setup({ text: 'test text' });
            runs(function() {
                expect(tip.layout.innerCt.dom).hasHTML('test text');
            });
        });

        it("should use the 'title' parameter as the tooltip title", function() {
            setup({ title: 'tip title' });
            runs(function() {
                expect(tip.title).toEqual('tip title');
            });
        });

        it("should use the 'width' parameter as the tooltip width", function() {
            setup({ width: 345 });
            runs(function() {
                expect(tip.el.getWidth()).toEqual(345);
            });
        });

        it("should add the 'cls' parameter to the tooltip element's className", function() {
            setup({ cls: 'test-class-name' });
            runs(function() {
                expect(tip.el.hasCls('test-class-name')).toBeTruthy();
            });
        });

        it("should use the 'autoHide' parameter as the tooltip's autoHide value", function() {
            setup({ autoHide: false });
            runs(function() {
                expect(tip.autoHide).toBeFalsy();
            });
        });

        it("should use the 'dismissDelay' parameter for the tooltip's dismissDelay value", function() {
            setup({ dismissDelay: 123 });
            runs(function() {
                expect(tip.dismissDelay).toEqual(123);
            });
        });

        it("should accept a dismissDelay of 0", function() {
            setup({ dismissDelay: 0 });
            runs(function() {
                expect(tip.dismissDelay).toEqual(0);
            });
        });

        it("should default to the main tip dismissDelay", function() {
            setup({ dismissDelay: null });
            runs(function() {
                expect(tip.dismissDelay).toEqual(5000);
            });
        });

        it("should not throw an error when the registered target is destroyed", function() {
            createTargetEl('id="tipExample1"');
            createTargetEl('id="tipExample2"');

            createTip({ maxWidth: 400 });
            tip.register({
                target: 'tipExample1',
                text: 'Foo'
            });

            tip.register({
                target: 'tipExample2',
                text: 'Bar'
            });

            mouseoverTarget('tipExample1');
            waitsFor(function() {
                return tip.isVisible();
            });
            runs(function() {
                tip.hide();
                Ext.get('tipExample1').destroy();
                mouseoverTarget('tipExample2');
            });

            waitsFor(function() {
                return tip.isVisible();
            });

            runs(function() {
                Ext.get('tipExample2').destroy();
            });
        });
    });

    describe("unregister", function() {
        it("should unregister the element as a target", function() {
            createTargetEl('');
            createTip();
            var spy = spyOn(tip, 'delayShow');

            tip.register({ target: target, text: 'tip text' });
            tip.unregister(target);
            mouseoverTarget();
            expect(spy).not.toHaveBeenCalled();
        });
    });

    describe('interceptTitles', function() {
        it('should remove the title attribute from the target', function() {
            var dom;

            createTargetEl('title="tip text"');

            dom = target.dom;

            // Confirm that the target still has the title attribute with which it was configured.
            expect(dom.getAttribute('title')).toBe('tip text');

            createTip({ interceptTitles: true });
            mouseoverTarget();

            // And now it's gone!
            expect(dom.getAttribute('title')).toBe(null);
        });

        it('should use the title attribute value for the quicktip', function() {
            createTargetEl('title="tip text"');
            createTip({ interceptTitles: true });
            mouseoverTarget();

            waitsFor(function() {
                return tip.isVisible();
            }, 'QuickTip never showed', 2000);

            runs(function() {
                expect(tip.layout.innerCt.dom).hasHTML('tip text');
            });
        });

        it('should use the title attribute value rather than the qtip value when both are set', function() {
            createTargetEl('data-qtip="foobar" title="tip text"');
            createTip({ interceptTitles: true });
            mouseoverTarget();

            waitsFor(function() {
                return tip.isVisible();
            }, 'QuickTip never showed', 2000);

            runs(function() {
                expect(tip.layout.innerCt.dom).hasHTML('tip text');
            });
        });
    });

    describe("size", function() {
        it("should size to the title of the title is larger than the text", function() {
            var body = Ext.htmlEncode('<div style="width: 50px;">a</div>'),
                title = Ext.htmlEncode('<div style="width: 100px;">a</div>');

            runs(function() {
                createTargetEl('data-qtip="' + body + '" data-qtitle="' + title + '"');
                createTip();
                mouseoverTarget();
            });
            waitsFor(function() {
                return tip.isVisible();
            }, "QuickTip never showed");
            runs(function() {
                expect(tip.getWidth()).toBeGreaterThan(100);
            });
        });
    });

});
