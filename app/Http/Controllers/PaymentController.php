<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Yabacon\Paystack;

class PaymentController extends Controller
{
    /**
     * Display credit purchase page
     */
    public function index()
    {
        $packages = $this->getCreditPackages();
        $transactions = Transaction::where('user_id', Auth::id())
            ->whereIn('type', ['purchase', 'subscription'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('payments.index', compact('packages', 'transactions'));
    }

    /**
     * Get available credit packages
     */
    protected function getCreditPackages(): array
    {
        return [
            'starter' => [
                'name' => 'Starter Pack',
                'credits' => 100,
                'price' => 9.99,
                'description' => 'Perfect for trying out the platform',
            ],
            'creator' => [
                'name' => 'Creator Pack',
                'credits' => 500,
                'price' => 39.99,
                'description' => 'Best value for regular creators',
                'popular' => true,
            ],
            'pro' => [
                'name' => 'Pro Pack',
                'credits' => 1000,
                'price' => 69.99,
                'description' => 'For power users and professionals',
            ],
            'enterprise' => [
                'name' => 'Enterprise Pack',
                'credits' => 5000,
                'price' => 299.99,
                'description' => 'Maximum credits for businesses',
            ],
        ];
    }

    /**
     * Create Stripe checkout session
     */
    public function createStripeCheckout(Request $request)
    {
        $validated = $request->validate([
            'package' => 'required|string',
        ]);

        $packages = $this->getCreditPackages();
        $package = $packages[$validated['package']] ?? null;

        if (!$package) {
            return redirect()->back()->with('error', 'Invalid package selected');
        }

        try {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $package['name'],
                            'description' => $package['description'] . ' - ' . $package['credits'] . ' credits',
                        ],
                        'unit_amount' => $package['price'] * 100, // Convert to cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payments.stripe-success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payments.index'),
                'client_reference_id' => Auth::id(),
                'metadata' => [
                    'user_id' => Auth::id(),
                    'credits' => $package['credits'],
                    'package' => $validated['package'],
                ],
            ]);

            return redirect($session->url);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle Stripe success callback
     */
    public function stripeSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('payments.index')->with('error', 'Invalid payment session');
        }

        try {
            Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                $userId = $session->metadata->user_id;
                $credits = $session->metadata->credits;
                $amount = $session->amount_total / 100;

                // Add credits to user
                $user = \App\Models\User::find($userId);
                $user->addCredits($credits);

                // Record transaction
                Transaction::create([
                    'user_id' => $userId,
                    'transaction_id' => $session->id,
                    'payment_gateway' => 'stripe',
                    'type' => 'purchase',
                    'amount' => $amount,
                    'currency' => strtoupper($session->currency),
                    'credits' => $credits,
                    'status' => 'completed',
                    'metadata' => json_encode(['package' => $session->metadata->package]),
                ]);

                return redirect()->route('payments.index')->with('success', "Payment successful! {$credits} credits added to your account.");
            }

            return redirect()->route('payments.index')->with('error', 'Payment not completed');

        } catch (\Exception $e) {
            return redirect()->route('payments.index')->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Initialize Paystack payment
     */
    public function createPaystackPayment(Request $request)
    {
        $validated = $request->validate([
            'package' => 'required|string',
        ]);

        $packages = $this->getCreditPackages();
        $package = $packages[$validated['package']] ?? null;

        if (!$package) {
            return redirect()->back()->with('error', 'Invalid package selected');
        }

        try {
            $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
            $user = Auth::user();

            $tranx = $paystack->transaction->initialize([
                'amount' => $package['price'] * 100, // Convert to kobo/cents
                'email' => $user->email,
                'callback_url' => route('payments.paystack-callback'),
                'metadata' => json_encode([
                    'user_id' => $user->id,
                    'credits' => $package['credits'],
                    'package' => $validated['package'],
                ]),
            ]);

            if ($tranx->status) {
                return redirect($tranx->data->authorization_url);
            }

            return redirect()->back()->with('error', 'Payment initialization failed');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle Paystack callback
     */
    public function paystackCallback(Request $request)
    {
        $reference = $request->get('reference');

        if (!$reference) {
            return redirect()->route('payments.index')->with('error', 'Invalid payment reference');
        }

        try {
            $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));
            $tranx = $paystack->transaction->verify(['reference' => $reference]);

            if ($tranx->status && $tranx->data->status === 'success') {
                $metadata = json_decode($tranx->data->metadata);
                $userId = $metadata->user_id;
                $credits = $metadata->credits;
                $amount = $tranx->data->amount / 100;

                // Add credits to user
                $user = \App\Models\User::find($userId);
                $user->addCredits($credits);

                // Record transaction
                Transaction::create([
                    'user_id' => $userId,
                    'transaction_id' => $reference,
                    'payment_gateway' => 'paystack',
                    'type' => 'purchase',
                    'amount' => $amount,
                    'currency' => $tranx->data->currency,
                    'credits' => $credits,
                    'status' => 'completed',
                    'metadata' => json_encode(['package' => $metadata->package]),
                ]);

                return redirect()->route('payments.index')->with('success', "Payment successful! {$credits} credits added to your account.");
            }

            return redirect()->route('payments.index')->with('error', 'Payment not completed');

        } catch (\Exception $e) {
            return redirect()->route('payments.index')->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }
}
