<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?php echo htmlentities($this->getRequest()->getHttpHost(), ENT_QUOTES, 'UTF-8') ?> :: Pimcore</title>


    <!-- libraries and stylesheets -->
    <link rel="stylesheet" type="text/css" href="/pimcore/static/js/lib/ext/resources/css/ext-all.css"/>
    <link rel="stylesheet" type="text/css" href="/pimcore/static/js/lib/ext/resources/css/xtheme-gray.css"/>

    <style type="text/css">
        body {
            background: #222;
        }
    </style>
</head>

<body>

<script type="text/javascript">
    var pimcore_version = "<?php echo \Pimcore\Version::getVersion() ?>";
</script>

<?php

$scripts = array(
    // library
    "lib/prototype-light.js",
    "lib/jquery.min.js",
    "lib/ext/adapter/jquery/ext-jquery-adapter.js",
    "lib/ext/ext-all-debug.js"
);

?>

<?php foreach ($scripts as $scriptUrl) { ?>
<script type="text/javascript" src="/pimcore/static/js/<?php echo $scriptUrl ?>"></script>
<?php } ?>


<script type="text/javascript">

    var errorMessages = '<?php echo implode("<br />", $this->errors) ?>';
    var installdisabled = false;

    <?php if (!empty($this->errors)) { ?>
            installdisabled = true;
        <?php } ?>

    Ext.onReady(function() {

        Ext.Ajax.timeout = 900000;

        var pimcoreViewport = new Ext.Viewport({
            id: "pimcore_viewport"
        });

        var win = new Ext.Window({
            width: 300,
            closable: false,
            title: "PIMCORE Installer",
            closeable: false,
            y: 50,
            items: [
                {
                    xtype: "panel",
                    id: "logo",
                    border: false,
                    bodyStyle: "padding: 10px",
                    html: '<div align="center"><img width="200" src="/pimcore/static/img/logo-gray.png" align="center" /></div>'
                },
                {
                    xtype: "panel",
                    id: "install_errors",
                    border: false,
                    bodyStyle: "color: red; padding: 10px",
                    html: errorMessages
                },
                {
                    xtype: "form",
                    id: "install_form",
                    defaultType: "textfield",
                    bodyStyle: "padding: 10px",
                    items: [
                        {
                            title: "MySQL Settings",
                            xtype: "fieldset",
                            defaults: {
                                width: 130
                            },
                            items: [{
                                    xtype: "combo",
                                    name: "mysql_adapter",
                                    fieldLabel: "Adapter",
                                    store: [
                                        ["Mysqli", "Mysqli"],
                                        ["Pdo_Mysql", "Pdo_Mysql"]
                                    ],
                                    mode: "local",
                                    value: "Mysqli",
                                    width: 120,
                                    triggerAction: "all"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_host_socket",
                                    fieldLabel: "Host / Socket",
                                    value: "localhost"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_port",
                                    fieldLabel: "Port",
                                    value: "3306"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_username",
                                    fieldLabel: "Username"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_password",
                                    fieldLabel: "Password"
                                },
                                {
                                    xtype: "textfield",
                                    name: "mysql_database",
                                    fieldLabel: "Database"
                                }
                            ]
                        },
                        {
                            title: "Admin User",
                            xtype: "fieldset",
                            defaults: {
                                width: 130
                            },
                            items: [
                                {
                                    xtype: "textfield",
                                    name: "admin_username",
                                    fieldLabel: "Username",
                                    value: "admin"
                                },
                                {
                                    xtype: "textfield",
                                    name: "admin_password",
                                    fieldLabel: "Password"
                                }
                            ]
                        }
                    ]
                }
            ],
            bbar: [{
                    text: "Check Requirements",
                    icon: "/pimcore/static/img/icon/laptop_magnify.png",
                    handler: function () {
                        window.open("/install/check/?" + Ext.urlEncode(Ext.getCmp("install_form").getForm().getFieldValues()));
                    }
                },"->",
                {
                    text: "<b>Install Now!</b>",
                    icon: "/pimcore/static/img/icon/accept.png",
                    disabled: installdisabled,
                    handler: function (btn) {

                        btn.disable();

                        Ext.getCmp("install_errors").update("Installing ...");

                        Ext.Ajax.request({
                            url: "/install/index/install",
                            method: "post",
                            params: Ext.getCmp("install_form").getForm().getFieldValues(),
                            success: function (transport) {
                                try {
                                    var response = Ext.decode(transport.responseText);
                                    if (response.success) {
                                        location.href = "/admin/";
                                    }
                                }
                                catch (e) {
                                    Ext.getCmp("install_errors").update(transport.responseText);
                                    btn.enable();
                                }
                            },
                            failure: function (transport) {
                                Ext.getCmp("install_errors").update("Failed: " + transport.responseText);
                                btn.enable();
                            }
                        });
                    }
                }
            ]
        });

        pimcoreViewport.add(win);

        win.show();
    });

</script>

</body>
</html>
