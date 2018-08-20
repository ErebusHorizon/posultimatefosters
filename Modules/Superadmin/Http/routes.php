<?php

Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'superadmin', 'namespace' => 'Modules\Superadmin\Http\Controllers'], function()
{
    Route::get('/install', 'InstallController@index');

    Route::get('/', 'SuperadminController@index');
    Route::get('/stats', 'SuperadminController@stats');
    
    Route::get('/{business_id}/toggle-active/{is_active}', 'BusinessController@toggleActive');
    Route::resource('/business', 'BusinessController');
    Route::get('/business/{id}/destroy', 'BusinessController@destroy');

    Route::resource('/packages', 'PackagesController');
    Route::get('/packages/{id}/destroy', 'PackagesController@destroy');

    Route::get('/settings', 'SuperadminSettingsController@edit');
    Route::put('/settings', 'SuperadminSettingsController@update');
    Route::resource('/superadmin-subscription', 'SuperadminSubscriptionsController');
});

Route::group(['middleware' => ['web', 'auth', 'timezone'], 'namespace' => 'Modules\Superadmin\Http\Controllers'], function()
{
	//Routes related to paypal checkout
	Route::get('/subscription/{package_id}/paypal-express-checkout', 
		'SubscriptionController@paypalExpressCheckout');

	Route::get('/subscription/{package_id}/pay', 'SubscriptionController@pay');
	Route::post('/subscription/{package_id}/confirm', 'SubscriptionController@confirm');
    Route::resource('/subscription', 'SubscriptionController');
});