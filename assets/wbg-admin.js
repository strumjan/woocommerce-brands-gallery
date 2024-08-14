jQuery(document).ready(function ($) {
    $('.wbg_upload_image_button').click(function (e) {
        e.preventDefault();
        var custom_uploader = wp.media({
            title: 'Upload Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        }).on('select', function () {
            var attachment = custom_uploader.state().get('selection').first().toJSON();
            $('#wbg_imgs').val(attachment.url);
        }).open();
    });

    $('.wbg-toggle-category').click(function () {
        var productsDiv = $(this).parent().next('.wbg-products');
        productsDiv.slideToggle();
        $(this).text($(this).text() === '+' ? '-' : '+');
    });
});
