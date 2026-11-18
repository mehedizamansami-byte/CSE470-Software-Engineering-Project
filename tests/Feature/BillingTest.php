<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Feature 5 (plans and credits) and Feature 6 (simulated payments and invoices). */
class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_upgrade_with_bkash_otp_and_pin(): void
    {
        $seller = User::factory()->create();
        $basic = Plan::where('slug', 'basic')->first();

        $this->actingAs($seller)->get('/billing')->assertOk()->assertSee('Premium');

        $this->actingAs($seller)->post("/billing/plans/{$basic->id}")->assertRedirect();
        $payment = Payment::first();
        $this->assertSame(499, $payment->amount);

        $this->actingAs($seller)->post("/checkout/{$payment->id}", ['method' => 'bkash', 'wallet' => '123'])->assertSessionHasErrors('wallet');
        $this->actingAs($seller)->post("/checkout/{$payment->id}", ['method' => 'bkash', 'wallet' => '01712345678'])
            ->assertRedirect(route('checkout.verify', $payment));
        $otp = session('demo_code');

        $wrong = $otp === '000000' ? '111111' : '000000';
        $this->actingAs($seller)->post("/checkout/{$payment->id}/verify", ['otp' => $wrong, 'pin' => '12345'])->assertSessionHasErrors('otp');
        $this->actingAs($seller)->post("/checkout/{$payment->id}/verify", ['otp' => $otp, 'pin' => '123'])->assertSessionHasErrors('pin');
        $this->actingAs($seller)->post("/checkout/{$payment->id}/verify", ['otp' => $otp, 'pin' => '12345'])
            ->assertRedirect(route('invoices.show', $payment));

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertStringStartsWith('BK', $payment->transaction_id);
        $this->assertSame('017****5678', $payment->account);
        $this->assertSame(150, $seller->fresh()->ai_credits);

        $this->actingAs($seller)->get("/invoices/{$payment->id}")->assertOk()->assertSee('PAID');
        $pdf = $this->actingAs($seller)->get("/invoices/{$payment->id}/pdf");
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->actingAs($seller)->get('/billing/transactions')->assertOk()->assertSee($payment->invoice_no);
    }

    public function test_card_payment_validates_luhn_and_three_wrong_otps_fail(): void
    {
        $seller = User::factory()->create();
        $this->actingAs($seller)->post('/billing/credits', ['pack' => 'small']);
        $payment = Payment::first();

        $this->actingAs($seller)->post("/checkout/{$payment->id}", [
            'method' => 'card', 'card_name' => 'Test', 'card_number' => '4242 4242 4242 4241', 'card_expiry' => '12/40', 'card_cvv' => '123',
        ])->assertSessionHasErrors('card_number');

        $this->actingAs($seller)->post("/checkout/{$payment->id}", [
            'method' => 'card', 'card_name' => 'Test', 'card_number' => '4242 4242 4242 4242', 'card_expiry' => '12/40', 'card_cvv' => '123',
        ])->assertRedirect(route('checkout.verify', $payment));
        $this->assertSame('Visa **** 4242', $payment->fresh()->account);

        $otp = session('demo_code');
        $wrong = $otp === '000000' ? '111111' : '000000';
        foreach (range(1, 3) as $i) {
            $this->actingAs($seller)->post("/checkout/{$payment->id}/verify", ['otp' => $wrong]);
        }

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame(0, $seller->fresh()->ai_credits);
    }

    public function test_credit_pack_by_nagad_adds_credits(): void
    {
        $seller = User::factory()->create();
        $this->actingAs($seller)->post('/billing/credits', ['pack' => 'medium']);
        $payment = Payment::first();

        $this->actingAs($seller)->post("/checkout/{$payment->id}", ['method' => 'nagad', 'wallet' => '01812345678']);
        $this->actingAs($seller)->post("/checkout/{$payment->id}/verify", ['otp' => session('demo_code'), 'pin' => '1234'])
            ->assertRedirect(route('invoices.show', $payment));

        $this->assertSame(150, $seller->fresh()->ai_credits);
        $this->assertSame(1, $seller->notifications()->where('data', 'like', '%Payment successful%')->count());
    }

    public function test_other_users_cannot_see_my_invoice(): void
    {
        $seller = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($seller)->post('/billing/credits', ['pack' => 'small']);
        $payment = Payment::first();

        $this->actingAs($other)->get("/checkout/{$payment->id}")->assertNotFound();
        $this->actingAs($other)->get("/invoices/{$payment->id}")->assertNotFound();
    }
}
