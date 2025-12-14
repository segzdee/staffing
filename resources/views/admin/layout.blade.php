<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="_token" content="{!! csrf_token() !!}"/>
    <title>{{ trans('admin.admin') }}</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    @include('includes.css_admin')

    <!-- IcoMoon CSS -->
    <link href="{{ asset('css/icomoon.css') }}" rel="stylesheet">

    <!-- AdminLTE Skins. Choose a skin from the css/skins
        folder instead of downloading all of them to reduce the load. -->
    <link href="{{ asset('admin/css/skins/skin-black.min.css')}}" rel="stylesheet" type="text/css" />

    <link rel="shortcut icon" href="{{ url('img', $settings->favicon) }}" />

    <link href='//fonts.googleapis.com/css?family=Montserrat:700' rel='stylesheet' type='text/css'>

    <link href="{{ asset('plugins/sweetalert/sweetalert.css')}}" rel="stylesheet" type="text/css" />

    @yield('css')

  <script type="text/javascript">
    var URL_BASE = "{{ url('/') }}";
    var url_file_upload = "{{route('upload.image', ['_token' => csrf_token()])}}";
    var delete_confirm = "{{trans('general.delete_confirm')}}";
    var yes_confirm = "{{trans('general.yes_confirm')}}";
    var yes = "{{trans('general.yes')}}";
    var cancel_confirm = "{{trans('general.cancel_confirm')}}";
    var timezone = "{{env('TIMEZONE')}}";
    var add_tag = "{{ trans("general.add_tag") }}";
    var choose_image = '{{trans('general.choose_image')}}';
    var formats_available = "{{ trans('general.formats_available_verification_form_w9', ['formats' => 'JPG, PNG, GIF, SVG']) }}";
    var cancel_payment = "{!!trans('general.confirm_cancel_payment')!!}";
    var yes_cancel_payment = "{{trans('general.yes_cancel_payment')}}";
    var approve_confirm_verification = "{{trans('admin.approve_confirm_verification')}}";
    var yes_confirm_approve_verification = "{{trans('admin.yes_confirm_approve_verification')}}";
    var yes_confirm_verification = "{{trans('admin.yes_confirm_verification')}}";
    var delete_confirm_verification = "{{trans('admin.delete_confirm_verification')}}";
    var login_as_user_warning = "{{trans('general.login_as_user_warning')}}";
    var yes_confirm_reject_post = "{{trans('general.yes_confirm_reject_post')}}";
    var delete_confirm_post = "{{trans('general.delete_confirm_post')}}";
    var yes_confirm_approve_post = "{{trans('general.yes_confirm_approve_post')}}";
    var approve_confirm_post = "{{trans('general.approve_confirm_post')}}";
    var yes_confirm_refund = "{{trans('general.refund')}}";
  </script>

  </head>
  <!--
  BODY TAG OPTIONS:
  =================
  Apply one or more of the following classes to get the
  desired effect
  |---------------------------------------------------------|
  | SKINS         | skin-blue                               |
  |               | skin-black                              |
  |               | skin-purple                             |
  |               | skin-yellow                             |
  |               | skin-red                                |
  |               | skin-green                              |
  |---------------------------------------------------------|
  |LAYOUT OPTIONS | fixed                                   |
  |               | layout-boxed                            |
  |               | layout-top-nav                          |
  |               | sidebar-collapse                        |
  |               | sidebar-mini                            |
  |---------------------------------------------------------|
  -->
  
  <body class="skin-black sidebar-mini">
    <div class="wrapper">

      <!-- Main Header -->
      <header class="main-header">

        <!-- Logo -->
        <a href="{{ url('panel/admin') }}" class="logo">
          <!-- mini logo for sidebar mini 50x50 pixels -->
          <span class="logo-mini"><b><i class="fas fa-bolt"></i></b></span>
          <!-- logo for regular state and mobile devices -->
          <span class="logo-lg"><b><i class="fas fa-bolt"></i> {{ trans('admin.admin') }}</b></span>
        </a>

        <!-- Header Navbar -->
        <nav class="navbar navbar-static-top" role="navigation">
          <!-- Sidebar toggle button-->
          <a href="#" class="sidebar-toggle border-none" data-toggle="offcanvas" role="button">
            <i class="fa fa-bars"></i>
          </a>
          <!-- Navbar Right Menu -->
          <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">

            	<li>
            		<a href="{{ url('/') }}" class="border-none"><i class="glyphicon glyphicon-home myicon-right"></i> {{ trans('admin.view_site') }}</a>
            	</li>

          <li class="dropdown notifications-menu d-none">
            <a href="#" class="dropdown-toggle border-none" data-toggle="dropdown" aria-expanded="false">
              <i class="fa fa-bell"></i>
              <span class="label label-danger">1</span>
            </a>
            <ul class="dropdown-menu">
              <li class="header">Notifications</li>
              <li>
                <!-- inner menu: contains the actual data -->
                <ul class="menu">
                  <li>
                    <a href="#">
                      <i class="fa fa-users text-aqua"></i> 5 new members joined today
                    </a>
                  </li>
                </ul>
              </li>
              <li class="footer"><a href="#">View all</a></li>
            </ul>
          </li>

              <!-- User Account Menu -->
              <li class="dropdown user user-menu">
                <!-- Menu Toggle Button -->
                <a href="#" class="dropdown-toggle border-none" data-toggle="dropdown">
                  <!-- The user image in the navbar-->
                  <img src="{{ Helper::getFile(config('path.avatar').auth()->user()->avatar) }}" class="user-image" alt="User Image" />
                  <!-- hidden-xs hides the username on small devices so only the image appears. -->
                  <span class="hidden-xs">{{ Auth::user()->name }}</span> <i class="fas fa-angle-down margin-left-5"></i>
                </a>
                <ul class="dropdown-menu">
                  <!-- The user image in the menu -->
                  <li class="user-header">
                    <img src="{{ Helper::getFile(config('path.avatar').auth()->user()->avatar) }}" class="img-circle" alt="User Image" />
                    <p>
                      <small>{{ Auth::user()->name }}</small>
                    </p>
                  </li>

                  <!-- Menu Footer-->
                  <li class="user-footer">
                    <div class="pull-left">
                      <a href="{{ url('settings/page') }}" class="btn btn-default btn-flat">{{ trans('general.edit_my_page') }}</a>
                    </div>
                    <div class="pull-right">
                      <a href="{{ url('logout') }}" class="btn btn-default btn-flat">{{ trans('users.logout') }}</a>
                    </div>
                  </li>
                </ul>
              </li>

            </ul>
          </div>
        </nav>
      </header>
      <!-- Left side column. contains the logo and sidebar -->
      <aside class="main-sidebar">

        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">

          <!-- Sidebar user panel (optional) -->
          <div class="user-panel">
            <div class="pull-left image">
              <img src="{{ Helper::getFile(config('path.avatar').auth()->user()->avatar) }}" class="img-circle" alt="User Image" />
            </div>
            <div class="pull-left info">
              <p class="text-overflow">{{ Auth::user()->name }}</p>
              <small class="btn-block text-overflow"><a href="javascript:void(0);"><i class="fa fa-circle text-success"></i> {{ trans('general.online') }}</a></small>
            </div>
          </div>


          <!-- Sidebar Menu -->
          <ul class="sidebar-menu">

            <li class="header">{{ trans('admin.main_menu') }}</li>

            @if (auth()->user()->hasPermission('dashboard'))
              <!-- Links -->
              <li @if (Request::is('panel/admin')) class="active" @endif>
              	<a href="{{ url('panel/admin') }}"><i class="iconmoon icon-Speedometter myicon-right"></i> <span>{{ trans('admin.dashboard') }}</span></a>
              </li><!-- ./Links -->
            @endif

            @if (auth()->user()->hasPermission('shifts'))
            <!-- Shift Management -->
            <li class="treeview @if( Request::is('panel/admin/shifts*') ) active @endif">
            	<a href="{{ url('panel/admin/shifts') }}"><i class="fa fa-calendar"></i> <span>Shift Management</span> <i class="fa fa-angle-left pull-right"></i></a>
           		<ul class="treeview-menu">
                <li @if(Request::is('panel/admin/shifts') && !Request::is('panel/admin/shifts/*')) class="active" @endif><a href="{{ url('panel/admin/shifts') }}"><i class="fas fa fa-angle-right"></i> All Shifts</a></li>
                <li @if(Request::is('panel/admin/shifts/flagged/review')) class="active" @endif><a href="{{ url('panel/admin/shifts/flagged/review') }}"><i class="fas fa fa-angle-right"></i> Flagged Shifts</a></li>
                <li @if(Request::is('panel/admin/shifts/statistics/view')) class="active" @endif><a href="{{ url('panel/admin/shifts/statistics/view') }}"><i class="fas fa fa-angle-right"></i> Statistics</a></li>
              </ul>
            </li><!-- ./Shift Management -->
            @endif

            @if (auth()->user()->hasPermission('payments'))
            <!-- Payment Management -->
            <li class="treeview @if( Request::is('panel/admin/payments*') && !Request::is('panel/admin/payments/settings*') ) active @endif">
            	<a href="{{ url('panel/admin/payments') }}"><i class="fa fa-money-bill-wave"></i> <span>Payment Management</span> <i class="fa fa-angle-left pull-right"></i></a>
           		<ul class="treeview-menu">
                <li @if(Request::is('panel/admin/payments') && !Request::is('panel/admin/payments/*')) class="active" @endif><a href="{{ url('panel/admin/payments') }}"><i class="fas fa fa-angle-right"></i> All Payments</a></li>
                <li @if(Request::is('panel/admin/payments/disputes')) class="active" @endif><a href="{{ url('panel/admin/payments/disputes') }}"><i class="fas fa fa-angle-right"></i> Disputes</a></li>
                <li @if(Request::is('panel/admin/payments/statistics')) class="active" @endif><a href="{{ url('panel/admin/payments/statistics') }}"><i class="fas fa fa-angle-right"></i> Statistics</a></li>
              </ul>
            </li><!-- ./Payment Management -->
            @endif

            @if (auth()->user()->hasPermission('workers'))
            <!-- Worker Management -->
            <li class="treeview @if( Request::is('panel/admin/workers*') ) active @endif">
            	<a href="{{ url('panel/admin/workers') }}"><i class="fa fa-users"></i> <span>Worker Management</span> <i class="fa fa-angle-left pull-right"></i></a>
           		<ul class="treeview-menu">
                <li @if(Request::is('panel/admin/workers') && !Request::is('panel/admin/workers/*')) class="active" @endif><a href="{{ url('panel/admin/workers') }}"><i class="fas fa fa-angle-right"></i> All Workers</a></li>
                <li @if(Request::is('panel/admin/workers/skills')) class="active" @endif><a href="{{ url('panel/admin/workers/skills') }}"><i class="fas fa fa-angle-right"></i> Skills Management</a></li>
                <li @if(Request::is('panel/admin/workers/certifications')) class="active" @endif><a href="{{ url('panel/admin/workers/certifications') }}"><i class="fas fa fa-angle-right"></i> Certifications</a></li>
              </ul>
            </li><!-- ./Worker Management -->
            @endif

            @if (auth()->user()->hasPermission('businesses'))
            <!-- Business Management -->
            <li class="treeview @if( Request::is('panel/admin/businesses*') ) active @endif">
            	<a href="{{ url('panel/admin/businesses') }}"><i class="fa fa-building"></i> <span>Business Management</span> <i class="fa fa-angle-left pull-right"></i></a>
           		<ul class="treeview-menu">
                <li @if(Request::is('panel/admin/businesses') && !Request::is('panel/admin/businesses/*')) class="active" @endif><a href="{{ url('panel/admin/businesses') }}"><i class="fas fa fa-angle-right"></i> All Businesses</a></li>
              </ul>
            </li><!-- ./Business Management -->
            @endif

            @if (auth()->user()->hasPermission('general'))
           <!-- Links -->
            <li class="treeview @if( Request::is('panel/admin/settings') || Request::is('panel/admin/settings/limits') ) active @endif">
            	<a href="{{ url('panel/admin/settings') }}"><i class="fa fa-cogs"></i> <span>{{ trans('admin.general_settings') }}</span> <i class="fa fa-angle-left pull-right"></i></a>

           		<ul class="treeview-menu">
                <li @if(Request::is('panel/admin/settings')) class="active" @endif><a href="{{ url('panel/admin/settings') }}"><i class="fas fa fa-angle-right"></i> {{ trans('admin.general') }}</a></li>
                <li @if(Request::is('panel/admin/settings/limits')) class="active" @endif><a href="{{ url('panel/admin/settings/limits') }}"><i class="fas fa fa-angle-right"></i> {{ trans('admin.limits') }}</a></li>
              </ul>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('announcements'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/announcements')) class="active" @endif>
            	<a href="{{ url('panel/admin/announcements') }}"><i class="fa fa-bullhorn"></i> <span>{{ trans('general.announcements') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('maintenance'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/maintenance/mode')) class="active" @endif>
            	<a href="{{ url('panel/admin/maintenance/mode') }}"><i class="fa fa-paint-roller"></i> <span>{{ trans('admin.maintenance_mode') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('billing'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/billing')) class="active" @endif>
            	<a href="{{ url('panel/admin/billing') }}"><i class="fa fa-file-invoice"></i> <span>{{ trans('general.billing_information') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('tax'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/tax-rates')) class="active" @endif>
            	<a href="{{ url('panel/admin/tax-rates') }}"><i class="fa fa-receipt"></i> <span>{{ trans('general.tax_rates') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('countries_states'))
            <!-- Links -->
            <li class="treeview @if( Request::is('panel/admin/countries') || Request::is('panel/admin/states') ) active @endif">
            	<a href="{{ url('panel/admin/countries') }}"><i class="fa fa-globe"></i> <span>{{ trans('general.countries_states') }}</span> <i class="fa fa-angle-left pull-right"></i></a>

           		<ul class="treeview-menu">
                <li @if(Request::is('panel/admin/countries')) class="active" @endif><a href="{{ url('panel/admin/countries') }}"><i class="fas fa fa-angle-right"></i> {{ trans('general.countries') }}</a></li>
                <li @if(Request::is('panel/admin/states')) class="active" @endif><a href="{{ url('panel/admin/states') }}"><i class="fas fa fa-angle-right"></i> {{ trans('general.states') }}</a></li>
              </ul>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('email'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/settings/email')) class="active" @endif>
            	<a href="{{ url('panel/admin/settings/email') }}"><i class="fa fa-at"></i> <span>{{ trans('admin.email_settings') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('live_streaming'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/live-streaming')) class="active" @endif>
            	<a href="{{ url('panel/admin/live-streaming') }}"><i class="fa fa-video"></i> <span>{{ trans('general.live_streaming') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('shop'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/shop')) class="active" @endif>
            	<a href="{{ url('panel/admin/shop') }}"><i class="fa fa-store"></i> <span>{{ trans('general.shop') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('products'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/products')) class="active" @endif>
            	<a href="{{ url('panel/admin/products') }}"><i class="fa fa-tag"></i> <span>{{ trans('general.products') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('sales'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/sales')) class="active" @endif>
            	<a href="{{ url('panel/admin/sales') }}"><i class="fa fa-shopping-cart"></i> <span>{{ trans('general.sales') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('storage'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/storage')) class="active" @endif>
            	<a href="{{ url('panel/admin/storage') }}"><i class="fa fa-database"></i> <span>{{ trans('admin.storage') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('theme'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/theme')) class="active" @endif>
            	<a href="{{ url('panel/admin/theme') }}"><i class="fa fa-paint-brush"></i> <span>{{ trans('admin.theme') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('custom_css_js'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/custom-css-js')) class="active" @endif>
            	<a href="{{ url('panel/admin/custom-css-js') }}"><i class="fa fa-code"></i> <span>{{ trans('general.custom_css_js') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('posts'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/posts')) class="active" @endif>
            	<a href="{{ url('panel/admin/posts') }}"><i class="fa fa-user-edit"></i>
                <span>Shifts</span>
                @if (\App\Models\Shift::where('status','open')->count() != 0)
                  <span class="label label-warning label-admin">{{\App\Models\Shift::where('status','open')->count()}}</span>
                @endif
              </a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('subscriptions'))
            <!-- Links -->
            <li class="@if( Request::is('panel/admin/subscriptions') ) active @endif">
            	<a href="{{ url('panel/admin/subscriptions') }}"><i class="fa fa-dollar-sign"></i> <span>{{ trans('admin.subscriptions') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('transactions'))
            <!-- Links -->
            <li class="@if( Request::is('panel/admin/transactions') ) active @endif">
            	<a href="{{ url('panel/admin/transactions') }}"><i class="fa fa-file-invoice-dollar"></i> <span>{{ trans('admin.transactions') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('deposits'))
            <!-- Links -->
           <li @if (Request::is('panel/admin/deposits')) class="active" @endif>
             <a href="{{ url('panel/admin/deposits') }}"><i class="fa fa-money-bill-alt"></i>
               <span>Payments</span>
               @if(\App\Models\ShiftPayment::where('status','in_escrow')->count() != 0)
                 <span class="label label-warning label-admin">{{\App\Models\ShiftPayment::where('status','in_escrow')->count()}}</span>
               @endif
             </a>
           </li><!-- ./Links -->
         @endif

         @if (auth()->user()->hasPermission('members'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/members')) class="active" @endif>
            	<a href="{{ url('panel/admin/members') }}"><i class="glyphicon glyphicon-user"></i> <span>{{ trans('admin.members') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('referrals'))
             <!-- Links -->
             <li @if(Request::is('panel/admin/referrals')) class="active" @endif>
             	<a href="{{ url('panel/admin/referrals') }}"><i class="fa fa-user-plus"></i> <span>{{ trans('general.referrals') }}</span></a>
             </li><!-- ./Links -->
           @endif

          @if (auth()->user()->hasPermission('languages'))
            <!-- Links -->
           <li @if(Request::is('panel/admin/languages')) class="active" @endif>
             <a href="{{ url('panel/admin/languages') }}"><i class="fa fa-language"></i> <span>{{ trans('admin.languages') }}</span></a>
           </li><!-- ./Links -->
         @endif

         @if (auth()->user()->hasPermission('categories'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/categories')) class="active" @endif>
            	<a href="{{ url('panel/admin/categories') }}"><i class="fa fa-list-ul"></i> <span>{{ trans('admin.categories') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('reports'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/reports')) class="active" @endif>
            	<a href="{{ url('panel/admin/reports') }}"><i class="glyphicon glyphicon-ban-circle"></i> <span>
                Disputes
                @if(\App\Models\AdminDisputeQueue::where('status','pending')->count() != 0)
                  <span class="label label-danger label-admin">{{\App\Models\AdminDisputeQueue::where('status','pending')->count()}}</span>
                @endif
              </span>
            </a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('withdrawals'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/withdrawals')) class="active" @endif>
            	<a href="{{ url('panel/admin/withdrawals') }}"><i class="fa fa-university"></i> <span>
                Payouts
                @if(\App\Models\ShiftPayment::where('status','pending_payout')->count() != 0)
                  <span class="label label-warning label-admin">{{\App\Models\ShiftPayment::where('status','pending_payout')->count()}}</span>
                @endif
              </span>
            </a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('verification_requests'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/verification/members')) class="active" @endif>
            	<a href="{{ url('panel/admin/verification/members') }}"><i class="far fa-check-circle myicon-right"></i>
                <span>Verifications
                  @if (\App\Models\VerificationQueue::where('status','pending')->count() != 0)
                    <span class="label label-warning label-admin">{{\App\Models\VerificationQueue::where('status','pending')->count()}}</span>
                  @endif
                </span>
              </a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('pages'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/pages')) class="active" @endif>
            	<a href="{{ url('panel/admin/pages') }}"><i class="glyphicon glyphicon-file"></i> <span>{{ trans('admin.pages') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('blog'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/blog')) class="active" @endif>
            	<a href="{{ url('panel/admin/blog') }}"><i class="fa fa-pencil-alt"></i> <span>{{ trans('general.blog') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('payments'))
            <!-- Links -->
            <li class="treeview @if(Request::is('panel/admin/payments') || Request::is('panel/admin/payments/*')) active @endif">
            	<a href="{{ url('panel/admin/payments') }}"><i class="fa fa-credit-card"></i> <span>{{ trans('admin.payment_settings') }}</span> <i class="fa fa-angle-left pull-right"></i></a>

           		<ul class="treeview-menu">
                <li @if(Request::is('panel/admin/payments')) class="active" @endif><a href="{{ url('panel/admin/payments') }}"><i class="fas fa fa-angle-right"></i> {{ trans('admin.general') }}</a></li>

                  {{-- TODO: Replace with OvertimeStaff payment gateway settings --}}
                  {{-- <?php
                  foreach (PaymentGateways::all() as $key) {
                    ?>
                    <li @if(Request::is('panel/admin/payments/'.$key->id)) class="active" @endif>
                      <a href="{{ url('panel/admin/payments/'.$key->id) }}"><i class="fas fa fa-angle-right"></i> {{ $key->type == 'bank' ? trans('general.bank_transfer') : $key->name }}</a>
                    </li>
                <?php
                  } --}}
                  <li><a href="{{ url('panel/admin/payments/stripe') }}"><i class="fas fa fa-angle-right"></i> Stripe Connect</a></li>
                ?>
              </ul>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('profiles_social'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/profiles-social')) class="active" @endif>
            	<a href="{{ url('panel/admin/profiles-social') }}"><i class="fa fa-share-alt"></i> <span>{{ trans('admin.profiles_social') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('social_login'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/social-login')) class="active" @endif>
            	<a href="{{ url('panel/admin/social-login') }}"><i class="fab fa-facebook myicon-right"></i> <span>{{ trans('admin.social_login') }}</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('google'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/google')) class="active" @endif>
            	<a href="{{ url('panel/admin/google') }}"><i class="fab fa-google myicon-right"></i> <span>Google</span></a>
            </li><!-- ./Links -->
          @endif

          @if (auth()->user()->hasPermission('pwa'))
            <!-- Links -->
            <li @if(Request::is('panel/admin/pwa')) class="active" @endif>
            	<a href="{{ url('panel/admin/pwa') }}"><i class="fa fa-mobile-alt myicon-right"></i> <span>PWA</span></a>
            </li><!-- ./Links -->
          @endif

          
          {{-- LOGS @if (auth()->user()->hasPermission('pwa')) --}}
          <!-- Links -->
          <li @if(Request::is('panel/admin/pwa')) class="active" @endif>
            <a href="{{ url('panel/admin/pwa') }}"><i class="fa fa-mobile-alt myicon-right"></i> <span>PWA</span></a>
          </li><!-- ./Links -->
          <li><a href="{{url('/panel/admin/creator-report')}}"><i class="fa fa-bug"></i>Creator report</a></li>
        {{-- @endif --}}

          </ul><!-- /.sidebar-menu -->
        </section>
        <!-- /.sidebar -->
      </aside>

      @yield('content')

      <!-- Main Footer -->
      <footer class="main-footer">
        <!-- Default to the left -->
       &copy; <strong>{{ $settings->title }} v{{$settings->version}}</strong> - <?php echo date('Y'); ?>
      </footer>

    </div><!-- ./wrapper -->

    <!-- REQUIRED JS SCRIPTS -->

    <script src="{{ asset('plugins/jQuery/jQuery-2.1.4.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('bootstrap/js/bootstrap.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('plugins/fastclick/fastclick.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('admin/js/app.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('plugins/sweetalert/sweetalert.min.js')}}" type="text/javascript"></script>
    <script src="{{ asset('plugins/iCheck/icheck.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/ckeditor/ckeditor.js')}}"></script>
    <script src="{{ asset('plugins/select2/select2.full.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('plugins/tagsinput/jquery.tagsinput.min.js') }}" type="text/javascript"></script>
    <script src="{{{ asset('plugins/colorpicker/bootstrap-colorpicker.min.js') }}}" type="text/javascript"></script>
    <script src="{{ asset('admin/js/functions.js')}}?v={{$settings->version}}" type="text/javascript"></script>

    @yield('javascript')

    @if (session('success_update'))
      <script type="text/javascript">
          swal({
            title: "{{ session('success_update') }}",
            type: "success",
            confirmButtonText: "{{ trans('users.ok') }}"
            });
        </script>
    	 @endif

		 @if (session('unauthorized'))
       <script type="text/javascript">
    		swal({
    			title: "{{ trans('general.error_oops') }}",
    			text: "{{ session('unauthorized') }}",
    			type: "error",
    			confirmButtonText: "{{ trans('users.ok') }}"
    			});
          </script>
   		 @endif
  </body>
</html>
