@extends('layouts.app')
@section('title', 'Transactions')

@section('content')
<div class="page-head">
    <div>
        <h1>Transaction history</h1>
        <p class="sub">All your bKash, Nagad and card payments. Download a PDF invoice for any paid transaction.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('billing.index') }}">Plans &amp; credits</a>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Invoice</th><th>Item</th><th>Method</th><th>Transaction ID</th><th class="right">Amount</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($payments as $payment)
                <tr>
                    <td class="nowrap">{{ ($payment->paid_at ?? $payment->created_at)->format('d M Y') }}</td>
                    <td class="nowrap">{{ $payment->invoice_no }}</td>
                    <td>{{ $payment->description }}</td>
                    <td class="nowrap">{{ $payment->method ? $payment->methodLabel() : '—' }}<div class="small muted">{{ $payment->account }}</div></td>
                    <td class="small">{{ $payment->transaction_id ?? '—' }}</td>
                    <td class="right nowrap"><strong>৳{{ number_format($payment->amount) }}</strong></td>
                    <td><span class="badge badge-{{ $payment->statusColor() }}">{{ $payment->statusLabel() }}</span></td>
                    <td class="nowrap right">
                        @if ($payment->isPaid())
                            <a class="btn btn-secondary btn-sm" href="{{ route('invoices.show', $payment) }}">Invoice</a>
                            <a class="btn btn-ghost btn-sm" href="{{ route('invoices.pdf', $payment) }}"><x-icon name="download"/> PDF</a>
                        @elseif (in_array($payment->status, ['pending', 'failed', 'awaiting_otp']))
                            <a class="btn btn-secondary btn-sm" href="{{ route('checkout.show', $payment) }}">{{ $payment->status === 'failed' ? 'Try again' : 'Continue' }}</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty"><h3>No transactions yet</h3><p>Upgrade your plan or buy credits to see payments here.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt">{{ $payments->links('partials.pagination') }}</div>
</div>
@endsection
