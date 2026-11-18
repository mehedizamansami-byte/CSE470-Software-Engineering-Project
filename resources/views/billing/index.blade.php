@extends('layouts.app')
@section('title', 'Plans & credits')

@section('content')
@php($plan = $subscription->plan)
<div class="page-head">
    <div>
        <h1>Plans and AI credits</h1>
        <p class="sub">Every AI action uses credits from your wallet. Pay safely with bKash, Nagad or card (simulated, no real money).</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('billing.transactions') }}"><x-icon name="receipt"/> Transactions</a>
</div>

<div class="grid grid-main">
    <div class="card">
        <div class="card-head">
            <h2>Current plan: {{ $plan->name }}</h2>
            <span class="badge badge-green">Active</span>
        </div>
        <div class="grid grid-2">
            <div class="stat">
                <span class="label">AI credits left</span>
                <span class="value">{{ $credits }}</span>
                <div class="meter {{ $credits < $threshold ? 'low' : '' }}"><span style="width: {{ min(100, (int) round($credits / max($plan->monthly_credits, $credits, 1) * 100)) }}%"></span></div>
                <span class="hint">{{ $plan->monthly_credits }} credits included per month</span>
            </div>
            <div class="stat">
                <span class="label">{{ $subscription->ends_at ? 'Renews / ends' : 'Billing' }}</span>
                <span class="value" style="font-size:18px">{{ $subscription->ends_at ? $subscription->ends_at->format('d M Y') : 'Free forever' }}</span>
                <span class="hint">Started {{ $subscription->starts_at?->format('d M Y') }}</span>
            </div>
        </div>
        @if ($credits < $threshold)
            <div class="alert alert-warn mt" style="margin-bottom:0">⚠️ Low credits: only {{ $credits }} left. Upgrade your plan or buy a credit pack below.</div>
        @endif
    </div>

    <div class="card">
        <h2>What uses credits?</h2>
        <ul class="list">
            <li><span>Generate a brand kit</span><strong>{{ $costs['brand_kit'] }}</strong></li>
            <li><span>Regenerate one kit asset</span><strong>{{ $costs['brand_asset'] }}</strong></li>
            <li><span>Generate a style guide</span><strong>{{ $costs['style_guide'] }}</strong></li>
            <li><span>Generate 3 captions + hashtags</span><strong>{{ $costs['caption'] }}</strong></li>
        </ul>
    </div>
</div>

<h2 class="mt" style="margin-top:28px">Choose a plan</h2>
<div class="grid grid-4">
    @foreach ($plans as $p)
        <div class="card plan {{ $p->id === $plan->id ? 'current' : '' }}">
            @if ($p->id === $plan->id) <span class="badge ribbon">Current</span> @elseif ($p->slug === 'premium') <span class="badge badge-amber ribbon">Popular</span> @endif
            <h3 style="font-size:17px">{{ $p->name }}</h3>
            <div class="price">{{ $p->isFree() ? 'Free' : '৳'.number_format($p->price) }} @unless ($p->isFree())<small>/ month</small>@endunless</div>
            <ul>
                @foreach ($p->features ?? [] as $feature) <li>{{ $feature }}</li> @endforeach
            </ul>
            @if ($p->id === $plan->id)
                <button class="btn btn-secondary" disabled>Your plan</button>
            @else
                <form method="POST" action="{{ route('billing.subscribe', $p) }}" @if ($p->isFree()) data-confirm="Switch to the Free plan now? Your remaining credits stay in your wallet." @endif>
                    @csrf
                    <button class="btn {{ $p->isFree() ? 'btn-secondary' : '' }}" type="submit" style="width:100%">{{ $p->isFree() ? 'Switch to Free' : ($p->price > $plan->price ? 'Upgrade' : 'Switch').' to '.$p->name }}</button>
                </form>
            @endif
        </div>
    @endforeach
</div>

<div class="grid grid-2 mt">
    <div class="card">
        <h2>Buy extra credits</h2>
        <p class="muted">One-time packs. Credits never expire.</p>
        <form method="POST" action="{{ route('billing.credits') }}">
            @csrf
            <div class="stack">
                @foreach ($packs as $key => $pack)
                    <label class="check" style="border:1px solid var(--line);border-radius:10px;padding:12px;margin:0">
                        <input type="radio" name="pack" value="{{ $key }}" @checked($loop->first)>
                        <span style="flex:1"><strong>{{ $pack['credits'] }} credits</strong></span>
                        <strong>৳{{ number_format($pack['price']) }}</strong>
                    </label>
                @endforeach
            </div>
            <div class="actions"><button class="btn" type="submit">Continue to payment</button></div>
        </form>
    </div>

    <div class="card">
        <h2>Credit history</h2>
        <ul class="list">
            @forelse ($history as $row)
                <li>
                    <span>{{ $row->reason }}<br><span class="small muted">{{ $row->created_at->format('d M, h:i A') }} · balance {{ $row->balance_after }}</span></span>
                    <strong class="{{ $row->amount > 0 ? 'yes' : '' }}" style="{{ $row->amount < 0 ? 'color:var(--err)' : '' }}">{{ $row->amount > 0 ? '+' : '' }}{{ $row->amount }}</strong>
                </li>
            @empty
                <li class="muted">No credit activity yet.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
