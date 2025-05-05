@extends('voyager::master')

@section('page_title', __('voyager::generic.viewing').' '.$dataType->getTranslatedAttribute('display_name_plural'))

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            Workout Leaderboard
        </h1>
    </div>
@stop

@section('content')
    <div class="page-content workout-leaderboard browse container-fluid">
        @if ($csvUrl)
            <a href="{{$csvUrl}}">Get as CSV</a>
        @endif
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table id="dataTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        @foreach($rows as $row)
                                        <th>
                                            @if ($row->type != 'custom')
                                                <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                                    {{ $row->getTranslatedAttribute('display_name') }}
                                                    @if ($row->isCurrentSortField($orderBy))
                                                        @if ($sortOrder == 'asc')
                                                            <i class="voyager-angle-up pull-right"></i>
                                                        @else
                                                            <i class="voyager-angle-down pull-right"></i>
                                                        @endif
                                                    @endif
                                                </a>
                                            @else
                                                <a href="{{ $row->sortByUrl }}">
                                                    {{$row->label}}
                                                    @if ($row->isCurrentSortField)
                                                        @if ($sortOrder == 'asc')
                                                            <i class="voyager-angle-up pull-right"></i>
                                                        @else
                                                            <i class="voyager-angle-down pull-right"></i>
                                                        @endif
                                                    @endif
                                                </a>
                                            @endif
                                        </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dataset as $data)
                                    <tr>
                                        @foreach($rows as $row)
                                            <td>
                                                @if ($row->field == 'name')
                                                    <a href="{{$data->url}}">{{$data->name}}</a>
                                                @else
                                                    <span>{{ $data->{$row->field} }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
@if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
    <link rel="stylesheet" href="{{ voyager_asset('lib/css/responsive.dataTables.min.css') }}">
@endif
@stop

@section('javascript')
    <!-- DataTables -->
    @if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
        <script src="{{ voyager_asset('lib/js/dataTables.responsive.min.js') }}"></script>
    @endif
@stop
