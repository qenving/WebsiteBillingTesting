<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Events\InvoicePaid;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLog;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TripayGateway extends AbstractGateway implements PaymentGateway
{
    public function key(): string { return 'tripay'; }
    public function displayName(): string { return 'Tripay'; }
    protected function isSandbox(): bool { return (bool) config('tripay.sandbox', true); }
    protected function baseUrl(): string { return $this->isSandbox()? 'https://tripay.co.id/api-sandbox':'https://tripay.co.id/api'; }

    public function createPayment(Invoice $invoice, array $options = []): Payment
    {
        $method = $options['method'] ?? config('tripay.default_method'); if(! $method) throw new \RuntimeException('Tripay method not specified');
        $p=$invoice->payments()->create(['gateway'=>$this->key(),'amount'=>$invoice->total,'currency'=>$invoice->currency,'status'=>'pending']);
        $merchantCode=(string) config('tripay.merchant_code'); $privateKey=(string) config('tripay.private_key'); $apiKey=(string) config('tripay.api_key');
        $merchantRef=$invoice->number.'-PAY'.$p->id; $amount=(int)round($invoice->total); $signature=hash_hmac('sha256',$merchantCode.$merchantRef.$amount,$privateKey);
        $items=[]; foreach($invoice->items as $it){ $items[]=['name'=>$it->description,'price'=>(int)round($it->unit_price),'quantity'=>(int)$it->quantity]; }
        $payload=['method'=>$method,'merchant_ref'=>$merchantRef,'amount'=>$amount,'customer_name'=>optional($invoice->client->user)->name,'customer_email'=>optional($invoice->client->user)->email,'order_items'=>$items,'callback_url'=>url('/webhook/payments/tripay'),'return_url'=>url('/invoices/'.$invoice->id),'signature'=>$signature];
        $cli=new Client(['timeout'=>30]); $resp=$cli->post($this->baseUrl().'/transaction/create',['headers'=>['Authorization'=>'Bearer '.$apiKey,'Accept'=>'application/json'],'form_params'=>$payload]); $data=json_decode((string)$resp->getBody(),true); $checkoutUrl=Arr::get($data,'data.checkout_url');
        $p->transaction_id=$merchantRef; $meta=$p->meta??[]; $meta['checkout_url']=$checkoutUrl; $meta['redirect_url']=$checkoutUrl; $meta['tripay']=$data; $p->meta=$meta; $p->save(); return $p;
    }
    public function handleWebhook(Request $request): void
    {
        $payload=$request->all(); $merchantCode=(string) config('tripay.merchant_code'); $privateKey=(string) config('tripay.private_key');
        $merchantRef=Arr::get($payload,'merchant_ref'); $amount=(int)Arr::get($payload,'amount',0); $status=strtoupper((string)Arr::get($payload,'status','')); $sig=(string)Arr::get($payload,'signature','');
        $expected=hash_hmac('sha256',$merchantCode.$merchantRef.$amount.$status,$privateKey); if(! hash_equals($expected,$sig)) abort(403,'Invalid signature');
        $payment=Payment::where('gateway',$this->key())->where('transaction_id',$merchantRef)->first(); PaymentLog::create(['payment_id'=>$payment?->id,'gateway'=>$this->key(),'event'=>$status,'payload'=>$payload,'ip_address'=>$request->ip()]); if(! $payment) return;
        if($status==='PAID'){
            if($payment->status!=='completed'){ $payment->status='completed'; $payment->paid_at=now(); $payment->meta=array_merge($payment->meta??[],['tripay'=>$payload]); $payment->save(); $inv=$payment->invoice; if($inv && $inv->status!=='paid'){ $inv->status='paid'; $inv->paid_at=now(); $inv->save(); event(new InvoicePaid($inv)); } }
        } elseif(in_array($status,['EXPIRED','CANCELED','FAILED'],true)) { $payment->status='failed'; $payment->meta=array_merge($payment->meta??[],['tripay'=>$payload]); $payment->save(); }
    }
}

