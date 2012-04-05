CKEDITOR.plugins.add('pimcoreimage', {
    init:function (editor) {
        var pluginName = 'pimcoreimage';

        editor.addCommand(pluginName, {
            exec:function (editor) {
                var sel = editor.getSelection();
                var element = sel.getSelectedElement();

                if(element) {
                    var pimcoreId = element.getAttribute("pimcore_id");
                    if(pimcoreId) {
                        top.pimcore.helpers.openAsset(pimcoreId, "image");
                    }
                }
            }
        });

        if (editor.addMenuItems) {
            editor.addMenuItems({
                pimcoreimage:{
                    label: t("open_image"),
                    command: 'pimcoreimage',
                    group: 'image',
                    icon: "/pimcore/static/img/icon/picture_go.png"
                }
            });
        }

        if (editor.contextMenu) {
            editor.contextMenu.addListener(function (element, selection) {
                if (!element || !element.is('img') || element.data('cke-realelement') || element.isReadOnly())
                    return null;

                return { pimcoreimage:CKEDITOR.TRISTATE_OFF };
            });
        }
    }
});
