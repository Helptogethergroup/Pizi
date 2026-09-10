<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::where('owner_id', auth()->id())->latest()->paginate(20);
        return view('owner.invoices.index', compact('invoices'));
    }
}
