topSuite("Ext.mixin.Accessible", 'Ext.Container', function() {
    var c, cnt;

    function makeComponent(config) {
        config = Ext.apply({
            renderTo: Ext.getBody()
        }, config);

        return c = new Ext.Component(config);
    }

    function makeContainer(config) {
        config = Ext.apply({
            renderTo: Ext.getBody()
        }, config);

        return cnt = new Ext.container.Container(config);
    }

    afterEach(function() {
        if (cnt) {
            cnt.destroy();
        }

        if (c) {
            c.destroy();
        }

        c = cnt = null;
    });

    describe("getAriaLabelEl", function() {
        var foo, bar, qux;

        beforeEach(function() {
            makeContainer({
                referenceHolder: true,
                items: [{
                    xtype: 'component',
                    reference: 'foo',
                    ariaLabelledBy: function() {
                        return this.reference;
                    }
                }, {
                    xtype: 'container',
                    items: [{
                        xtype: 'component',
                        reference: 'bar',
                        ariaLabelledBy: 'qux'
                    }, {
                        xtype: 'container',
                        items: [{
                            xtype: 'component',
                            reference: 'qux',
                            ariaDescribedBy: ['foo', 'bar']
                        }]
                    }]
                }]
            });

            foo = cnt.down('[reference=foo]');
            bar = cnt.down('[reference=bar]');
            qux = cnt.down('[reference=qux]');
        });

        it("should support single reference", function() {
            var want = qux.ariaEl.id;

            expect(bar).toHaveAttr('aria-labelledby', want);
        });

        it("should support array of references", function() {
            var want = foo.ariaEl.id + ' ' + bar.ariaEl.id;

            expect(qux).toHaveAttr('aria-describedby', want);
        });

        it("should support function", function() {
            expect(foo).toHaveAttr('aria-labelledby', 'foo');
        });
    });
});
