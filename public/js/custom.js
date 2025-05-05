(function ($, window, document, undefined) {
    $(document).ready(function () {
        if ($('.search-clear').length) {
            $('.search-clear').click(function(e) {
                e.preventDefault();
                $(this).siblings().find('input').each(function () {
                    $(this).val(null);
                });
                $(this).siblings('.search-input').each(function () {
                    $(this).val(null);
                });
            })
        }
        if ($('#datetime_options').length) {
            processDatePicking($('#datetime_options'));
            $('#datetime_options').on('input', function (e) {
                processDatePicking($(this));
            });
        }
        $('.search-input').keydown(function(event) {
            if (event.keyCode === 13) {
                event.preventDefault();
                $('.form-search').submit();
            }
        });
        $('#adminmenu .dropdown').mouseup(function(){
            setTimeout(function(){
                let $height = $('.side-menu').height();
                let $innerHeight = $('.side-menu .navbar').height();
                if ($innerHeight > $height) {
                    $('.ps__scrollbar-y').height($height*$height/$innerHeight);
                } else {
                    $('.ps__scrollbar-y').height(0);
                }
            },350);
        })
        function processDatePicking($element) {
            if ('between' == $element.val()) {
                $element.siblings('.date-additional').removeClass('hidden');
            } else {
                if (!$element.siblings('.date-additional').hasClass('hidden')) {
                    $element.siblings('.date-additional').addClass('hidden');
                    $element.siblings('.date-additional').val('');
                }
            }
        }
    });
})(jQuery, window, document);
