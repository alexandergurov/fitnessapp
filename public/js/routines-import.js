(function ($, window, document, undefined) {
    $(document).ready(function () {
        let $videoField = $('#vimeo-url');
        var vimeoPlayer = null;
        processVideoInput($videoField);

        function processVideoInput($field) {
            let $url;
            try {
                $url = new URL($field.val());
                let $arguments = $url.pathname.split('/');
                if ($arguments[1]) {
                    $('#video-id').val($arguments[1]);
                    $.ajax({
                        url: '/admin/video-info?video-id=' + $arguments[1],
                        cache: false,
                        success: function (html) {
                            let $videoData = JSON.parse(html);
                            $('#vimeo-import').prepend($videoData.embed.html);
                            $('#vimeo-import').find('iframe').attr('id','vimeo-player-iframe');
                            vimeoPlayer = new Vimeo.Player('vimeo-player-iframe');
                            $('#vimeo-player-iframe').fadeOut(0);
                            $('#import-button').fadeIn();
                        }
                    });
                }
            }
            catch (error) {
                console.error(error);
            }
        }
        $videoField.on('input', function (e) {
            processVideoInput($(this));
        })

        $('#import-button').click(function (e) {
            e.preventDefault();
            if (vimeoPlayer) {
                let $durationPromise = vimeoPlayer.getDuration();
                let $chaptersPromise = vimeoPlayer.getChapters();
                Promise.all([$durationPromise, $chaptersPromise]).then(values => {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $('#chapters-data').val(JSON.stringify({data: values}));
                    $.ajax({
                        type: "POST",
                        url: '/admin/create-routines',
                        data: {data: JSON.stringify(values), videoid:$('#video-id').val()},
                        success: function ($resp) {
                            if (typeof $resp == 'string') {
                                $resp = JSON.parse($resp);
                                if (typeof $resp ==  'object') {location.reload();}
                            }
                        },
                    });

                });
            }
        });
    });
})(jQuery, window, document);
