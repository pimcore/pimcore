CKEDITOR.plugins.add('pimcorelink', {
    init:function (editor) {
        var pluginName = 'pimcorelink';

        editor.addCommand(pluginName, {
            exec:function (editor) {
                var sel = editor.getSelection();
                console.log(sel);

                var selection = editor.getSelection();
                var element = null;

                var getSelectedLink = function( editor )
                {
                    try
                    {
                        var selection = editor.getSelection();
                        if ( selection.getType() == CKEDITOR.SELECTION_ELEMENT )
                        {
                            var selectedElement = selection.getSelectedElement();
                            if ( selectedElement.is( 'a' ) )
                                return selectedElement;
                        }

                        var range = selection.getRanges( true )[ 0 ];
                        range.shrink( CKEDITOR.SHRINK_TEXT );
                        var root = range.getCommonAncestor();
                        return root.getAscendant( 'a', true );
                    }
                    catch( e ) { return null; }
                };

                // Fill in all the relevant fields if there's already one link selected.
                if ( ( element = getSelectedLink( editor ) ) && element.hasAttribute( 'href' ) ) {
                    selection.selectElement( element );
                } else {
                    element = null;
                }


                if(element) {
                    var pimcoreId = element.getAttribute("pimcore_id");
                    var pimcoreType = element.getAttribute("pimcore_type");

                    if(pimcoreId && pimcoreType == "document") {
                        top.pimcore.helpers.openDocument(pimcoreId, "page");
                    } else if (pimcoreId && pimcoreType == "asset") {
                        top.pimcore.helpers.openAsset(pimcoreId, "image");
                    }
                }
            }
        });

        if (editor.addMenuItems) {
            editor.addMenuItems({
                pimcorelink:{
                    label: t("open_document"),
                    command: 'pimcorelink',
                    group: 'link',
                    icon: "/pimcore/static/img/icon/page_go.png"
                }
            });
        }

        if (editor.contextMenu) {
            editor.contextMenu.addListener(function (element, selection) {
                if (!element || !element.is('a') || element.data('cke-realelement') || element.isReadOnly())
                    return null;

                return { pimcorelink:CKEDITOR.TRISTATE_OFF };
            });
        }
    }
});
