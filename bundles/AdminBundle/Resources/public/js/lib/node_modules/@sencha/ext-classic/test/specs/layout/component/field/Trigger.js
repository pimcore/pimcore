xtopSuite("Ext.layout.component.field.Trigger", function() {
    var text, trigger, width, height;

    afterEach(function() {
        Ext.destroy(text, trigger);
        text = trigger = width = null;
    });

    describe("shrink wrap", function() {

        describe("without label", function() {

            it("should have the same size as a text field with a single trigger", function() {
                text = new Ext.form.field.Text({
                    renderTo: Ext.getBody(),
                    shrinkWrap: 3
                });
                trigger = new Ext.form.field.Trigger({
                    renderTo: Ext.getBody(),
                    shrinkWrap: 3
                });
                width = text.getWidth();

                expect(trigger.getWidth()).toBe(width);
                expect(trigger.inputEl.getWidth()).toBe(width - trigger.getTriggerWidth());
            });

            it("should have the same size as a text field with a 3 triggers", function() {
                text = new Ext.form.field.Text({
                    renderTo: Ext.getBody(),
                    shrinkWrap: 3
                });
                trigger = new Ext.form.field.Trigger({
                    renderTo: Ext.getBody(),
                    shrinkWrap: 3,
                    trigger1Cls: 'foo',
                    trigger2Cls: 'bar',
                    trigger3Cls: 'baz'
                });
                width = text.getWidth();

                expect(trigger.getWidth()).toBe(width);
                expect(trigger.inputEl.getWidth()).toBe(width - trigger.getTriggerWidth());
            });

            it("should respect an inputWidth", function() {
                trigger = new Ext.form.field.Trigger({
                    renderTo: Ext.getBody(),
                    shrinkWrap: 3,
                    inputWidth: 200
                });
                expect(trigger.getWidth()).toBe(200);
                expect(trigger.inputEl.getWidth()).toBe(200 - trigger.getTriggerWidth());
            });
        });

        describe("with label", function() {

            describe("labelAlign: 'left'", function() {

                it("should take into account labelWidth", function() {
                    text = new Ext.form.field.Text({
                        renderTo: Ext.getBody(),
                        shrinkWrap: 3,
                        labelWidth: 150,
                        fieldLabel: 'A label'
                    });
                    trigger = new Ext.form.field.Trigger({
                        renderTo: Ext.getBody(),
                        shrinkWrap: 3,
                        labelWidth: 150,
                        fieldLabel: 'A label'
                    });
                    width = text.getWidth();

                    expect(trigger.getWidth()).toBe(width);
                    expect(trigger.inputEl.getWidth()).toBe(width - trigger.getTriggerWidth() - trigger.labelWidth - trigger.labelPad);
                });

                it("should take into account labelPad", function() {
                    text = new Ext.form.field.Text({
                        renderTo: Ext.getBody(),
                        shrinkWrap: 3,
                        labelPad: 20,
                        fieldLabel: 'A label'
                    });
                    trigger = new Ext.form.field.Trigger({
                        renderTo: Ext.getBody(),
                        shrinkWrap: 3,
                        labelPad: 20,
                        fieldLabel: 'A label'
                    });
                    width = text.getWidth();

                    expect(trigger.getWidth()).toBe(width);
                    expect(trigger.inputEl.getWidth()).toBe(width - trigger.getTriggerWidth() - trigger.labelWidth - trigger.labelPad);
                });
            });

            describe("labelAlign: 'top'", function() {
                it("should take ignore labelWidth", function() {
                    text = new Ext.form.field.Text({
                        renderTo: Ext.getBody(),
                        shrinkWrap: 3,
                        labelWidth: 150,
                        fieldLabel: 'A label',
                        labelAlign: 'top'
                    });
                    trigger = new Ext.form.field.Trigger({
                        renderTo: Ext.getBody(),
                        shrinkWrap: 3,
                        labelWidth: 150,
                        fieldLabel: 'A label',
                        labelAlign: 'top'
                    });
                    width = text.getWidth();

                    expect(trigger.getWidth()).toBe(width);
                    expect(trigger.inputEl.getWidth()).toBe(width - trigger.getTriggerWidth());
                });

                it("should take into account labelPad", function() {
                    text = new Ext.form.field.Text({
                        renderTo: Ext.getBody(),
                        shrinkWrap: 3,
                        labelPad: 20,
                        fieldLabel: 'A label',
                        labelAlign: 'top'
                    });
                    trigger = new Ext.form.field.Trigger({
                        renderTo: Ext.getBody(),
                        shrinkWrap: 3,
                        labelPad: 20,
                        fieldLabel: 'A label',
                        labelAlign: 'top'
                    });
                    width = text.getWidth();

                    expect(trigger.getWidth()).toBe(width);
                    expect(trigger.inputEl.getWidth()).toBe(width - trigger.getTriggerWidth());
                });
            });
        });

    });

    describe("configured", function() {
        describe("height", function() {
            beforeEach(function() {
                text = new Ext.form.field.Text({
                    renderTo: Ext.getBody(),
                    height: 200
                });

                trigger = new Ext.form.field.Trigger({
                    renderTo: Ext.getBody(),
                    height: 200
                });

                height = text.getHeight();
            });

            it("should have the same height as text field", function() {
                expect(trigger.getHeight()).toBe(height);
                // AND
                expect(trigger.inputEl.getHeight()).toBe(height);
            });
        });

        describe("width", function() {
            beforeEach(function() {
                text = new Ext.form.field.Text({
                    renderTo: Ext.getBody(),
                    width: 300
                });

                trigger = new Ext.form.field.Trigger({
                    renderTo: Ext.getBody(),
                    hideTrigger: true,
                    width: 300
                });

                width = text.getWidth();
            });

            it("should have the same width as text field w/o trigger", function() {
                expect(trigger.getWidth()).toBe(width);
                // AND
                expect(trigger.inputEl.getWidth()).toBe(width);
            });

            it("should have the same overall width as text field w/ trigger", function() {
                var outerWidth = trigger.getWidth();

                expect(outerWidth).toBe(width);

                var inputWidth = trigger.inputEl.getWidth(),
                    triggerWidth = 0;

                for (var i = 0, l = trigger.triggerEl.elements.length; i < l; i++) {
                    var el = trigger.triggerEl.elements[i];

                    triggerWidth += el.getWidth();
                }

                expect(inputWidth + triggerWidth).toBe(width);
            });
        });
    });
});
