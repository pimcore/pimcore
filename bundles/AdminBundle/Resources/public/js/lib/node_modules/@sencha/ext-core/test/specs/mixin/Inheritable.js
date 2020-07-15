topSuite("Ext.mixin.Inheritable", ['Ext.Component', 'Ext.Container'], function() {
    var ct;

    function makeCt(cfg) {
        ct = new Ext.Container(cfg);
    }

    afterEach(function() {
        ct = Ext.destroy(ct);
    });

    describe("isAncestor", function() {
        it("it should return true for a direct child", function() {
            makeCt({
                items: [{
                    xtype: 'component',
                    id: 'foo'
                }]
            });
            var c = Ext.getCmp('foo');

            expect(ct.isAncestor(c)).toBe(true);
        });

        it("it should return true for a deep child", function() {
            makeCt({
                items: [{
                    xtype: 'container',
                    items: [{
                        id: 'foo'
                    }]
                }]
            });
            var c = Ext.getCmp('foo');

            expect(ct.isAncestor(c)).toBe(true);
        });

        it("should return true for an item that is a ref item", function() {
            var T = Ext.define(null, {
                extend: 'Ext.Container',

                constructor: function(config) {
                    this.foo = new Ext.Component();

                    this.foo.getRefOwner = function() {
                        return ct;
                    };

                    this.callParent([config]);
                },

                destroy: function() {
                    this.foo.destroy();
                    this.callParent();
                }
            });

            ct = new T();
            expect(ct.isAncestor(ct.foo)).toBe(true);
        });

        it("should return false for the item itself", function() {
            makeCt();
            expect(ct.isAncestor(ct)).toBe(false);
        });

        it("should return false for a direct parent", function() {
            makeCt({
                items: [{
                    xtype: 'component',
                    id: 'foo'
                }]
            });
            var c = Ext.getCmp('foo');

            expect(c.isAncestor(ct)).toBe(false);
        });

        it("should return false for a deep parent", function() {
            makeCt({
                items: [{
                    xtype: 'container',
                    items: [{
                        id: 'foo'
                    }]
                }]
            });
            var c = Ext.getCmp('foo');

            expect(c.isAncestor(ct)).toBe(false);
        });

        it("should return false for a sibling", function() {
            makeCt({
                items: [{
                    xtype: 'component',
                    id: 'foo'
                }, {
                    xtype: 'component',
                    id: 'bar'
                }]
            });
            var foo = Ext.getCmp('foo'),
                bar = Ext.getCmp('bar');

            expect(foo.isAncestor(bar)).toBe(false);
        });

        it("should return false for null", function() {
            makeCt();
            expect(ct.isAncestor(null)).toBe(false);
        });

        it("should return false for a component outside the hierarchy", function() {
            var c = new Ext.Component();

            makeCt();
            expect(ct.isAncestor(c)).toBe(false);
            c.destroy();
        });
    });

    describe("isDescendantOf", function() {
        it("it should return true for a direct child", function() {
            makeCt({
                items: [{
                    xtype: 'component',
                    id: 'foo'
                }]
            });
            var c = Ext.getCmp('foo');

            expect(c.isDescendantOf(ct)).toBe(true);
        });

        it("it should return true for a deep child", function() {
            makeCt({
                items: [{
                    xtype: 'container',
                    items: [{
                        id: 'foo'
                    }]
                }]
            });
            var c = Ext.getCmp('foo');

            expect(c.isDescendantOf(ct)).toBe(true);
        });

        it("should return true for an item that is a ref item", function() {
            var T = Ext.define(null, {
                extend: 'Ext.Container',

                constructor: function(config) {
                    this.foo = new Ext.Component();

                    this.foo.getRefOwner = function() {
                        return ct;
                    };

                    this.callParent([config]);
                },

                destroy: function() {
                    this.foo.destroy();
                    this.callParent();
                }
            });

            ct = new T();
            expect(ct.foo.isDescendantOf(ct)).toBe(true);
        });

        it("should return false for the item itself", function() {
            makeCt();
            expect(ct.isDescendantOf(ct)).toBe(false);
        });

        it("should return false for a direct parent", function() {
            makeCt({
                items: [{
                    xtype: 'component',
                    id: 'foo'
                }]
            });
            var c = Ext.getCmp('foo');

            expect(ct.isDescendantOf(c)).toBe(false);
        });

        it("should return false for a deep parent", function() {
            makeCt({
                items: [{
                    xtype: 'container',
                    items: [{
                        id: 'foo'
                    }]
                }]
            });
            var c = Ext.getCmp('foo');

            expect(ct.isDescendantOf(c)).toBe(false);
        });

        it("should return false for a sibling", function() {
            makeCt({
                items: [{
                    xtype: 'component',
                    id: 'foo'
                }, {
                    xtype: 'component',
                    id: 'bar'
                }]
            });
            var foo = Ext.getCmp('foo'),
                bar = Ext.getCmp('bar');

            expect(foo.isDescendantOf(bar)).toBe(false);
        });

        it("should return false for null", function() {
            makeCt();
            expect(ct.isDescendantOf(null)).toBe(false);
        });

        it("should return false for a component outside the hierarchy", function() {
            var c = new Ext.Component();

            makeCt();
            expect(c.isDescendantOf(ct)).toBe(false);
            c.destroy();
        });
    });
});
