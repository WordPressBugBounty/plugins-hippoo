jQuery(document).ready(function($) {
    console.log("Document is ready!");

    /* Tab Navigation */
    $(document).on('click', '#hippoo_settings .tabs .nav-tab-wrapper .nav-tab', function(event) {
        event.preventDefault();

        console.log("Tab clicked!");

        var selectedTab = $(this).attr('href').replace('#', '');
        console.log("Selected Tab:", selectedTab);

        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').removeClass('active');

        $(this).addClass('nav-tab-active');
        $('#' + selectedTab).addClass('active');
    });

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

    /* PWA */
    function togglePwaSettingsFields() {
        if ($('#pwa_plugin_enabled').is(':checked')) {
            $('#pwa_route_name, #pwa_custom_css').prop('disabled', false);
            $('#pwa_route_name, #pwa_custom_css').closest('tr').removeClass('disabled');
        } else {
            $('#pwa_route_name, #pwa_custom_css').prop('disabled', true);
            $('#pwa_route_name, #pwa_custom_css').closest('tr').addClass('disabled');
        }
    }

    togglePwaSettingsFields();

    $(document).on('click', '#hippoo_settings #pwa_plugin_enabled', function() {
        console.log("PWA checkbox clicked");
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
});
