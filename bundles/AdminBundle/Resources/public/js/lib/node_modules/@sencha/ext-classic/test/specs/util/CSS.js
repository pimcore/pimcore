topSuite("Ext.util.CSS", 'Ext.dom.Element', function() {
    var div;

    beforeEach(function() {
        div = Ext.getBody().createChild({
            tag: 'div',
            style: 'height:100px;width:100px'
        });
        div.dom.className = 'ext-css-test';
    });

    afterEach(function() {
        div.destroy();
    });

    it('should create a stylesheet, and apply and update rules', function() {

        // Create a stylesheet with a single rule in it
        var stylesheet = Ext.util.CSS.createStyleSheet('.ext-css-test { background-color:red}', 'unit-test-stylesheet');

        expect(div.getStyle('background-color') === "rgb(255, 0, 0)" || div.getStyle('background-color') === "red").toBe(true);

        // Update the single rule in the above stylesheet
        Ext.util.CSS.updateRule('.ext-css-test', 'background-color', 'blue');
        expect(div.getStyle('background-color') === "rgb(0, 0, 255)" || div.getStyle('background-color') === "blue").toBe(true);

        // Create a new rule in the stylesheet
        Ext.util.CSS.createRule(stylesheet, '.ext-css-test', 'color:green');
        expect(div.getStyle('color') === "rgb(0, 128, 0)" || div.getStyle('color') === "green").toBe(true);

        // Update the new rule
        Ext.util.CSS.updateRule('.ext-css-test', 'color', 'red');
        expect(div.getStyle('color') === "rgb(255, 0, 0)" || div.getStyle('color') === "red").toBe(true);
    });

    describe('createStyleSheet', function() {
        afterEach(function() {
            var node = document.getElementById('createStyleSheetSpec');

            node.parentNode.removeChild(node);
        });

        it('should append a style accessible in the stylesheets collection', function() {
            var isIE = Ext.isIE8m,
                cssText = !isIE ? 'body { background-color: red; }' : 'BODY {\r\n\tBACKGROUND-COLOR: red\r\n}\r\n',
                ss, cssRules;

            ss = Ext.util.CSS.createStyleSheet(cssText, 'createStyleSheetSpec');

            if (isIE) {
                expect(ss.cssText).toBe(cssText);
            }
            else {
                cssRules = ss.cssRules;
                expect(cssRules.length).toBe(1);
                expect(cssRules[0].cssText).toBe(cssText);
            }
        });
    });
});
