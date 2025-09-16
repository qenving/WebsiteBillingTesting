<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function plans(){ return Product::where('is_active',true)->get(); }
    public function create(Request $request)
    {
        $data=$request->validate(['product_id'=>'required|exists:products,id','billing_cycle'=>'sometimes|string|in:monthly,quarterly,semiannually,annually']);
        $user=Auth::user(); if(! $user) abort(401);
        $client=Client::firstOrCreate(['user_id'=>$user->id]); $product=Product::findOrFail($data['product_id']);
        return DB::transaction(function() use($client,$product,$data){
            $service=Service::create(['client_id'=>$client->id,'product_id'=>$product->id,'status'=>'pending','billing_cycle'=>$data['billing_cycle']??'monthly','meta'=>['driver'=>'virtfusion','plan'=>$product->options]]);
            $subtotal=$product->base_price;
            $taxRate=(float) \App\Support\Settings::get('finance.tax_rate',0);
            $taxTotal=$taxRate>0?round($subtotal*($taxRate/100)):0;
            $discount=0;
            $total=$subtotal-$discount+$taxTotal;
            $invoice=Invoice::create(['client_id'=>$client->id,'status'=>'unpaid','subtotal'=>$subtotal,'tax_total'=>$taxTotal,'discount_total'=>$discount,'tax_rate'=>$taxRate,'total'=>$total,'currency'=>$product->currency,'due_date'=>now()->addDay()]);
            InvoiceItem::create(['invoice_id'=>$invoice->id,'product_id'=>$product->id,'service_id'=>$service->id,'description'=>$product->name.' ('.$service->billing_cycle.')','quantity'=>1,'unit_price'=>$product->base_price,'discount'=>$discount,'total'=>$product->base_price-$discount]);
            return ['invoice_id'=>$invoice->id,'invoice_number'=>$invoice->number,'total'=>$invoice->total,'currency'=>$invoice->currency];
        });
    }
}
