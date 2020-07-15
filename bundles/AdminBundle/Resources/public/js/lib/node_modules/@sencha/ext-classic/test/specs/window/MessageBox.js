topSuite("Ext.window.MessageBox", function() {
    var M;

    beforeEach(function() {
        M = new Ext.window.MessageBox();
    });

    afterEach(function() {
        if (M.isVisible()) {
            M.hide();
        }

        Ext.destroy(M);
    });

    it("should have a single instance", function() {
        var MessageBox = Ext.MessageBox,
            Msg = Ext.Msg;

        expect(MessageBox).toBe(Msg);
        expect(MessageBox instanceof Ext.window.MessageBox).toBe(true);
    });

    it('should show up on top of another window', function() {
        var win = Ext.widget('window', {
            height: 200,
            width: 200
        });

        win.show();

        M.alert('Title', 'Message');
        expect(M.el.dom.style.zIndex).toBeGreaterThan(win.el.dom.style.zIndex);
        Ext.destroy(win);
    });

    it("should be able to hide before being shown", function() {
        expect(function() {
            M.hide();
        }).not.toThrow();
    });

    it('should show even when not passed a config object', function() {
        expect(M.show().rendered).toBe(true);
    });

    describe('MessageBox header', function() {
        it('should not create a header by default', function() {
            expect(M.header).toBeUndefined();
        });

        describe('passed in the config to the constructor', function() {
            beforeEach(function() {
                // Note that beforeEach creates a MessageBox, so destroy it before we create another!
                M.destroy();
            });

            it('should create a header when `true`', function() {
                M = new Ext.window.MessageBox({
                    header: true
                }).show();

                expect(M.header).toBeDefined();
            });

            it('should create a header when an object`', function() {
                M = new Ext.window.MessageBox({
                    header: {
                        title: 'GLUMR'
                    }
                }).show();

                expect(M.header).toBeDefined();
                expect(M.header.getTitle().getText()).toBe('GLUMR');
                expect(M.header.isHeader).toBe(true);
            });

            it('should not create a header when `false`', function() {
                M = new Ext.window.MessageBox({
                    header: false
                }).show();

                expect(M.header).toBe(false);
            });
        });
    });

    describe('MessageBox title', function() {
        it('should set an HTML entitiy as the title if none is provided (default)', function() {
            M.show();

            expect(M.title).toBe('&#160;');
        });

        it('should set the title on the instance', function() {
            M.show({ title: 'Bob the Cat' });

            expect(M.title).toBe('Bob the Cat');
        });

        it('should pass the title to the Header constructor', function() {
            M.show({ title: 'Chuck the Cat' });

            expect(M.header.getTitle().getText()).toBe('Chuck the Cat');
        });

        it('should set the same title on the instance and on the Header', function() {
            M.show({ title: 'Chuck the Cat' });

            expect(M.title).toBe(M.header.getTitle().getText());
        });

        it("should accept empty string as valid title", function() {
            M.show({ title: "foo" });
            M.show({ title: "" });

            expect(M.header.getTitle().getText()).toBe('&#160;');
        });

        describe('passed in the config to the constructor', function() {
            beforeEach(function() {
                // Note that beforeEach creates a MessageBox, so destroy it before we create another!
                M.destroy();
            });

            it('should pass the title to the Header constructor', function() {
                M = new Ext.window.MessageBox({
                    title: 'Mr. G'
                }).show();

                expect(M.header.getTitle().getText()).toBe('Mr. G');
            });

            it('should set the same title on the instance and on the Header', function() {
                M = new Ext.window.MessageBox({
                    title: 'Kerfuffle'
                }).show();

                expect(M.title).toBe(M.header.getTitle().getText());
            });

            it('should give precedence to the title in the header config if both are present', function() {
                M = new Ext.window.MessageBox({
                    header: {
                        title: 'Zap!'
                    },
                    title: 'Foo'
                }).show();

                expect(M.title).toBe('Zap!');
            });

            it('should not "win" if show is also called with a title config', function() {
                M = new Ext.window.MessageBox({
                    header: {
                        title: 'Zap!'
                    },
                    title: 'Foo'
                }).show({
                    title: 'Bar'
                });

                expect(M.title).toBe('Bar');
            });

            it("should accept empty string as a valid default header title", function() {
                M = new Ext.window.MessageBox({
                    header: {
                        title: ''
                    }
                });

                M.show({ title: 'throbbe' });
                M.show({});

                expect(M.header.getTitle().getText()).toBe('&#160;');
            });
        });
    });

    describe("iconCls", function() {
        it("should accept empty string in config", function() {
            M.show({ iconCls: 'frobbe' });
            M.show({ iconCls: '' });

            expect(M.header.getTitle().iconEl.hasCls('frobbe')).toBe(false);
        });

        describe("passed in constructor config", function() {
            beforeEach(function() {
                M.destroy();
            });

            it("should accept default value for iconCls", function() {
                M = new Ext.window.MessageBox({
                    header: {
                        iconCls: 'bonzo'
                    }
                });

                M.show({});

                expect(M.header.getTitle().iconEl.hasCls('bonzo')).toBe(true);
            });
        });
    });

    describe("custom button text", function() {
        var oldText;

        beforeEach(function() {
            oldText = M.buttonText;
            M.buttonText = {
                ok: 'okText',
                yes: 'yesText',
                no: 'noText',
                cancel: 'cancelText'
            };
        });

        afterEach(function() {
            M.buttonText = oldText;
        });

        it("should apply custom text to the buttons", function() {
            var btns = M.msgButtons;

            M.show({
                buttons: M.YESNO
            });
            expect(btns.yes.text).toBe('yesText');
            expect(btns.no.text).toBe('noText');

            M.hide();

            M.show({
                buttons: M.OKCANCEL
            });
            expect(btns.ok.text).toBe('okText');
            expect(btns.cancel.text).toBe('cancelText');
        });

        it("should persist the custom text on each show", function() {
            var btns = M.msgButtons;

            M.show({
                buttons: M.YES
            });
            expect(btns.yes.text).toBe('yesText');

            M.hide();

            M.show({
                buttons: M.YES
            });
            expect(btns.yes.text).toBe('yesText');
        });

        it("should accept a buttonText config", function() {
            var btns = M.msgButtons;

            M.show({
                buttons: M.YESNO,
                buttonText: {
                    yes: 'newYesText',
                    no: 'newNoText'
                }
            });
            expect(btns.yes.text).toBe('newYesText');
            expect(btns.no.text).toBe('newNoText');
        });
    });

    describe("shortcuts", function() {

        describe("buttons", function() {
            it("should use the OK shortcut", function() {
                M.show({
                    buttons: M.OK
                });
                var btns = M.msgButtons;

                expect(btns.yes.isVisible()).toBe(false);
                expect(btns.no.isVisible()).toBe(false);
                expect(btns.ok.isVisible()).toBe(true);
                expect(btns.cancel.isVisible()).toBe(false);
            });

            it("should use the YES shortcut", function() {
                M.show({
                    buttons: M.YES
                });
                var btns = M.msgButtons;

                expect(btns.yes.isVisible()).toBe(true);
                expect(btns.no.isVisible()).toBe(false);
                expect(btns.ok.isVisible()).toBe(false);
                expect(btns.cancel.isVisible()).toBe(false);
            });

            it("should use the NO shortcut", function() {
                M.show({
                    buttons: M.NO
                });
                var btns = M.msgButtons;

                expect(btns.yes.isVisible()).toBe(false);
                expect(btns.no.isVisible()).toBe(true);
                expect(btns.ok.isVisible()).toBe(false);
                expect(btns.cancel.isVisible()).toBe(false);
            });

            it("should use the CANCEL shortcut", function() {
                M.show({
                    buttons: M.CANCEL
                });
                var btns = M.msgButtons;

                expect(btns.yes.isVisible()).toBe(false);
                expect(btns.no.isVisible()).toBe(false);
                expect(btns.ok.isVisible()).toBe(false);
                expect(btns.cancel.isVisible()).toBe(true);
            });

            it("should use the OKCANCEL shortcut", function() {
                M.show({
                    buttons: M.OKCANCEL
                });
                var btns = M.msgButtons;

                expect(btns.yes.isVisible()).toBe(false);
                expect(btns.no.isVisible()).toBe(false);
                expect(btns.ok.isVisible()).toBe(true);
                expect(btns.cancel.isVisible()).toBe(true);
            });

            it("should use the YESNO shortcut", function() {
                M.show({
                    buttons: M.YESNO
                });
                var btns = M.msgButtons;

                expect(btns.yes.isVisible()).toBe(true);
                expect(btns.no.isVisible()).toBe(true);
                expect(btns.ok.isVisible()).toBe(false);
                expect(btns.cancel.isVisible()).toBe(false);
            });

            it("should use the YESNOCANCEL shortcut", function() {
                M.show({
                    buttons: M.YESNOCANCEL
                });
                var btns = M.msgButtons;

                expect(btns.yes.isVisible()).toBe(true);
                expect(btns.no.isVisible()).toBe(true);
                expect(btns.ok.isVisible()).toBe(false);
                expect(btns.cancel.isVisible()).toBe(true);
            });
        });

        describe("confirm", function() {

            it("should configure yes/no buttons", function() {
                M.confirm('a', 'b');
                var btns = M.msgButtons;

                expect(btns.yes.isVisible()).toBe(true);
                expect(btns.no.isVisible()).toBe(true);
                expect(btns.ok.isVisible()).toBe(false);
                expect(btns.cancel.isVisible()).toBe(false);
            });

            it("should show the close tool", function() {
                M.confirm('a', 'b');
                expect(M.down('tool').isVisible()).toBe(true);
            });
        });

        describe("prompt", function() {
            it("should configure ok/cancel buttons", function() {
                M.prompt('a', 'b');
                var btns = M.msgButtons;

                expect(btns.yes.isVisible()).toBe(false);
                expect(btns.no.isVisible()).toBe(false);
                expect(btns.ok.isVisible()).toBe(true);
                expect(btns.cancel.isVisible()).toBe(true);
            });

            it("should show the close tool", function() {
                M.prompt('a', 'b');
                expect(M.down('tool').isVisible()).toBe(true);
            });
        });

        describe("wait", function() {
            it("should hide all buttons", function() {
                M.wait('a', 'b');
                var btns = M.msgButtons;

                expect(btns.yes.isVisible()).toBe(false);
                expect(btns.no.isVisible()).toBe(false);
                expect(btns.ok.isVisible()).toBe(false);
                expect(btns.cancel.isVisible()).toBe(false);
            });

            it("should hide the close tool", function() {
                M.wait('a', 'b');
                expect(M.down('tool').isVisible()).toBe(false);
            });
        });

        describe("alert", function() {
            it("should configure an ok button", function() {
                M.alert('a', 'b');
                var btns = M.msgButtons;

                expect(btns.yes.isVisible()).toBe(false);
                expect(btns.no.isVisible()).toBe(false);
                expect(btns.ok.isVisible()).toBe(true);
                expect(btns.cancel.isVisible()).toBe(false);
            });

            it("should show the close tool", function() {
                M.alert('a', 'b');
                expect(M.down('tool').isVisible()).toBe(true);
            });
        });

        describe("progress", function() {
            it("should hide all buttons", function() {
                M.progress('a', 'b');
                var btns = M.msgButtons;

                expect(btns.yes.isVisible()).toBe(false);
                expect(btns.no.isVisible()).toBe(false);
                expect(btns.ok.isVisible()).toBe(false);
                expect(btns.cancel.isVisible()).toBe(false);
            });

            it("should show the close tool", function() {
                M.progress('a', 'b');
                expect(M.down('tool').isVisible()).toBe(true);
            });
        });
    });

    describe("callbacks", function() {
        var click;

        beforeEach(function() {
            click = function(btn) {
                if (typeof btn === 'string') {
                    btn = M.msgButtons[btn];
                }

                btn.onClick({
                    button: 0,
                    preventDefault: Ext.emptyFn,
                    stopEvent: Ext.emptyFn
                });
            };
        });

        afterEach(function() {
            click = null;
        });

        it("should pass ok when ok is clicked", function() {
            var name;

            M.show({
                buttons: M.OK,
                callback: function(btn) {
                    name = btn;
                }
            });
            click('ok');
            expect(name).toBe('ok');
        });

        it("should pass cancel when cancel is clicked", function() {
            var name;

            M.show({
                buttons: M.CANCEL,
                callback: function(btn) {
                    name = btn;
                }
            });
            click('cancel');
            expect(name).toBe('cancel');
        });

        it("should pass yes when yes is clicked", function() {
            var name;

            M.show({
                buttons: M.YES,
                callback: function(btn) {
                    name = btn;
                }
            });
            click('yes');
            expect(name).toBe('yes');
        });

        it("should pass no when no is clicked", function() {
            var name;

            M.show({
                buttons: M.NO,
                callback: function(btn) {
                    name = btn;
                }
            });
            click('no');
            expect(name).toBe('no');
        });

        it("should pass cancel when close is pressed", function() {
            var name;

            M.show({
                buttons: M.OKCANCEL,
                callback: function(btn) {
                    name = btn;
                }
            });
            click(M.down('tool'));
            expect(name).toBe('cancel');
        });
    });

    describe("closable", function() {
        it("should be closable by default", function() {
            M.show({
                title: 'a',
                msg: 'b'
            });
            expect(M.down('tool').isVisible()).toBe(true);
        });

        it("should not show the tool if closable it set to false", function() {
            M.show({
                title: 'a',
                msg: 'b',
                closable: false
            });
            expect(M.down('tool').isVisible()).toBe(false);
        });
    });

    describe("ARIA", function() {
        describe("aria-describedby", function() {
            it("should set aria-describedby for alert", function() {
                M.alert('foo', 'bar');

                expect(M.ariaEl).toHaveAttribute('aria-describedby', M.msg.id);
            });

            it("should set aria-describedby for confirm", function() {
                M.confirm('blerg', 'zingbong');

                expect(M.ariaEl).toHaveAttribute('aria-describedby', M.msg.id);
            });

            it("should remove aria-describedby for prompt", function() {
                M.prompt('quiz', 'type something');

                expect(M.ariaEl).not.toHaveAttribute('aria-describedby');
            });
        });

        describe("aria-labelledby", function() {
            describe("textField", function() {
                beforeEach(function() {
                    M.show({
                        title: 'throbbe',
                        msg: 'bonzo',
                        prompt: true
                    });
                });

                it("should not have for attribute on labelEl", function() {
                    expect(M.textField.labelEl).not.toHaveAttribute('for');
                });

                it("should have aria-labelledby attribute on inputEl", function() {
                    expect(M.textField.inputEl).toHaveAttribute('aria-labelledby', M.msg.id);
                });
            });

            describe("textArea", function() {
                beforeEach(function() {
                    M.show({
                        title: 'changa',
                        msg: 'masala',
                        multiline: true
                    });
                });

                it("should not have for attribute on labelEl", function() {
                    expect(M.textArea.labelEl).not.toHaveAttribute('for');
                });

                it("should have aria-labelledby attribute on inputEl", function() {
                    expect(M.textArea.inputEl).toHaveAttribute('aria-labelledby', M.msg.id);
                });
            });
        });
    });

    describe("layouts", function() {

        var widths = [
            null,
            10,
            250,
            10000
        ],
        longLine = "a b c d e f g h i j k l m n o p q r s t u v w x y z",
        veryLongLine = longLine + ' ' + longLine.toUpperCase() + '<br>',
        shortMsg = [
            "line1<br>",
            "line2<br>",
            "line3<br>",
            "line4<br>",
            "line5<br>",
            "<ol>",
            "<li>list1 list1 list1</li>",
            "<li>list2 list2 list2</li>",
            "</ol>",
            "line6<br>"
        ].join(''),
        minW = 250,
        minH = 110,
        maxW = 600,
        maxH = 500,
        framePadding = 4,
        hboxPadding = 10,
        longMsg = veryLongLine + '<br>' + shortMsg,
        headerPad = 5,
        displayTopPad = 3,
        displayBtmMargin = 5,
        footerPadTB = 4,
        footerPadL = 6,
        footerPadR = 0,
        border = 1,
        mbox = Ext.MessageBox;

        function verifyMessageBoxLayout(isShrinkWrap) {

            var panelH = mbox.el.getHeight(),
                panelW = mbox.el.getWidth(),
                msgH = mbox.msg.el.getHeight(),
                msgW = mbox.msg.el.getWidth(),
                tbH = mbox.bottomTb.el.getHeight(),
                tbW = mbox.bottomTb.el.getWidth(),
                hdrH = mbox.header.el.getHeight(),
                hdrW = mbox.header.el.getWidth(),
                containerW = mbox.promptContainer.el.getWidth(),
                deltaW = containerW - msgW,
                layout = {
                    el: {
                        h: [minH, maxH],
                        w: [minW, maxW]
                    },
                    msg: {
                        el: {
                            w: panelW -
                                deltaW -
                                ((framePadding + hboxPadding + border) * 2),
                            h: panelH - (
                                headerPad +
                                    hdrH +
                                    hboxPadding * 2 +
                                    displayBtmMargin +
                                    footerPadTB +
                                    tbH +
                                    framePadding
                                )
                        }
                    }
                };

            expect(mbox).toHaveLayout(layout);
        }

        xdescribe("Standard Layouts (Msg Only - No Icons)", function() {

            beforeEach(function() {
                mbox = Ext.MessageBox = Ext.Msg = new Ext.window.MessageBox();
            });
            afterEach(function() {
                Ext.destroy(mbox);
                mbox = null;
            });

            it('should layout an unspecified width (shrinkWrap)', function() {
                var cfg = {
                    title: "TEXT",
                    modal: true,
                    buttons: Ext.MessageBox.OKCANCEL,
                    msg: longMsg
                };

                mbox.show(cfg);
                expect(mbox.el.getHeight()).toBeLessThan(mbox.el.getWidth());
                verifyMessageBoxLayout(true);
                mbox.hide();
            });

            it('should layout a configured width', function() {
                var cfg = {
                    width: 250,
                    title: "TEXT",
                    modal: true,
                    buttons: Ext.MessageBox.OKCANCEL,
                    msg: longMsg
                };

                mbox.show(cfg);
                verifyMessageBoxLayout();
                mbox.hide();
            });

            it('should honor minWidth constraints', function() {
                var cfg = {
                    width: 10,
                    title: "TEXT",
                    modal: true,
                    buttons: Ext.MessageBox.OKCANCEL,
                    msg: longMsg
                };

                mbox.show(cfg);
                verifyMessageBoxLayout();
                mbox.hide();
            });

            it('should honor maxWidth constraints', function() {
                var cfg = {
                    width: 10000,
                    title: "TEXT",
                    modal: true,
                    buttons: Ext.MessageBox.OKCANCEL,
                    msg: longMsg
                };

                mbox.show(cfg);
                verifyMessageBoxLayout();
                mbox.hide();
            });

        });

        xdescribe('Generated Test', function() {

            beforeEach(function() {
                mbox = Ext.MessageBox = Ext.Msg = new Ext.window.MessageBox();
            });
            afterEach(function() {
                Ext.destroy(mbox);
                mbox = null;
            });

            it('should layout shrinkWrap', function() {
                var cfg = {
                    title: "TEXT",
                    modal: true,
                    buttons: Ext.MessageBox.OKCANCEL,
                    msg: longMsg
                };

                mbox.show(cfg);
                expect(mbox).toHaveLayout({
                    "el": {
                        "xywh": "0 0 582 229"
                    },
                    "body": {
                        "xywh": "0 0 572 168"
                    },
                    "items": {
                        "container-1004": {
                            "el": {
                                "xywh": "5 27 572 168"
                            },
                            "items": {
                                "container-1003": {
                                    "el": {
                                        "xywh": "10 10 552 148"
                                    },
                                    "items": {
                                        "messagebox-1001-displayfield": {
                                            "el": {
                                                "xywh": "0 0 547 143"
                                            },
                                            "inputRow": {
                                                "xywh": "0 0 547 143"
                                            },
                                            "bodyEl": {
                                                "xywh": "0 0 547 143"
                                            },
                                            "inputEl": {
                                                "xywh": "0 0 547 143"
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    },
                    "dockedItems": {
                        "messagebox-1001_header": {
                            "el": {
                                "xywh": "0 0 582 22"
                            },
                            "body": {
                                "xywh": "6 6 570 16"
                            },
                            "items": {
                                "messagebox-1001_header_hd": {
                                    "el": {
                                        "xywh": "6 6 553 16"
                                    },
                                    "textEl": {
                                        "xywh": "6 6 553 16"
                                    }
                                },
                                "tool-1009": {
                                    "el": {
                                        "xywh": "561 7 15 15"
                                    },
                                    "toolEl": {
                                        "xywh": "561 7 15 15"
                                    }
                                }
                            }
                        },
                        "messagebox-1001-toolbar": {
                            "el": {
                                "xywh": "5 198 572 26"
                            },
                            "items": {
                                "button-1005": {
                                    "el": {
                                        "xywh": "208 2 75 22"
                                    },
                                    "btnIconEl": {
                                        "xywh": "211 21 0 0"
                                    },
                                    "btnInnerEl": {
                                        "xywh": "211 5 69 16"
                                    },
                                    "btnWrap": {
                                        "xywh": "211 8 69 13"
                                    },
                                    "btnEl": {
                                        "xywh": "211 5 69 16"
                                    }
                                },
                                "button-1008": {
                                    "el": {
                                        "xywh": "289 2 75 22"
                                    },
                                    "btnIconEl": {
                                        "xywh": "292 21 0 0"
                                    },
                                    "btnInnerEl": {
                                        "xywh": "292 5 69 16"
                                    },
                                    "btnWrap": {
                                        "xywh": "292 8 69 13"
                                    },
                                    "btnEl": {
                                        "xywh": "292 5 69 16"
                                    }
                                }
                            }
                        }
                    }
                });
            });

            it("should layout configured width", function() {
                var cfg = {
                    width: 250,
                    title: "TEXT",
                    modal: true,
                    buttons: Ext.MessageBox.OKCANCEL,
                    msg: longMsg
                };

                mbox.show(cfg);
                expect(mbox).toHaveLayout({
                    "el": {
                        "xywh": "0 0 260 257"
                    },
                    "body": {
                        "xywh": "0 0 250 196"
                    },
                    "items": {
                        "container-1004": {
                            "el": {
                                "xywh": "5 27 250 196"
                            },
                            "items": {
                                "container-1003": {
                                    "el": {
                                        "xywh": "10 10 230 176"
                                    },
                                    "items": {
                                        "messagebox-1001-displayfield": {
                                            "el": {
                                                "xywh": "0 0 230 171"
                                            },
                                            "inputRow": {
                                                "xywh": "0 0 230 171"
                                            },
                                            "bodyEl": {
                                                "xywh": "0 0 230 171"
                                            },
                                            "inputEl": {
                                                "xywh": "0 0 230 171"
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    },
                    "dockedItems": {
                        "messagebox-1001_header": {
                            "el": {
                                "xywh": "0 0 260 22"
                            },
                            "body": {
                                "xywh": "6 6 248 16"
                            },
                            "items": {
                                "messagebox-1001_header_hd": {
                                    "el": {
                                        "xywh": "6 6 231 16"
                                    },
                                    "textEl": {
                                        "xywh": "6 6 231 16"
                                    }
                                },
                                "tool-1009": {
                                    "el": {
                                        "xywh": "239 7 15 15"
                                    },
                                    "toolEl": {
                                        "xywh": "239 7 15 15"
                                    }
                                }
                            }
                        },
                        "messagebox-1001-toolbar": {
                            "el": {
                                "xywh": "5 226 250 26"
                            },
                            "items": {
                                "button-1005": {
                                    "el": {
                                        "xywh": "47 2 75 22"
                                    },
                                    "btnIconEl": {
                                        "xywh": "50 21 0 0"
                                    },
                                    "btnInnerEl": {
                                        "xywh": "50 5 69 16"
                                    },
                                    "btnWrap": {
                                        "xywh": "50 8 69 13"
                                    },
                                    "btnEl": {
                                        "xywh": "50 5 69 16"
                                    }
                                },
                                "button-1008": {
                                    "el": {
                                        "xywh": "128 2 75 22"
                                    },
                                    "btnIconEl": {
                                        "xywh": "131 21 0 0"
                                    },
                                    "btnInnerEl": {
                                        "xywh": "131 5 69 16"
                                    },
                                    "btnWrap": {
                                        "xywh": "131 8 69 13"
                                    },
                                    "btnEl": {
                                        "xywh": "131 5 69 16"
                                    }
                                }
                            }
                        }
                    }
                });
            });
        });
    });
});

