topSuite("Ext.ProgressBar", function() {
    var c;

    function makeProgress(config) {
        c = new Ext.ProgressBar(Ext.apply({
            renderTo: Ext.getBody(),
            width: 100
        }, config));
    }

    function expectHtml(want) {
        var have = c.textEl.elements[0].innerHTML;

        expect(have).toBe(want);

        have = (c.textEl.elements[1].innerHTML || '').replace(/<\/?.*?>/g, '');
        expect(have).toBe(want);
    }

    afterEach(function() {
        c = Ext.destroy(c);
    });

    describe("init", function() {
        describe("no configs", function() {
            beforeEach(function() {
                makeProgress();
            });

            it("should have value of 0", function() {
                expect(c.value).toBe(0);
            });

            it("should not set text", function() {
                expect(c.text).toBe('');
            });

            it("should display 0%", function() {
                expectHtml('0%');
            });
        });

        describe("value with no text config", function() {
            beforeEach(function() {
                makeProgress({
                    value: 0.42
                });
            });

            it("should have value of 0.42", function() {
                expect(c.value).toBe(0.42);
            });

            it("should not set text", function() {
                expect(c.text).toBe('');
            });

            it("should display 42%", function() {
                expectHtml('42%');
            });
        });

        describe("with text config", function() {
            beforeEach(function() {
                makeProgress({
                    text: '0/100'
                });
            });

            it("should have value of 0", function() {
                expect(c.value).toBe(0);
            });

            it("should set text", function() {
                expect(c.text).toBe('0/100');
            });

            it("should display 0/100", function() {
                expectHtml('0/100');
            });
        });
    });

    describe("setValue", function() {
        it("should cast undefined to 0", function() {
            makeProgress({
                value: 50
            });
            c.setValue(undefined);
            expect(c.getValue()).toBe(0);
        });

        it("should cast null to 0", function() {
            makeProgress({
                value: 50
            });
            c.setValue(null);
            expect(c.getValue()).toBe(0);
        });
    });

    describe("updateProgress", function() {
        beforeEach(function() {
            makeProgress();
        });

        describe("no text", function() {
            beforeEach(function() {
                c.updateProgress(0.42);
            });

            it("should set value to 0.42", function() {
                expect(c.value).toBe(0.42);
            });

            it("should not set text", function() {
                expect(c.text).toBe('');
            });

            it("should display 42%", function() {
                expectHtml('42%');
            });
        });

        describe("with text", function() {
            beforeEach(function() {
                c.updateProgress(0.99, 'Almost there');
            });

            it("should set value to 0.99", function() {
                expect(c.value).toBe(0.99);
            });

            it("should set text", function() {
                expect(c.text).toBe('Almost there');
            });

            it("should display the text", function() {
                expectHtml('Almost there');
            });
        });
    });

    describe("wait", function() {
        beforeEach(function() {
            makeProgress();
        });
        it("should be able to set a text", function() {
            c.wait({
                interval: 100,
                duration: 1000,
                text: 'Foo...',
                scope: c,
                fn: function() {
                    this.updateText('Bar');
                }
            });

            waitsFor(function() {
                return c.text.length;
            });

            runs(function() {
                expect(c.text).toBe('Foo...');
            });

            waitsFor(function() {
                return c.text !== 'Foo...';
            }, 'callback text', 2000);

            runs(function() {
                expect(c.text).toBe('Bar');
            });
        });

        it("should display %age if no text has been specified", function() {
            var innerHtml;

            c.wait({
                interval: 100,
                duration: 1000
            });

            waitsFor(function() {
                innerHtml = c && c.textEl && c.textEl.elements[1].innerHTML;

                return innerHtml.length;
            });

            runs(function() {
                expect(innerHtml).toBe('10%');
            });
        });
    });

    describe("Accessibility", function() {
        describe("general", function() {
            beforeEach(function() {
                makeProgress();
            });

            it("should have progressbar role", function() {
                expect(c).toHaveAttr('role', 'progressbar');
            });

            it("should be tabbable", function() {
                expect(c).toHaveAttr('tabIndex', '0');
            });

            it("should have aria-valuemin attribute", function() {
                expect(c).toHaveAttr('aria-valuemin', '0');
            });

            it("should have aria-valuemax attribute", function() {
                expect(c).toHaveAttr('aria-valuemax', '100');
            });

            it("should have aria-valuenow attribute", function() {
                expect(c).toHaveAttr('aria-valuenow', '0');
            });
        });

        describe("no text", function() {
            beforeEach(function() {
                makeProgress();
            });

            it("should not have aria-valuetext attribute", function() {
                expect(c).not.toHaveAttr('aria-valuetext');
            });

            describe("updating with no text", function() {
                beforeEach(function() {
                    c.updateProgress(0.42);
                });

                it("should update aria-valuenow attribute", function() {
                    expect(c).toHaveAttr('aria-valuenow', '42');
                });

                it("should not set aria-valuetext when updating value", function() {
                    expect(c).not.toHaveAttr('aria-valuetext');
                });
            });

            describe("updating with text", function() {
                beforeEach(function() {
                    c.updateProgress(0.42, '42% complete');
                });

                it("should update aria-valuenow attribute", function() {
                    expect(c).toHaveAttr('aria-valuenow', '42');
                });

                it("should update aria-valuetext attribute", function() {
                    expect(c).toHaveAttr('aria-valuetext', '42% complete');
                });
            });
        });

        describe("with text", function() {
            beforeEach(function() {
                makeProgress({
                    text: 'Complete 10%'
                });
            });

            it("should have aria-valuetext attribute", function() {
                expect(c).toHaveAttr('aria-valuetext', 'Complete 10%');
            });

            describe("updating with no text", function() {
                beforeEach(function() {
                    c.updateProgress(0.58);
                });

                it("should update aria-valuenow attribute", function() {
                    expect(c).toHaveAttr('aria-valuenow', '58');
                });

                it("should remove aria-valuetext attribute", function() {
                    expect(c).not.toHaveAttribute('aria-valuetext');
                });
            });

            describe("updating with text", function() {
                beforeEach(function() {
                    c.updateProgress(0.88, 'Time jump!');
                });

                it("should update aria-valuenow attribute", function() {
                    expect(c).toHaveAttr('aria-valuenow', '88');
                });

                it("should update aria-valuetext attribute", function() {
                    expect(c).toHaveAttr('aria-valuetext', 'Time jump!');
                });
            });
        });
    });
});
