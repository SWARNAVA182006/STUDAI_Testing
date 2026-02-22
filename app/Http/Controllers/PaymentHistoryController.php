<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentHistoryController extends Controller
{
    /**
     * Display payment history page
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $transactions = $user->paymentTransactions()
            ->with('subscriptionPlan')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('payments.history', compact('transactions'));
    }

    /**
     * Show single transaction details
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        $transaction = $user->paymentTransactions()
            ->with('subscriptionPlan')
            ->findOrFail($id);
        
        return view('payments.show', compact('transaction'));
    }
}
