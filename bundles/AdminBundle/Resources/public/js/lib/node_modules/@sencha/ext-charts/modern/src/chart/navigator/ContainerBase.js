/**
 *
 */
Ext.define('Ext.chart.navigator.ContainerBase', {
    extend: 'Ext.Container',

    updateNavigator: function(navigator, oldNavigator) {
        if (oldNavigator) {
            this.remove(oldNavigator, true);
        }

        this.add(navigator);
    }

});
