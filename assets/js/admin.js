(function($){
  $(function(){
    console.log('OdoTech admin loaded (v0.2.1)');
    
    // ==========================================
    // 1. Image Editor (Single Image)
    // ==========================================
    var mediaUploader;
    
    $(document).on('click', '.odo-select-image', function(e) {
      e.preventDefault();
      var button = $(this);
      var targetId = button.data('target');
      var storeMode = button.data('store') || 'url';
      var inputField = $('#' + targetId);
      var previewContainer = button.closest('.odo-image-editor').find('.odo-image-preview');
      var urlDisplay = $('#' + targetId + '_display');
      
      // Create new media uploader
      mediaUploader = wp.media({
        title: 'Chọn ảnh',
        button: {
          text: 'Sử dụng ảnh này'
        },
        multiple: false,
        library: {
          type: 'image'
        }
      });
      
      // When image is selected
      mediaUploader.on('select', function() {
        var attachment = mediaUploader.state().get('selection').first().toJSON();
        var imageUrl = attachment.url;
        var storedValue = (storeMode === 'id') ? attachment.id : imageUrl;
        
        // Update hidden input
        inputField.val(storedValue);
        
        // Update preview
        previewContainer.html('<img src="' + imageUrl + '" style="max-width:100%;max-height:400px;display:inline-block;border:2px solid #ddd;box-shadow:0 2px 8px rgba(0,0,0,0.1);">');
        
        // Update URL display
        if (urlDisplay.length) {
          urlDisplay.text(storedValue);
        }
      });
      
      mediaUploader.open();
    });
    
    // Remove image
    $(document).on('click', '.odo-remove-image', function(e) {
      e.preventDefault();
      var button = $(this);
      var targetId = button.data('target');
      var inputField = $('#' + targetId);
      var previewContainer = button.closest('.odo-image-editor').find('.odo-image-preview');
      var urlDisplay = $('#' + targetId + '_display');
      
      // Clear input
      inputField.val('');
      
      // Clear preview
      previewContainer.html('<p style="color:#999;font-size:16px;">📷 Chưa có ảnh</p>');
      
      // Clear URL display
      if (urlDisplay.length) {
        urlDisplay.text('');
      }
    });

    // ==========================================
    // 2. Slider Editor & 3. Inline Slider Editor
    // ==========================================

    // Helper: Reindex inputs after modification
    function reindexSliderItems(wrapper) {
        wrapper.find('.slider-item').each(function(index) {
            $(this).find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    // Replace the first index [n] with [index]
                    // Handles names like slider_data[0][url] -> slider_data[index][url]
                    var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                    $(this).attr('name', newName);
                }
            });
        });
    }

    // Helper: Create Slider Item HTML
    function createSliderItemHtml(attachment, fieldNamePrefix) {
        // Default prefix if not provided
        var prefix = fieldNamePrefix || 'odo_slider'; 
        
        return `
            <div class="slider-item">
                <div class="slider-image-wrapper">
                    <img src="${attachment.url}" alt="Slider Image" />
                    <div class="slider-overlay">
                        <button type="button" class="button odo-slider-change-img odo-img-change" data-id="${attachment.id}">Change</button>
                        <button type="button" class="button odo-slider-remove-img odo-img-remove">Remove</button>
                    </div>
                </div>
                <input type="hidden" name="${prefix}[][id]" value="${attachment.id}" class="slider-img-id" />
                <input type="hidden" name="${prefix}[][url]" value="${attachment.url}" class="slider-img-url" />
            </div>
        `;
    }

    // Add Single Image (Both Editors)
    $(document).on('click', '.odo-slider-add-img, .odo-slider-add-image, .odo-add-slider-image', function(e) {
        e.preventDefault();
        var button = $(this);
        var wrapper = button.closest('.odo-slider-editor, .odo-inline-slider-editor').find('.slider-items-wrapper');
        var fieldName = button.data('field-name') || 'odo_slider';

        var frame = wp.media({
            title: 'Thêm ảnh vào slider',
            multiple: false,
            library: { type: 'image' },
            button: { text: 'Thêm ảnh' }
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            wrapper.append(createSliderItemHtml(attachment, fieldName));
            reindexSliderItems(wrapper);
        });

        frame.open();
    });

    // Add Multiple Images
    $(document).on('click', '.odo-slider-add-multiple', function(e) {
        e.preventDefault();
        var button = $(this);
        var wrapper = button.closest('.odo-slider-editor, .odo-inline-slider-editor').find('.slider-items-wrapper');
        var fieldName = button.data('field-name') || 'odo_slider';

        var frame = wp.media({
            title: 'Thêm nhiều ảnh',
            multiple: true,
            library: { type: 'image' },
            button: { text: 'Thêm các ảnh đã chọn' }
        });

        frame.on('select', function() {
            var selection = frame.state().get('selection');
            selection.map(function(attachment) {
                attachment = attachment.toJSON();
                wrapper.append(createSliderItemHtml(attachment, fieldName));
            });
            reindexSliderItems(wrapper);
        });

        frame.open();
    });

    // Change Image (Both Editors)
    $(document).on('click', '.odo-slider-change-img, .odo-img-change', function(e) {
        e.preventDefault();
        var button = $(this);
        var item = button.closest('.slider-item');
        var img = item.find('img');
        var inputId = item.find('.slider-img-id');
        var inputUrl = item.find('.slider-img-url');

        var frame = wp.media({
            title: 'Thay đổi ảnh',
            multiple: false,
            library: { type: 'image' },
            button: { text: 'Cập nhật' }
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            img.attr('src', attachment.url);
            inputId.val(attachment.id);
            inputUrl.val(attachment.url);
        });

        frame.open();
    });

    // Remove Image (Both Editors + Legacy)
    $(document).on('click', '.odo-slider-remove-img, .odo-img-remove, .odo-remove-slider-item', function(e) {
        e.preventDefault();
        var button = $(this);
        var wrapper = button.closest('.slider-items-wrapper');
        
        // Animation before remove
        button.closest('.slider-item').fadeOut(300, function() {
            $(this).remove();
            reindexSliderItems(wrapper);
        });
    });

    // Clear All
    $(document).on('click', '.odo-slider-clear-all', function(e) {
        e.preventDefault();
        if (confirm('Bạn có chắc chắn muốn xóa tất cả ảnh trong slider này không?')) {
            var wrapper = $(this).closest('.odo-slider-editor, .odo-inline-slider-editor').find('.slider-items-wrapper');
            wrapper.find('.slider-item').fadeOut(300, function() {
                $(this).remove();
                // No need to reindex if empty, but good practice if we add logic later
            });
        }
    });

    // AJAX Save (Inline Editor)
    $(document).on('click', '.odo-slider-save', function(e) {
        e.preventDefault();
        var button = $(this);
        var container = button.closest('.odo-inline-slider-editor');
        var postId = button.data('post-id');
        var nonce = button.data('nonce'); // Assuming nonce is present
        
        // Collect data
        // We serialize the inputs within the container
        var data = container.find('input, select, textarea').serialize();
        
        button.addClass('loading').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data + '&action=odo_save_slider_inline&post_id=' + postId + '&_ajax_nonce=' + nonce,
            success: function(response) {
                button.removeClass('loading').prop('disabled', false);
                if (response.success) {
                    // Show success feedback
                    var originalText = button.text();
                    button.text('Đã lưu!');
                    setTimeout(function() {
                        button.text(originalText);
                    }, 2000);
                } else {
                    alert('Lỗi: ' + (response.data || 'Không thể lưu dữ liệu.'));
                }
            },
            error: function() {
                button.removeClass('loading').prop('disabled', false);
                alert('Đã xảy ra lỗi kết nối.');
            }
        });
    });

  });
})(jQuery);