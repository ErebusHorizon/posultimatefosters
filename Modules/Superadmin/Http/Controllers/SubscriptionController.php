<?php

namespace Modules\Superadmin\Http\Controllers;

use Modules\Superadmin\Entities\Subscription,
    Modules\Superadmin\Entities\Package,
    App\System,
    App\Business;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Superadmin\Notifications\SubscriptionOfflinePaymentActivationConfirmation;

use \Notification,
    Stripe\Stripe,
    Stripe\Customer,
    Stripe\Charge;

use Srmklive\PayPal\Services\ExpressCheckout;

class SubscriptionController extends Controller
{
    protected $provider;

    public function __construct(){
        $this->provider = new ExpressCheckout();
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        if (!auth()->user()->can('subscribe')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Get active subscription and upcoming subscriptions.
        $active = Subscription::active_subscription($business_id);
        //print_r($active);exit;
        $nexts = Subscription::upcoming_subscriptions($business_id);
        $waiting = Subscription::waiting_approval($business_id);

        $packages = Package::active()->orderby('sort_order')->get();

        $system_currency = System::getCurrency();

        return view('superadmin::subscription.index')
            ->with(compact('packages', 'active', 'nexts', 'system_currency', 'waiting'));
    }

    /**
     * Returns the list of all configured payment gateway
     * @return Response
     */
    protected function _payment_gateways(){
        $gateways = [];
        
        //Check if configured or not
        if(env('STRIPE_PUB_KEY') && env('STRIPE_SECRET_KEY')){
            $gateways['stripe'] = 'Stripe';
        }

        if((env('PAYPAL_SANDBOX_API_USERNAME') && env('PAYPAL_SANDBOX_API_PASSWORD')  && env('PAYPAL_SANDBOX_API_SECRET')) || (env('PAYPAL_LIVE_API_USERNAME') && env('PAYPAL_LIVE_API_PASSWORD')  && env('PAYPAL_LIVE_API_SECRET')) ){
            $gateways['paypal'] = 'PayPal';
        }

        $gateways['offline'] = 'Offline';

        return $gateways;
    }

    /**
     * Show pay form for a new package.
     * @return Response
     */
    public function pay($package_id)
    {
        if (!auth()->user()->can('subscribe')) {
            abort(403, 'Unauthorized action.');
        }

        try{
            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');

            $package = Package::active()->find($package_id);

            //Check for free package & subscribe it.
            if($package->price == 0){
                $gateway = NULL;
                $payment_transaction_id = 'FREE';
                $user_id = request()->session()->get('user.id');

                $this->_add_subscription($business_id, $package, $gateway, $payment_transaction_id, $user_id);

                DB::commit();

                $output = ['success' => 1, 'msg' => __('lang_v1.success')];
                return redirect()
                    ->action('\Modules\Superadmin\Http\Controllers\SubscriptionController@index')
                    ->with('status', $output);                
            }

            $gateways = $this->_payment_gateways();

            $system_currency = System::getCurrency();
            
            DB::commit();

            return view('superadmin::subscription.pay')
                ->with(compact('package', 'gateways', 'system_currency'));

        } catch(\Exception $e){

            DB::rollBack();

            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0, 'msg' => "File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage()];

            return redirect()
                ->action('\Modules\Superadmin\Http\Controllers\SubscriptionController@index')
                ->with('status', $output);
        }
    }

    /**
     * Save the payment details and add subscription details
     * @return Response
     */
    public function confirm($package_id, Request $request){
        if (!auth()->user()->can('subscribe')) {
            abort(403, 'Unauthorized action.');
        }

        try{

            //Disable in demo
            if (config('app.env') == 'demo') {
                $output = ['success' => 0,
                                'msg' => 'Feature disabled in demo!!'
                            ];
                return back()->with('status', $output);
            }
        
            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');
            $business_name = request()->session()->get('business.name');
            $user_id = request()->session()->get('user.id');
            $package = Package::active()->find($package_id);

            //Call the payment method
            $pay_function = 'pay_' . request()->gateway;
            $payment_transaction_id = null;
            if(method_exists($this, $pay_function)){
                $payment_transaction_id = $this->$pay_function($business_id, $business_name, $package, $request);
            }

            //Add subscription details after payment is succesful
            $this->_add_subscription($business_id, $package, request()->gateway, $payment_transaction_id, $user_id);
            DB::commit();

            $msg = __('lang_v1.success');
            if(request()->gateway == 'offline'){
                $msg = __('superadmin::lang.notification_sent_for_approval');
            }
            $output = ['success' => 1, 'msg' => $msg];

        } catch(\Exception $e){
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => $e->getMessage()];
        }

        return redirect()
            ->action('\Modules\Superadmin\Http\Controllers\SubscriptionController@index')
            ->with('status', $output);
    }

    /**
     * Enter details for subscriptions
     * @return object
     */
    protected function _add_subscription($business_id, $package, $gateway, $payment_transaction_id, $user_id){

        $subscription = ['business_id' => $business_id,
                        'package_id' => $package->id,
                        'paid_via' => $gateway,
                        'payment_transaction_id' => $payment_transaction_id
                    ];

        if($gateway == 'offline'){
            //If offline then dates will be decided when approved by superadmin
            $subscription['start_date'] = null;
            $subscription['end_date'] = null;
            $subscription['trial_end_date'] = null;
            $subscription['status'] = 'waiting';
        } else {
            $subscription_end_date = Subscription::end_date($business_id);
            $subscription['start_date'] = $subscription_end_date->toDateString();

            if($package->interval == 'days'){
                $subscription['end_date'] = $subscription_end_date->addDays($package->interval_count)->toDateString();
            } elseif($package->interval == 'months'){
                $subscription['end_date'] = $subscription_end_date->addMonths($package->interval_count)->toDateString();
            } elseif($package->interval == 'years'){
                $subscription['end_date'] = $subscription_end_date->addYears($package->interval_count)->toDateString();
            }
            
            $subscription['trial_end_date'] = $subscription_end_date->addDays($package->trial_days);

            $subscription['status'] = 'approved';
        }

        $subscription['package_price'] = $package->price;
        $subscription['package_details'] = ['location_count' => $package->location_count, 
                'user_count' => $package->user_count, 
                'product_count' => $package->product_count, 
                'invoice_count' => $package->invoice_count,
                'name' => $package->name
            ];
        $subscription['created_id'] = $user_id;

        $subscription = Subscription::create($subscription);

        return $subscription;
    }

    /**
     * Stripe payment method
     * @return Response
     */
    protected function pay_stripe($business_id, $business_name, $package, $request){
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        $metadata = ['business_id' => $business_id, 'business_name' => $business_name, 'stripe_email' => $request->stripeEmail, 'package_name' => $package->name];
        // $customer = Customer::create(array(
        //     'email' => $request->stripeEmail,
        //     'source'  => $request->stripeToken,
        //     'metadata' => $metadata
        // ));
        
        $system_currency = System::getCurrency();

        $charge = Charge::create(array(
            'amount'   => $package->price*100,
            'currency' => strtolower($system_currency->code),
            "source" => $request->stripeToken,
            //'customer' => $customer
            'metadata' => $metadata
        ));

        return $charge->id;
    }

    /**
     * Stripe payment method
     * @return Response
     */
    protected function pay_offline($business_id, $business_name, $package, $request){

        //Disable in demo
        if (config('app.env') == 'demo') {
            $output = ['success' => 0,
                            'msg' => 'Feature disabled in demo!!'
                        ];
            return back()->with('status', $output);
        }

        //Send notification
        $email = System::getProperty('email');
        $business = Business::find($business_id);
        Notification::route('mail', $email)
            ->notify(new SubscriptionOfflinePaymentActivationConfirmation($business, $package));

        return NULL;
    }

    /**
     * Paypal payment method
     * @return Response
     */
    protected function pay_paypal($business_id, $business_name, $package, $request){

        $response = $provider->getExpressCheckoutDetails($request->token);

        $token = $request->get('token');
        $PayerID = $request->get('PayerID');
        $invoice_id = explode('_', $response['INVNUM'])[1];

        // if response ACK value is not SUCCESS or SUCCESSWITHWARNING we return back with error
        if (!in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
            return back()
                ->with('status', ['success' => 0, 'msg' => 'Something went wrong with paypal transaction']);
        }

        $package = Package::active()->find($package_id);

        $data = [];
        $data['items'] = [
                [
                    'name' => $package->name,
                    'price' => $package->price,
                    'qty' => 1
                ]
            ];
        $data['invoice_id'] = $invoice_id;
        $data['invoice_description'] = "Order #{$data['invoice_id']} Invoice";
        $data['return_url'] = action('\Modules\Superadmin\Http\Controllers\SubscriptionController@confirm', [$package_id]);
        $data['cancel_url'] = action('\Modules\Superadmin\Http\Controllers\SubscriptionController@pay', [$package_id]);
        $data['total'] = $package->price;

        // if payment is not recurring just perform transaction on PayPal and get the payment status
        $payment_status = $this->provider->doExpressCheckoutPayment($data, $token, $PayerID);
        $status = $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'];

        if($status != 'Invalid'){
            return $invoice_id;
        } else {
            $error = 'Something went wrong with paypal transaction';
            throw new Exception($error);
        }
    }

    /**
     * Paypal payment method - redirect to paypal url for payments
     * 
     * @return Response
     */
    public function paypalExpressCheckout(Request $request, $package_id){

        //Disable in demo
        if (config('app.env') == 'demo') {
            $output = ['success' => 0,
                            'msg' => 'Feature disabled in demo!!'
                        ];
            return back()->with('status', $output);
        }

        // Get the cart data or package details.
        $package = Package::active()->find($package_id);

        $data = [];
        $data['items'] = [
                [
                    'name' => $package->name,
                    'price' => $package->price,
                    'qty' => 1
                ]
            ];
        $data['invoice_id'] = str_random(5);
        $data['invoice_description'] = "Order #{$data['invoice_id']} Invoice";
        $data['return_url'] = action('\Modules\Superadmin\Http\Controllers\SubscriptionController@confirm', [$package_id]);
        $data['cancel_url'] = action('\Modules\Superadmin\Http\Controllers\SubscriptionController@pay', [$package_id]);
        $data['total'] = $package->price;

        // send a request to paypal 
        // paypal should respond with an array of data
        // the array should contain a link to paypal's payment system
        $system_currency = System::getCurrency();
        $response = $this->provider->setCurrency(strtoupper($system_currency->code))
                        ->setExpressCheckout($data);

        // if there is no link redirect back with error message
        if (!$response['paypal_link']) {
            return back()
                ->with('status', ['success' => 0, 'msg' => 'Something went wrong with paypal transaction']);
            //For the actual error message dump out $response and see what's in there
        }

        // redirect to paypal
        // after payment is done paypal
        // will redirect us back to $this->expressCheckoutSuccess
        return redirect($response['paypal_link']);
    }


    // /**
    //  * Show the form for creating a new resource.
    //  * @return Response
    //  */
    // public function create()
    // {
    //     return view('superadmin::create');
    // }

    // /**
    //  * Store a newly created resource in storage.
    //  * @param  Request $request
    //  * @return Response
    //  */
    // public function store(Request $request)
    // {
    // }

    // /**
    //  * Show the specified resource.
    //  * @return Response
    //  */
    // public function show()
    // {
    //     return view('superadmin::show');
    // }

    // /**
    //  * Show the form for editing the specified resource.
    //  * @return Response
    //  */
    // public function edit()
    // {
    //     return view('superadmin::edit');
    // }

    // /**
    //  * Update the specified resource in storage.
    //  * @param  Request $request
    //  * @return Response
    //  */
    // public function update(Request $request)
    // {
    // }

    // /**
    //  * Remove the specified resource from storage.
    //  * @return Response
    //  */
    // public function destroy()
    // {
    // }
}
