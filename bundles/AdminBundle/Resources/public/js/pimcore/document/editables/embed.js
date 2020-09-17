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

pimcore.registerNS("pimcore.document.editables.embed");
pimcore.document.editables.embed = Class.create(pimcore.document.editable, {

    initialize: function(id, name, config, data, inherited) {
        this.id = id;
        this.name = name;
        this.config = this.parseConfig(config);
        this.data = data;
    },

    render: function () {
        this.setupWrapper();

        this.element = Ext.get(this.id);

        let button = new Ext.Button({
            iconCls: "pimcore_icon_embed pimcore_icon_overlay_edit",
            cls: "pimcore_edit_link_button",
            handler: this.openEditor.bind(this)
        });
        button.render(this.element.insertHtml("afterBegin", '<div class="pimcore_video_edit_button"></div>'));

        if(empty(this.data["url"])) {
            this.element.addCls("pimcore_tag_embed_empty pimcore_editable_embed_empty");
            this.element.on("click", this.openEditor.bind(this));
        }
    },

    openEditor: function () {

        // disable the global dnd handler in this editmode/frame
        window.dndManager.disable();

        parent.Ext.MessageBox.prompt("", 'URL (eg. https://www.youtube.com/watch?v=nPntDiARQYw)',
        function (button, value, object) {
            if(button == "ok") {
                this.data["url"] = value;
                this.reloadDocument();
            }
        }.bind(this), this, false, this.data["url"]);
    },

    getValue: function () {
        return this.data;
    },

    getType: function () {
        return "embed";
    }
});