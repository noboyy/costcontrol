@extends('layouts.investor')

@section('title', 'Galeri — '.$project->nama_project)

@section('content')
@php $filterLabels = $labels->filter(fn ($l) => $l !== null && $l !== ''); @endphp

<div class="head">
    <div class="head-row">
        <div>
            <h1 class="h1">Galeri</h1>
            <div class="meta">{{ $project->nama_project }} · {{ $items->count() }} file @if($labelFilter)· filter: <strong>{{ $labelFilter }}</strong>@endif</div>
        </div>
    </div>
</div>

@if($items->isEmpty())
<div class="card">
    <div class="empty">
        <i class="bi bi-images"></i>
        <p>Belum ada file di galeri ini.</p>
    </div>
</div>
@else
<nav class="pills" style="margin-bottom:18px;">
    <a href="{{ route('cost-centers.gallery', $project->id_project) }}" class="pill {{ !$labelFilter ? 'active' : '' }}">Semua</a>
    @foreach($filterLabels as $lbl)
    <a href="{{ route('cost-centers.gallery', ['id' => $project->id_project, 'label' => $lbl]) }}" class="pill {{ $labelFilter === $lbl ? 'active' : '' }}">{{ $lbl }}</a>
    @endforeach
</nav>

<div class="gal-grid">
    @foreach($items as $item)
    @php $serve = route('cost-centers.gallery.serve', [$project->id_project, $item->id_gallery]); @endphp
    <div class="gal-card" onclick="gPreview('{{ $item->file_type }}','{{ $serve }}',@js($item->original_name))" role="button" tabindex="0" onkeydown="if(event.key==='Enter')this.click()">
        <div class="gal-thumb">
            @if($item->file_type === 'image')
            <img src="{{ $serve }}" alt="{{ $item->original_name }}" loading="lazy">
            @elseif($item->file_type === 'video')
            <i class="bi bi-play-circle-fill"></i>
            @else
            <i class="bi bi-file-earmark-pdf-fill"></i>
            @endif
        </div>
        <div class="gal-info">
            <div class="gal-top">
                <span class="gal-chip">{{ $item->label ?: 'Tanpa label' }}</span>
                <span class="gal-size">{{ $item->fileSizeHuman() }}</span>
            </div>
            @if($item->caption)
            <div class="gal-cap">{{ $item->caption }}</div>
            @endif
            <div class="gal-date">{{ $item->created_at->format('d M Y, H:i') }}</div>
        </div>
    </div>
    @endforeach
</div>

<div id="gPreviewWrap" style="display:none;position:fixed;inset:0;z-index:100;background:rgba(8,18,15,.88);align-items:center;justify-content:center;flex-direction:column;padding:26px;">
    <div style="position:absolute;top:14px;right:18px;color:#e8f1ec;font-size:26px;cursor:pointer;line-height:1;" onclick="gClose()" aria-label="Tutup">&times;</div>
    <div id="gPreviewBox" style="display:flex;justify-content:center;align-items:center;width:100%;max-height:78vh;"></div>
    <div id="gPreviewName" style="color:rgba(232,241,236,.75);font-size:13px;margin-top:12px;text-align:center;"></div>
    <a id="gPreviewOpen" href="#" target="_blank" rel="noopener" style="display:none;margin-top:10px;color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.35);padding:7px 14px;border-radius:9px;font-size:13px;">Buka di tab baru <i class="bi bi-box-arrow-up-right"></i></a>
</div>
@endif

<style>
.gal-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:16px; }
.gal-card { background:var(--surface); border:1px solid var(--line); border-radius:12px; overflow:hidden; cursor:pointer; display:flex; flex-direction:column; transition:box-shadow .15s ease, transform .15s ease; }
.gal-card:hover { box-shadow:0 10px 22px -14px rgba(8,30,24,.35); transform:translateY(-2px); }
.gal-thumb { height:150px; overflow:hidden; background:linear-gradient(135deg,#e9f3ee,#dcebe3); display:flex; align-items:center; justify-content:center; }
.gal-thumb img { width:100%; height:100%; object-fit:cover; transition:transform .2s; }
.gal-card:hover .gal-thumb img { transform:scale(1.04); }
.gal-thumb i { font-size:2.6rem; }
.gal-thumb i.bi-play-circle-fill { color:#2f9e77; }
.gal-thumb i.bi-file-earmark-pdf-fill { color:#d24b5e; }
.gal-info { padding:10px 12px 12px; }
.gal-top { display:flex; align-items:center; justify-content:space-between; gap:8px; }
.gal-chip { display:inline-block; font-size:11px; font-weight:600; color:#3f5b53; background:#eef4f1; padding:2px 9px; border-radius:999px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:70%; }
.gal-size { font-size:11px; color:var(--muted-2); white-space:nowrap; }
.gal-cap { font-size:12.5px; color:var(--muted); margin-top:6px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.gal-date { font-size:11px; color:var(--muted-2); margin-top:5px; }
#gPreviewBox img, #gPreviewBox video { max-width:100%; max-height:76vh; border-radius:10px; }
#gPreviewBox iframe { width:min(880px,94vw); height:76vh; border:none; border-radius:10px; background:#fff; }
</style>

<script>
function gPreview(type, url, name) {
    const box = document.getElementById('gPreviewBox');
    const nameEl = document.getElementById('gPreviewName');
    const openEl = document.getElementById('gPreviewOpen');
    box.innerHTML = '';
    nameEl.textContent = name;
    openEl.style.display = type === 'document' ? 'inline-flex' : 'none';
    openEl.href = url;
    if (type === 'image') {
        const img = document.createElement('img'); img.src = url; img.alt = name; box.appendChild(img);
    } else if (type === 'video') {
        const v = document.createElement('video'); v.src = url; v.controls = true; box.appendChild(v);
    } else {
        const f = document.createElement('iframe'); f.src = url; box.appendChild(f);
    }
    document.getElementById('gPreviewWrap').style.display = 'flex';
}
function gClose() {
    const box = document.getElementById('gPreviewBox');
    const v = box.querySelector('video'); if (v) { v.pause(); v.src = ''; }
    box.innerHTML = '';
    document.getElementById('gPreviewWrap').style.display = 'none';
}
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const wrap = document.getElementById('gPreviewWrap');
        if (wrap && wrap.style.display === 'flex') gClose();
    }
});
</script>
@endsection
