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


Ext.onReady(function () {

    Ext.Loader.setConfig({
        enabled: true
    });

    Ext.enableAria = false;

    Ext.Loader.setPath('Ext.ux', '/bundles/pimcoreadmin/js/lib/node_modules/@sencha/ext-ux/classic/src');
    Ext.Loader.setPath('Ext', '/bundles/pimcoreadmin/js/lib/node_modules/@sencha/ext-classic/src');

    Ext.require([
        'Ext.form.field.Date',
        'Ext.ux.form.MultiSelect',
    ], function () {

        let internalScripts = [
            "/admin/misc/pimcore-editmode-scripts"
        ];

        // hack: this friend is private
        let syncBackup = Ext.Loader.syncModeEnabled;
        Ext.Loader.syncModeEnabled = true;


        let scriptUrls = [];
        for (let i = 0; i < internalScripts.length; i++) {
            let script = "/bundles/pimcoreadmin/js/" + internalScripts [i];

            script = internalScripts[i];
            scriptUrls.push(script);

            try {
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

        Ext.Loader.syncModeEnabled = syncBackup;
    });

});


