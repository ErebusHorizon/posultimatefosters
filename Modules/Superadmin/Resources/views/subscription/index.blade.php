@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | ' . __('superadmin::lang.subscription'))

@section('content')

<!-- Main content -->
<section class="content">

	<!-- Page level currency setting -->
	<input type="hidden" id="p_code" value="{{$system_currency->code}}">
	<input type="hidden" id="p_symbol" value="{{$system_currency->symbol}}">
	<input type="hidden" id="p_thousand" value="{{$system_currency->thousand_separator}}">
	<input type="hidden" id="p_decimal" value="{{$system_currency->decimal_separator}}">
	
	<div class="box">
        <div class="box-header">
            <h3 class="box-title">@lang('superadmin::lang.active_subscription')</h3>
        </div>

        <div class="box-body">
        	@if(!empty($active))
        		<div class="col-md-4">
	        		<div class="box box-success">
						<div class="box-header with-border text-center">
							<h2 class="box-title">
								{{$active->package_details['name']}}
							</h2>

							<div class="box-tools pull-right">
								<span class="badge bg-green">
									@lang('superadmin::lang.running')
								</span>
              				</div>

						</div>
						<div class="box-body text-center">
							@lang('superadmin::lang.start_date') : {{$active->start_date->toDateString()}} <br/>
							@lang('superadmin::lang.end_date') : {{$active->end_date->toDateString()}} <br/>

							@lang('superadmin::lang.remaining', ['days' => \Carbon::today()->diffInDays($active->end_date)])

						</div>
					</div>
				</div>
        	@else
        		<h3 class="text-danger">@lang('superadmin::lang.no_active_subscription')</h3>
        	@endif

        	@if(!empty($nexts))
        		<div class="clearfix"></div>
        		@foreach($nexts as $next)
        			<div class="col-md-4">
		        		<div class="box box-success">
							<div class="box-header with-border text-center">
								<h2 class="box-title">
									{{$next->package_details['name']}}
								</h2>
							</div>
							<div class="box-body text-center">
								@lang('superadmin::lang.start_date') : {{$next->start_date->toDateString()}} <br/>
								@lang('superadmin::lang.end_date') : {{$next->end_date->toDateString()}}
							</div>
						</div>
					</div>
        		@endforeach
        	@endif

        	@if(!empty($waiting))
        		<div class="clearfix"></div>
        		@foreach($waiting as $row)
        			<div class="col-md-4">
		        		<div class="box box-success">
							<div class="box-header with-border text-center">
								<h2 class="box-title">
									{{$row->package_details['name']}}
								</h2>
							</div>
							<div class="box-body text-center">
								@lang('superadmin::lang.waiting_approval')
							</div>
						</div>
					</div>
        		@endforeach
        	@endif


        </div>
    </div>

    <div class="box">
        <div class="box-header">
            <h3 class="box-title">@lang('superadmin::lang.packages')</h3>
        </div>

        <div class="box-body">
        	@foreach ($packages as $package)
                <div class="col-md-4">
                	
					<div class="box box-success hvr-grow-shadow">
						<div class="box-header with-border text-center">
							<h2 class="box-title">{{$package->name}}</h2>
						</div>
						<!-- /.box-header -->
						<div class="box-body text-center">
							
								
									@if($package->location_count == 0)
										@lang('superadmin::lang.unlimited')
									@else
										{{$package->location_count}}
									@endif

									@lang('business.business_locations')
								<br/>

								
									@if($package->user_count == 0)
										@lang('superadmin::lang.unlimited')
									@else
										{{$package->user_count}}
									@endif

									@lang('superadmin::lang.users')
								<br/>

								
									@if($package->product_count == 0)
										@lang('superadmin::lang.unlimited')
									@else
										{{$package->product_count}}
									@endif

									@lang('superadmin::lang.products')
								<br/>

								
									@if($package->invoice_count == 0)
										@lang('superadmin::lang.unlimited')
									@else
										{{$package->invoice_count}}
									@endif

									@lang('superadmin::lang.invoices')
								<br/>

								@if($package->trial_days != 0)
									
										{{$package->trial_days}} @lang('superadmin::lang.trial_days')
									<br/>
								@endif
							
							<h3 class="text-center">

								@if($package->price != 0)
									<span class="display_currency" data-currency_symbol="true">
										{{$package->price}}
									</span>

									<small>
										/ {{$package->interval_count}} {{ucfirst($package->interval)}}
									</small>
								@else
									@lang('superadmin::lang.free_for_duration', ['duration' => $package->interval_count . ' ' . ucfirst($package->interval)])
								@endif
							</h3>
						</div>
						<!-- /.box-body -->

						<div class="box-footer text-center">
							<a href="{{action('\Modules\Superadmin\Http\Controllers\SubscriptionController@pay', [$package->id])}}" 
								class="btn btn-block btn-success">
                				<i class="fa fa-check"></i>
                				@if($package->price != 0)
                					@lang('superadmin::lang.pay_and_subscribe')
                				@else
                					@lang('superadmin::lang.subscribe')
                				@endif
                			</a>
                			
                			{{$package->description}}
						</div>
					</div>
					<!-- /.box -->
                </div>
            @endforeach

        </div>
    </div>

</section>
@endsection

@section('javascript')

@endsection