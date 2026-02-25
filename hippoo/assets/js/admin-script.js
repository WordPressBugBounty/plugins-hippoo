jQuery(document).ready(function($) {

    /* Notice */
    $(document).on('click', '.hippoo-notice .notice-dismiss', function(event) {
        event.preventDefault();

        var nonce = $('#handle_dismiss_nonce').val();
        console.log("Notice dismissed, nonce:", nonce);

        $.ajax({
            url: ajaxurl,
            data: {
                action: 'dismiss_admin_notice',
                nonce: nonce
            },
            success: function(response) {
                console.log("Notice dismissal response:", response);
            },
            error: function(error) {
                console.error("Notice dismissal error:", error);
            }
        });
    });

    /* Carousel */
    var carousel = $('#hippoo_settings #image-carousel .carousel-inner');
    var sliderImages = carousel.find('.carousel-image');
    var slideCount = sliderImages.length;
    var currentPosition = 0;

    console.log("Carousel initialized with slide count:", slideCount);

    function updateCarouselPosition() {
        var slideWidth = sliderImages.first().outerWidth();
        carousel.css('transform', 'translateX(-' + (currentPosition * slideWidth) + 'px)');
        console.log("Carousel position updated to:", currentPosition);
    }

    function moveCarouselPrev() {
        if (currentPosition > 0) {
            currentPosition--;
            updateCarouselPosition();
        }
    }

    function moveCarouselNext() {
        if (currentPosition < slideCount - 1) {
            currentPosition++;
            updateCarouselPosition();
        }
    }

    $(document).on('click', '#hippoo_settings #image-carousel .carousel-arrow.prev', function() {
        console.log("Previous arrow clicked");
        moveCarouselPrev();
    });
    
    $(document).on('click', '#hippoo_settings #image-carousel .carousel-arrow.next', function() {
        console.log("Next arrow clicked");
        moveCarouselNext();
    });

    /* Image Optimization */
    function toggleImageSizeDropdown() {
        if ($('#image_optimization_enabled').is(':checked')) {
            $('#image_size_selection').prop('disabled', false);
        } else {
            $('#image_size_selection').prop('disabled', true);
        }
    }
    
    toggleImageSizeDropdown();

    $(document).on('click', '#hippoo_settings #image_optimization_enabled', function() {
        toggleImageSizeDropdown();
    });

    /* PWA */
    function togglePwaSettingsFields() {
        if ($('#pwa_plugin_enabled').is(':checked')) {
            $('#pwa_route_name, #pwa_custom_css').prop('disabled', false);
            $('#pwa_route_name, #pwa_custom_css').closest('tr').removeClass('disabled');
        } else {
            $('#pwa_route_name, #pwa_custom_css').prop('disabled', true);
            $('#pwa_route_name, #pwa_custom_css').closest('tr').addClass('disabled');
        }
		$('#pwa_route_name').prop('disabled', true);
    }

    togglePwaSettingsFields();

    $(document).on('click', '#hippoo_settings #pwa_plugin_enabled', function() {
        togglePwaSettingsFields();
    });

    /* Review Banner */
    $(document).on('click', '.hippoo-dismiss-review', function(event) {
        event.preventDefault();

        $.ajax({
            url: hippoo.ajax_url,
            type: 'POST',
            data: {
                action: 'hippoo_dismiss_review',
                nonce: hippoo.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.hippoo-review-banner').remove();
                }
            }
        });
    });

    /* API Error */
    $(document).on('click', '.hippoo-dismiss-api-error', function(event) {
        event.preventDefault();

        $.ajax({
            url: hippoo.ajax_url,
            method: 'POST',
            data: {
                action: 'hippoo_dismiss_api_error',
                nonce: hippoo.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.hippoo-rest-api-error').fadeOut();
                }
            }
        });
    });

    $(document).on('click', '.hippoo-retry-api-check', function(event) {
        event.preventDefault();

        $.ajax({
            url: hippoo.ajax_url,
            method: 'POST',
            data: {
                action: 'hippoo_retry_api_check',
                nonce: hippoo.nonce
            },
            success: function(response) {
                if (response.status === 'success') {
                    $('.hippoo-rest-api-error').fadeOut();
                } else {
                    $('.hippoo-rest-api-error p:first').text(response.message);
                }
            }
        });
    });
    
    /* AI Test Connection */
    $('#test-ai-connection').on('click', function() {
        var button = $(this);
        var originalText = button.text();
        
        
        button.text('Testing...').prop('disabled', true);
        
        $.ajax({
            url: hippoo.ajax_url,
            method: 'POST',
            data: {
                action: 'hippoo_test_ai_connection',
                nonce: hippoo.nonce,
                api_token: $('input[name="hippoo_ai_settings[api_token]"]').val(),
                ai_provider: $('select[name="hippoo_ai_settings[ai_provider]"]').val()
            },
            success: function(response) {
                if (response.success) {
                    alert('Connection successful!');
                } else {
                    alert('Connection failed: ' + response.data);
                }
                
                button.text(originalText).prop('disabled', false);
            },
            error: function() {
                alert('Connection test failed.');
                button.text(originalText).prop('disabled', false);
            }
        });
    });

    $(document).on('change', 'select[name="hippoo_ai_settings[ai_provider]"]', function() {
        var provider = $(this).val();

        $.ajax({
            url: hippoo.ajax_url,
            method: 'POST',
            data: {
                action: 'hippoo_get_models_by_provider',
                nonce: hippoo.nonce,
                ai_provider: provider
            },
            success: function(response) {
                if (!response.success) {
                    console.error("Failed to load models");
                    return;
                }

                var models = response.data;
                var modelSelect = $('select[name="hippoo_ai_settings[ai_model]"]');

                modelSelect.empty();

                models.forEach(function(model) {
                    modelSelect.append('<option value="'+model+'">'+model+'</option>');
                });
            }
        });
    });

    /* Integrations */
    function loadIntegrations() {
        $('#hippoo-integrations-loading').show();
        $('#hippoo-integrations-list').hide();

        $.ajax({
            url: hippoo.ajax_url,
            method: 'POST',
            data: {
                action: 'hippoo_get_integrations',
                nonce: hippoo.nonce
            },
            success: function(response) {
                if (!response.success) return;

                let html = '';
                response.data.forEach(function(item) {
                    const status = item.status;
                    let button = '';
                    if (status === 'active') {
                        button = '<span class="button button-secondary">Activated</span>';
                    } else if (status === 'installed') {
                        button = `<button class="button button-primary hippoo-integrate-btn" data-slug="${item.slug}">Activate</button>`;
                    } else {
                        button = `<button class="button button-primary hippoo-integrate-btn" data-slug="${item.slug}">Install Now</button>`;
                    }

                    html += `
                        <div class="integration-item">
                            <a href="${item.detail_url}" target="_blank"><img src="${item.image}" alt="${item.name}"></a>
                            <div class="integration-info">
                                <h4><a href="${item.detail_url}" target="_blank">${item.name}</a></h4>
                                <p>${item.description.replace(/<[^>]*>/g, '')}</p>
                                <div class="integration-actions">
                                    ${button}
                                </div>
                            </div>
                        </div>`;
                });
                $('#hippoo-integrations-list').html(html).show();
            },
            complete: function () {
                $('#hippoo-integrations-loading').hide();
            }
        });
    }

    $(document).on('click', '.hippoo-integrate-btn', function(e) {
        e.preventDefault();

        var button = $(this);
        var slug = button.data('slug');
        var originalText = button.text();
        var actionsWrapper = button.closest('.integration-actions');

        if (button.prop('disabled')) return;

        button.prop('disabled', true).text('Processing...');

        $.ajax({
            url: hippoo.ajax_url,
            method: 'POST',
            data: {
                action: 'hippoo_install_integration',
                nonce: hippoo.nonce,
                slug: slug
            },
            success: function(response) {
                if (response.success) {
                    const status = response.data.status;

                    if (status === 'active') {
                        actionsWrapper.html('<span class="button button-secondary">Activated</span>');
                    } else if (status === 'installed') {
                        actionsWrapper.html(`<button class="button button-primary hippoo-integrate-btn" data-slug="${slug}">Activate</button>`);
                    } else {
                        actionsWrapper.html(`<button class="button button-primary hippoo-integrate-btn" data-slug="${slug}">Install Now</button>`);
                    }
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                    button.prop('disabled', false).text(originalText);
                }
            },
            error: function(error) {
                alert('Connection error. Please try again.');
                button.prop('disabled', false).text(originalText);
            }
        });
    });

    if ($('#hippoo-integrations-list').length > 0) {
        loadIntegrations();
    }

});
