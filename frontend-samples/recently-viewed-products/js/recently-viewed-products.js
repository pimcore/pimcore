$(document).ready(function(){
    var productId = $('#js-productId').val();
    $.ajax({
        url: window.location.pathname + '/ajaxroute/ajax/test',
        type: 'post',
        data: 'id=' + productId,
        success: function (data) {
            $('#js-recent-products').html(data);
        }
    })
});