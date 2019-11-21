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

document.addEventListener("DOMContentLoaded", function(event) {
    var cleanupButtons = document.getElementById('cleanupButtons');
    if (cleanupButtons) {
        new Button(cleanupButtons);
    }

    /**
     * Init Tabs
     */
    var navTabs = document.getElementById('tabs');
    var navTabsCollection = navTabs.getElementsByTagName('A');
    for (var i = 0; i < navTabsCollection.length; i++) {
        new Tab(navTabsCollection[i]);
    }

    /**
     * Init Status Messages Fadeout
     */
    var initFadeOut = function () {
        setTimeout(function () {
            var alert = document.querySelector('.js-fadeout');
            if (alert) {
                alert.style.display = 'none';
            }
        }, 5000);
    };

    initFadeOut();

    /**
     * Init Modal
     */
    document.querySelectorAll('.js-modal').forEach(function(el){
        el.addEventListener('click', function() {
            var selector = this.getAttribute('data-modal');

            var modal = document.getElementById(selector);
            var modalInstance = new Modal(modal,
                {
                    "backdrop": "static",
                    "keyboard": true,
                });
            modalInstance.show();
        });
    });

    /**
     * Init Modal Loadings
     */
    document.querySelectorAll('.modal .js-loading').forEach(function(el) {
        el.addEventListener('click', function () {
            var text = this.getAttribute('data-msg');
            var children = this.parentNode.children;
            var children = Array.prototype.slice.call(children);
            children.forEach.call(children, function(child) {
                child.style.display = 'none';
            });

            var newChild = document.createElement('div');
            newChild.setAttribute('class', 'text-left row');
            newChild.innerHTML = "<div class='text-left row'> <div class='col col-sm-12'> <span>"
                + text +
                "</span>&nbsp;<img class='pull-right' src='/bundles/pimcoreadmin/img/video-loading.gif' alt='loading' style='margin-right: 40px;'><div><div>";
            document.querySelector('.modal-footer').appendChild(newChild);
            return true;
        });
    });

});