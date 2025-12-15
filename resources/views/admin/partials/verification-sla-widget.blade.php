{{--
    Admin Dashboard Widget: Verification Queue SLA Status
    ADM-001: Bulk Verification Operations & SLA Tracking

    Include in admin dashboard with:
    @include('admin.partials.verification-sla-widget', ['slaStats' => $slaStats])

    Controller should provide:
    $slaStats = \App\Models\VerificationQueue::getSLAStatistics();
--}}

@php
    // Get stats if not passed
    $slaStats = $slaStats ?? \App\Models\VerificationQueue::getSLAStatistics();
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            <i class="fa fa-shield-alt"></i> Verification Queue
        </h3>
        <div class="box-tools pull-right">
            <a href="{{ route('admin.verification-queue.index') }}" class="btn btn-box-tool" title="View All">
                <i class="fa fa-external-link-alt"></i>
            </a>
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="box-body">
        <!-- SLA Compliance Gauge -->
        <div class="row">
            <div class="col-md-4 text-center">
                <div class="sla-gauge">
                    @php
                        $compliance = $slaStats['current_compliance_percentage'];
                        $gaugeColor = $compliance >= 90 ? '#00a65a' : ($compliance >= 70 ? '#f39c12' : '#dd4b39');
                    @endphp
                    <div class="gauge-circle" style="background: conic-gradient({{ $gaugeColor }} {{ $compliance }}%, #ddd {{ $compliance }}%);">
                        <div class="gauge-inner">
                            <span class="gauge-value">{{ $compliance }}%</span>
                            <span class="gauge-label">SLA Compliance</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Queue Breakdown -->
                <div class="row text-center">
                    <div class="col-xs-4">
                        <div class="description-block border-right">
                            <span class="description-percentage text-green">
                                <i class="fa fa-check"></i> {{ $slaStats['on_track'] }}
                            </span>
                            <span class="description-text">On Track</span>
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <div class="description-block border-right">
                            <span class="description-percentage text-yellow">
                                <i class="fa fa-exclamation-triangle"></i> {{ $slaStats['at_risk'] }}
                            </span>
                            <span class="description-text">At Risk</span>
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <div class="description-block">
                            <span class="description-percentage text-red">
                                <i class="fa fa-times-circle"></i> {{ $slaStats['breached'] }}
                            </span>
                            <span class="description-text">Breached</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mt-3">
                    <div class="col-xs-12 text-center">
                        @if($slaStats['breached'] > 0)
                            <a href="{{ route('admin.verification-queue.index', ['sla_status' => 'breached']) }}"
                               class="btn btn-danger btn-sm">
                                <i class="fa fa-exclamation-circle"></i>
                                Review {{ $slaStats['breached'] }} Breached
                            </a>
                        @elseif($slaStats['at_risk'] > 0)
                            <a href="{{ route('admin.verification-queue.index', ['sla_status' => 'at_risk']) }}"
                               class="btn btn-warning btn-sm">
                                <i class="fa fa-clock"></i>
                                Review {{ $slaStats['at_risk'] }} At Risk
                            </a>
                        @elseif($slaStats['total_pending'] > 0)
                            <a href="{{ route('admin.verification-queue.index') }}"
                               class="btn btn-info btn-sm">
                                <i class="fa fa-tasks"></i>
                                Process {{ $slaStats['total_pending'] }} Pending
                            </a>
                        @else
                            <span class="text-success">
                                <i class="fa fa-check-circle"></i> Queue Clear
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Historical Comparison -->
        <div class="row mt-3">
            <div class="col-xs-12">
                <small class="text-muted">
                    30-day historical compliance: {{ $slaStats['historical_compliance_percentage'] }}%
                </small>
            </div>
        </div>
    </div>

    <div class="box-footer">
        <div class="row">
            <div class="col-sm-6">
                <span class="text-muted">
                    Total Pending: <strong>{{ $slaStats['total_pending'] }}</strong>
                </span>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.verification-queue.index') }}" class="text-primary">
                    View Queue <i class="fa fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.sla-gauge {
    display: inline-block;
}
.gauge-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}
.gauge-inner {
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.gauge-value {
    font-size: 18px;
    font-weight: bold;
    line-height: 1;
}
.gauge-label {
    font-size: 10px;
    color: #777;
}
.description-percentage {
    font-size: 24px;
    font-weight: bold;
    display: block;
}
.description-text {
    font-size: 12px;
    color: #777;
}
</style>
