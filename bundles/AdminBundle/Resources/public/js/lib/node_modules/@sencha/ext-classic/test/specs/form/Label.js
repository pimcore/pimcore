topSuite("Ext.form.Label", function() {
    var component;

    function makeComponent(config) {
        component = new Ext.form.Label(Ext.apply({
            name: 'test'
        }, config));
    }

    afterEach(function() {
        if (component) {
            Ext.destroy(component);
        }

        component = null;
    });

    it("should have a label as the element", function() {
        makeComponent({
            renderTo: Ext.getBody()
        });

        expect(component.el.dom.tagName.toUpperCase()).toEqual("LABEL");
    });

    it("should use the forId attribute", function() {
        makeComponent({
            renderTo: Ext.getBody(),
            forId: "foo"
        });

        expect(component.el.dom.htmlFor).toEqual("foo");
    });

    it("should encode the text attribute", function() {
        makeComponent({
            text: "<div>foo</div>",
            renderTo: Ext.getBody()
        });

        expect(component.el.dom).hasHTML("&lt;div&gt;foo&lt;/div&gt;");
    });

    it("should not encode the html attribute", function() {
        makeComponent({
            html: "<span>foo</span>",
            renderTo: Ext.getBody()
        });
        expect(component.el.dom).hasHTML("<span>foo</span>");
    });

    it("should support setText when not rendered", function() {
        makeComponent();
        component.setText("foo");
        component.render(Ext.getBody());
        expect(component.el.dom).hasHTML("foo");
        component.destroy();

        makeComponent({
            text: "foo"
        });
        component.setText("bar");
        component.render(Ext.getBody());
        expect(component.el.dom).hasHTML("bar");
    });

    it("should enforce the encode attribute", function() {
        makeComponent();
        component.setText("<b>bar</b>", false);
        component.render(Ext.getBody());
        expect(component.el.dom).hasHTML("<b>bar</b>");

        component.setText("<span>foo</span>");
        expect(component.el.dom).hasHTML("&lt;span&gt;foo&lt;/span&gt;");

        component.setText("<span>bar</span>", false);
        expect(component.el.dom).hasHTML("<span>bar</span>");
    });

    it("should update the layout when text is set after render", function() {
        makeComponent({
            renderTo: document.body
        });
        var width = component.getWidth();

        component.setText('New text');
        expect(component.getWidth()).toBeGreaterThan(width);
    });
});
