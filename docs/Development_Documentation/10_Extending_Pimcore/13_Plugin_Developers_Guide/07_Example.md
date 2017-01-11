# Plugin Example

## Plugin Skeleton

Plugins are situated within the plugins folder in the Pimcore root directory. For each plugin a separate folder must be created.

You can generate the structure of the plugin using *skeleton generator* in the administration panel:

As a name choose: *ExtensionExample*

<div class="inline-imgs">

[comment]: #TODOinlineimgs

Go to: ![Tools](../../img/Icon_tools.png)  **Tools -> Extensions ->** ![Create new plugin skeleton](../../img/Icon_Create_new_plugin_skeleton.png)

You can find your newly generated plugin including all necessary files in `plugins/ExtensionExample`

At this point, if you log into the Pimcore admin then navigate to ![Tools](../../img/Icon_tools.png)**Tools -> Extensions**  
you should be able to see, enable, install and uninstall your new plugin.

Don't forget to activate the extension ![Enable extension](../../img/Extensions_enable.png)

</div>

## Modifying The Admin Interface
Next we're going to modify the admin interface. 
All of the UI changes are driven by javascript, so open the `plugins/ExtensionExample/static/js/startup.js` file.

Let's create a new menu item:

Change `pimcoreReady` function like below:

```javascript
pimcoreReady: function(params,broker){
    // add a sub-menu item under "Extras" in the main menu
    var toolbar = pimcore.globalmanager.get("layout_toolbar");

    var action = new Ext.Action({
        id: "extensionexample_menu_item",
        text: "Extension Example",
        iconCls:"extensionexample_menu_icon",
        handler: this.showTab
    });

    toolbar.extrasMenu.add(action);
}
```

Now if you reload the Pimcore panel, you will see new menu item in Tools menu:

![Extension Example menu item](../../img/Extensions_new_menu_item.png)

But when you click on it nothing happens. 


## Accessing Plugin Controllers
Before we start, you need to understand a bit about how plugins are routed.

If you strictly follow this example, you probably won't have any problems but if you make a mistake as little as a 
wrong-cased letter you will have a hard time debugging it. Pimcore has special routes for plugins so you need to understand 
how these work. 

For plugins, requesting a controller and action in the browser works a little bit different from the standard Pimcore way. 

For the example below, we will have a URL like this: `http://your_domain/plugin/ExtensionExample/admin/get-address-book`

There are some aspects regarding the above URL that you should keep in mind:
1. /plugin - is always the prefix for accessing plugins (notice there is no "s" in plugin)
2. /ExtensionExample/ - the plugin name is always case sensitive
3. /get-address-book - if the action is in camelcase you should write it with hyphens and make it lowercase

For more information have a look at [Routing and URLs](../../02_MVC/04_Routing_and_URLs/README.md).

## Making It Do Something Useful
Let's add some functionality to pull data from a web service and display it in a table.

In the `plugins/ExtensionExample/static/js/startup.js` file add new function `showTab` with content as below. 

```javascript
pimcore.registerNS("pimcore.plugin.extensionexample");

pimcore.plugin.extensionexample = Class.create(pimcore.plugin.admin, {
    getClassName: function() {
        return "pimcore.plugin.extensionexample";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params,broker){
        // add a sub-menu item under "Extras" in the main menu
        var toolbar = pimcore.globalmanager.get("layout_toolbar");

        var action = new Ext.Action({
            id: "extensionexample_menu_item",
            text: "Extension Example",
            iconCls:"extensionexample_menu_icon",
            handler: this.showTab
        });

        toolbar.extrasMenu.add(action);
    },
    showTab: function() {
        extensionexamplePlugin.panel = new Ext.Panel({
            id:         "extensionexample_check_panel",
            title:      "Extension example",
            iconCls:    "extensionexample_check_panel_icon",
            border:     false,
            layout:     "fit",
            closable:   true,
            items:      []
        });

        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.add(extensionexamplePlugin.panel);
        tabPanel.setActiveTab(extensionexamplePlugin.panel);
        
        pimcore.layout.refresh();
    }
});

var extensionexamplePlugin = new pimcore.plugin.extensionexample();
```

Now, a new tab is opened if you click on the plugin button.

![New extension Tab](../../img/Extensions_new_tab.png)


To add items into the new tab, exchange the line `items:      []` with `items:      [myPlugin.getGrid()]` and 
add a new method `getGrid` to the plugin class. 

```javascript
showTab: function() {
    ...
},

getGrid: function() {
    // fetch data from a webservice (which we haven't written yet!)
    extensionexamplePlugin.store = new Ext.data.JsonStore({
        proxy: {
            url: '/plugin/ExtensionExample/admin/get-address-book',
            type: 'ajax',
            reader: {
                type: 'json',
                rootProperty: 'addresses'
            }
        },
        fields: [
            "name",
            "phoneNumber",
            "address"
        ]
    });

    extensionexamplePlugin.store.load();

    var typeColumns = [
        {header: "Name",         width: 100, sortable: true, dataIndex: 'name'},
        {header: "Phone Number", width: 100, sortable: true, dataIndex: 'phoneNumber'},
        {header: "Address",      width: 100, sortable: true, dataIndex: 'address'}
    ];

    extensionexamplePlugin.grid = new Ext.grid.GridPanel({
        frame:          false,
        autoScroll:     true,
        store:          extensionexamplePlugin.store,
        columns:        typeColumns,
        trackMouseOver: true,
        columnLines:    true,
        stripeRows:     true,
        viewConfig:     { forceFit: true }
    });

    return extensionexamplePlugin.grid;
}
```

Now write a simple webservice that we can pull some data from - create the following file: 

`plugins/ExtensionExample/controllers/AdminController.php`

With the following content:

```php
<?php
use Pimcore\Controller\Action\Admin;

/**
 * Class ExtensionExample_AdminController
 */
class ExtensionExample_AdminController extends Admin
{
    /**
     * @return mixed
     */
    public function getAddressBookAction()
    {
        $addresses = [
            [
                "name"        => "Bob Dole",
                "phoneNumber" => "1234567890",
                "address"     => "123 Fake Street"
            ],
            [
                "name"        => "Joe Smith",
                "phoneNumber" => "0987654321",
                "address"     => "45 Newington Heights"
            ]
        ];

        return $this->_helper->json(["addresses" => $addresses]);
    }
}
```

Reload the admin interface, navigate to **Tools-> Extension Example**  and you should see a table with two rows, which 
can be sorted by column.

![Final grid](../../img/Extensions_final_grid.png)