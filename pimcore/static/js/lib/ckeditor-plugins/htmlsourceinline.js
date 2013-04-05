
CKEDITOR.plugins.add("htmlsourceinline", {

    init:function (editor) {

        editor.addCommand('htmlDialog', new CKEDITOR.dialogCommand('htmlDialog'));
        editor.ui.addButton("htmlsourceinline", {
            label: editor.lang.sourcearea.toolbar,
            command:'htmlDialog',
            icon: "/pimcore/static/img/icon/page_white_code_grey.png"
        });

        CKEDITOR.dialog.add('htmlDialog', function (editor) {
            return {
                title: editor.lang.sourcearea.toolbar,
                minWidth:600,
                minHeight:400,
                contents:[
                    {
                        id:'general',
                        label:'Settings',
                        elements:[
                            // UI elements of the Settings tab.
                            {
                                type:'textarea',
                                id:'contents',
                                rows:25,
                                onShow:function () {

                                    var makeReadable = function(readableHTML) {
                                        var lb = '\r\n';
                                        var htags = ["<html", "</html>", "</head>", "<title", "</title>", "<meta", "<link", "<style", "</style>", "</body>"];
                                        for (i = 0; i < htags.length; ++i) {
                                            var hhh = htags[i];
                                            readableHTML = readableHTML.replace(new RegExp(hhh, 'gi'), lb + hhh);
                                        }
                                        var btags = ["</div>", "</span>", "</form>", "</fieldset>", "<br>", "<br />", "<hr", "<pre", "</pre>", "<blockquote", "</blockquote>", "<ul", "</ul>", "<ol", "</ol>", "<li", "<dl", "</dl>", "<dt", "</dt>", "<dd", "</dd>", "<\!--", "<table", "</table>", "<caption", "</caption>", "<th", "</th>", "<tr", "</tr>", "<td", "</td>", "<script", "</script>", "<noscript", "</noscript>"];
                                        for (i = 0; i < btags.length; ++i) {
                                            var bbb = btags[i];
                                            readableHTML = readableHTML.replace(new RegExp(bbb, 'gi'), lb + bbb);
                                        }
                                        var ftags = ["<label", "</label>", "<legend", "</legend>", "<object", "</object>", "<embed", "</embed>", "<select", "</select>", "<option", "<option", "<input", "<textarea", "</textarea>"];
                                        for (i = 0; i < ftags.length; ++i) {
                                            var fff = ftags[i];
                                            readableHTML = readableHTML.replace(new RegExp(fff, 'gi'), lb + fff);
                                        }
                                        var xtags = ["<body", "<head", "<div", "<span", "<p", "<form", "<fieldset"];
                                        for (i = 0; i < xtags.length; ++i) {
                                            var xxx = xtags[i];
                                            readableHTML = readableHTML.replace(new RegExp(xxx, 'gi'), lb + lb + xxx);
                                        }
                                        return readableHTML;
                                    }


                                    this.getElement().addClass("cke-code-editor");
                                    this.setValue(makeReadable(editor.container.$.innerHTML));
                                },
                                commit:function (data) {              //--I get only the body part in case I paste a complete html
                                    data.contents = this.getValue().replace(/^[\S\s]*<body[^>]*?>/i, "").replace(/<\/body[\S\s]*$/i, "");
                                }

                            }
                        ]
                    }
                ],

                onOk:function () {
                    var data = {};
                    this.commitContent(data);
                    $(editor.container.$).html(data.contents);
                },
                onCancel:function () {
                    //  console.log('Cancel');
                }
            };
        });
    }
});



