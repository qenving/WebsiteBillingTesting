<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Events\InvoicePaid;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLog;
use Illuminate\Http\Request;

class StripeGateway extends AbstractGateway implements PaymentGateway
{
    public function key(): string { return 'stripe'; }
    public function displayName(): string { return 'Stripe'; }
    public function createPayment(Invoice $invoice, array $options = []): Payment
    {
        \Stripe\Stripe::setApiKey((string) config('stripe.secret'));
        $p=$invoice->payments()->create(['gateway'=>$this->key(),'amount'=>$invoice->total,'currency'=>$invoice->currency,'status'=>'pending']);
        $items=[]; foreach($invoice->items as $it){ $items[]=['price_data'=>['currency'=>strtolower($invoice->currency??'idr'),'product_data'=>['name'=>$it->description],'unit_amount'=>(int)round($it->unit_price*100)],'quantity'=>(int)$it->quantity]; }
        $session=\Stripe\Checkout\Session::create(['mode'=>'payment','line_items'=>$items,'success_url'=>url('/invoices/'.$invoice->id),'cancel_url'=>url('/invoices/'.$invoice->id),'client_reference_id'=>(string)$invoice->id,'metadata'=>['payment_id'=>(string)$p->id]]);
        $p->transaction_id=$session->id; $meta=$p->meta??[]; $meta['checkout_session']=$session->id; $meta['redirect_url']=$session->url; $p->meta=$meta; $p->save(); return $p;
    }
    public function handleWebhook(Request $request): void
    {
        $payload=$request->getContent(); $sig=$request->header('Stripe-Signature'); $secret=(string) config('stripe.webhook_secret'); if(! $secret) abort(403,'No webhook secret');
        try { $event=\Stripe\Webhook::constructEvent($payload,$sig,$secret); } catch(\Throwable $e){ abort(400,'Invalid webhook'); }
        if($event->type==='checkout.session.completed'){
            $session=$event->data->object; $txn=$session->id; $payment=Payment::where('gateway',$this->key())->where('transaction_id',$txn)->first(); PaymentLog::create(['payment_id'=>$payment?->id,'gateway'=>$this->key(),'event'=>$event->type,'payload'=>json_decode($payload,true),'signature'=>$sig,'ip_address'=>$request->ip()]); if(! $payment) return;
            if($payment->status!=='completed'){ $payment->status='completed'; $payment->paid_at=now(); $payment->save(); $inv=$payment->invoice; if($inv && $inv->status!=='paid'){ $inv->status='paid'; $inv->paid_at=now(); $inv->save(); event(new InvoicePaid($inv)); } }
        }
    }
}

