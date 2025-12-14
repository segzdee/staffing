@extends('admin.layout')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Shift Statistics
            <small>Analytics & Insights</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ url('panel/admin') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ url('panel/admin/shifts') }}">Shifts</a></li>
            <li class="active">Statistics</li>
        </ol>
    </section>

    <section class="content">
        <!-- Overview Stats -->
        <div class="row">
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3>{{ number_format($stats['total_shifts']) }}</h3>
                        <p>Total Shifts</p>
                    </div>
                    <div class="icon"><i class="fa fa-calendar"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3>{{ number_format($stats['filled_shifts']) }}</h3>
                        <p>Filled Shifts</p>
                    </div>
                    <div class="icon"><i class="fa fa-check"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <h3>{{ number_format($stats['open_shifts']) }}</h3>
                        <p>Open Shifts</p>
                    </div>
                    <div class="icon"><i class="fa fa-folder-open"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3>{{ number_format($stats['cancelled_shifts']) }}</h3>
                        <p>Cancelled</p>
                    </div>
                    <div class="icon"><i class="fa fa-times"></i></div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-line-chart"></i> Performance Metrics</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-green">{{ $stats['fill_rate'] }}%</h5>
                                    <span class="description-text">FILL RATE</span>
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="description-block">
                                    <h5 class="description-header text-blue">{{ $stats['avg_fill_time'] }}h</h5>
                                    <span class="description-text">AVG FILL TIME</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calendar-check-o"></i> Recent Activity</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-4">
                                <div class="description-block">
                                    <h5 class="description-header">{{ number_format($stats['shifts_today']) }}</h5>
                                    <span class="description-text">TODAY</span>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="description-block">
                                    <h5 class="description-header">{{ number_format($stats['shifts_this_week']) }}</h5>
                                    <span class="description-text">THIS WEEK</span>
                                </div>
                            </div>
                            <div class="col-xs-4">
                                <div class="description-block">
                                    <h5 class="description-header">{{ number_format($stats['shifts_this_month']) }}</h5>
                                    <span class="description-text">THIS MONTH</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Industries -->
        <div class="row">
            <div class="col-md-6">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-industry"></i> Top Industries</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Industry</th>
                                    <th>Total Shifts</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topIndustries as $industry)
                                <tr>
                                    <td>{{ ucfirst($industry->industry) }}</td>
                                    <td>{{ number_format($industry->total) }}</td>
                                    <td>
                                        <span class="badge bg-blue">
                                            {{ round(($industry->total / $stats['total_shifts']) * 100, 1) }}%
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Businesses -->
            <div class="col-md-6">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-building"></i> Top Businesses</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Business</th>
                                    <th>Shifts Posted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topBusinesses as $business)
                                <tr>
                                    <td>{{ $business->name }}</td>
                                    <td>{{ number_format($business->total_shifts) }}</td>
                                    <td>
                                        <a href="{{ url('panel/admin/businesses/'.$business->id) }}" class="btn btn-xs btn-info">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <a href="{{ url('panel/admin/shifts') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Shifts
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
