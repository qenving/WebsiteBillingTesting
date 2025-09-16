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

class DuitkuGateway extends AbstractGateway implements PaymentGateway
{
    public function key(): string { return 'duitku'; }
    public function displayName(): string { return 'Duitku'; }
    protected function isSandbox(): bool { return (bool) config('duitku.sandbox', true); }
    protected function baseUrl(): string { return $this->isSandbox()? 'https://api-sandbox.duitku.com':'https://api-prod.duitku.com'; }
    public function createPayment(Invoice $invoice, array $options = []): Payment
    {
        $merchantCode=(string) config('duitku.merchant_code'); $apiKey=(string) config('duitku.api_key'); if(! $merchantCode||!$apiKey) throw new \RuntimeException('Duitku not configured');
        $p=$invoice->payments()->create(['gateway'=>$this->key(),'amount'=>$invoice->total,'currency'=>$invoice->currency,'status'=>'pending']);
        $merchantOrderId=$invoice->number.'-PAY'.$p->id; $amount=(int)round($invoice->total); $signature=md5($merchantCode.$merchantOrderId.$amount.$apiKey);
        $payload=['merchantCode'=>$merchantCode,'paymentAmount'=>$amount,'merchantOrderId'=>$merchantOrderId,'productDetails'=>'Invoice '.$invoice->number,'email'=>optional($invoice->client->user)->email,'customerVaName'=>optional($invoice->client->user)->name,'callbackUrl'=>url('/webhook/payments/duitku'),'returnUrl'=>url('/invoices/'.$invoice->id),'signature'=>$signature,'expiryPeriod'=>60];
        $cli=new Client(['timeout'=>30]); $resp=$cli->post($this->baseUrl().'/api/merchant/createInvoice',['headers'=>['Accept'=>'application/json','Content-Type'=>'application/json'],'json'=>$payload]); $data=json_decode((string)$resp->getBody(),true); $url=Arr::get($data,'paymentUrl');
        $p->transaction_id=$merchantOrderId; $meta=$p->meta??[]; $meta['payment_url']=$url; $meta['redirect_url']=$url; $meta['duitku']=$data; $p->meta=$meta; $p->save(); return $p;
    }
    public function handleWebhook(Request $request): void
    {
        $payload=$request->all(); $merchantCode=(string) config('duitku.merchant_code'); $apiKey=(string) config('duitku.api_key');
        $merchantOrderId=Arr::get($payload,'merchantOrderId'); $amount=(int)Arr::get($payload,'amount'); $statusCode=(string)Arr::get($payload,'resultCode'); $signature=(string)Arr::get($payload,'signature'); $expected=md5($merchantCode.$amount.$merchantOrderId.$statusCode.$apiKey);
        if(! hash_equals($expected,$signature)) abort(403,'Invalid signature');
        $payment=Payment::where('gateway',$this->key())->where('transaction_id',$merchantOrderId)->first(); PaymentLog::create(['payment_id'=>$payment?->id,'gateway'=>$this->key(),'event'=>$statusCode,'payload'=>$payload,'ip_address'=>$request->ip()]); if(! $payment) return;
        if($statusCode==='00'){ if($payment->status!=='completed'){ $payment->status='completed'; $payment->paid_at=now(); $payment->meta=array_merge($payment->meta??[],['duitku'=>$payload]); $payment->save(); $inv=$payment->invoice; if($inv && $inv->status!=='paid'){ $inv->status='paid'; $inv->paid_at=now(); $inv->save(); event(new InvoicePaid($inv)); } }} else { $payment->status='failed'; $payment->meta=array_merge($payment->meta??[],['duitku'=>$payload]); $payment->save(); }
    }
}

