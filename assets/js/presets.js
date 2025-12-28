jQuery(document).ready(function($) {
    function attachMediaPicker(btnId, inputId, previewId, opts) {
        var $btn = $('#' + btnId);
        if (!$btn.length) return;

        var frame;
        $btn.on('click', function(e) {
            e.preventDefault();

            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: opts && opts.title ? opts.title : "Chọn ảnh",
                button: { text: (opts && opts.buttonText) ? opts.buttonText : "Dùng ảnh này" },
                library: { type: (opts && opts.libraryType) ? opts.libraryType : "image" },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                var url = attachment.url;

                $('#' + inputId).val(url);

                var $preview = $('#' + previewId);
                if (url) {
                    $preview.attr('src', url).show().css('display', 'inline-block');
                } else {
                    $preview.attr('src', '').hide();
                }
            });

            frame.open();
        });
    }

    // Gắn cho Logo website
    attachMediaPicker(
        "odo_pick_logo",
        "odo_website_logo_url",
        "odo_website_logo_url_preview",
        { title: "Chọn logo", buttonText: "Dùng logo này" }
    );

    // Gắn cho Favicon
    attachMediaPicker(
        "odo_pick_favicon",
        "odo_branding_favicon",
        "odo_branding_favicon_preview",
        { title: "Chọn favicon", buttonText: "Dùng favicon này" }
    );
});