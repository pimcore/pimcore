topSuite("Ext.layout.container.Fit", ['Ext.Panel'], function() {
    var ct;

    afterEach(function() {
        Ext.destroy(ct);
        ct = null;
    });

    function makeCt(options, layoutOptions) {
        var failedLayouts = Ext.failedLayouts;

        ct = Ext.widget(Ext.apply({
                renderTo: Ext.getBody(),
                width: 100,
                height: 100,
                defaultType: 'component',
                xtype: 'container',
                layout: Ext.apply({ type: 'fit' }, layoutOptions)
            }, options));

        // eslint-disable-next-line eqeqeq
        if (failedLayouts != Ext.failedLayouts) {
            expect('failedLayout=true').toBe('false');
        }
    }

    describe('should handle minWidth and/or minHeight', function() {
        it('should stretch the configured size child', function() {
            makeCt({
                    width: undefined,
                    height: undefined,
                    floating: true,
                    minWidth: 100,
                    minHeight: 100,
                    // style: 'border: 1px solid red',
                    items: {
                        xtype: 'component',
                        width: 50,
                        height: 50
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '0 0 100 100' } }
                }
            });
        });
    });

    describe('Fixed dimensions', function() {
        it('should size the child item to the parent', function() {
            makeCt({
                    items: {}
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '0 0 100 100' } }
                }
            });
        });

        it('should account for padding on the owner', function() {
            makeCt({
                    padding: 10,
                    items: {}
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '10 10 80 80' } }
                }
            });
        });

        it('should account for top padding on the owner', function() {
            makeCt({
                    padding: '10 0 0 0',
                    items: {}
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '0 10 100 90' } }
                }
            });
        });

        it('should account for right padding on the owner', function() {
            makeCt({
                    padding: '0 10 0 0',
                    items: {}
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '0 0 90 100' } }
                }
            });
        });

        it('should account for bottom padding on the owner', function() {
            makeCt({
                    padding: '0 0 10 0',
                    items: {}
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '0 0 100 90' } }
                }
            });
        });

        it('should account for left padding on the owner', function() {
            makeCt({
                    padding: '0 0 0 10',
                    items: {}
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '10 0 90 100' } }
                }
            });
        });

        it('should account for margin on the child', function() {
            makeCt({
                    items: {
                        margin: 10
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '10 10 80 80' } }
                }
            });
        });

        it('should account for a top margin on the child', function() {
            makeCt({
                    items: {
                        margin: '10 0 0 0'
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '0 10 100 90' } }
                }
            });
        });

        it('should account for a right margin on the child', function() {
            makeCt({
                    items: {
                        margin: '0 10 0 0'
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '0 0 90 100' } }
                }
            });
        });

        it('should account for a bottom margin on the child', function() {
            makeCt({
                    items: {
                        margin: '0 0 10'
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '0 0 100 90' } }
                }
            });
        });

        it('should account for a left margin on the child', function() {
            makeCt({
                    items: {
                        margin: '0 0 0 10'
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '10 0 90 100' } }
                }
            });
        });

        it('should account for both padding & margin', function() {
            makeCt({
                    padding: 10,
                    items: {
                        margin: 10
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '20 20 60 60' } }
                }
            });
        });

        it('should account for margin and bodyPadding in a panel', function() {
            makeCt({
                    items: {
                        margin: 10
                    },
                    bodyPadding: '5 15',
                    border: false,
                    xtype: 'panel'
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '25 15 50 70' } }
                }
            });
        });

        it('should support margin and a style margin', function() {
            makeCt({
                    items: {
                        style: { margin: '10px' }, // Will be ignored
                        margin: 15
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '15 15 70 70' } }
                }
            });
        });

        it('should support multiple items', function() {
            makeCt({
                    style: 'position: relative',
                    items: [{}, {
                        // TODO: this is currently required but perhaps shouldn't be
                        style: { position: 'absolute' },
                        itemId: 'second'
                    }]
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '0 0 100 100' } },
                    1: { el: { xywh: '0 0 100 100' } }
                }
            });
        });

        it('should support multiple items with margin & padding', function() {
            makeCt({
                    style: 'position: relative',
                    padding: 10,
                    items: [{
                        margin: true // 5
                    }, {
                        // TODO: this is currently required but perhaps shouldn't be
                        style: { position: 'absolute' },
                        itemId: 'second',
                        margin: 20
                    }]
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '15 15 70 70' } },
                        // TODO: '30 30 40 40' - currently the padding is ignored
                    1: { el: { xywh: '20 20 40 40' } }
                }
            });
        });

        it('should prioritize fitting the child over a configured size', function() {
            makeCt({
                    items: {
                        height: 50, // should be ignored
                        margin: 10,
                        width: 50 // should be ignored
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '10 10 80 80' } }
                }
            });
        });
    });

    describe('Shrink-wrapping', function() {
        it('should force the parent to the child size', function() {
            makeCt({
                    floating: true, // avoid stretching to full body width
                    items: {
                        width: 100,
                        height: 100
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '0 0 100 100' } }
                }
            });
        });

        it('should take into account owner padding', function() {
            makeCt({
                    floating: true, // avoid stretching to full body width
                    padding: 10,
                    items: {
                        width: 80,
                        height: 80
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '10 10 80 80' } }
                }
            });
        });

        it('should take into account child margin', function() {
            makeCt({
                    floating: true, // avoid stretching to full body width
                    items: {
                        margin: 10,
                        width: 80,
                        height: 80
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '10 10 80 80' } }
                }
            });
        });

        it('should account for both padding/margin', function() {
            makeCt({
                    floating: true, // avoid stretching to full body width
                    padding: 10,
                    items: {
                        margin: 10,
                        width: 60,
                        height: 60
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '20 20 60 60' } }
                }
            });
        });

        it('should account for left padding & a top margin', function() {
            makeCt({
                    floating: true, // avoid stretching to full body width
                    padding: '0 0 0 10',
                    items: {
                        margin: '10 0 0',
                        width: 90,
                        height: 90
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '10 10 90 90' } }
                }
            });
        });

        it('should account for margin in a panel', function() {
            // This is different to a simple container because of the body element
            makeCt({
                    floating: true, // avoid stretching to full body width
                    items: {
                        margin: '10 5 20 15',
                        width: 80,
                        height: 70
                    },
                    border: false,
                    xtype: 'panel'
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '15 10 80 70' } }
                }
            });
        });

        it('should account for margin and bodyPadding in a panel', function() {
            makeCt({
                    floating: true, // avoid stretching to full body width
                    items: {
                        margin: 10,
                        width: 70,
                        height: 70
                    },
                    bodyPadding: 5,
                    border: false,
                    xtype: 'panel'
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 100 },
                items: {
                    0: { el: { xywh: '15 15 70 70' } }
                }
            });
        });

        it('should account for hscrollbar if overflowing', function() {
            makeCt({
                    floating: true,
                    width: 100,
                    height: undefined,
                    autoScroll: true,
                    items: {
                        minWidth: 200,
                        height: 50
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 50 + Ext.getScrollbarSize().height },
                items: {
                    0: { el: { xywh: '0 0 200 50' } }
                }
            });
        });

        it('should account for vscrollbar if overflowing', function() {
            makeCt({
                    floating: true,
                    xtype: 'panel',
                    border: false,
                    width: undefined,
                    height: 100,
                    autoScroll: true,
                    items: {
                        minHeight: 200,
                        width: 50
                    }
                });

            expect(ct).toHaveLayout({
                el: { w: 50 + Ext.getScrollbarSize().width, h: 100 },
                items: {
                    0: { el: { xywh: '0 0 50 200' } }
                }
            });
        });
    });

    it("should not fail when the item is hidden & the container is shrink wrapping", function() {
        expect(function() {
            ct = new Ext.container.Container({
                shrinkWrap: 3,
                renderTo: Ext.getBody(),
                layout: 'fit',
                items: {
                    hidden: true,
                    xtype: 'component'
                }
            });
        }).not.toThrow();
    });
});
