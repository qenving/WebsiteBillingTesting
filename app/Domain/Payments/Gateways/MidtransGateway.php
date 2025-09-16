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

class MidtransGateway extends AbstractGateway implements PaymentGateway
{
    public function key(): string { return 'midtrans'; }
    public function displayName(): string { return 'Midtrans'; }
    protected function isSandbox(): bool { return (bool) config('midtrans.sandbox', true); }
    protected function snapUrl(): string { return $this->isSandbox()? 'https://app.sandbox.midtrans.com/snap/v1/transactions':'https://app.midtrans.com/snap/v1/transactions'; }
    protected function statusUrl(string $orderId): string { return $this->isSandbox()? "https://api.sandbox.midtrans.com/v2/{$orderId}/status":"https://api.midtrans.com/v2/{$orderId}/status"; }
    protected function authHeader(): array { $k=(string) config('midtrans.server_key'); return ['Authorization'=>'Basic '.base64_encode($k.':'),'Content-Type'=>'application/json','Accept'=>'application/json']; }

    public function createPayment(Invoice $invoice, array $options = []): Payment
    {
        $payment = $invoice->payments()->create(['gateway'=>$this->key(),'amount'=>$invoice->total,'currency'=>$invoice->currency,'status'=>'pending']);
        $orderId = $invoice->number.'-PAY'.$payment->id;
        $items=[]; foreach($invoice->items as $it){ $items[]=['id'=>(string)($it->product_id??$it->id),'price'=>(int)round($it->unit_price),'quantity'=>(int)$it->quantity,'name'=>substr($it->description,0,50)]; }
        $payload=['transaction_details'=>['order_id'=>$orderId,'gross_amount'=>(int)round($invoice->total)],'item_details'=>$items,'customer_details'=>['first_name'=>optional($invoice->client->user)->name,'email'=>optional($invoice->client->user)->email,'phone'=>$invoice->client->phone]];
        $cli=new Client(['timeout'=>30]); $resp=$cli->post($this->snapUrl(),['headers'=>$this->authHeader(),'json'=>$payload]); $data=json_decode((string)$resp->getBody(),true);
        $payment->transaction_id=$orderId; $meta=$payment->meta??[]; $meta=array_merge($meta,['snap_token'=>$data['token']??null,'redirect_url'=>$data['redirect_url']??null,'order_id'=>$orderId]); $payment->meta=$meta; $payment->save(); return $payment;
    }

    public function handleWebhook(Request $request): void
    {
        $p=$request->all(); $oid=Arr::get($p,'order_id'); $sc=Arr::get($p,'status_code'); $ga=Arr::get($p,'gross_amount'); $sig=Arr::get($p,'signature_key'); $k=(string) config('midtrans.server_key'); $exp=hash('sha512',$oid.$sc.$ga.$k);
        if (! $oid || ! hash_equals($exp,(string)$sig)) abort(403,'Invalid signature');
        $payment=Payment::where('gateway',$this->key())->where('transaction_id',$oid)->first();
        PaymentLog::create(['payment_id'=>$payment?->id,'gateway'=>$this->key(),'event'=>Arr::get($p,'transaction_status'),'payload'=>$p,'signature'=>$sig,'ip_address'=>$request->ip()]);
        if (! $payment) return; $status=Arr::get($p,'transaction_status'); $fraud=Arr::get($p,'fraud_status');
        $completed = ($status==='settlement') || ($status==='capture' && $fraud==='accept');
        if ($completed) {
            if ($payment->status!=='completed'){
                $payment->status='completed'; $payment->paid_at=now(); $payment->meta=array_merge($payment->meta??[],['midtrans'=>$p]); $payment->save();
                $inv=$payment->invoice; if ($inv && $inv->status!=='paid'){ $inv->status='paid'; $inv->paid_at=now(); $inv->save(); event(new InvoicePaid($inv)); }
            }
        } else if (in_array($status,['pending','cancel','expire','deny','refund','partial_refund'])){ $map=['pending'=>'pending','cancel'=>'failed','expire'=>'failed','deny'=>'failed','refund'=>'refunded','partial_refund'=>'refunded']; $payment->status=$map[$status]??$payment->status; $payment->save(); }
    }
}

