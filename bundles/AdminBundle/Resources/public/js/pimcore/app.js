/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

console.log("start app");

// debug
if (typeof console == "undefined") {
    console = {
        log: function (v) {
        },
        dir: function (v) {
        },
        debug: function (v) {
        },
        info: function (v) {
        },
        warn: function (v) {
        },
        error: function (v) {
        },
        trace: function (v) {
        },
        group: function (v) {
        },
        groupEnd: function (v) {
        },
        time: function (v) {
        },
        timeEnd: function (v) {
        },
        profile: function (v) {
        },
        profileEnd: function (v) {
        }
    };
}

function importScripts(scripts, callback) {
    if (scripts.length === 0) {
        if (callback !== undefined) {
            callback(null);
        }
        return;
    }
    var i = 0, s, r, e, l;
    e = function(event) {
        s.parentNode.removeChild(s);
        if (callback !== undefined) {
            callback(event);
        }
    };
    l = function() {
        s.parentNode.removeChild(s);
        i++;
        if (i < scripts.length) {
            r();
            return;
        }
        if (callback !== undefined) {
            callback(null);
        }
    };
    r = function() {
        s = document.createElement("script");
        s.src = scripts[i];
        s.onerror = e;
        s.onload = l;
        document.head.appendChild(s);
    };
    r();
}


Ext.onReady(function () {
    console.log("Ext core version is " + Ext.versions.core.version);

    var xhrActive = 0; // number of active xhr requests

    Ext.Loader.setConfig({
        enabled: true
    });
    Ext.enableAriaButtons = false;

    Ext.Loader.setPath('Ext.ux', '/bundles/pimcoreadmin/js/lib/node_modules/@sencha/ext-ux/classic/src');
    Ext.Loader.setPath('Ext', '/bundles/pimcoreadmin/js/lib/node_modules/@sencha/ext-classic/src');
    Ext.Loader.setPath('Ext.chart', '/bundles/pimcoreadmin/js/lib/node_modules/@sencha/ext-charts/src/chart');

    console.log("Ext.require...");

    Ext.require([
        'Ext.form.field.Date',
        'Ext.form.field.Date',
        'Ext.data.JsonStore',
        'Ext.button.Split',
        'Ext.container.Viewport',
        'Ext.data.JsonStore',
        'Ext.grid.column.Action',
        'Ext.grid.plugin.CellEditing',
        'Ext.form.field.ComboBox',
        'Ext.form.field.Hidden',
        'Ext.grid.column.Check',
        'Ext.grid.property.Grid',
        'Ext.form.field.Time',
        'Ext.form.FieldSet',
        'Ext.form.Label',
        'Ext.form.Panel',
        'Ext.grid.feature.Grouping',
        'Ext.grid.Panel',
        'Ext.grid.plugin.DragDrop',
        'Ext.layout.container.Accordion',
        'Ext.layout.container.Border',
        'Ext.tip.QuickTipManager',
        'Ext.tab.Panel',
        'Ext.toolbar.Paging',
        'Ext.toolbar.Spacer',
        'Ext.tree.plugin.TreeViewDragDrop',
        'Ext.tree.Panel',
        'Ext.ux.colorpick.Field',
        'Ext.ux.colorpick.SliderAlpha',
        'Ext.ux.DataTip',
        'Ext.ux.form.MultiSelect',
        'Ext.ux.TabCloseMenu',
        'Ext.ux.TabReorderer',
        'Ext.ux.grid.SubTable',
        'Ext.window.Toast',
        'Ext.slider.Single',

        // charts
        'Ext.chart.interactions.ItemHighlight',
        'Ext.chart.axis.Numeric',
        'Ext.chart.axis.Category',
        'Ext.chart.series.Line',
        'Ext.chart.PolarChart',
        'Ext.chart.series.Pie',
        'Ext.chart.theme.DefaultGradients',
        'Ext.chart.interactions.Rotate',
        'Ext.chart.series.Bar',
        'Ext.chart.interactions.PanZoom'
    ], function () {
        console.log("load pimcore core sripts ...");

        let internalScripts = [
            "/admin/misc/pimcoreInternalScripts"
        ];

        var syncwas = Ext.Loader.syncModeEnabled;

        // hack: this friend is private
        Ext.Loader.syncModeEnabled = true;


        let scriptUrls = [];
        for (let i = 0; i < internalScripts.length; i++) {
            let script = "/bundles/pimcoreadmin/js/" + internalScripts [i];

            script = internalScripts[i];


            scriptUrls.push(script);

            try {
                // TODO EXTJS7 loader approach
                // Ext.Loader.loadScript({
                //     url: script,
                //     onLoad: function (script) {
                //         // console.log("successful load " + script);
                //     }.bind(this, script)
                // });

                // //TODO EXTJS7 script approach
                var newScript = document.createElement("script");
                newScript.src = script;
                newScript.setAttribute("defer", "");
                newScript.type = "text/javascript";
                document.body.appendChild(newScript);

            } catch (e) {
                console.log(e);
            }
        }
        Ext.Loader.syncModeEnabled = syncwas;

        return;
    });
});


