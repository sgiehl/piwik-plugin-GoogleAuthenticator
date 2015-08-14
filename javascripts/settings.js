(function ($) {
    function showQRCode() {
        var secret = $('#gasecret').val();
        var title  = $('#gatitle').val();
        var descr  = $('#gadescription').val();

        var urlencoded = encodeURI('otpauth://totp/'+ descr + '?secret=' + secret);
        if(title) {
            urlencoded += encodeURIComponent('&issuer=' + encodeURIComponent(title).replace(/%20/g,'+'));
        }

        $('#qrcode').attr('src', 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' + urlencoded);
    }

    $(function() {
        $('#changeDescription, #changeTitle').bind('click', function () {
            var newValue = window.prompt($('label', $(this).parents('.form-group')).text(),
                $('input', $(this).parents('.form-group')).val());
            $('input', $(this).parents('.form-group')).val(newValue);
            showQRCode();
        });
    });
}(jQuery));
