<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:newsletters,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('email'),
            ], 422);
        }

        $newsletter = Newsletter::create([
            'email' => $request->email,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed to newsletter!',
        ]);
    }

    public function unsubscribe(Request $request, $token)
    {
        $newsletter = Newsletter::where('token', $token)->firstOrFail();

        $newsletter->update([
            'is_subscribed' => false,
            'unsubscribed_at' => now(),
        ]);

        return view('newsletter.unsubscribed');
    }
}
