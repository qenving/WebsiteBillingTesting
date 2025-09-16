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

class XenditGateway extends AbstractGateway implements PaymentGateway
{
    public function key(): string { return 'xendit'; }
    public function displayName(): string { return 'Xendit'; }
    protected function apiKey(): string { return (string) config('xendit.api_key'); }
    public function createPayment(Invoice $invoice, array $options = []): Payment
    {
        $p=$invoice->payments()->create(['gateway'=>$this->key(),'amount'=>$invoice->total,'currency'=>$invoice->currency,'status'=>'pending']);
        $externalId=$invoice->number.'-PAY'.$p->id; $payload=['external_id'=>$externalId,'amount'=>(int)round($invoice->total),'payer_email'=>optional($invoice->client->user)->email,'description'=>'Invoice '.$invoice->number,'success_redirect_url'=>url('/invoices/'.$invoice->id),'currency'=>strtoupper($invoice->currency??'IDR')];
        $cli=new Client(['timeout'=>30]); $resp=$cli->post('https://api.xendit.co/v2/invoices',['headers'=>['Authorization'=>'Basic '.base64_encode($this->apiKey().':'),'Content-Type'=>'application/json','Accept'=>'application/json'],'json'=>$payload]); $data=json_decode((string)$resp->getBody(),true);
        $p->transaction_id=$externalId; $meta=$p->meta??[]; $meta['xendit_id']=$data['id']??null; $meta['invoice_url']=$data['invoice_url']??null; $meta['redirect_url']=$data['invoice_url']??null; $p->meta=$meta; $p->save(); return $p;
    }
    public function handleWebhook(Request $request): void
    {
        $token=$request->header('X-Callback-Token')??$request->header('x-callback-token'); $expected=(string) config('xendit.callback_token'); if(! $expected || ! hash_equals($expected,(string)$token)) abort(403,'Invalid callback token');
        $payload=$request->all(); $externalId=Arr::get($payload,'external_id'); $status=strtoupper((string)Arr::get($payload,'status'));
        $payment=Payment::where('gateway',$this->key())->where('transaction_id',$externalId)->first();
        PaymentLog::create(['payment_id'=>$payment?->id,'gateway'=>$this->key(),'event'=>$status,'payload'=>$payload,'ip_address'=>$request->ip()]); if(! $payment) return;
        if(in_array($status,['PAID','SETTLED'],true)){
            if($payment->status!=='completed'){ $payment->status='completed'; $payment->paid_at=now(); $payment->meta=array_merge($payment->meta??[],['xendit'=>$payload]); $payment->save(); $inv=$payment->invoice; if($inv && $inv->status!=='paid'){ $inv->status='paid'; $inv->paid_at=now(); $inv->save(); event(new InvoicePaid($inv)); } }
        } elseif(in_array($status,['EXPIRED','FAILED','CANCELLED'],true)) { $payment->status='failed'; $payment->meta=array_merge($payment->meta??[],['xendit'=>$payload]); $payment->save(); }
    }
}

