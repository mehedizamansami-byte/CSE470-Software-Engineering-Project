<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice {{ $payment->invoice_no }}</title>
<style>
    @page { margin: 36px 42px; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 12px; color: #1f2433; }
    .brand { font-size: 22px; font-weight: bold; color: #4f46e5; }
    .muted { color: #667085; }
    table { width: 100%; border-collapse: collapse; }
    .items th { background: #f4f5fb; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; color: #667085; }
    .items td { padding: 10px 8px; border-bottom: 1px solid #e4e7ef; }
    .right { text-align: right; }
    .stamp { border: 3px solid #067647; color: #067647; padding: 4px 12px; font-weight: bold; font-size: 16px; letter-spacing: 2px; }
    .box { background: #f8f9fc; border: 1px solid #e4e7ef; padding: 10px; }
    .total td { font-size: 14px; font-weight: bold; border-bottom: 0; }
</style>
</head>
<body>
<table>
    <tr>
        <td>
            <div class="brand">BrandBondhu</div>
            <div class="muted">AI branding for Bangladeshi sellers<br>Dhaka, Bangladesh</div>
        </td>
        <td class="right">
            <div style="font-size:20px;font-weight:bold">INVOICE</div>
            <div class="muted">{{ $payment->invoice_no }}</div>
            <div class="muted">Date: {{ ($payment->paid_at ?? $payment->created_at)->format('d M Y, h:i A') }}</div>
            <div style="margin-top:10px"><span class="stamp">PAID</span></div>
        </td>
    </tr>
</table>

<table style="margin-top:24px">
    <tr>
        <td style="width:50%;vertical-align:top;padding-right:10px">
            <div class="box">
                <div class="muted">Billed to</div>
                <strong>{{ $payment->user->name }}</strong><br>
                {{ $payment->user->email }}<br>
                @if ($payment->user->shop) {{ $payment->user->shop->name }} @endif
            </div>
        </td>
        <td style="width:50%;vertical-align:top;padding-left:10px">
            <div class="box">
                <div class="muted">Payment details</div>
                Method: <strong>{{ $payment->methodLabel() }}</strong><br>
                Account: {{ $payment->account }}<br>
                Transaction ID: <strong>{{ $payment->transaction_id }}</strong>
            </div>
        </td>
    </tr>
</table>

<table class="items" style="margin-top:24px">
    <thead><tr><th>Description</th><th class="right">Qty</th><th class="right">Amount (BDT)</th></tr></thead>
    <tbody>
        <tr><td>{{ $payment->description }}</td><td class="right">1</td><td class="right">{{ number_format($payment->amount, 2) }}</td></tr>
        <tr class="total"><td></td><td class="right">Total</td><td class="right">BDT {{ number_format($payment->amount, 2) }}</td></tr>
    </tbody>
</table>

<p class="muted" style="margin-top:40px">Thank you for using BrandBondhu.<br>
This invoice is for a simulated payment made in the BrandBondhu CSE470 demo. No real money was charged.</p>
</body>
</html>
