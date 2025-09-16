<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    public function index()
    {
        $products = Product::where('is_active', true)->orderBy('base_price')->get();
        return view('shop.index', [
            'products' => $products,
            'isAuthed' => Auth::check(),
        ]);
    }
}

