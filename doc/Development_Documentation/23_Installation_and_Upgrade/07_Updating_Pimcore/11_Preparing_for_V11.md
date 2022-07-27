# Preparing Pimcore for Version 11

## Preparatory Work
- Replace plugins with [event listener](../../20_Extending_Pimcore/13_Bundle_Developers_Guide/06_Event_Listener_UI.md) as follows:
    ```javascript
    pimcore.registerNS("pimcore.plugin.MyTestBundle");

    pimcore.plugin.MyTestBundle = Class.create(pimcore.plugin.admin, {
        getClassName: function () {
            return "pimcore.plugin.MyTestBundle";
        },
    
        initialize: function () {
            pimcore.plugin.broker.registerPlugin(this);
        },
    
        preSaveObject: function (object, type) {
            var userAnswer = confirm("Are you sure you want to save " + object.data.general.o_className + "?");
            if (!userAnswer) {
                throw new pimcore.error.ActionCancelledException('Cancelled by user');
            }
        }
    });
    
    var MyTestBundlePlugin = new pimcore.plugin.MyTestBundle();
    ```
    
    ```javascript
    document.addEventListener(pimcore.events.preSaveObject, (e) => {
        let userAnswer = confirm(`Are you sure you want to save ${e.detail.object.data.general.o_className}?`);
        if (!userAnswer) {
            throw new pimcore.error.ActionCancelledException('Cancelled by user');
        }
    });
    ```
- Replace deprecated JS functions
  - Use t() instead of ts()
  - Don't use pimcore.helpers.addCsrfTokenToUrl()
