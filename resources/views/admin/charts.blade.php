<script type="text/javascript">
(function($) {
"use strict";

var IndexToMonth = [
  "@lang('months.01')",
  "@lang('months.02')",
  "@lang('months.03')",
  "@lang('months.04')",
  "@lang('months.05')",
  "@lang('months.06')",
  "@lang('months.07')",
  "@lang('months.08')",
  "@lang('months.09')",
  "@lang('months.10')",
  "@lang('months.11')",
  "@lang('months.12')"
];

//** Charts
new Morris.Area({
  // ID of the element in which to draw the chart.
  element: 'chart1',
  // Chart data records -- each entry in this array corresponds to a point on
  // the chart.
  data: [
    <?php
    for ( $i=0; $i < 30; ++$i) {

    $date = date('Y-m-d', strtotime('today - '.$i.' days'));
    // OvertimeStaff metric: Daily shifts created
    $_subscriptions = \App\Models\Shift::whereDate('created_at', '=', $date)->count();
    ?>

    { days: '<?php echo $date; ?>', value: <?php echo $_subscriptions ?> },

    <?php } ?>
  ],
  // The name of the data record attribute that contains x-values.
  xkey: 'days',
  // A list of names of data record attributes that contain y-values.
  ykeys: ['value'],
  // Labels for the ykeys -- will be displayed when you hover over the
  // chart.
  labels: ['{{ trans("admin.shifts_created") }}'],
  pointFillColors: ['#03b0da'],
  lineColors: ['#ddd'],
  hideHover: 'auto',
  gridIntegers: true,
  resize: true,
  xLabelFormat: function (x) {
                  var month = IndexToMonth[ x.getMonth() ];
                  var year = x.getFullYear();
                  var day = x.getDate();
                  return  day +' ' + month;
                  //return  year + ' '+ day +' ' + month;
              },
          dateFormat: function (x) {
                  var month = IndexToMonth[ new Date(x).getMonth() ];
                  var year = new Date(x).getFullYear();
                  var day = new Date(x).getDate();
                  return day +' ' + month;
                  //return year + ' '+ day +' ' + month;
              },

});// <------------ MORRIS

/* jQueryKnob */
  $(".knob").knob();

  //jvectormap data
  var visitorsData = {
  <?php

  $users_countries = \App\Models\User::where('countries_id', '<>', '')->groupBy('countries_id')->get();
  foreach ( $users_countries as $key ) {
    $total = Countries::find($key->countries_id);
  ?>
  "{{ $key->country()->country_code }}": {{ $total->users()->count() }}, <?php } ?>
  };

  //World map by jvectormap
  $('#world-map').vectorMap({
    map: 'world_mill_en',
    backgroundColor: "transparent",
    regionStyle: {
      initial: {
        fill: '#e4e4e4',
        "fill-opacity": 1,
        stroke: 'none',
        "stroke-width": 0,
        "stroke-opacity": 1
      }
    },
    series: {
      regions: [{
          values: visitorsData,
          scale: ["#92c1dc", "#00a65a"],
          normalizeFunction: 'polynomial'
        }]
    },
    onRegionLabelShow: function (e, el, code) {
      if (typeof visitorsData[code] != "undefined")
        el.html(el.html() + ': ' + visitorsData[code] + ' {{ trans("admin.registered_members") }}');
    }
  });

  @php
  $month = date('m');
  $year = date('Y');
  $daysMonth = Helper::daysInMonth($month, $year);
  $dateFormat = "$year-$month-";

  $monthFormat  = trans("months.$month");
  $currencySymbol = $settings->currency_symbol;

  for ($i=1; $i <= $daysMonth; ++$i) {

    $date = date('Y-m-d', strtotime($dateFormat.$i));
    // OvertimeStaff metric: Daily platform revenue from shift payments
    $_subscriptions = \App\Models\ShiftPayment::whereDate('created_at', '=', $date)->sum('platform_fee');

    $monthsData[] =  "'$monthFormat $i'";


    $_earningNetUser = $_subscriptions;

    $earningNetUserSum[] = $_earningNetUser;

  }

  $label = implode(',', $monthsData);
  $data = implode(',', $earningNetUserSum);
  @endphp

  function decimalFormat(nStr)
  {
    @if ($settings->decimal_format == 'dot')
     var $decimalDot = '.';
     var $decimalComma = ',';
     @else
     var $decimalDot = ',';
     var $decimalComma = '.';
     @endif

     @if ($settings->currency_position == 'left')
     var currency_symbol_left = '{{$settings->currency_symbol}}';
     var currency_symbol_right = '';
     @else
     var currency_symbol_right = '{{$settings->currency_symbol}}';
     var currency_symbol_left = '';
     @endif

      nStr += '';
      var x = nStr.split('.');
      var x1 = x[0];
      var x2 = x.length > 1 ? $decimalDot + x[1] : '';
      var rgx = /(\d+)(\d{3})/;
      while (rgx.test(x1)) {
          var x1 = x1.replace(rgx, '$1' + $decimalComma + '$2');
      }
      return currency_symbol_left + x1 + x2 + currency_symbol_right;
    }
    function transparentize(color, opacity) {
			var alpha = opacity === undefined ? 0.5 : 1 - opacity;
			return Color(color).alpha(alpha).rgbString();
		}

    var init = document.getElementById("salesChart").getContext('2d');

    const gradient = init.createLinearGradient(0, 0, 0, 300);
                      gradient.addColorStop(0, '#00a65a');
                      gradient.addColorStop(1, '#00a65a2b');

    const lineOptions = {
                          pointRadius: 4,
                          pointHoverRadius: 6,
                          hitRadius: 5,
                          pointHoverBorderWidth: 3
                      }

    var ChartArea = new Chart(init, {
        type: 'line',
        data: {
            labels: [{!!$label!!}],
            datasets: [{
                label: '{{trans('general.earnings')}} ',
                backgroundColor: gradient,
                borderColor: '#00a65a',
                data: [{!!$data!!}],
                borderWidth: 2,
                fill: true,
                lineTension: 0.4,
                ...lineOptions
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        min: 0, // it is for ignoring negative step.
                        display: true,
                         maxTicksLimit: 8,
                         padding: 10,
                        beginAtZero: true,
                        callback: function(value, index, values) {
                            return '@if($settings->currency_position == 'left'){{ $settings->currency_symbol }}@endif' + value + '@if($settings->currency_position == 'right'){{ $settings->currency_symbol }}@endif';
                        }
                    },
                    gridLines: {
                      display:true
                    }
          }],
            xAxes: [{
              gridLines: {
                display:false
              },
              display: true,
              ticks: {
                maxTicksLimit: 15,
                padding: 5,
              }
            }]
          },
            tooltips: {
              mode: 'index',
              intersect: false,
              reverse: true,
              backgroundColor: '#000',
              xPadding: 16,
              yPadding: 16,
              cornerRadius: 4,
              caretSize: 7,
                callbacks: {
                    label: function(t, d) {
                        var xLabel = d.datasets[t.datasetIndex].label;
                        var yLabel = t.yLabel == 0 ? decimalFormat(t.yLabel) : decimalFormat(t.yLabel.toFixed(2));
                        return xLabel + ': ' + yLabel;
                    }
                }
            },
            hover: {
              mode: 'index',
              intersect: false
            },
            legend: {
                display: false
            },
            responsive: true
        }
    });
})(jQuery);

</script>
