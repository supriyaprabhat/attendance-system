
@extends('layouts.master')

@section('title',__('index.leave_requests'))

@section('action',__('index.lists'))

@section('button')
    @can('create_leave_request')
        <a href="{{ route('admin.leave-request.add')}}">
            <button class="btn btn-primary">
                <i class="link-icon" data-feather="plus"></i>{{ __('index.create_leave_request') }}
            </button>
        </a>
    @endcan
@endsection

@section('main-content')
    <?php
    if(\App\Helpers\AppHelper::ifDateInBsEnabled()){
        $filterData['min_year'] = '2076';
        $filterData['max_year'] = '2089';
        $filterData['month'] = 'np';
    }else{
        $filterData['min_year'] = '2020';
        $filterData['max_year'] = '2033';
        $filterData['month'] = 'en';
    }
    ?>

    <section class="content">

        @include('admin.section.flash_message')

        @include('admin.leaveRequest.common.breadcrumb')
        <div class="row">
            <div class="col-lg-2">
                @include('admin.leaveRequest.common.leave_menu')
            </div>
            <div class="col-lg-10">
                <div class="search-box p-4 bg-white rounded mb-3 box-shadow">
                    <form class="forms-sample" action="{{route('admin.leave-request.index')}}" method="get">

                        <h5>{{ __('index.leave_request_filter') }}</h5>

                        <div class="row align-items-center">

                            <div class="col-xxl col-xl-4 col-md-6 mt-3">
                                <input type="text" placeholder="{{ __('index.requested_by') }}" id="requestedBy" name="requested_by" value="{{$filterParameters['requested_by']}}" class="form-control">
                            </div>

                            <div class="col-xxl col-xl-4 col-md-6 mt-3">
                                <select class="form-select form-select-lg" name="leave_type" id="leaveType">
                                    <option value="" {{!isset($filterParameters['leave_type']) ? 'selected': ''}}   >{{ __('index.all_leave_type') }}</option>
                                    @foreach($leaveTypes as $key => $value)
                                        <option value="{{$value->id}}" {{ (isset($filterParameters['leave_type']) && $value->id == $filterParameters['leave_type'] ) ?'selected':'' }} > {{($value->name)}} </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xxl col-xl-4 col-md-6 mt-3">
                                <input type="number" min="{{ $filterData['min_year']}}"
                                       max="{{ $filterData['max_year']}}" step="1"
                                       placeholder="{{ __('index.leave_requested_year') }} : {{$filterData['min_year']}}"
                                       id="year"
                                       name="year" value="{{$filterParameters['year']}}"
                                       class="form-control">
                            </div>

                            <div class="col-xxl col-xl-4 col-md-6 mt-3">
                                <select class="form-select form-select-lg" name="month" id="month">
                                    <option value="" {{!isset($filterParameters['month']) ? 'selected': ''}} >{{ __('index.all_month') }}</option>
                                    @foreach($months as $key => $value)
                                        <option value="{{$key}}" {{ (isset($filterParameters['month']) && $key == $filterParameters['month'] ) ?'selected':'' }} >
                                            {{$value[$filterData['month']]}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xxl col-xl-4 col-md-6 mt-3">
                                <select class="form-select form-select-lg" name="status" id="status">
                                    <option value="" {{!isset($filterParameters['status']) ? 'selected': ''}}   >{{ __('index.all_status') }}</option>
                                    @foreach(\App\Models\LeaveRequestMaster::STATUS as  $value)
                                        <option value="{{$value}}" {{ (isset($filterParameters['status']) && $value == $filterParameters['status'] ) ?'selected':'' }} > {{ucfirst($value)}} </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xxl col-xl-4 mt-3">
                                <div class="d-flex float-end">
                                    <button type="submit" class="btn btn-block btn-secondary me-2">{{ __('index.filter') }}</button>
                                    <a class="btn btn-block btn-primary" href="{{route('admin.leave-request.index')}}">{{ __('index.reset') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <div class="card-body">

                        <div class="table-responsive">
                            <table id="dataTableExample" class="table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('index.type') }}</th>
                                    <th>{{ __('index.from') }}</th>
                                    <th>{{ __('index.to') }}</th>
                                    <th>{{ __('index.requested_date') }}</th>
                                    <th>{{ __('index.requested_by') }}</th>
                                    <th class="text-center">{{ __('index.requested_days') }}</th>
                                    @can('show_leave_request_detail')
                                        <th class="text-center">{{ __('index.reason') }}</th>
                                    @endcan
                                    @can('update_leave_request')
                                        <th class="text-center">{{ __('index.status') }}</th>
                                    @endcan
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <?php
                                    $color = [
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'pending' => 'secondary',
                                        'cancelled' => 'danger'
                                    ];

                                    ?>
                                @forelse($leaveDetails as $key => $value)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $value->leaveType ? ucfirst($value->leaveType->name) : ''}}</td>
                                        <td>{{\App\Helpers\AppHelper::convertLeaveDateFormat($value->leave_from)}}</td>
                                        <td>{{\App\Helpers\AppHelper::convertLeaveDateFormat($value->leave_to)}}</td>
                                        <td>{{\App\Helpers\AppHelper::formatDateForView($value->leave_requested_date)}}</td>
                                        <td>{{$value->leaveRequestedBy ? ucfirst($value->leaveRequestedBy->name) : 'N/A'}} </td>
                                        <td class="text-center">{{($value->no_of_days )}}</td>

                                        @can('show_leave_request_detail')
                                            <td class="text-center">
                                                <a href="#" class="showLeaveReason" data-href="{{ route('admin.leave-request.show', $value->id) }}" title="{{ __('index.show_leave_reason') }}">
                                                    <i class="link-icon" data-feather="eye"></i>
                                                </a>

                                            </td>
                                        @endcan

                                        @can('update_leave_request')
                                            <td class="text-center">
                                                <a href=""
                                                   id="leaveRequestUpdate"
                                                   data-href="{{route('admin.leave-request.update-status',$value->id)}}"
                                                   data-status="{{$value->status}}"
                                                   data-remark="{{$value->admin_remark}}"
                                                >
                                                    <button class="btn btn-{{ $color[$value->status] }} btn-xs">
                                                        {{ucfirst($value->status)}}
                                                    </button>
                                                </a>
                                            </td>
                                    @endcan
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
            </div>
        </div>

    </section>
    <div class="dataTables_paginate mt-3">
        {{$leaveDetails->appends($_GET)->links()}}
    </div>

    @include('admin.leaveRequest.show')
    @include('admin.leaveRequest.common.form-model')
@endsection

@section('scripts')
    @include('admin.leaveRequest.common.scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.showLeaveReason').forEach(function(element) {
                element.addEventListener('click', function(event) {
                    event.preventDefault();
                    const url = this.getAttribute('data-href');

                    fetch(url)
                        .then(response => response.json())
                        .then(data => {

                            if (data && data.data) {
                                const leaveRequest = data.data;
                                document.getElementById('referredBy').innerText = leaveRequest.name || 'N/A';
                                document.getElementById('description').innerText = leaveRequest.reasons || 'N/A';
                                document.getElementById('adminRemark').innerText = leaveRequest.admin_remark || 'N/A';

                                const modalElement = document.getElementById('addslider');

                                if (modalElement) {
                                    const modal = new bootstrap.Modal(modalElement);
                                    modal.show();
                                } else {
                                    console.error('Modal element not found');
                                }
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            });
        });


    </script>
@endsection





