topSuite("Ext.layout.component.field.HtmlEditor",
    ['Ext.form.Panel', 'Ext.form.field.HtmlEditor'],
function() {
    var htmlEditor;

    afterEach(function() {
        htmlEditor = Ext.destroy(htmlEditor);
    });

    it("should layout form with shrinkwrap height that contains an auto height html editor with toolbar overflow trigger", function() {
        // this spec exists mainly to ensure that the html editor publishes the right content height
        // when its toolbar has an overflow trigger.
        var form = Ext.widget({
            renderTo: document.body,
            xtype: 'form',
            width: 200,
            bodyPadding: 10,
            items: [{
                xtype: 'htmleditor',
                // disable font selector for this test becuase the version of PhantomJS
                // that we currently use in our test runner adds extra height to the
                // select element, throwing the layout numbers off.
                enableFont: false,
                anchor: '100%'
            }]
        }),
        layoutSpec = {
           "el": {
              "xywh": "0 0 200 206"
           },
           "body": {
              "xywh": "0 0 200 206"
           },
           "items": {
              "0": {
                 "el": {
                    "xywh": "11 11 178 179"
                 },
                 "containerEl": {
                    "xywh": "11 11 178 179"
                 },
                 "bodyEl": {
                    "xywh": "11 11 178 179"
                 },
                 "iframeEl": {
                    "xywh": "12 39 176 150"
                 },
                 "inputEl": {
                    "xywh": "12 39 176 150"
                 }
              }
           }
        };

        expect(form).toHaveLayout(layoutSpec);

        // EXTJSIV-12777: make sure we can layout again after initial layout
        form.updateLayout();

        expect(form).toHaveLayout(layoutSpec);

        form.destroy();
    });

    it("should provide a natural height when configured without one", function() {
        htmlEditor = new Ext.form.field.HtmlEditor({
            renderTo: Ext.getBody()
        });
        expect(htmlEditor.iframeEl.getHeight()).toBe(htmlEditor.componentLayout.naturalHeight);
    });

    describe("stretching iframe/textarea", function() {
        function getHeightOffset() {
            return htmlEditor.getToolbar().getHeight() + htmlEditor.inputCmp.getEl().getBorderWidth('tb');
        }

        it("should stretch the iframe height when shrink wrapping height", function() {
            htmlEditor = new Ext.form.field.HtmlEditor({
                renderTo: Ext.getBody()
            });
            expect(htmlEditor.iframeEl.getHeight()).toBe(htmlEditor.componentLayout.naturalHeight);
        });

        it("should stretch the iframe height when using a configured height", function() {
            htmlEditor = new Ext.form.field.HtmlEditor({
                renderTo: Ext.getBody(),
                height: 800
            });
            expect(htmlEditor.iframeEl.getHeight()).toBe(800 - getHeightOffset());
        });

        it("should stretch the iframe correctly when changing from shrinkWrap to a configured height", function() {
            htmlEditor = new Ext.form.field.HtmlEditor({
                renderTo: Ext.getBody()
            });
            expect(htmlEditor.iframeEl.getHeight()).toBe(htmlEditor.componentLayout.naturalHeight);
            htmlEditor.setHeight(800);
            expect(htmlEditor.iframeEl.getHeight()).toBe(800 - getHeightOffset());
        });
    });

});
