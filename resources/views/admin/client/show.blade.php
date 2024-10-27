@extends('layouts.master')

@section('title', __('index.client_invoices'))

@section('action', __('index.client_invoices'))

@section('button')
    <a href="{{ route('admin.clients.index') }}">
        <button class="btn btn-sm btn-primary">
            <i class="link-icon" data-feather="arrow-left"></i> {{ __('index.back') }}
        </button>
    </a>
@endsection

@section('main-content')

    <section class="content">

        @include('admin.section.flash_message')

        @include('admin.client.common.breadcrumb')

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __('index.client_detail') }}</h5>
                    </div>
                    <div class="card-body pb-0">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="row">
                                    <div class="col-lg col-md-4 mb-4">
                                        <div class="uploaded-image">
                                            <a href="#">
                                                <img class="w-100" style=""
                                                     src="{{ asset(\App\Models\Client::UPLOAD_PATH . $clientDetail->avatar) }}"
                                                     alt="avatar">
                                            </a>
                                        </div>
                                    </div>

                                    <div class="col-lg col-md-4 mb-4">
                                        <h6 class="mb-1">{{ __('index.name') }}</h6>
                                        <p>{{ $clientDetail->name }}</p>
                                    </div>

                                    <div class="col-lg col-md-4 mb-4">
                                        <h6 class="mb-1">{{ __('index.phone') }}</h6>
                                        <p>{{ $clientDetail->contact_no }}</p>
                                    </div>

                                    <div class="col-lg col-md-4 mb-4">
                                        <h6 class="mb-1">{{ __('index.email') }}</h6>
                                        <p>{{ $clientDetail->email }}</p>
                                    </div>

                                    <div class="col-lg col-md-4 mb-4">
                                        <h6 class="mb-1">{{ __('index.address') }}</h6>
                                        <p>{{ $clientDetail->address ?? 'N/A' }}</p>
                                    </div>

                                    <div class="col-lg col-md-4 mb-4">
                                        <h6 class="mb-1">{{ __('index.country') }}</h6>
                                        <p>{{ $clientDetail->country ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ __('index.client_project_lists') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="dataTableExample" class="table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('index.name') }}</th>
                                    <th>{{ __('index.date_start') }}</th>
                                    <th>{{ __('index.deadline') }}</th>
                                    <th>{{ __('index.amount') }} (Rs.)</th>
                                    <th>{{ __('index.status') }}</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                               @php
                                $ProjectStatus = [
                                    'in_progress' => 'primary',
                                    'not_started' => 'primary',
                                    'on_hold' => 'info',
                                    'cancelled' => 'danger',
                                    'completed' => 'success',
                                ]
                                @endphp

                                @forelse($clientDetail->projects as $key => $value)
                                    <tr>
                                        <td>{{ ++$key }}</td>
                                        <td>
                                            <a href="{{ route('admin.projects.show', $value->id) }}">{{ ucfirst($value->name) }}</a><br>
                                            <small>{{ __('index.all_tasks') }}: {{ $value->tasks->count() }}</small><br>
                                            <small>{{ __('index.completed_tasks') }}: {{ $value->completedTask->count() }}</small>
                                        </td>
                                        <td>{{ \App\Helpers\AppHelper::formatDateForView($value->start_date) }}</td>
                                        <td>{{ \App\Helpers\AppHelper::formatDateForView($value->deadline) }}</td>
                                        <td>{{ number_format($value->cost) }}</td>
                                        <td>
                                            <span class="btn btn-{{ $ProjectStatus[$value->status] }} btn-xs">{{ \App\Helpers\PMHelper::STATUS[$value->status] }}</span>
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
            </div>
        </div>

    </section>
@endsection

@section('scripts')
    @include('admin.client.common.scripts')
@endsection
