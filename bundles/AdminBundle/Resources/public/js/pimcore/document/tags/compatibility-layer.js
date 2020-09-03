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
 *
 * @deprecated since v6.8 and will be removed in 7. Use {@link pimcore.document.editables.*} instead.
 */

pimcore.document["tags"] = {
    "area": pimcore.document.editables.area,
    "areablock": pimcore.document.editables.areablock,
    "block": pimcore.document.editables.block,
    "checkbox": pimcore.document.editables.checkbox,
    "date": pimcore.document.editables.date,
    "embed": pimcore.document.editables.embed,
    "image": pimcore.document.editables.image,
    "input": pimcore.document.editables.input,
    "link": pimcore.document.editables.link,
    "multiselect": pimcore.document.editables.multiselect,
    "numeric": pimcore.document.editables.numeric,
    "pdf": pimcore.document.editables.pdf,
    "relation": pimcore.document.editables.relation,
    "href": pimcore.document.editables.relation,
    "relations": pimcore.document.editables.relations,
    "multihref": pimcore.document.editables.relations,
    "renderlet": pimcore.document.editables.renderlet,
    "scheduledblock": pimcore.document.editables.scheduledblock,
    "select": pimcore.document.editables.select,
    "snippet": pimcore.document.editables.snippet,
    "table": pimcore.document.editables.table,
    "textarea": pimcore.document.editables.textarea,
    "video": pimcore.document.editables.video,
    "wysiwyg": pimcore.document.editables.wysiwyg,
};

pimcore.document["tag"] = pimcore.document.editable;
