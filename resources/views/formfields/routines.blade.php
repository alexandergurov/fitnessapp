<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
<script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script><div class="routines-fieldset">
    <input type="button" class="fill-from-chapters" value="Use chapters from vimeo">
    <div class="routine-row new-routine">
        <input type="text" class="length-input" placeholder="sec" size="4">
        <span>seconds of</span>
        <select class="routine-select" name="{{ $row->field }}">
            <option value="">--</option>
            @if(isset($routineOptions))
                @foreach($routineOptions as $key => $routine)
                    <option value="{{ $routine->id }}">{{ $routine->title }}({{ $routine->identifier }})</option>
                @endforeach
            @endif
        </select>
        <span>starts at</span>
        <input type="time" step='1' class="start-input" min="00:00:00" max="23:59:59">
        <input type="button" class="pull-time" value="Get time from player">
        <input type="button" id="routines-add" value="Add Routine">
    </div>
</div>
<input type="hidden"
       class="form-control"
       name="{{ $row->field }}"
       data-name="{{ $row->display_name }}"
       id="routines-data"
       @if($row->required == 1) required @endif
       step="any"
       placeholder="{{ isset($options->placeholder)? old($row->field, $options->placeholder): $row->display_name }}"
       value="@if(isset($dataTypeContent->{$row->field})){{ old($row->field, $dataTypeContent->{$row->field}) }}@else{{old($row->field)}}@endif">

<script src="/js/routines.js"></script>
<script src="https://player.vimeo.com/api/player.js"></script>
