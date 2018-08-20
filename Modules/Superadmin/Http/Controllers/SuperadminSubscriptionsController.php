<?php

namespace Modules\Superadmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Superadmin\Entities\Subscription;
use Yajra\DataTables\Facades\DataTables;

use App\Utils\BusinessUtil;

use App\System;

class SuperadminSubscriptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }
        
        $currency = System::getCurrency();

        if(request()->ajax())
        {   
            
            $superadmin_subscription = Subscription::join('business',           'subscriptions.business_id', '=', 'business.id')
                ->join('packages', 'subscriptions.package_id', '=', 'packages.id')
                ->select('business.name as business_name', 'packages.name as package_name', 'subscriptions.status', 'subscriptions.start_date', 'subscriptions.trial_end_date', 'subscriptions.end_date', 'subscriptions.package_price', 'subscriptions.paid_via', 'subscriptions.payment_transaction_id', 'subscriptions.id');
            
            return DataTables::of($superadmin_subscription)
                        ->addColumn('action',
                            '<button data-href ="{{action(\'\Modules\Superadmin\Http\Controllers\SuperadminSubscriptionsController@edit\',[$id])}}" class="btn btn-info btn-xs change_status" data-toggle="modal" data-target="#statusModal">
                            @lang( "superadmin::lang.status")
                            </button>'
                        )
                        ->editColumn('trial_end_date', '{{@format_date($trial_end_date)}}')
                        ->editColumn('start_date', '{{@format_date($start_date)}}')
                        ->editColumn('end_date', '{{@format_date($end_date)}}')
                        ->editColumn('status', 
                            '@if($status == "approved")
                                <span class="label bg-light-green">{{__(\'superadmin::lang.\'.$status)}}
                                </span>
                            @elseif($status == "waiting")
                                <span class="label bg-aqua">{{__(\'superadmin::lang.\'.$status)}}
                                </span>
                            @else($status == "declined")
                                <span class="label bg-red">{{__(\'superadmin::lang.\'.$status)}}
                                </span>
                            @endif'
                        )
                        ->editColumn('package_price', 
                            '<span class="display_currency" data-currency_symbol="true">
                                {{$package_price}}
                            </span>'
                        )
                        ->removeColumn('id')
                        ->rawColumns([2, 6, 9])
                        ->make(false);
           }
        return view('superadmin::superadmin_subscription.index')
                ->with(compact('currency'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('superadmin::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('superadmin::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        if(request()->ajax()){ 
            
            $status = Subscription::package_subscription_status();
            $subscription = Subscription::find($id);

            return view('superadmin::superadmin_subscription.edit')
                        ->with(compact('subscription', 'status'));
        }
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id )
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        if(request()->ajax()){

           try{
                $input = $request->only(['status', 'payment_transaction_id']);
        
                $subscriptions = Subscription::findOrFail($id);
                $subscriptions->status = $input['status'];
                $subscriptions->payment_transaction_id = $input['payment_transaction_id'];
                $subscriptions->save();

                $output = array('success' => true, 
                                    'msg' => __("superadmin::lang.subcription_updated_success")
                                );
                                
            } catch(\Exception $e){
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
                $output = array('success' => false, 
                            'msg' => __("messages.something_went_wrong")
                            );
            }
          return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }
}
