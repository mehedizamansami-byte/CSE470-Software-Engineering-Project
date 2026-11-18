@extends('layouts.app')
@section('title', 'Style guides')

@section('content')
<div class="page-head">
    <div>
        <h1>Brand style guides</h1>
        <p class="sub">A style guide turns a brand kit into clear rules for logos, colours, fonts, voice and social posts. Export it as a PDF for your designers and staff.</p>
    </div>
    <span class="badge badge-blue">Generating costs {{ $cost }} credits</span>
</div>

@if ($kits->isEmpty())
    <div class="card">
        <div class="empty">
            <h3>Create a brand kit first</h3>
            <p>The style guide is built from a brand kit.</p>
            @can('brand_kit') <a class="btn" href="{{ route('brand-kits.create') }}">Generate a brand kit</a> @endcan
        </div>
    </div>
@else
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Brand kit</th><th>Style guide</th><th>Layout</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                @foreach ($kits as $kit)
                    <tr>
                        <td>
                            <div class="row" style="flex-wrap:nowrap">
                                <x-brand-logo :kit="$kit" :size="36"/>
                                <div><strong>{{ $kit->name }}</strong> @if ($kit->is_active) <span class="badge badge-green">Active</span> @endif<div class="small muted">{{ $kit->business_name }}</div></div>
                            </div>
                        </td>
                        <td>{{ $kit->styleGuide?->title ?? 'Not generated yet' }}</td>
                        <td>@if ($kit->styleGuide) <span class="preset-pill">{{ ucfirst($kit->styleGuide->preset) }}</span> @else — @endif</td>
                        <td class="small muted">{{ $kit->styleGuide?->updated_at->diffForHumans() ?? '—' }}</td>
                        <td class="right nowrap">
                            @if ($kit->styleGuide)
                                <a class="btn btn-sm" href="{{ route('style-guides.show', $kit) }}">Open</a>
                                <a class="btn btn-secondary btn-sm" href="{{ route('style-guides.pdf', $kit) }}"><x-icon name="download"/> PDF</a>
                            @else
                                <form method="POST" action="{{ route('style-guides.generate', $kit) }}" class="row" style="justify-content:flex-end;flex-wrap:nowrap">
                                    @csrf
                                    <select name="preset" style="width:auto;padding:6px 8px">
                                        @foreach (\App\Models\StyleGuide::PRESETS as $key => $label) <option value="{{ $key }}" @selected($key === 'modern')>{{ ucfirst($key) }}</option> @endforeach
                                    </select>
                                    <button class="btn btn-sm" type="submit"><x-icon name="sparkles"/> Generate</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
