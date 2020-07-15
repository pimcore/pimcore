topSuite("Ext.form.field.Radio", function() {
    var component, radios;

    function makeComponent(config) {
        config = Ext.apply({
            name: 'test',
            renderTo: Ext.getBody()
        }, config);

        return component = new Ext.form.field.Radio(config);
    }

    function makeRadios(count, configFn) {
        var cfg, i;

        for (i = 0; i < count; i++) {
            cfg = {
                inputValue: i + 1
            };

            if (configFn) {
                cfg = configFn(cfg, i);
            }

            radios.push(makeComponent(cfg));
        }

        component = null;

        return radios;
    }

    beforeEach(function() {
        radios = [];
    });

    afterEach(function() {
        Ext.destroy(component);

        for (var i = 0, len = radios.length; i < len; i++) {
            Ext.destroy(radios[i]);
        }

        radios = component = null;
    });

    it("should be registered with the 'radiofield' xtype", function() {
        component = new Ext.form.field.Radio({ name: 'test' });

        expect(component instanceof Ext.form.field.Radio).toBe(true);
        expect(Ext.getClass(component).xtype).toBe("radiofield");
    });

    it("should render input with type='radio'", function() {
        makeComponent();
        expect(component.inputEl.dom.getAttribute('type').toLowerCase()).toEqual("radio");
    });

    describe("configuring", function() {
        it("should have falsy value by default", function() {
            makeComponent();

            expect(component.getValue()).toBe(false);
        });

        it("should respect checked: true", function() {
            makeComponent({ checked: true });

            expect(component.getValue()).toBeTruthy();
        });

        it("should respect checked: false", function() {
            makeComponent({ checked: false });

            expect(component.getValue()).toBeFalsy();
        });
    });

    describe("group value", function() {
        beforeEach(function() {
            makeRadios(5, function(cfg, index) {
                if (index === 2) {
                    cfg.checked = true;
                }

                return cfg;
            });
        });

        it("should get the correct group value", function() {
            expect(radios[0].getGroupValue()).toEqual(3);
        });
    });

    describe("setValue", function() {
        it("should unset the values when checking in a group", function() {
            makeRadios(3);

            expect(radios[0].getGroupValue()).toBeNull();

            radios[1].setValue(true);
            expect(radios[0].getValue()).toBeFalsy();
            expect(radios[1].getValue()).toBeTruthy();
            expect(radios[2].getValue()).toBeFalsy();

            radios[2].setValue(true);
            expect(radios[0].getValue()).toBeFalsy();
            expect(radios[1].getValue()).toBeFalsy();
            expect(radios[2].getValue()).toBeTruthy();
        });

        it("should check the sibling radio matching a passed string value", function() {
            makeRadios(3);

            radios[0].setValue(2);
            expect(radios[0].getValue()).toBeFalsy();
            expect(radios[1].getValue()).toBeTruthy();
            expect(radios[2].getValue()).toBeFalsy();

            radios[0].setValue(3);
            expect(radios[0].getValue()).toBeFalsy();
            expect(radios[1].getValue()).toBeFalsy();
            expect(radios[2].getValue()).toBeTruthy();
        });

        it("should call handlers for all items in a group", function() {
            var handlers = [],
                spies = [],
                i = 0;

            for (i = 0; i < 3; ++i) {
                handlers.push({
                    fn: function() {}
                });
                spies.push(spyOn(handlers[i], 'fn'));
                radios.push(new Ext.form.field.Radio({
                    renderTo: Ext.getBody(),
                    name: 'test',
                    inputValue: i + 1,
                    handler: handlers[i].fn
                }));
            }

            radios[1].setValue(true);
            expect(handlers[1].fn).toHaveBeenCalledWith(radios[1], true);

            radios[0].setValue(true);
            expect(handlers[0].fn).toHaveBeenCalledWith(radios[0], true);
            expect(handlers[1].fn).toHaveBeenCalledWith(radios[1], false);
        });
    });

    describe('getModelData', function() {
        it("should return the inputValue", function() {
            var component = new Ext.form.field.Radio({
                checked: true,
                name: 'test',
                inputValue: 'the-input-value',
                renderTo: Ext.getBody()
            });

            radios = [component];
            expect(component.getModelData().test).toBe('the-input-value');
        });
        it("should return null when unchecked", function() {
            var component = new Ext.form.field.Radio({
                name: 'test',
                inputValue: 'the-input-value',
                renderTo: Ext.getBody()
            });

            radios = [component];
            expect(component.getModelData().test).toBeNull();
        });
    });
});
