@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('investor.index') }}">{{ $project->nama_project }}</a>
    <span class="sep">/</span>
    <span class="current">Biaya</span>
@endsection

@section('content')
@include('investor._nav')

<div class="page-header">
    <div>
        <h2>Biaya</h2>
        <p>Daftar biaya keberangkatan (read-only).</p>
    </div>
    <div class="page-actions">
        <form method="GET" action="{{ route('investor.costs') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Dari</label>
                <input type="date" class="form-input" name="from" value="{{ $from }}">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Sampai</label>
                <input type="date" class="form-input" name="to" value="{{ $to }}">
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Terapkan</button>
            @if($from || $to)
            <a href="{{ route('investor.costs') }}" class="btn btn-outline">Atur Ulang</a>
            @endif
        </form>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left"></div>
    <div class="toolbar-right">
        <span class="stat-inline"><strong>{{ $entries->count() }}</strong> transaksi ·
            Total: <strong class="money negative">Rp {{ number_format($total, 0, ',', '.') }}</strong></span>
    </div>
</div>

<div class="card">
    <div class="card-body compact">
        @if($entries->isEmpty())
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>Belum ada biaya tercatat.</p>
        </div>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Kategori</th>
                        <th class="text-end">Jumlah</th>
                        <th class="text-end">Harga Satuan</th>
                        <th class="text-end">Total</th>
                        <th>Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $e)
                    <tr>
                        <td class="whitespace-nowrap">{{ $e->tanggal?->format('d M Y') }}</td>
                        <td>
                            <div>{{ $e->keterangan ?: '—' }}</div>
                            @if($e->costType?->nama)<div class="cell-sub">{{ $e->costType->nama }}</div>@endif
                            @if($e->catatan)<div class="cell-sub" style="font-style:italic;">{{ $e->catatan }}</div>@endif
                        </td>
                        <td class="cell-sub">{{ $e->costType?->kategori ?? '—' }}</td>
                        <td class="text-end">{{ $e->qty }} {{ $e->unit ?? '' }}</td>
                        <td class="text-end">Rp {{ number_format($e->harga_satuan, 0, ',', '.') }}</td>
                        <td class="text-end money negative">Rp {{ number_format($e->total, 0, ',', '.') }}</td>
                        <td>
                            @foreach($e->gallery as $g)
                            <a href="{{ route('cost-centers.gallery.serve', [$project->id_project, $g->id_gallery]) }}" target="_blank" rel="noopener" title="{{ $g->original_name }}" style="display:inline-block;margin-right:4px;vertical-align:middle;">
                                @if($g->file_type === 'image')
                                <img src="{{ route('cost-centers.gallery.serve', [$project->id_project, $g->id_gallery]) }}" alt="{{ $g->original_name }}" style="width:34px;height:34px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
                                @else
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:6px;border:1px solid var(--border);background:#f1f5f9;color:var(--text-secondary);">
                                    <i class="bi bi-{{ $g->file_type === 'video' ? 'film' : 'file-earmark' }}"></i>
                                </span>
                                @endif
                            </a>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
