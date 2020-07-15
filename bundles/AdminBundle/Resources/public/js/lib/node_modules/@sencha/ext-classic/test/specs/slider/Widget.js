topSuite("Ext.slider.Widget", ['Ext.Panel', 'Ext.app.ViewModel'], function() {
    var panel, slider, viewModel, data;

    afterEach(function() {
        panel = slider = viewModel = data = Ext.destroy(panel, slider, viewModel);
    });

    function notify() {
        viewModel.getScheduler().notify();
    }

    function makeSlider(config, useBinding) {
        if (useBinding) {
            viewModel = new Ext.app.ViewModel({
                data: {
                    val: 20
                }
            });
            data = viewModel.getData();

            config.bind = '{val}';
            config.viewModel = viewModel;
        }

        panel = Ext.create({
            xtype: 'panel',
            renderTo: Ext.getBody(),
            items: slider = Ext.create(Ext.apply({
                xtype: 'sliderwidget',
                width: 200,
                height: 20,
                animate: false
            }, config))
        });

        if (useBinding) {
            notify();
        }
    }

    describe('binding', function() {
        it('should receive the initial value', function() {
            makeSlider({}, true);

            var v = slider.getValue();

            expect(v).toBe(20);
        });

//        it('should not update viewModel on setValue incomplete', function () {
//            makeSlider({
//                publishOnComplete: true
//            });
//
//            slider.setValue(50);
//            notify();
//
//            expect(data.val).toBe(20);
//        });

        it('should update viewModel on setValue complete', function() {
            makeSlider({
                publishOnComplete: true
            }, true);

            slider.setValue(50);
            notify();

            expect(data.val).toBe(50);
        });

        it('should update viewModel on setValue when publishOnComplete:false', function() {
            makeSlider({
                publishOnComplete: false
            }, true);

            slider.setValue(50);
            notify();

            expect(data.val).toBe(50);
        });
    });

    describe("update minValue", function() {
        var thumb;

        beforeEach(function() {
            makeSlider({
                minValue: 10,
                maxValue: 50,
                value: 20
            }, false);

            thumb = slider.getThumb(0);
        });

        it("should update value when minValue is greater than current value", function() {
            var oldValue, newValue;

            oldValue = slider.getValue();
            // update minValue
            slider.setMinValue(40);
            newValue = slider.getValue();
            // new slider "value" should be greater than the old value since minValue change should enforce range
            expect(oldValue).toBeLessThan(newValue);
        });

        it("should update position of thumb when new minValue is less than current value", function() {
            var expectedPos,
                thumbPos;

            // where should thumb be?
            expectedPos = slider.calculateThumbPosition(slider.getValue());
            // where is thumb?
            thumbPos = thumb.dom.style.left;
            // run expectations for current state
            // slider position should be 25% (10|[20]|30|40|50)
            expect(expectedPos).toBe(25);
            expect(thumbPos).toBe(expectedPos + '%');

            // update minValue
            slider.setMinValue(0);

            // where is thumb?
            expectedPos = slider.calculateThumbPosition(slider.getValue());
            // where thumb is
            thumbPos = thumb.dom.style.left;
            // run expectations for current state
            // slider position should be 40% (0|10|[20]|30|40|50)
            expect(expectedPos).toBe(40);
            expect(thumbPos).toBe(expectedPos + '%');
        });

        it("should update position of thumb when new minValue is greater than current value", function() {
            var expectedPos,
                thumbPos;

            // where should thumb be?
            expectedPos = slider.calculateThumbPosition(slider.getValue());
            // where is thumb?
            thumbPos = thumb.dom.style.left;
            // run expectations for current state
            // slider position should be 25% (10|[20]|30|40|50)
            expect(expectedPos).toBe(25);
            expect(thumbPos).toBe(expectedPos + '%');

            // update minValue
            slider.setMinValue(30);

            // where should thumb be?
            expectedPos = slider.calculateThumbPosition(slider.getValue());
            // where is thumb?
            thumbPos = thumb.dom.style.left;
            // run expectations for current state
            // slider position should be 50% (10|20|[30]|40|50)
            expect(expectedPos).toBe(0);
            expect(thumbPos).toBe(expectedPos + '%');
        });
    });

    describe("update maxValue", function() {
        var thumb;

        beforeEach(function() {
            makeSlider({
                maxValue: 40,
                value: 20
            }, false);

            thumb = slider.getThumb(0);
        });

        it("should update value when maxValue is less than current value", function() {
            var oldValue, newValue;

            oldValue = slider.getValue();
            // update maxValue
            slider.setMaxValue(10);
            newValue = slider.getValue();
            // new slider "value" should be less than the old value since maxValue change should enforce range
            expect(oldValue).toBeGreaterThan(newValue);
        });

        it("should update position of thumb when new maxValue is less than current value", function() {
            var expectedPos,
                thumbPos;

            // where should thumb be?
            expectedPos = slider.calculateThumbPosition(slider.getValue());
            // where thumb is
            thumbPos = thumb.dom.style.left;
            // run expectations for current state
            // slider position should be 50% (20/40)
            expect(expectedPos).toBe(50);
            expect(thumbPos).toBe(expectedPos + '%');

            // update maxValue
            slider.setMaxValue(10);

            // where should thumb be?
            expectedPos = slider.calculateThumbPosition(slider.getValue());
            // where thumb is
            thumbPos = thumb.dom.style.left;
            // run expectations for current state
            // slider position should be 50% (20/40)
            expect(expectedPos).toBe(100);
            expect(thumbPos).toBe(expectedPos + '%');
        });

        it("should update position of thumb when new maxValue is greater than current value", function() {
            var expectedPos,
                thumbPos;

            // where should thumb be?
            expectedPos = slider.calculateThumbPosition(slider.getValue());
            // where thumb is
            thumbPos = thumb.dom.style.left;
            // run expectations for current state
            // slider position should be 50% (20/40)
            expect(expectedPos).toBe(50);
            expect(thumbPos).toBe(expectedPos + '%');

            // update maxValue
            slider.setMaxValue(80);

            // where should thumb be?
            expectedPos = slider.calculateThumbPosition(slider.getValue());
            // where thumb is
            thumbPos = thumb.dom.style.left;
            // run expectations for current state
            // slider position should be 50% (20/40)
            expect(expectedPos).toBe(25);
            expect(thumbPos).toBe(expectedPos + '%');
        });
    });
});
