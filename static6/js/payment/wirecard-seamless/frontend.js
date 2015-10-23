var wirecardSeamlessHandler = {
    init: function() {
        this.initEvents();
    },

    initEvents: function() {
        $('#js-wirecard-cc-submit').on('click', function(e){
            var form = $(this).closest('form');
            var paymentInformation = form.serializeObject();
            wirecardSeamlessHandler.storeCreditCardDataToSession(paymentInformation, form);
        });

        $('.js-wirecard-payment-submit').on('click', function(e){
            var form = $(this).closest('form');
            var container = form.closest('.checkout-container');
            var paymentType = form.find("input[name=paymentType]").val();
            container.html(wirecardSeamlessHandler.getLoadingDiv());
            wirecardSeamlessHandler.scrollTo(container);
            $.ajax({
                url: '/at/shop/_action/shop_checkout/payment-iframe?paymentType=' + paymentType,
                success: function(data) {
                    container.html(data);
                }
            });
        });


    },

    storeCreditCardDataToSession: function(paymentInformation, form) {
        var dataStorage = new WirecardCEE_DataStorage();

        var container = form.closest('.checkout-container');
        $('.cc-error').remove();
        form.addClass('ajax-loading');
        wirecardSeamlessHandler.scrollTo(form);
        form.prepend(wirecardSeamlessHandler.getLoadingDiv());

        window.name='wirecard_checkout_topwindow';

        dataStorage.storeCreditCardInformation(paymentInformation, callbackFunction);

        function callbackFunction(response) {
            if(!response.getErrors()) {
                container.addClass('ajax-loading').html(wirecardSeamlessHandler.getLoadingDiv());
                wirecardSeamlessHandler.scrollTo(container);
                $.ajax({
                    url: '/at/shop/_action/shop_checkout/payment-iframe?paymentType=CCARD',
                    success: function(data) {
                        container.html(data);
                    }
                });
            } else {
                var errors = '<ul>';
                $.each(response.getErrors(), function(i,error){
                    errors += '<li>' + error.consumerMessage + '</li>';
                });
                errors += '</ul>';
                form.removeClass('ajax-loading');
                $('.wirecard-seamless-loading').replaceWith($('<div class="alert alert-danger cc-error">' + errors + '</div>'));
                console.log('errors');
                console.log(response.getErrors());
            }


        }
    },

    getLoadingDiv: function() {
        return '<div class="wirecard-seamless-loading ajax-loader"></div>';
    },

    scrollTo: function(element) {
        $('html, body').animate({
            scrollTop: element.offset().top - 50
        }, 500);
    }
};

$(function(){
    wirecardSeamlessHandler.init();
});

(function($){
    $.fn.serializeObject = function () {
        "use strict";

        var result = {};
        var extend = function (i, element) {
            var node = result[element.name];

            // If node with same name exists already, need to convert it to an array as it
            // is a multi-value field (i.e., checkboxes)

            if ('undefined' !== typeof node && node !== null) {
                if ($.isArray(node)) {
                    node.push(element.value);
                } else {
                    result[element.name] = [node, element.value];
                }
            } else {
                result[element.name] = element.value;
            }
        };

        $.each(this.serializeArray(), extend);
        return result;
    };
})(jQuery);