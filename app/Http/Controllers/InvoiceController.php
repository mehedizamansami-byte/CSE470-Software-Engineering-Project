<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * CONTROLLER layer: printable invoices and PDF download (Feature 6).
 */
class InvoiceController extends Controller
{
    public function show(Request $request, Payment $payment): View
    {
        $this->ownPayment($request, $payment);

        return view('billing.invoice', ['payment' => $payment->load('user.shop')]);
    }

    public function pdf(Request $request, Payment $payment, PdfService $pdf): Response
    {
        $this->ownPayment($request, $payment);
        abort_unless($payment->isPaid(), 404, 'Only paid payments have an invoice.');

        return $pdf->download('pdf.invoice', ['payment' => $payment->load('user.shop')], "invoice-{$payment->invoice_no}.pdf");
    }

    private function ownPayment(Request $request, Payment $payment): void
    {
        abort_unless((int) $payment->user_id === $request->user()->id, 404);
    }
}
