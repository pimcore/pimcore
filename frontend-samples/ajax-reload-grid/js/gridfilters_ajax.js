/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


if(!ELEMENTS) {
    var ELEMENTS = {};
}
if(!ELEMENTS.onlineShop) {
    ELEMENTS.onlineShop = {};
}

ELEMENTS.onlineShop.gridfilters = {};

ELEMENTS.onlineShop.gridfilters.loadAdditionalProducts = function loadAdditionalProducts() {
    $('#js_filterfield').ajaxSubmit({
        url:  window.location.pathname + '/ajaxroute/ajax/reload-products',
        success: function(response, statusText, xhr, form) {
           $(form).parent().next().children('.js_ajaxcontainer').html(response);
        }
    });
};

ELEMENTS.onlineShop.gridfilters.registerEvents = function() {
    $('#maincontent').on('click', '.filter .js_optionfilter_option', function() {
        ELEMENTS.onlineShop.gridfilters.selectFilter(this);
    });

    $('#maincontent').on('click', '.filter .js_genderfilter_option', function() {
        if($(this).hasClass('active')) {
            var parent = $(this).parents('.js_filterparent');
            ELEMENTS.onlineShop.gridfilters.resetFilter(parent, this);
        } else {
            ELEMENTS.onlineShop.gridfilters.selectFilter(this);
        }
    });



    $('#maincontent').on('click', '.filter .js_reset_filter', function() {
        var parent = $(this).parents('.js_filterparent');
        ELEMENTS.onlineShop.gridfilters.resetFilter(parent, this);
    });
};


ELEMENTS.onlineShop.gridfilters.selectFilter = function(option) {
    var parent = $(option).parents('.js_filterparent');
    parent.addClass("active");
    $(parent).children('.js_options').hide();
    $(parent).children('.js_optionvaluefield').val($(option).attr('rel'));

    $(parent).find('.js_curent_selection_text').html($(option).html());

    $(parent).find('.js_icon').addClass('js_reset_filter');

    ELEMENTS.onlineShop.gridfilters.submitForm();
};

ELEMENTS.onlineShop.gridfilters.resetFilter = function(parent, icon) {
    parent.removeClass('active');
    $(parent).children('.js_optionvaluefield').val('');

    if(icon) {
        $(icon).removeClass('js_reset_filter');
        $(icon).removeClass('active');
    }

    ELEMENTS.onlineShop.gridfilters.submitForm();
};

ELEMENTS.onlineShop.gridfilters.submitForm = function() {
    var url = "?" + $('#js_filterfield').formSerialize();
    $.address.value(url);
};

$.address.state(window.location.pathname);
$.address.crawlable(1).init(function() {
}).change(function(event) {
    if($.address.firstLoaded) {
        var data = {};
        if(event.value == "/") {
            data.filterdef = $('#filterdef').val();
        }
        $.ajax({
            url: window.location.pathname + '/ajaxroute/ajax/grid' + event.value,
            data: data,
            success: function(response) {
                $('#js_filterfield').parent().parent().html(response);
                ELEMENTS.onlineShop.gridfilters.loadAdditionalProducts();
            }
        });
    } else {
        $.address.firstLoaded = true;
    }
});

$(document).ready(function () {
    ELEMENTS.onlineShop.gridfilters.registerEvents();
    ELEMENTS.onlineShop.gridfilters.loadAdditionalProducts();
});