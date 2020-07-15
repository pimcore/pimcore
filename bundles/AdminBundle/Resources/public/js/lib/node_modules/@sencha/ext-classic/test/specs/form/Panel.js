topSuite("Ext.form.Panel", function() {
    var panel;

    function createPanel(config) {
        config = config || {};
        panel = new Ext.form.Panel(config);
    }

    afterEach(function() {
        panel = Ext.destroy(panel);
    });

    describe("creation", function() {
        it("should extend Ext.Panel", function() {
            expect(Ext.form.Panel.superclass).toBe(Ext.Panel.prototype);
        });

        it("should be registered with the 'form' xtype", function() {
            var component = Ext.create("Ext.form.Panel", { name: 'test' });

            expect(component instanceof Ext.form.Panel).toBe(true);
            expect(Ext.getClass(component).xtype).toBe("form");
            component.destroy();
        });
    });

    describe("form property", function() {
        it("should instantiate a Ext.form.Basic as its 'form' property", function() {
            createPanel();
            expect(panel.form instanceof Ext.form.Basic).toBeTruthy();
        });

        it("should return the BasicForm when calling the getForm() method", function() {
            createPanel();
            expect(panel.getForm() instanceof Ext.form.Basic).toBeTruthy();
        });
    });

    describe("default configs", function() {
        var undef,
            expected,
            cfg;

        function createTest(name, value) {
            it("should default the '" + cfg + "' config to " + expected[cfg], function() {
                createPanel();
                expect(panel[name]).toBe(value);
            });
        }

        expected = {
            hideLabels: undef,
            labelPad: undef,
            labelSeparator: undef,
            labelWidth: undef,
            labelAlign: undef,
            ariaRole: 'presentation',
            bodyAriaRole: 'form'
        };

        for (cfg in expected) {
            if (expected.hasOwnProperty(cfg)) {
                createTest.call(this, cfg, expected[cfg]);
            }
        }
    });

    describe("event relaying", function() {
        function testRelay(eventName) {
            var spy = jasmine.createSpy(eventName + ' handler');

            createPanel();
            panel.on(eventName, spy);
            panel.getForm().fireEvent(eventName);
            expect(spy).toHaveBeenCalled();
        }

        it("should relay 'beforeaction' events from the BasicForm", function() {
            testRelay('beforeaction');
        });
        it("should relay 'actionfailed' events from the BasicForm", function() {
            testRelay('actionfailed');
        });
        it("should relay 'actioncomplete' events from the BasicForm", function() {
            testRelay('actioncomplete');
        });
        it("should relay 'validitychange' events from the BasicForm", function() {
            testRelay('validitychange');
        });
        it("should relay 'dirtychange' events from the BasicForm", function() {
            testRelay('dirtychange');
        });
    });

    describe("destroying", function() {
        it("should call its form object's destroy method", function() {
            createPanel();
            var spy = spyOn(panel.getForm(), 'destroy');

            panel.destroy();

            expect(spy).toHaveBeenCalled();
        });
    });

    describe("fieldDefaults", function() {
        it("should copy properties to a sub-field if those properties are not already configured on the field", function() {
            createPanel({
                fieldDefaults: {
                    dummyConfig: 'foo'
                },
                renderTo: Ext.getBody()
            });
            var field = panel.add({ xtype: 'textfield', name: 'myfield' });

            expect(field.dummyConfig).toBe('foo');
        });

        it("should not copy properties to a sub-field if those properties are already configured on the field", function() {
            createPanel({
                fieldDefaults: {
                    dummyConfig: 'foo'
                }
            });
            var field = panel.add({ xtype: 'textfield', name: 'myfield', dummyConfig: 'bar' });

            expect(field.dummyConfig).toBe('bar');
        });

        it("should copy fieldDefaults deep", function() {
            createPanel({
                renderTo: Ext.getBody(),
                fieldDefaults: {
                    dummyConfig: 'foo'
                },
                items: {
                    xtype: 'container',
                    items: {
                        xtype: 'container',
                        items: {
                            xtype: 'container',
                            items: {
                                xtype: 'textfield',
                                itemId: 'foo'
                            }
                        }
                    }
                }
            });
            var field = panel.down('#foo');

            expect(field.dummyConfig).toBe('foo');
        });

    });

    describe("minButtonWidth config", function() {
        it("should copy to items in the 'buttons' legacy toolbar config", function() {
            var panelCfg = {
                    minButtonWidth: 1234,
                    buttons: [{
                        text: 'foo'
                    }]
                };

            createPanel(panelCfg);
            var docked = panel.getDockedItems();

            expect(docked[docked.length - 1].child('button').minWidth).toBe(1234);
        });

        it("should not copy to items in the 'buttons' legacy toolbar config with an explicit minWidth", function() {
            var panelCfg = {
                    minButtonWidth: 1234,
                    buttons: [{
                        text: 'foo',
                        minWidth: 2345
                    }]
                };

            createPanel(panelCfg);
            var docked = panel.getDockedItems();

            expect(docked[docked.length - 1].child('button').minWidth).toBe(2345);
        });
    });

    describe("load method", function() {
        it("should call the load method of the BasicForm", function() {
            createPanel();
            spyOn(panel.getForm(), 'load');
            panel.load({ foo: 'bar' });
            expect(panel.getForm().load).toHaveBeenCalledWith({ foo: 'bar' });
        });
    });

    describe("submit method", function() {
        it("should call the submit method of the BasicForm", function() {
            createPanel();
            spyOn(panel.getForm(), 'submit');
            panel.submit({ foo: 'bar' });
            expect(panel.getForm().submit).toHaveBeenCalledWith({ foo: 'bar' });
        });
    });

    describe("polling", function() {
        it("should call the startPolling method if the 'pollForChanges' config is true", function() {
            createPanel({
                pollForChanges: true,
                startPolling: jasmine.createSpy()
            });
            expect(panel.startPolling).toHaveBeenCalled();
        });

        it("should pass the 'pollInterval' config to the startPolling method", function() {
            createPanel({
                pollForChanges: true,
                pollInterval: 12345,
                startPolling: jasmine.createSpy()
            });
            expect(panel.startPolling).toHaveBeenCalledWith(12345);
        });

        it("should start running the 'checkChange' method on an interval", function() {
            runs(function() {
                createPanel();
                spyOn(panel, 'checkChange');
                panel.startPolling(1);
            });
            waitsFor(function() {
                return panel.checkChange.callCount > 1;
            }, 'did not start polling');
        });
    });

    describe("ARIA", function() {
        beforeEach(function() {
            createPanel({
                renderTo: Ext.getBody()
            });
        });

        describe("attributes", function() {
            it("should have form role on the body", function() {
                expect(panel.body).toHaveAttr('role', 'form');
            });
        });
    });
});
