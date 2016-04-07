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


$(document).ready(function () {
    $('.filter .js_optionfilter_option').click(function() {
        ELEMENTS.onlineShop.gridfilters.selectFilter(this);
    });

    $('.filter .js_genderfilter_option').click(function() {
        if($(this).hasClass('active')) {
            var parent = $(this).parents('.js_filterparent');
            ELEMENTS.onlineShop.gridfilters.resetFilter(parent, this);
        } else {
            ELEMENTS.onlineShop.gridfilters.selectFilter(this);
        }
    });



    $('.filter .js_reset_filter').on('click', function() {
        var parent = $(this).parents('.js_filterparent');
        ELEMENTS.onlineShop.gridfilters.resetFilter(parent, this);
    });
});


if(!ELEMENTS) {
    var ELEMENTS = {};
}
if(!ELEMENTS.onlineShop) {
    ELEMENTS.onlineShop = {};
}

ELEMENTS.onlineShop.gridfilters = {};

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
    $('#js_filterfield').submit();
};
