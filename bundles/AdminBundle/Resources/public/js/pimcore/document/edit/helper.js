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

pimcore.edithelpers = {};

// disable reload & links, this function is here because it has to be in the header (body attribute)
function pimcoreOnUnload() {
    editWindow.protectLocation();
}

pimcore.edithelpers.frame = {
    active: false,
    topEl: null,
    bottomEl: null,
    rightEl: null,
    leftEl: null,
    timeout: null
};

 pimcore.edithelpers.pasteHtmlAtCaret = function (html, selectPastedContent) {
     var sel, range;
     if (window.getSelection) {
         // IE9 and non-IE
         sel = window.getSelection();
         if (sel.getRangeAt && sel.rangeCount) {
             range = sel.getRangeAt(0);
             range.deleteContents();

             // Range.createContextualFragment() would be useful here but is
             // only relatively recently standardized and is not supported in
             // some browsers (IE9, for one)
             var el = document.createElement("div");
             el.innerHTML = html;
             var frag = document.createDocumentFragment(), node, lastNode;
             while ((node = el.firstChild)) {
                 lastNode = frag.appendChild(node);
             }
             var firstNode = frag.firstChild;
             range.insertNode(frag);

             // Preserve the selection
             if (lastNode) {
                 range = range.cloneRange();
                 range.setStartAfter(lastNode);
                 if (selectPastedContent) {
                     range.setStartBefore(firstNode);
                 } else {
                     range.collapse(true);
                 }
                 sel.removeAllRanges();
                 sel.addRange(range);
             }
         }
     }
 };
