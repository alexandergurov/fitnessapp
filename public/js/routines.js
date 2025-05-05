(function ($, window, document, undefined) {
    $(document).ready(function () {
        let $routinesData = $('#routines-data').val();
        var vimeoPlayer = null;
        if ($routinesData) {
            var $dataStored = JSON.parse($routinesData);
            if (typeof $dataStored == 'object') {
                $dataStored.forEach(function ($item) {
                    addRoutine($item);
                });
            }
        }
        $videoField = $('input[name ="video_url"]');
        processVideoInput($videoField);

        $('.fill-from-chapters').click(function (e) {
            if (vimeoPlayer) {
                let $durationPromise = vimeoPlayer.getDuration();
                let $chaptersPromise = vimeoPlayer.getChapters();
                Promise.all([$durationPromise, $chaptersPromise]).then(values => {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $url = new URL($videoField.val());
                    let $arguments = $url.pathname.split('/');
                    let $videoid = $arguments[1];
                    $.ajax({
                        type: "POST",
                        url: '/admin/create-routines',
                        data: {data: JSON.stringify(values), videoid:$videoid},
                        success: function ($resp) {
                            $('.routine-row:not(.new-routine)').remove();
                            if (typeof $resp == 'string') {
                                $resp = JSON.parse($resp);
                                if (typeof $resp ==  'object') {
                                    $resp = Object.values($resp);
                                    for (let $i=0; $i<$resp.length; $i++) {
                                        if ($(".routine-select option[value='"+$resp[$i]['id']+"']").length == 0) {
                                            $(".routine-select").append(new Option($resp[$i]['title']+'('+$resp[$i]['identifier']+')', $resp[$i]['id']));
                                        }
                                        let dataRow = {
                                            routineId: $resp[$i]['id'],
                                            start: toHHMMSS(values[1][$i].startTime),
                                            length: $resp[$i]['length']
                                        };
                                        addRoutine(dataRow);
                                    }
                                    let $routinesData = processRoutinesInput();
                                    $('#routines-data').val(JSON.stringify($routinesData));
                                }
                            }
                        },
                    });
                });
            }
        });
        $('.length-input').on('input', function (e) {
            let $routinesData = processRoutinesInput();
            $('#routines-data').val(JSON.stringify($routinesData));
        });
        $('.start-input').on('input', function (e) {
            let $routinesData = processRoutinesInput();
            $('#routines-data').val(JSON.stringify($routinesData));
        });
        $('.routine-select').on('input', function (e) {
            let $routinesData = processRoutinesInput();
            $('#routines-data').val(JSON.stringify($routinesData));
        });
        $('.routine-remove').click(function (e) {
            $(this).parent().remove();
        });
        $('.pull-time').click(function (e) {
            if (vimeoPlayer) {
                $positionPromise = vimeoPlayer.getCurrentTime();
                $positionPromise.then($time => {
                    let $starts;
                    let $length;
                    let $previousRoutine = $(this).parent('.routine-row').prev('.routine-row');
                    if ($previousRoutine.length) {
                        let $prevStart = fromHHMMSS($previousRoutine.find('.start-input').val());
                        let $startsSec = parseInt($prevStart)+parseInt($previousRoutine.find('.length-input').val());
                        $starts = toHHMMSS($startsSec);
                        $length = $time - $startsSec;
                    } else {
                        $starts = '00:00:00';
                        $length = $time - 0;
                    }
                    $(this).siblings('.length-input').val($length);
                    $(this).siblings('.start-input').val($starts);
                })
            }
        });


        $('#routines-add').click(function (e) {addRoutine();});

        function addRoutine($data = null) {
            let $newRoutine = $('.new-routine');
            if (!$data) {
                $data = extractData($newRoutine);
            }
            if ($data.error) {
                $data.element.addClass('red-border');
            }
            else {
                $('.red-border').removeClass('red-border');

                let $newRow = $newRoutine.clone();
                $newRow.removeClass('new-routine');
                if (!$newRow.find('.length-input').val()) {
                    $newRow.find('.length-input').val($data.length);
                }
                if (!$newRow.find('.start-input').val()) {
                    $newRow.find('.start-input').val($data.start);
                }
                if (!$newRow.find('.routine-select').val()) {
                    $newRow.find('.routine-select').val($data.routineId);
                }
                $newRow.find('#routines-add').remove();
                $newRow.insertBefore($newRoutine);
                let $deleteButton = $('<input/>', {
                    'type': 'button',
                    'class': 'delete-row',
                    'value': 'Delete routine'
                });

                $newRow.find('.length-input').on('input', function (e) {
                    let $routinesData = processRoutinesInput();
                    $('#routines-data').val(JSON.stringify($routinesData));
                });
                $newRow.find('.start-input').on('input', function (e) {
                    let $routinesData = processRoutinesInput();
                    $('#routines-data').val(JSON.stringify($routinesData));
                });
                $newRow.find('.routine-select').on('input', function (e) {
                    let $routinesData = processRoutinesInput();
                    $('#routines-data').val(JSON.stringify($routinesData));
                });
                $newRow.append($deleteButton);
                $deleteButton.click(function () {
                    $(this).parent().remove()
                });

                $newRoutine.find('.length-input').val(null);
                $newRoutine.find('.start-input').val(null);
                $newRoutine.find('.routine-select').val(null);
            }
        }

        function extractData($row) {
            let $lengthInput = $row.find('.length-input');
            let $startInput = $row.find('.start-input');
            let $routineInput = $row.find('.routine-select');
            let $data = {
                length: $lengthInput.val(),
                start: $startInput.val(),
                routineId: $routineInput.val(),
            }
            if (!isValid($data.length)) {
                return {error: 1, element: $lengthInput};
            }
            if (!isValid($data.start)) {
                return {error: 1, element: $startInput};
            }
            if (!isValid($data.routineId)) {
                return {error: 1, element: $routineInput};
            }
            return $data;
        }

        function isValid($value) {
            if (typeof $value != 'string') {
                return false;
            }
            return ($value.length !== 0 && $value.trim());
        }

        function processRoutinesInput() {
            let $routinesData = [];
            let $i = 0;
            $('.routines-fieldset .routine-row').each(function () {
                let $data = extractData($(this));
                if (!$data.error) {
                    $routinesData[$i] = $data;
                    $i++;
                }
            });
            return $routinesData;
        }

        function processVideoInput($field) {
            let $url;
            try {
                $field.removeClass('red-border');
                $('.routines-fieldset iframe').remove();
                $('.video-message').remove();
                $url = new URL($field.val());
                let $arguments = $url.pathname.split('/');
                if ($arguments[1]) {
                    $.ajax({
                        url: '/admin/video-info?video-id=' + $arguments[1],
                        cache: false,
                        success: function (html) {
                            if (typeof html != 'undefined' && !html.error) {
                                let $videoData = JSON.parse(html);
                                $('input[name ="video_data"]').val(JSON.stringify($videoData));
                                $('.routines-fieldset').prepend($videoData.embed.html);
                                $('.routines-fieldset').find('iframe').attr('id','vimeo-player-iframe');
                                vimeoPlayer = new Vimeo.Player('vimeo-player-iframe');
                                let $lengthField = $('input[name ="length"]');
                                $lengthField.val($videoData.duration);
                            }
                        }
                    });
                }
            }
            catch (error) {
                $('<div class="video-message">Invalid url</div>').insertBefore($videoField);
                $videoField.addClass('red-border');
                console.error(error);
                // expected output: ReferenceError: nonExistentFunction is not defined
                // Note - error messages will vary depending on browser
            }
        }

        var toHHMMSS = (secs) => {
            var sec_num = parseInt(secs, 10)
            var hours   = Math.floor(sec_num / 3600)
            var minutes = Math.floor(sec_num / 60) % 60
            var seconds = sec_num % 60

            return [hours,minutes,seconds]
                .map(v => v < 10 ? "0" + v : v)
                .join(":")
        }
        var fromHHMMSS = (time) => {
            let $hmsArray = time.split(':');
            let hs = parseInt($hmsArray[0])*3600;
            let ms = parseInt($hmsArray[1])*60;
            let ss = parseInt($hmsArray[2]);
            return hs + ms + ss;
        }

        $videoField.on('input', function (e) {
            processVideoInput($(this));
        })
        $('.start-input').timepicker({
            timeFormat: 'HH:mm:ss',
            interval: 1,
            minTime: '1',
            maxTime: '23:59:59',
            defaultTime: '0',
            startTime: '00:00:00',
            dynamic: true,
            dropdown: true,
            scrollbar: false
        });
    });
})(jQuery, window, document);
