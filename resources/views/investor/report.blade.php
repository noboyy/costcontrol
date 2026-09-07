@extends('layouts.investor')

@section('title', 'Laporan — '.$project->nama_project)

@section('content')
@php $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'); @endphp

<div class="head">
    <div class="head-row">
        <div>
            <h1 class="h1">Laporan</h1>
            <div class="meta">{{ $project->nama_project }} · {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>
        </div>
    </div>
    <div style="margin-top:16px;">
        <form method="GET" action="{{ route('investor.report') }}" class="filter">
            <div class="field">
                <label for="from">Dari</label>
                <input type="date" id="from" name="from" class="finput" value="{{ $from }}" required>
            </div>
            <div class="field">
                <label for="to">Sampai</label>
                <input type="date" id="to" name="to" class="finput" value="{{ $to }}" required>
            </div>
            <button type="submit" class="btn btn-pri"><i class="bi bi-funnel"></i> Tampilkan</button>
        </form>
    </div>
</div>

<div class="cols-3">
    <div class="card">
        <div class="mini-label">Pendapatan</div>
        <div class="mini-val pos">{{ $rp($totalIncome) }}</div>
    </div>
    <div class="card">
        <div class="mini-label">Biaya</div>
        <div class="mini-val neg">{{ $rp($totalCost) }}</div>
    </div>
    <div class="card">
        <div class="mini-label">Margin</div>
        <div class="mini-val {{ $margin >= 0 ? 'pos' : 'neg' }}">{{ $rp($margin) }}</div>
    </div>
</div>

@if($byCost->isEmpty() && $byIncome->isEmpty())
<div class="card" style="margin-top:16px;">
    <div class="empty">
        <i class="bi bi-inbox"></i>
        <p>Tidak ada transaksi pada periode ini.</p>
    </div>
</div>
@else
<section class="sec">
    <div class="cols-2">
        @if($byCost->isNotEmpty())
        <div class="card" style="padding:6px 0;">
            <div class="hero-label" style="padding:0 16px 6px;">Biaya per Kategori</div>
            <div style="overflow-x:auto;">
                <table class="table-min">
                    <tbody>
                        @foreach($byCost as $label => $amount)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="num"><span class="pct">{{ $totalCost > 0 ? number_format(($amount/$totalCost)*100,1,',','.').'%' : '' }}</span></td>
                            <td class="num neg">{{ $rp($amount) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td class="num">100%</td>
                            <td class="num neg">{{ $rp($totalCost) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif
        @if($byIncome->isNotEmpty())
        <div class="card" style="padding:6px 0;">
            <div class="hero-label" style="padding:0 16px 6px;">Pendapatan per Kategori</div>
            <div style="overflow-x:auto;">
                <table class="table-min">
                    <tbody>
                        @foreach($byIncome as $label => $amount)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="num"><span class="pct">{{ $totalIncome > 0 ? number_format(($amount/$totalIncome)*100,1,',','.').'%' : '' }}</span></td>
                            <td class="num pos">{{ $rp($amount) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td class="num">100%</td>
                            <td class="num pos">{{ $rp($totalIncome) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @endif
    </div>
</section>
@endif
@endsection
