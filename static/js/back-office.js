var _config = _config || {};
var _cfg = _config || {};

var site = {
    modules: {},
    _cfg: {},
    options: {},

    init: function(){
        site.initModules();
    },


    updateConfig: function(){
        _cfg = _config || {};
    }
};


/**
 *
 * @param $scope
 */
site.modules.ajaxModal = function($scope){
    var ajaxModal = $scope.find('.ajax-modal');

    ajaxModal.each(function (i, item) {

        // init
        var targetContainer = $( $(item).data('target') );
        var modalContent = targetContainer.find('.modal-content');


        // load on show
        targetContainer.on('show.bs.modal', function(e){

            // init
            e.stopImmediatePropagation();
            targetContainer.addClass('loading');


            // get url
            var ajaxUrl;
            if($(e.relatedTarget).attr('data-url') != undefined){
                ajaxUrl = $(e.relatedTarget).data('url');
            }else{
                ajaxUrl = $(e.relatedTarget).attr('href');
            }

            $.ajax({
                url: ajaxUrl
            }).done(function(data) {

                modalContent.html( data );
                site.initModules( modalContent );

                targetContainer.removeClass('loading');

            }).error(function(e){
                console.warn(e.message);
            });

        });

    });

};

/* init Modules kann doch hier bleiben weil der Aufruf erst später erfolgt. Einfach modules hinzufügen -> siehe shop.js*/

site.initModules = function ($scope) {
    if(!$scope) {
        $scope = $('body');
    }

    //init all modules if the right _cfg key is set for them
    for (var module in _cfg) {
        if (_cfg[module] && site.modules[module] && typeof site.modules[module] === 'function') {
            site.modules[module]($scope);
        }
    }
};

$(function () {
    site.init();
});