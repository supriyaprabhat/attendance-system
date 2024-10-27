@extends('layouts.master')

@section('title',__('index.award'))

@section('action',__('index.lists'))

@section('button')
    @can('create_award')
        <a href="{{ route('admin.awards.create')}}">
            <button class="btn btn-primary">
                <i class="link-icon" data-feather="plus"></i>{{ __('index.add_award') }}
            </button>
        </a>
    @endcan
@endsection

@section('main-content')
    <section class="content">
        @include('admin.section.flash_message')

        @include('admin.assetManagement.assetDetail.common.breadcrumb')

        <div class="search-box p-4  bg-white rounded mb-3 box-shadow pb-0">

            <form class="forms-sample" action="{{route('admin.awards.index')}}" method="get">

                <h5 class="mb-3">{{ __('index.award_lists') }}</h5>

{{--                <div class="row align-items-center">--}}
{{--                    <div class="col-xxl col-xl-4 col-md-6 mb-4">--}}
{{--                        <label for="" class="form-label">Name</label>--}}
{{--                        <input type="text" placeholder="Award name" id="asset" name="name" value="{{$filterParameters['name']}}" class="form-control">--}}
{{--                    </div>--}}

{{--                    <div class="col-xxl col-xl-4  col-md-6 mb-4">--}}
{{--                        <label for="" class="form-label">Type</label>--}}
{{--                        <select class="form-select form-select-lg" name="type" id="type">--}}
{{--                            <option value="" {{!isset($filterParameters['type']) ? 'selected': ''}} > All </option>--}}
{{--                            @foreach($assetType as $key => $value)--}}
{{--                                <option value="{{$value->id}}" {{ isset($filterParameters['type']) && $filterParameters['type'] == $value->id ? 'selected': '' }}>--}}
{{--                                    {{ucfirst($value->name)}}--}}
{{--                                </option>--}}
{{--                            @endforeach--}}
{{--                        </select>--}}
{{--                    </div>--}}

{{--                    <div class="col-xxl col-xl-4 col-md-6 mb-4">--}}
{{--                        <label for="" class="form-label">Working Status</label>--}}
{{--                        <select class="form-select form-select-lg" name="is_working" id="is_working">--}}
{{--                            <option value="" {{!isset($filterParameters['is_working']) ? 'selected': ''}} > All </option>--}}
{{--                            @foreach(\App\Models\Award::IS_WORKING as $value)--}}
{{--                                <option value="{{$value}}" {{ isset($filterParameters['is_working']) && $filterParameters['is_working'] == $value ? 'selected': '' }}>--}}
{{--                                    {{ucfirst($value)}}--}}
{{--                                </option>--}}
{{--                            @endforeach--}}
{{--                        </select>--}}
{{--                    </div>--}}

{{--                    <div class="col-xxl col-xl-4 col-md-6 mb-4">--}}
{{--                        <label for="" class="form-label">Availability Status</label>--}}
{{--                        <select class="form-select form-select-lg" name="is_available" id="is_available">--}}
{{--                            <option value="" {{!isset($filterParameters['is_available']) ? 'selected': ''}} >All</option>--}}
{{--                            <option value="1" {{isset($filterParameters['is_available']) && $filterParameters['is_available'] == 1 ? 'selected': ''}} >Yes Available</option>--}}
{{--                            <option value="0" {{isset($filterParameters['is_available']) && $filterParameters['is_available'] == 0 ? 'selected': ''}} >Not Available</option>--}}
{{--                        </select>--}}
{{--                    </div>--}}

{{--                    @if(\App\Helpers\AppHelper::ifDateInBsEnabled())--}}
{{--                        <div class="col-xxl col-xl-4 col-md-6 mb-4">--}}
{{--                            <label for="" class="form-label">Purchased From</label>--}}
{{--                            <input type="text"  id="nepali-datepicker-from"--}}
{{--                                   name="purchased_from"--}}
{{--                                   value="{{$filterParameters['purchased_from']}}"--}}
{{--                                   placeholder="mm/dd/yyyy"--}}
{{--                                   class="form-control purchasedFrom"/>--}}
{{--                        </div>--}}

{{--                        <div class="col-xxl col-xl-4 col-md-6 mb-4">--}}
{{--                            <label for="" class="form-label">Purchased To </label>--}}
{{--                            <input type="text" id="nepali-datepicker-to"--}}
{{--                                   name="purchased_to"--}}
{{--                                   value="{{$filterParameters['purchased_to']}}"--}}
{{--                                   placeholder="mm/dd/yyyy"--}}
{{--                                   class="form-control purchasedTo"/>--}}
{{--                        </div>--}}
{{--                    @else--}}
{{--                        <div class="col-xxl col-xl-4 col-md-6 mb-4">--}}
{{--                            <label for="" class="form-label">Purchased From</label>--}}
{{--                            <input type="date"  value="{{$filterParameters['purchased_from']}}" name="purchased_from" class="form-control">--}}
{{--                        </div>--}}

{{--                        <div class="col-xxl col-xl-4 col-md-6 mb-4">--}}
{{--                            <label for="" class="form-label">Purchased To</label>--}}
{{--                            <input type="date"  value="{{$filterParameters['purchased_to']}}" name="purchased_to" class="form-control">--}}
{{--                        </div>--}}
{{--                    @endif--}}

{{--                    <div class="col-lg-12 mb-3">--}}
{{--                        <div class="d-flex float-lg-end">--}}
{{--                            <button type="submit" class="btn btn-block btn-secondary me-2">Filter</button>--}}
{{--                            <a href="{{route('admin.awards.index')}}" class="btn btn-block btn-primary">Reset</a>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
            </form>
        </div>

        <div class="card support-main">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="dataTableExample" class="table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('index.employee') }}</th>
                            <th>{{ __('index.award') }}</th>
                            <th>{{ __('index.gift_item') }}</th>
                            <th>{{ __('index.awarded_date') }}</th>
                            @canany(['show_award','delete_award','update_award'])
                                <th class="text-center">{{ __('index.action') }}</th>
                            @endcanany
                        </tr>
                        </thead>
                        <tbody>
                            @forelse($awardLists as $key => $value)
                                <tr>
                                    <td>{{++$key}}</td>
                                    <td>{{ $value->employee?->name }}</td>
                                    <td>{{ $value->type?->title }}</td>
                                    <td>{{ $value->gift_item }}</td>
                                    <td>
{{--                                        @if($value->award_base == \App\Enum\AwardBaseEnum::yearly->value)--}}
{{--                                            {{ date('Y', strtotime($value->awarded_date)) }}--}}
{{--                                        @elseif($value->award_base == \App\Enum\AwardBaseEnum::monthly->value)--}}
{{--                                            {{ date('F Y', strtotime($value->awarded_date)) }}--}}
{{--                                        @else--}}
                                            {{ \App\Helpers\AppHelper::formatDateForView($value->awarded_date) }}
{{--                                        @endif--}}
                                    </td>
                                    <td class="text-center">
                                        <ul class="d-flex list-unstyled mb-0 justify-content-center">
                                            @can('update_award')
                                                <li class="me-2">
                                                    <a href="{{route('admin.awards.edit',$value->id)}}" title="{{ __('index.edit') }}">
                                                        <i class="link-icon" data-feather="edit"></i>
                                                    </a>
                                                </li>
                                            @endcan

                                            @can('show_award')
                                                <li class="me-2">
                                                    <a href="{{route('admin.awards.show',$value->id)}}" title="{{ __('index.show_detail') }}">
                                                        <i class="link-icon" data-feather="eye"></i>
                                                    </a>
                                                </li>
                                            @endcan

                                            @can('delete_award')
                                                <li>
                                                    <a class="delete"
                                                       data-title="{{$value->name}} Award Detail"
                                                       data-href="{{route('admin.awards.delete',$value->id)}}"
                                                       title="{{ __('index.delete') }}">
                                                        <i class="link-icon"  data-feather="delete"></i>
                                                    </a>
                                                </li>
                                            @endcan
                                          </ul>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100%">
                                        <p class="text-center"><b>{{ __('index.no_records_found') }}</b></p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="dataTables_paginate mt-3">
            {{$awardLists->appends($_GET)->links()}}
        </div>
    </section>

@endsection

@section('scripts')
    @include('admin.assetManagement.assetDetail.common.scripts')
@endsection

