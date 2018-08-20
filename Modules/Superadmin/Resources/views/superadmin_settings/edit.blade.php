@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | Superadmin Settings')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('superadmin::lang.super_admin_settings')<small>@lang('superadmin::lang.edit_super_admin_settings')</small></h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    <div class="box box-solid">
        <div class="box-header">
        	<h3 class="box-title">@lang('superadmin::lang.super_admin_settings')</h3>
        </div>
        <div class="box-body">
            {!! Form::open(['action' => '\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@update', 'method' => 'put']) !!}
            
                <div class="col-xs-4">
                    <div class="form-group">
                         {!! Form::label('invoice_business_name', __('business.business_name') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-suitcase"></i>
                            </span>
                        {!! Form::text('invoice_business_name', $settings["invoice_business_name"], ['class' => 'form-control','placeholder' => __('business.business_name'), 'required']); !!}
                    </div>
                    </div>
                </div>

                <div class="col-xs-4">
                    <div class="form-group">
                        {!! Form::label('email', __('business.email'). ':')!!}
                        <div class="input-group">
                            <span class="input-group-addon">
                            <i class="fa fa-envelope"></i>
                            </span>
                        {!! Form::email('email',$settings["email"], ['class'=>'form-control', 'placeholder'=> __('business.email')])!!}
                        </div>
                    </div>
                </div>

                <div class="col-xs-4">
                    <div class="form-group">
                         {!! Form::label('app_currency_id', __('business.currency') . ':') !!}
                        <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-money"></i>
                        </span>
                        {!! Form::select('app_currency_id', $currencies, $settings["app_currency_id"], ['class' => 'form-control select2','placeholder' => __('business.currency_placeholder'), 'required']); !!}
                    </div>
                    </div>
                </div>

                <div class="clearfix"></div>
                <div class="col-xs-4">
                    <div class="form-group">
                         {!! Form::label('invoice_business_landmark', __('business.landmark') . ':') !!}
                        <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-map-marker"></i>
                        </span>
                        {!! Form::text('invoice_business_landmark', $settings["invoice_business_landmark"], ['class' => 'form-control','placeholder' => __('business.landmark'),'required']); !!}
                    </div>
                    </div>
                </div> 
                
                <div class="col-xs-4">
                    <div class="form-group">
                         {!! Form::label('invoice_business_zip', __('business.zip_code') . ':') !!}
                        <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-map-marker"></i>
                        </span>
                        {!! Form::text('invoice_business_zip',$settings["invoice_business_zip"], ['class' => 'form-control','placeholder' => __('business.zip_code'), 'required']); !!}
                    </div>
                    </div>
                </div>

                <div class="col-xs-4">
                    <div class="form-group">
                         {!! Form::label('invoice_business_state', __('business.state') . ':') !!}
                        <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-map-marker"></i>
                        </span>
                        {!! Form::text('invoice_business_state', $settings["invoice_business_state"], ['class' => 'form-control','placeholder' => __('business.state'), 'required']); !!}
                    </div>
                    </div>
                </div>

                <div class="col-xs-4">
                    <div class="form-group">
                         {!! Form::label('invoice_business_city', __('business.city') . ':') !!}
                        <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-map-marker"></i>
                        </span>
                        {!! Form::text('invoice_business_city',$settings["invoice_business_city"], ['class' => 'form-control','placeholder' => __('business.city'),'required']); !!}
                    </div>
                    </div>
                </div>
                <div class="col-xs-4">
                    <div class="form-group">
                         {!! Form::label('invoice_business_country', __('business.country') . ':') !!}
                        <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-globe"></i>
                        </span>
                        {!! Form::text('invoice_business_country', $settings["invoice_business_country"], ['class' => 'form-control','placeholder' => __('business.country'), 'required']); !!}
                    </div>
                    </div>
                </div>

                <div class="clearfix"></div>

                <div class="col-xs-12 ">
                    <br><i>@lang('superadmin::lang.payment_gateway_help')</i>
                </div>

                <div class="col-xs-12">
                    <div class="form-group pull-right">
                    {{Form::submit('update', ['class'=>"btn btn-primary"])}}
                    </div>
                </div>
            
                <div class="clearfix"></div>
    
    {!! Form::close() !!}
</section>

@endsection