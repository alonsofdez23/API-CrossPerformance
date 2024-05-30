<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;

class PaymentController extends Controller
{
    public function createCustomer(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        $stripe = new StripeClient(env('STRIPE_SECRET'));

        $ephemeralKey = $stripe->ephemeralKeys->create(
            ['customer' => $user->stripe_id],
            ['stripe_version' => '2022-11-15'],
            // ['stripe_version' => '2020-08-27'],
        );

        return response()->json([
            'customerId' => $user->stripe_id,
            'customerEphemeralKeySecret' => $ephemeralKey->secret,
        ]);
    }

    public function createPaymentIntent(Request $request)
    {
        $user = Auth::user();

        $amount = $request->input('amount');
        $currency = $request->input('currency', 'eur');

        // Convierte el monto a centimos/centavos
        $amountInCents = (int)($amount * 100);

        $stripe = new StripeClient(env('STRIPE_SECRET'));
        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $amountInCents,
            'currency' => $currency,
            'customer' => $user->stripe_id,
        ]);

        return response()->json([
            'paymentIntentClientSecret' => $paymentIntent->client_secret,
        ]);
    }

    public function pagoUnico(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        $stripe = new StripeClient(env('STRIPE_SECRET'));

        $ephemeralKey = $stripe->ephemeralKeys->create(
            ['customer' => $user->stripe_id],
            ['stripe_version' => '2022-11-15'],
            // ['stripe_version' => '2020-08-27'],
        );

        $amount = $request->input('amount');
        $currency = $request->input('currency', 'eur');

        // Convierte el monto a centimos/centavos
        $amountInCents = (int)($amount * 100);

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $amountInCents,
            'currency' => $currency,
            'customer' => $user->stripe_id,
        ]);

        return response()->json([
            'customerId' => $user->stripe_id,
            'customerEphemeralKeySecret' => $ephemeralKey->secret,
            'paymentIntentClientSecret' => $paymentIntent->client_secret,
        ]);
    }

    public function subscribe(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        $user->newSubscription('Full Box', $request->price)->create($request->paymentMethod);

        return response()->json(['message' => 'Suscripción creada']);
    }

    public function cancel()
    {
        $user = Auth::user();

        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        $user->subscription('Full Box')->cancel();

        return response()->json(['message' => 'Suscripción cancelada']);
    }

    public function changeSubscribe(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        $user->subscription('Full Box')->swap($request->price);

        return response()->json(['message' => 'Suscripción cambiada']);
    }

    public function resumeSubscribe(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        $user->subscription('Full Box')->resume();

        return response()->json(['message' => 'Suscripción reactivada']);
    }

    public function customSubscribe(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        if ($user->suscripcion) {
            $user->suscripcion = Carbon::create($user->suscripcion)->addMonths($request->suscripcion);
            $user->save();
        } else {
            $user->suscripcion = Carbon::now()->addMonths($request->suscripcion);
            $user->save();
        }

        // Construir el mensaje condicional
        $mesTexto = $request->suscripcion == 1 ? 'mes' : 'meses';
        $message = "Suscripción de $request->suscripcion $mesTexto activada correctamente";

        return response()->json([
            'message' => $message,
            'user' => $user,
        ], 200);
    }
}
