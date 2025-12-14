@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <h1 class="page-header">
        <i class="bi bi-graph-up"></i> Matching Algorithm Analytics
    </h1>

    <!-- Date Range Filter -->
    <div style="margin-bottom: 20px;">
        <form method="GET" class="form-inline">
            <label style="margin-right: 10px;">Date Range:</label>
            <select name="range" class="form-control" onchange="this.form.submit()">
                <option value="7" {{ $dateRange == '7' ? 'selected' : '' }}>Last 7 Days</option>
                <option value="30" {{ $dateRange == '30' ? 'selected' : '' }}>Last 30 Days</option>
                <option value="90" {{ $dateRange == '90' ? 'selected' : '' }}>Last 90 Days</option>
                <option value="365" {{ $dateRange == '365' ? 'selected' : '' }}>Last Year</option>
            </select>
        </form>
    </div>

    <!-- Key Metrics Cards -->
    <div class="row">
        <!-- Fill Rate -->
        <div class="col-lg-3 col-md-6">
            <div class="panel panel-{{ $fillRate >= 80 ? 'green' : ($fillRate >= 60 ? 'yellow' : 'red') }}">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="bi bi-check-circle fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge">{{ $fillRate }}%</div>
                            <div>Fill Rate</div>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <span class="pull-left">{{ $filledShifts }} / {{ $totalShifts }} shifts filled</span>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>

        <!-- Avg Time to Fill -->
        <div class="col-lg-3 col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="bi bi-clock fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge">{{ $avgTimeToFillHours }}h</div>
                            <div>Avg Time to Fill</div>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <span class="pull-left">Time from post to filled</span>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>

        <!-- Avg Match Score -->
        <div class="col-lg-3 col-md-6">
            <div class="panel panel-{{ $avgMatchScore >= 80 ? 'green' : ($avgMatchScore >= 60 ? 'yellow' : 'red') }}">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="bi bi-stars fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge">{{ $avgMatchScore }}</div>
                            <div>Avg Match Score</div>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <span class="pull-left">{{ $highMatchRate }}% high matches (80+)</span>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>

        <!-- Application Acceptance Rate -->
        <div class="col-lg-3 col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="bi bi-hand-thumbs-up fa-5x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div class="huge">{{ $applicationAcceptanceRate }}%</div>
                            <div>Acceptance Rate</div>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <span class="pull-left">{{ $totalApplications }} applications received</span>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Daily Fill Rates Chart -->
        <div class="col-lg-8">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="bi bi-graph-up"></i> Daily Fill Rate Trends
                </div>
                <div class="panel-body">
                    <canvas id="fillRateChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Match Score Distribution -->
        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="bi bi-pie-chart"></i> Match Score Distribution
                </div>
                <div class="panel-body">
                    <canvas id="matchScoreChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Industry Performance -->
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="bi bi-briefcase"></i> Performance by Industry
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Industry</th>
                                    <th>Total Shifts</th>
                                    <th>Filled</th>
                                    <th>Fill Rate</th>
                                    <th>Avg Time to Fill</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($industryPerformance as $industry => $metrics)
                                    <tr>
                                        <td><strong>{{ ucfirst($industry) }}</strong></td>
                                        <td>{{ $metrics['total'] }}</td>
                                        <td>{{ $metrics['filled'] }}</td>
                                        <td>
                                            <span class="label label-{{ $metrics['fill_rate'] >= 80 ? 'success' : ($metrics['fill_rate'] >= 60 ? 'warning' : 'danger') }}">
                                                {{ $metrics['fill_rate'] }}%
                                            </span>
                                        </td>
                                        <td>{{ $metrics['avg_time_hours'] }}h</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Availability Broadcast & Invitations -->
    <div class="row">
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="bi bi-broadcast"></i> Availability Broadcast Statistics
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tr>
                            <td><strong>Total Broadcasts:</strong></td>
                            <td>{{ $totalBroadcasts }}</td>
                        </tr>
                        <tr>
                            <td><strong>Avg Responses per Broadcast:</strong></td>
                            <td>{{ round($avgResponsesPerBroadcast, 1) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Responses:</strong></td>
                            <td>{{ $broadcastEffectiveness['total_responses'] }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="bi bi-envelope"></i> Invitation Statistics
                </div>
                <div class="panel-body">
                    <table class="table">
                        <tr>
                            <td><strong>Total Invitations Sent:</strong></td>
                            <td>{{ $totalInvitations }}</td>
                        </tr>
                        <tr>
                            <td><strong>Accepted:</strong></td>
                            <td>{{ $totalInvitations > 0 ? round(($invitationAcceptanceRate / 100) * $totalInvitations) : 0 }}</td>
                        </tr>
                        <tr>
                            <td><strong>Acceptance Rate:</strong></td>
                            <td>
                                <span class="label label-{{ $invitationAcceptanceRate >= 50 ? 'success' : ($invitationAcceptanceRate >= 30 ? 'warning' : 'danger') }}">
                                    {{ $invitationAcceptanceRate }}%
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Shifts by Urgency -->
    <div class="row">
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="bi bi-exclamation-triangle"></i> Shifts by Urgency Level
                </div>
                <div class="panel-body">
                    <canvas id="urgencyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Performing Workers -->
        <div class="col-lg-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="bi bi-trophy"></i> Top Performing Workers
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Worker</th>
                                    <th>Completed Shifts</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topWorkers as $index => $worker)
                                    <tr>
                                        <td><strong>{{ $index + 1 }}</strong></td>
                                        <td>{{ $worker->name }}</td>
                                        <td>{{ $worker->completed_shifts }}</td>
                                        <td>
                                            @if($worker->rating_as_worker)
                                                <i class="bi bi-star-fill" style="color: #ffc107;"></i>
                                                {{ number_format($worker->rating_as_worker, 1) }}
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Insights and Recommendations -->
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <i class="bi bi-lightbulb"></i> Algorithm Insights & Recommendations
                </div>
                <div class="panel-body">
                    @if($fillRate < 70)
                        <div class="alert alert-warning">
                            <strong><i class="bi bi-exclamation-triangle"></i> Low Fill Rate:</strong>
                            The current fill rate of {{ $fillRate }}% is below target. Consider:
                            <ul>
                                <li>Increasing worker engagement campaigns</li>
                                <li>Improving shift rates for harder-to-fill positions</li>
                                <li>Adjusting matching algorithm weights</li>
                            </ul>
                        </div>
                    @endif

                    @if($avgMatchScore < 70)
                        <div class="alert alert-warning">
                            <strong><i class="bi bi-exclamation-triangle"></i> Low Match Quality:</strong>
                            Average match score of {{ $avgMatchScore }} indicates suboptimal matching. Consider:
                            <ul>
                                <li>Encouraging workers to complete their profiles</li>
                                <li>Adjusting skill and location weight factors</li>
                                <li>Expanding worker recruitment in underserved industries</li>
                            </ul>
                        </div>
                    @endif

                    @if($avgTimeToFillHours > 24)
                        <div class="alert alert-info">
                            <strong><i class="bi bi-info-circle"></i> Slow Fill Times:</strong>
                            Average time to fill ({{ $avgTimeToFillHours }}h) exceeds 24 hours. Recommendations:
                            <ul>
                                <li>Implement instant notifications for high-match workers</li>
                                <li>Encourage businesses to offer competitive rates</li>
                                <li>Promote availability broadcasting feature</li>
                            </ul>
                        </div>
                    @endif

                    @if($fillRate >= 80 && $avgMatchScore >= 75)
                        <div class="alert alert-success">
                            <strong><i class="bi bi-check-circle"></i> Excellent Performance!</strong>
                            The matching algorithm is performing well with a {{ $fillRate }}% fill rate and {{ $avgMatchScore }} average match score.
                            Continue monitoring trends and gather worker feedback for further optimization.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Daily Fill Rate Chart
const fillRateCtx = document.getElementById('fillRateChart').getContext('2d');
new Chart(fillRateCtx, {
    type: 'line',
    data: {
        labels: [
            @foreach($dailyFillRates as $day)
                '{{ \Carbon\Carbon::parse($day["date"])->format("M d") }}',
            @endforeach
        ],
        datasets: [{
            label: 'Fill Rate %',
            data: [
                @foreach($dailyFillRates as $day)
                    {{ $day['rate'] }},
                @endforeach
            ],
            borderColor: 'rgb(102, 126, 234)',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        }
    }
});

// Match Score Distribution Chart
const matchScoreCtx = document.getElementById('matchScoreChart').getContext('2d');
new Chart(matchScoreCtx, {
    type: 'doughnut',
    data: {
        labels: ['90-100%', '80-89%', '70-79%', '60-69%', '0-59%'],
        datasets: [{
            data: [
                {{ $matchScoreDistribution['90-100'] }},
                {{ $matchScoreDistribution['80-89'] }},
                {{ $matchScoreDistribution['70-79'] }},
                {{ $matchScoreDistribution['60-69'] }},
                {{ $matchScoreDistribution['0-59'] }}
            ],
            backgroundColor: [
                '#28a745',
                '#5cb85c',
                '#ffc107',
                '#fd7e14',
                '#dc3545'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Urgency Chart
const urgencyCtx = document.getElementById('urgencyChart').getContext('2d');
new Chart(urgencyCtx, {
    type: 'bar',
    data: {
        labels: ['Normal', 'Urgent', 'Critical'],
        datasets: [{
            label: 'Number of Shifts',
            data: [
                {{ $shiftsByUrgency['normal'] ?? 0 }},
                {{ $shiftsByUrgency['urgent'] ?? 0 }},
                {{ $shiftsByUrgency['critical'] ?? 0 }}
            ],
            backgroundColor: [
                '#17a2b8',
                '#ffc107',
                '#dc3545'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
@endsection
