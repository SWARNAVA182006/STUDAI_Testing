<?php

namespace Database\Factories;

use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            PaymentTransaction::STATUS_SUCCESS,
            PaymentTransaction::STATUS_PENDING,
            PaymentTransaction::STATUS_PROCESSING,
            PaymentTransaction::STATUS_FAILED,
            PaymentTransaction::STATUS_REFUNDED,
            PaymentTransaction::STATUS_PARTIALLY_REFUNDED,
        ]);

        $initiatedAt = Carbon::instance($this->faker->dateTimeBetween('-1 month', '-1 day'));
        $paidAt = in_array($status, [PaymentTransaction::STATUS_SUCCESS, PaymentTransaction::STATUS_REFUNDED, PaymentTransaction::STATUS_PARTIALLY_REFUNDED])
            ? Carbon::instance($this->faker->dateTimeBetween($initiatedAt, 'now'))
            : null;
        $completedAt = $paidAt && in_array($status, [PaymentTransaction::STATUS_SUCCESS, PaymentTransaction::STATUS_REFUNDED, PaymentTransaction::STATUS_PARTIALLY_REFUNDED])
            ? $paidAt
            : null;
        $failedAt = $status === PaymentTransaction::STATUS_FAILED
            ? Carbon::instance($this->faker->dateTimeBetween($initiatedAt, 'now'))
            : null;
        $refundedAt = in_array($status, [PaymentTransaction::STATUS_REFUNDED, PaymentTransaction::STATUS_PARTIALLY_REFUNDED])
            ? Carbon::instance($this->faker->dateTimeBetween($paidAt ?? $initiatedAt, 'now'))
            : null;

        $amount = $this->faker->randomFloat(2, 9, 299);
        $refundAmount = in_array($status, [PaymentTransaction::STATUS_REFUNDED, PaymentTransaction::STATUS_PARTIALLY_REFUNDED])
            ? $this->faker->randomFloat(2, 1, $amount)
            : null;

        $paymentGateway = $this->faker->randomElement(['razorpay', 'payu', 'stripe']);
        $paymentMethod = $this->faker->randomElement(['card', 'netbanking', 'upi', 'wallet']);

        $metadata = [
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'geo' => [
                'country' => $this->faker->countryCode(),
                'city' => $this->faker->city(),
            ],
        ];

        return [
            'user_id' => User::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'transaction_id' => Str::upper(Str::random(16)),
            'order_id' => Str::upper(Str::random(12)),
            'payment_gateway' => $paymentGateway,
            'amount' => $amount,
            'currency' => $this->faker->randomElement(['USD', 'INR', 'EUR']),
            'gateway_fee' => $this->faker->randomFloat(2, 0.5, 4.5),
            'tax_amount' => $this->faker->randomFloat(2, 0.5, 6.5),
            'status' => $status,
            'payment_method' => $paymentMethod,
            'gateway_response' => $metadata,
            'error_message' => $status === PaymentTransaction::STATUS_FAILED ? $this->faker->sentence() : null,
            'retry_count' => $status === PaymentTransaction::STATUS_FAILED ? $this->faker->numberBetween(0, 3) : 0,
            'refund_amount' => $refundAmount,
            'refund_id' => $refundAmount ? 'REF_' . Str::upper(Str::random(10)) : null,
            'refunded_at' => $refundedAt,
            'paid_at' => $paidAt,
            'notes' => $this->faker->optional()->sentence(),
            'metadata' => Arr::add($metadata, 'attempt', $this->faker->numberBetween(1, 3)),
            'initiated_at' => $initiatedAt,
            'completed_at' => $completedAt,
            'failed_at' => $failedAt,
        ];
    }

    public function successful(): self
    {
        return $this->state(function () {
            $timestamp = Carbon::now()->subDays(2);

            return [
                'status' => PaymentTransaction::STATUS_SUCCESS,
                'paid_at' => $timestamp,
                'completed_at' => $timestamp,
                'failed_at' => null,
                'refund_amount' => null,
                'refunded_at' => null,
            ];
        });
    }

    public function failed(): self
    {
        return $this->state(function () {
            $timestamp = Carbon::now()->subDay();

            return [
                'status' => PaymentTransaction::STATUS_FAILED,
                'paid_at' => null,
                'completed_at' => null,
                'failed_at' => $timestamp,
                'error_message' => 'Payment authorization failed',
                'retry_count' => 1,
            ];
        });
    }

    public function refunded(): self
    {
        return $this->state(function () {
            $paidAt = Carbon::now()->subDays(10);
            $refundedAt = $paidAt->copy()->addDays(3);

            return [
                'status' => PaymentTransaction::STATUS_REFUNDED,
                'paid_at' => $paidAt,
                'completed_at' => $paidAt,
                'refund_amount' => $this->faker->randomFloat(2, 1, 199),
                'refunded_at' => $refundedAt,
                'failed_at' => null,
            ];
        });
    }
}
