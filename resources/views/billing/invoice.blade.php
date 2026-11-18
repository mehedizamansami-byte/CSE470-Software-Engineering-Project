@extends('layouts.app')
@section('title', 'Invoice '.$payment->invoice_no)

@section('content')
<div class="page-head no-print">
    <div>
        <h1>Invoice {{ $payment->invoice_no }}</h1>
        <p class="sub">Print it, or download the PDF copy.</p>
    </div>
    <div class="row">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
        @if ($payment->isPaid())
            <a class="btn" href="{{ route('invoices.pdf', $payment) }}"><x-icon name="download"/> Download PDF</a>
        @endif
    </div>
</div>

<div class="card invoice">
    <div class="inv-head">
        <div>
            <div class="row"><span class="brand-logo" style="padding:0;color:var(--text)"><span class="mark">B</span> BrandBondhu</span></div>
            <p class="muted small mt">AI branding for Bangladeshi sellers<br>Dhaka, Bangladesh</p>
        </div>
        <div class="right">
            <h2 style="margin:0">INVOICE</h2>
            <div class="small muted">{{ $payment->invoice_no }}</div>
            <div class="small muted">Date: {{ ($payment->paid_at ?? $payment->created_at)->format('d M Y, h:i A') }}</div>
            <div class="mt">
                @if ($payment->isPaid()) <span class="stamp">PAID</span> @else <span class="badge badge-{{ $payment->statusColor() }}">{{ $payment->statusLabel() }}</span> @endif
            </div>
        </div>
    </div>

    <hr>
    <div class="grid grid-2">
        <div>
            <div class="small muted">Billed to</div>
            <strong>{{ $payment->user->name }}</strong>
            <div class="small">{{ $payment->user->email }}</div>
            @if ($payment->user->shop) <div class="small">{{ $payment->user->shop->name }}</div> @endif
        </div>
        <div>
            <div class="small muted">Payment</div>
            <div class="small">Method: <strong>{{ $payment->method ? $payment->methodLabel() : '—' }}</strong> {{ $payment->account }}</div>
            <div class="small">Transaction ID: <strong>{{ $payment->transaction_id ?? '—' }}</strong></div>
        </div>
    </div>

    <div class="table-wrap mt">
        <table>
            <thead><tr><th>Description</th><th class="right">Qty</th><th class="right">Amount</th></tr></thead>
            <tbody>
                <tr><td>{{ $payment->description }}</td><td class="right">1</td><td class="right">৳{{ number_format($payment->amount, 2) }}</td></tr>
                <tr><td></td><td class="right"><strong>Total</strong></td><td class="right"><strong>৳{{ number_format($payment->amount, 2) }}</strong></td></tr>
            </tbody>
        </table>
    </div>
    <p class="small muted mt">This invoice is for a simulated payment made in the BrandBondhu CSE470 demo. No real money was charged.</p>
</div>
@endsection
