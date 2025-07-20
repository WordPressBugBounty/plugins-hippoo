jQuery(document).ready(function($) {
    /* Settings */

    
    $(document).on('click', '#hippoo_settings .tabs .nav-tab-wrapper .nav-tab', function(event) {
        event.preventDefault();

        var selectedTab = $(this).attr('href').replace('#', '');

        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').removeClass('active');

        $(this).addClass('nav-tab-active');
        $('#' + selectedTab).addClass('active');
    });


    /* Notice */


    $(document).on('click', '.hippoo-notice .notice-dismiss', function(event) {
        event.preventDefault();

        var nonce = $('#handle_dismiss_nonce').val();
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'dismiss_admin_notice',
                nonce: nonce
            }
        });
    });


    /* Carousel */


    var carousel = $('#hippoo_settings #image-carousel .carousel-inner');
    var sliderImages = carousel.find('.carousel-image');
    var slideCount = sliderImages.length;
    var currentPosition = 0;

    function updateCarouselPosition() {
        var slideWidth = sliderImages.first().outerWidth();
        carousel.css('transform', 'translateX(-' + (currentPosition * slideWidth) + 'px)');
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

    $(document).on('click', '#hippoo_settings #image-carousel .carousel-arrow.prev', moveCarouselPrev);
    $(document).on('click', '#hippoo_settings #image-carousel .carousel-arrow.next', moveCarouselNext);


});
