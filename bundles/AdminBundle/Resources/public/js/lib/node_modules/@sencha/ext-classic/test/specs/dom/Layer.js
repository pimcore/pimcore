topSuite("Ext.dom.Layer", function() {
    var layer;

    afterEach(function() {
        layer.destroy();
    });

    it("should create a div by default", function() {
        layer = new Ext.dom.Layer();

        expect(layer.dom.tagName).toBe('DIV');
    });

    it("should have the x-layer cls", function() {
        layer = new Ext.dom.Layer();

        expect(layer).toHaveCls('x-layer');
    });

    it("should accept a domhelper config as its element", function() {
        layer = new Ext.dom.Layer({
            dh: {
                tag: 'p',
                cls: 'today-is-the-greatest-day-Ive-ever-known'
            }
        });

        expect(layer.dom.tagName).toBe('P');
        expect(layer).toHaveCls('today-is-the-greatest-day-Ive-ever-known');
    });

    it("should append the layer to document.body", function() {
        layer = new Ext.dom.Layer();

        expect(layer.dom.parentNode).toBe(document.body);
    });

    it("should allow the parent node to be configured", function() {
        var parent = Ext.getBody().createChild();

        layer = new Ext.dom.Layer({
            parentEl: parent
        });

        expect(layer.dom.parentNode).toBe(parent.dom);

        parent.destroy();
    });

    it("should not create a shadow by default", function() {
        layer = new Ext.dom.Layer();

        expect(layer.shadow).toBeUndefined();
    });

    it("should create a shadow if shadow is true", function() {
        layer = new Ext.dom.Layer({
            shadow: true
        });

        expect(layer.shadow instanceof Ext.dom.Shadow).toBe(true);
        expect(layer.shadow.mode).toBe('drop');
    });

    it("should create a shadow using a shadow mode", function() {
        layer = new Ext.dom.Layer({
            shadow: 'sides'
        });

        expect(layer.shadow instanceof Ext.dom.Shadow).toBe(true);
        expect(layer.shadow.mode).toBe('sides');
    });

    it("should not create a shim by default", function() {
        layer = new Ext.dom.Layer();

        expect(layer.shim).toBeUndefined();
    });

    it("should create a shim if shim is true", function() {
        layer = new Ext.dom.Layer({
            shim: true
        });

        expect(layer.shim instanceof Ext.dom.Shim).toBe(true);
    });

    it("should accept a cls", function() {
        layer = new Ext.dom.Layer({
            cls: 'ohyeah'
        });

        expect(layer).toHaveCls('ohyeah');
    });

    it("should accept a shadowOffset", function() {
        layer = new Ext.dom.Layer({
            shadow: true,
            shadowOffset: 9999
        });

        expect(layer.shadow.offset).toBe(9999);
    });

    it("should use css visibility to hide", function() {
        layer = new Ext.dom.Layer();

        expect(layer.getVisibilityMode()).toBe(Ext.Element.VISIBILITY);
    });

    it("should use display to hide if useDisplay is true", function() {
        layer = new Ext.dom.Layer({
            useDisplay: true
        });

        expect(layer.getVisibilityMode()).toBe(Ext.Element.DISPLAY);
    });

    it("should configure the visibility mode using hideMode:'display'", function() {
        layer = new Ext.dom.Layer({
            hideMode: 'display'
        });

        expect(layer.getVisibilityMode()).toBe(Ext.Element.DISPLAY);
    });

    it("should configure the visibility mode using hideMode:'visibility'", function() {
        layer = new Ext.dom.Layer({
            hideMode: 'visibility'
        });

        expect(layer.getVisibilityMode()).toBe(Ext.Element.VISIBILITY);
    });

    it("should configure the visibility mode using hideMode:'offsets'", function() {
        layer = new Ext.dom.Layer({
            hideMode: 'offsets'
        });

        expect(layer.getVisibilityMode()).toBe(Ext.Element.OFFSETS);
    });
});
