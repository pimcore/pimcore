<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Params</title>

        <!-- ext css includes -->
        <link rel="stylesheet" type="text/css" href="/pimcore/static/js/lib/ext/resources/css/ext-all.css" rel="stylesheet" />
        <link rel="stylesheet" type="text/css" href="/pimcore/static/js/lib/ext-plugins/ux/treegrid/treegrid.css" rel="stylesheet" />
        <script type="text/javascript" src="/pimcore/static/js/lib/ext/adapter/ext/ext-base-debug.js?_dc=<?php echo Pimcore_Version::$revision ?>"></script>

        <?
        $scriptLibs = array('lib/ext/ext-all-debug.js',
                             'lib/ext-plugins/ux/treegrid/TreeGridSorter.js',
                             'lib/ext-plugins/ux/treegrid/TreeGridColumnResizer.js',
                             'lib/ext-plugins/ux/treegrid/TreeGridNodeUI.js',
                             'lib/ext-plugins/ux/treegrid/TreeGridLoader.js',
                             'lib/ext-plugins/ux/treegrid/TreeGridColumns.js',
                             'lib/ext-plugins/ux/treegrid/TreeGrid.js',
                            );
        ?>
        <?php foreach ($scriptLibs as $scriptUrl) { ?>
            <script type="text/javascript" src="/pimcore/static/js/<?php echo $scriptUrl ?>?_dc=<?php echo Pimcore_Version::$revision ?>"></script>
        <?php } ?>
        <script type="text/javascript">
/*!
 * Ext JS Library 3.4.0
 * Copyright(c) 2006-2011 Sencha Inc.
 * licensing@sencha.com
 * http://www.sencha.com/license
 */
var getParams = Ext.urlDecode(window.location.search.substring(1));
Ext.onReady(function() {

  /*  var tree = new Ext.ux.tree.TreeGrid({
        width: 500,
        height: 300,
        renderTo: Ext.getBody(),
        enableDD: true,

        columns:[{
            header: 'Task',
            dataIndex: 'task',
            width: 230
        },{
            header: 'Duration',
            width: 100,
            dataIndex: 'duration',
            align: 'center',
            sortType: 'asFloat',
            tpl: new Ext.XTemplate('{duration:this.formatHours}', {
                formatHours: function(v) {
                    if(v < 1) {
                        return Math.round(v * 60) + ' mins';
                    } else if (Math.floor(v) !== v) {
                        var min = v - Math.floor(v);
                        return Math.floor(v) + 'h ' + Math.round(min * 60) + 'm';
                    } else {
                        return v + ' hour' + (v === 1 ? '' : 's');
                    }
                }
            })
        },{
            header: 'Assigned To',
            width: 150,
            dataIndex: 'user'
        }],

        dataUrl: '/admin/email/show-email-log/?id=' + getParams.id + '&type=json&getData=true'
    });*/

    var tree = new Ext.ux.tree.TreeGrid({
        width: 500,
        height: 300,
        renderTo: Ext.getBody(),
        enableDD: true,

        columns:[{
            header: 'Property Key',
            dataIndex: 'property',
            width: 230
        },{
            header: 'Data',
            width: 150,
            dataIndex: 'data',
            tpl: new Ext.XTemplate('{data:this.formatTesten}', {
                formatTesten: function (v){
                    console.log(v);
                  return 'xxx';
                },
                formatHours: function(v) {
                    return 'halsslo';
                }
            })
        }],

        dataUrl: '/admin/email/show-email-log/?id=' + getParams.id + '&type=json&getData=true'
    });
    var url = location.href;
});

        </script>
    </head>

    <body style="padding: 50px;"></body>
</html>