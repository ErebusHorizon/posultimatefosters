@can('subscribe')
	<li class="{{ empty($request->segment(2)) ? 'active active-sub' : '' }}">
		<a href="{{action('\Modules\Superadmin\Http\Controllers\SubscriptionController@index')}}">
			<i class="fa fa-refresh"></i>
			<span class="title">
				@lang('superadmin::lang.subscription')
			</span>
		</a>
	</li>
@endcan