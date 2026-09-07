@extends('layouts.investor')

@section('title', 'Biaya — '.$project->nama_project)

@section('content')
@php $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'); @endphp

<div class="head">
    <div class="head-row">
        <div>
            <h1 class="h1">Biaya</h1>
            <div class="meta">{{ $project->nama_project }}</div>
        </div>
    </div>
    <div style="margin-top:16px;">
        <form method="GET" action="{{ route('investor.costs') }}" class="filter">
            <div class="field">
                <label for="from">Dari</label>
                <input type="date" id="from" name="from" class="finput" value="{{ $from }}">
            </div>
            <div class="field">
                <label for="to">Sampai</label>
                <input type="date" id="to" name="to" class="finput" value="{{ $to }}">
            </div>
            <button type="submit" class="btn btn-pri"><i class="bi bi-funnel"></i> Terapkan</button>
            @if($from || $to)
            <a href="{{ route('investor.costs') }}" class="btn btn-ghost">Atur Ulang</a>
            @endif
        </form>
    </div>
</div>

@if($entries->isEmpty())
<div class="card">
    <div class="empty">
        <i class="bi bi-inbox"></i>
        <p>Belum ada biaya tercatat.</p>
    </div>
</div>
@else
<div class="summary">
    <span><strong>{{ $entries->count() }}</strong> transaksi</span>
    <span>Total: <strong class="neg">{{ $rp($total) }}</strong></span>
</div>
<div class="card" style="padding:4px 0;">
    <div style="overflow-x:auto;">
        <table class="table-min">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th>Kategori</th>
                    <th class="num">Total</th>
                    <th>Bukti</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $e)
                <tr>
                    <td class="cell-sub" style="white-space:nowrap;padding-top:12px;">{{ $e->tanggal?->format('d M Y') }}</td>
                    <td>
                        <div class="cell">{{ $e->keterangan ?: '—' }}</div>
                        <div class="cell-sub">
                            @if($e->costType?->nama){{ $e->costType->nama }} · @endif
                            {{ (float) $e->qty }} {{ $e->unit ?? '' }} × {{ $rp($e->harga_satuan) }}
                        </div>
                        @if($e->catatan)<div class="cell-ital">{{ $e->catatan }}</div>@endif
                    </td>
                    <td><span class="chip">{{ $e->costType?->kategori ?? '—' }}</span></td>
                    <td class="num neg">{{ $rp($e->total) }}</td>
                    <td>
                        @if($e->gallery->isNotEmpty())
                        <div class="thumbs">
                            @foreach($e->gallery as $g)
                            <a class="thumb" href="{{ route('cost-centers.gallery.serve', [$project->id_project, $g->id_gallery]) }}" target="_blank" rel="noopener" title="{{ $g->original_name }}">
                                @if($g->file_type === 'image')
                                <img src="{{ route('cost-centers.gallery.serve', [$project->id_project, $g->id_gallery]) }}" alt="{{ $g->original_name }}">
                                @else
                                <span class="thumb ic"><i class="bi bi-{{ $g->file_type === 'video' ? 'film' : 'file-earmark' }}"></i></span>
                                @endif
                            </a>
                            @endforeach
                        </div>
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
