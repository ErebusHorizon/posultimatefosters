<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Utils\TransactionUtil;

use App\TransactionPayment;
use App\Transaction;

use DB;

class TransactionPaymentController extends Controller
{
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param TransactionUtil $transactionUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('purchase.create') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $transaction_id = $request->input('transaction_id');
            $transaction = Transaction::findOrFail($transaction_id);

            if ($transaction->payment_status != 'paid') {
                $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
                'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                'cheque_number', 'bank_account_number']);
                $inputs['paid_on'] = \Carbon::createFromFormat('m/d/Y', $request->input('paid_on'))->toDateTimeString();
                $inputs['transaction_id'] = $transaction->id;
                $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                $inputs['created_by'] = auth()->user()->id;
                $inputs['payment_for'] = $transaction->contact_id;

                if ($inputs['method'] == 'custom_pay_1') {
                    $inputs['transaction_no'] = $request->input('transaction_no_1');
                } else if ($inputs['method'] == 'custom_pay_2') {
                    $inputs['transaction_no'] = $request->input('transaction_no_2');
                } else if ($inputs['method'] == 'custom_pay_3') {
                    $inputs['transaction_no'] = $request->input('transaction_no_3');
                }

                $prefix_type = 'purchase_payment';
                if ($transaction->type == 'sell') {
                    $prefix_type = 'sell_payment';
                }

                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                //Generate reference number
                $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                TransactionPayment::create($inputs);

                //update payment status
                $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total);
            }

            $output = ['success' => true,
                            'msg' => __('purchase.payment_added_success')
                        ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => false,
                          'msg' => __('messages.something_went_wrong')
                      ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('purchase.create') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $transaction = Transaction::where('id', $id)
                                        ->with(['contact', 'business'])
                                        ->first();
            $payments = TransactionPayment::where('transaction_id', $id)
                                            ->get();
            $payment_types = $this->transactionUtil->payment_types();

            return view('transaction_payment.show_payments')
                    ->with(compact('transaction', 'payments', 'payment_types'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('purchase.create') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $payment_line = TransactionPayment::findOrFail($id);

            $transaction = Transaction::where('id', $payment_line->transaction_id)
                                        ->with(['contact', 'location'])
                                        ->first();
            $payment_types = $this->transactionUtil->payment_types();

            return view('transaction_payment.edit_payment_row')
                        ->with(compact('transaction', 'payment_types', 'payment_line'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('purchase.create') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
            'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
            'cheque_number', 'bank_account_number']);
            $inputs['paid_on'] = \Carbon::createFromFormat('m/d/Y', $request->input('paid_on'))->toDateTimeString();
            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            if ($inputs['method'] == 'custom_pay_1') {
                $inputs['transaction_no'] = $request->input('transaction_no_1');
            } else if ($inputs['method'] == 'custom_pay_2') {
                $inputs['transaction_no'] = $request->input('transaction_no_2');
            } else if ($inputs['method'] == 'custom_pay_3') {
                $inputs['transaction_no'] = $request->input('transaction_no_3');
            }

            $payment = TransactionPayment::findOrFail($id);
            $payment ->update($inputs);

            //update payment status
            $this->transactionUtil->updatePaymentStatus($payment->transaction_id);
            
            $output = ['success' => true,
                            'msg' => __('purchase.payment_updated_success')
                        ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => false,
                          'msg' => __('messages.something_went_wrong')
                      ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('purchase.create') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $payment = TransactionPayment::findOrFail($id);
                $payment->delete();
                
                //update payment status
                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);
                
                $output = ['success' => true,
                                'msg' => __('purchase.payment_deleted_success')
                            ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                
                $output = ['success' => false,
                                'msg' => __('messages.something_went_wrong')
                            ];
            }

            return $output;
        }
    }

    /**
     * Adds new payment to the given transaction.
     *
     * @param  int  $transaction_id
     * @return \Illuminate\Http\Response
     */
    public function addPayment($transaction_id)
    {
        if (!auth()->user()->can('purchase.create') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $transaction = Transaction::where('id', $transaction_id)
                                        ->with(['contact', 'location'])
                                        ->first();
            if ($transaction->payment_status != 'paid') {
                $payment_types = $this->transactionUtil->payment_types();

                $paid_amount = $this->transactionUtil->getTotalPaid($transaction_id);
                $amount = $transaction->final_total - $paid_amount;
                if ($amount < 0) {
                    $amount = 0;
                }
                $payment_line = new TransactionPayment();
                $payment_line->amount = $amount;
                $payment_line->method = 'cash';
                $payment_line->paid_on = \Carbon::now()->toDateString();
                $view = view('transaction_payment.payment_row')
                            ->with(compact('transaction', 'payment_types', 'payment_line'))->render();

                $output = [ 'status' => 'due',
                                    'view' => $view];
            } else {
                $output = [ 'status' => 'paid',
                                'view' => '',
                                'msg' => __('purchase.amount_already_paid')  ];
            }

            return json_encode($output);
        }
    }

    /**
     * Shows contact's payment due modal
     *
     * @param  int  $contact_id
     * @return \Illuminate\Http\Response
     */
    public function getPayContactDue($contact_id)
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $due_payment_type = request()->input('type');
            $query = Transaction::join(
                'contacts as c',
                'c.id',
                '=',
                'transactions.contact_id'
            )
            ->where('transactions.contact_id', $contact_id);
            if($due_payment_type == 'purchase'){
                $query->where('transactions.type', 'purchase')
                    ->select(
                        DB::raw("SUM(transactions.final_total) as total_purchase"),
                        DB::raw("SUM((SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id)) as total_paid"),
                        'c.name',
                        'c.supplier_business_name',
                        'transactions.contact_id'
                    );
            } else if($due_payment_type == 'sell'){
                $query->where('transactions.type', 'sell')
                    ->select(
                        DB::raw("SUM(transactions.final_total) as total_invoice"),
                        DB::raw("SUM((SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=transactions.id)) as total_paid"),
                        'c.name',
                        'c.supplier_business_name',
                        'transactions.contact_id'
                    );
            }
                               
            $contact_details = $query->first();

            $payment_line = new TransactionPayment();
            if($due_payment_type == 'purchase'){
                $contact_details->total_purchase = empty($contact_details->total_purchase) ? 0 : $contact_details->total_purchase;
                $payment_line->amount = $contact_details->total_purchase -
                                    $contact_details->total_paid;
            } elseif ($due_payment_type == 'sell') {
                $contact_details->total_invoice = empty($contact_details->total_invoice) ? 0 : $contact_details->total_invoice;

                $payment_line->amount = $contact_details->total_invoice -
                                    $contact_details->total_paid;
            }

            $contact_details->total_paid = empty($contact_details->total_paid) ? 0 : $contact_details->total_paid;
            
            $payment_line->method = 'cash';
            $payment_line->paid_on = \Carbon::now()->toDateString();
                   
            $payment_types = $this->transactionUtil->payment_types();
            if ($payment_line->amount > 0) {
                return view('transaction_payment.pay_supplier_due_modal')
                        ->with(compact('contact_details', 'payment_types', 'payment_line', 'due_payment_type'));
            }
        }
    }

    /**
     * Adds Payments for Contact due
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postPayContactDue(Request  $request)
    {
        if (!auth()->user()->can('purchase.create') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $contact_id = $request->input('contact_id');
            $inputs = $request->only(['amount', 'method', 'note', 'card_number', 'card_holder_name',
                'card_transaction_number', 'card_type', 'card_month', 'card_year', 'card_security',
                'cheque_number', 'bank_account_number']);
            $inputs['paid_on'] = \Carbon::createFromFormat('m/d/Y', $request->input('paid_on'))->toDateTimeString();
            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
            $inputs['created_by'] = auth()->user()->id;
            $inputs['payment_for'] = $contact_id;

            if ($inputs['method'] == 'custom_pay_1') {
                $inputs['transaction_no'] = $request->input('transaction_no_1');
            } else if ($inputs['method'] == 'custom_pay_2') {
                $inputs['transaction_no'] = $request->input('transaction_no_2');
            } else if ($inputs['method'] == 'custom_pay_3') {
                $inputs['transaction_no'] = $request->input('transaction_no_3');
            }
            $due_payment_type = $request->input('due_payment_type');
            
            $prefix_type = 'sell_payment';
            if ($due_payment_type == 'purchase') {
                $prefix_type = 'purchase_payment';
            }
            $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
            //Generate reference number
            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            $inputs['payment_ref_no'] = $payment_ref_no;

            DB::beginTransaction();

            $parent_payment = TransactionPayment::create($inputs);

            //Distribute above payment among unpaid transactions

            $this->transactionUtil->payAtOnce($parent_payment, $due_payment_type);

            DB::commit();
            $output = ['success' => true,
                            'msg' => __('purchase.payment_added_success')
                        ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => false,
                          'msg' => __('messages.something_went_wrong')
                      ];
        }

        return $output;
    }
}
