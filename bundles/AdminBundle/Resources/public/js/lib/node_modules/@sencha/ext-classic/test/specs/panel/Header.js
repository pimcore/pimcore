topSuite("Ext.panel.Header", ['Ext.Panel'], function() {
    var header;

    function makeHeader(cfg) {
        cfg = Ext.apply({
            title: 'foo',
            renderTo: Ext.getBody()
        }, cfg);

        return header = new Ext.panel.Header(cfg);
    }

    afterEach(function() {
        Ext.destroy(header);
        header = null;
    });

    describe('Title value', function() {
        it('should set it as configured', function() {
            makeHeader({
                title: 10
            });
            expect(header.title.getText()).toBe(10);
        });
    });

    describe("setTitlePosition", function() {
        beforeEach(function() {
            makeHeader({
                tools: [
                    { type: 'close' },
                    { type: 'pin' }
                ]
            });
        });

        it("should insert the header at the new title position", function() {
            header.setTitlePosition(2);
            expect(header.items.getAt(2)).toBe(header.getTitle());
        });

        it("should update the titlePosition property", function() {
            header.setTitlePosition(2);
            expect(header.titlePosition).toBe(2);
        });

        it("should not allow a titlePosition greater than the max item index", function() {
            header.setTitlePosition(3);
            expect(header.items.getAt(2)).toBe(header.getTitle());
            expect(header.titlePosition).toBe(2);
        });
    });

    describe("ARIA", function() {
        it("should have el as ariaEl", function() {
            makeHeader();

            expect(header.ariaEl).toBe(header.el);
        });

        describe("no tools", function() {
            describe("ordinary header", function() {
                beforeEach(function() {
                    makeHeader();
                });

                it("should have presentation ariaRole", function() {
                    expect(header.ariaRole).toBe('presentation');
                });

                it("should have presentation role on header el", function() {
                    expect(header).toHaveAttribute('role', 'presentation');
                });

                it("should have presentation role on titleCmp el", function() {
                    expect(header.titleCmp).toHaveAttribute('role', 'presentation');
                });

                it("should have presentation role on titleCmp textEl", function() {
                    expect(header.titleCmp.textEl).toHaveAttribute('role', 'presentation');
                });

                it("should have FocusableContainer disabled", function() {
                    expect(header.focusableContainer).toBe(false);
                });

                describe("after adding tools", function() {
                    beforeEach(function() {
                        header.addTool({ type: 'expand' });
                    });

                    it("should change ariaRole to toolbar", function() {
                        expect(header.ariaRole).toBe('toolbar');
                    });

                    it("should change header el role to toolbar", function() {
                        expect(header).toHaveAttribute('role', 'toolbar');
                    });

                    it("should not change titleCmp el role", function() {
                        expect(header.titleCmp).toHaveAttribute('role', 'presentation');
                    });

                    it("should not change titleCmp textEl role", function() {
                        expect(header.titleCmp.textEl).toHaveAttribute('role', 'presentation');
                    });

                    it("should enable FocusableContainer", function() {
                        expect(header.focusableContainer).toBe(true);
                    });
                });
            });

            describe("accordion header", function() {
                beforeEach(function() {
                    makeHeader({ isAccordionHeader: true });
                });

                it("should have presentation ariaRole", function() {
                    expect(header.ariaRole).toBe('presentation');
                });

                it("should have presentation role on header el", function() {
                    expect(header).toHaveAttribute('role', 'presentation');
                });

                it("should have tab role on titleCmp el", function() {
                    expect(header.titleCmp).toHaveAttribute('role', 'tab');
                });

                it("should have no role on titleCmp textEl", function() {
                    expect(header.titleCmp.textEl).not.toHaveAttribute('role');
                });

                it("should have FocusableContainer disabled", function() {
                    expect(header.focusableContainer).toBe(false);
                });

                describe("after adding tools", function() {
                    beforeEach(function() {
                        header.addTool({ type: 'pin' });
                    });

                    it("should not change ariaRole", function() {
                        expect(header.ariaRole).toBe('presentation');
                    });

                    it("should not change header el role", function() {
                        expect(header).toHaveAttribute('role', 'presentation');
                    });

                    it("should not change titleCmp el role", function() {
                        expect(header.titleCmp).toHaveAttribute('role', 'tab');
                    });

                    it("should not change titleCmp textEl role", function() {
                        expect(header.titleCmp.textEl).not.toHaveAttribute('role');
                    });

                    it("should not enable FocusableContainer", function() {
                        expect(header.focusableContainer).toBe(false);
                    });
                });
            });
        });

        describe("with tools", function() {
            describe("ordinary header", function() {
                describe("with focusable tool(s)", function() {
                    beforeEach(function() {
                        makeHeader({
                            tools: [{
                                type: 'collapse'
                            }]
                        });
                    });

                    it("should have toolbar ariaRole", function() {
                        expect(header.ariaRole).toBe('toolbar');
                    });

                    it("should have toolbar role on header el", function() {
                        expect(header).toHaveAttribute('role', 'toolbar');
                    });

                    it("should have presentation role on titleCmp", function() {
                        expect(header.titleCmp).toHaveAttribute('role', 'presentation');
                    });

                    it("should have presentation role on titleCmp textEl", function() {
                        expect(header.titleCmp.textEl).toHaveAttribute('role', 'presentation');
                    });

                    it("should have FocusableContainer enabled", function() {
                        expect(header.focusableContainer).toBe(true);
                    });
                });

                describe("with non-focusable tools", function() {
                    beforeEach(function() {
                        makeHeader({
                            tools: [{
                                type: 'close',
                                focusable: false,
                                tabIndex: null
                            }]
                        });
                    });

                    it("should have presentation role", function() {
                        expect(header.ariaRole).toBe('presentation');
                    });

                    it("should have presentation role on header el", function() {
                        expect(header).toHaveAttribute('role', 'presentation');
                    });

                    it("should have presentation role on titleCmp", function() {
                        expect(header.titleCmp).toHaveAttribute('role', 'presentation');
                    });

                    it("should have presentation role on titleCmp textEl", function() {
                        expect(header.titleCmp.textEl).toHaveAttribute('role', 'presentation');
                    });

                    it("should have FocusableContainer disabled", function() {
                        expect(header.focusableContainer).toBe(false);
                    });
                });
            });

            describe("accordion header", function() {
                beforeEach(function() {
                    makeHeader({
                        isAccordionHeader: true,
                        tools: [{
                            type: 'collapse'
                        }]
                    });
                });

                it("should have presentation ariaRole", function() {
                    expect(header.ariaRole).toBe('presentation');
                });

                it("should have presentation role on header el", function() {
                    expect(header).toHaveAttribute('role', 'presentation');
                });

                it("should have tab role on titleCmp el", function() {
                    expect(header.titleCmp).toHaveAttribute('role', 'tab');
                });

                it("should have no role on titleCmp textEl", function() {
                    expect(header.titleCmp.textEl).not.toHaveAttribute('role');
                });

                it("should have FocusableContainer disabled", function() {
                    expect(header.focusableContainer).toBe(false);
                });
            });
        });
    });
});
