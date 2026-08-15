@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <a href="{{ route('cost-centers.index') }}">Unit Bisnis</a>
    <span class="sep">/</span>
    <a href="{{ route('cost-centers.show', $project->id_project) }}">{{ $project->nama_project }}</a>
    <span class="sep">/</span>
    <span class="current">Galeri</span>
@endsection

@section('content')
@php
    $routeName = $prefix === 'projects' ? 'projects' : 'cost-centers';
    $canEdit = in_array(auth()->user()->role ?? '', ['ADMIN', 'SUPER ADMIN']);
@endphp

<div class="page-header">
    <div>
        <h2><i class="bi bi-images"></i> Galeri — {{ $project->nama_project }}</h2>
        <p style="color:var(--text-secondary);font-size:14px;">
            {{ $items->count() }} file
            @if($labelFilter) · filter: <strong>{{ $labelFilter }}</strong> @endif
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route("{$routeName}.show", $project->id_project) }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        @if($canEdit)
            <button class="btn btn-primary" onclick="openModal('addGalleryModal')">
                <i class="bi bi-cloud-upload"></i> Unggah File
            </button>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
@endif

{{-- Filter Label --}}
@if($labels->count() > 0)
<div class="toolbar" style="margin-bottom:16px;">
    <div class="toolbar-left" style="flex-wrap:wrap;gap:8px;">
        <a href="{{ route("{$routeName}.gallery", $project->id_project) }}"
           class="btn btn-sm {{ !$labelFilter ? 'btn-primary' : 'btn-outline' }}">
            Semua
        </a>
        @foreach($labels as $lbl)
            <a href="{{ route("{$routeName}.gallery", ['id' => $project->id_project, 'label' => $lbl]) }}"
               class="btn btn-sm {{ $labelFilter === $lbl ? 'btn-primary' : 'btn-outline' }}">
                {{ $lbl }}
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- Gallery Grid --}}
@if($items->isEmpty())
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <i class="bi bi-images" style="font-size:2.5rem;color:var(--text-muted);display:block;margin-bottom:12px;"></i>
                <p>Belum ada file di galeri ini.</p>
                @if($canEdit)
                    <button class="btn btn-primary" onclick="openModal('addGalleryModal')">
                        <i class="bi bi-cloud-upload"></i> Unggah File Pertama
                    </button>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="gallery-grid">
        @foreach($items as $item)
        <div class="gallery-item" data-type="{{ $item->file_type }}">
            {{-- Thumbnail --}}
            <div class="gallery-thumb" onclick="previewItem({{ $item->id_gallery }}, '{{ $item->file_type }}', '{{ route("{$routeName}.gallery.serve", [$project->id_project, $item->id_gallery]) }}', @js($item->original_name))">
                @if($item->file_type === 'image')
                    <img src="{{ route("{$routeName}.gallery.serve", [$project->id_project, $item->id_gallery]) }}"
                         alt="{{ $item->original_name }}" loading="lazy">
                @elseif($item->file_type === 'video')
                    <div class="gallery-thumb-placeholder video">
                        <i class="bi bi-play-circle-fill"></i>
                    </div>
                @else
                    <div class="gallery-thumb-placeholder document">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                    </div>
                @endif
            </div>

            {{-- Info --}}
            <div class="gallery-info">
                <div class="gallery-label">
                    <span class="badge badge-blue" style="font-size:11px;">{{ $item->label }}</span>
                    <span class="gallery-size">{{ $item->fileSizeHuman() }}</span>
                </div>
                @if($item->caption)
                    <div class="gallery-caption">{{ $item->caption }}</div>
                @endif
                <div class="gallery-meta">
                    {{ $item->created_at->format('d M Y, H:i') }}
                </div>
            </div>

            {{-- Actions --}}
            <div class="gallery-actions">
                @if($item->file_type === 'document')
                    <button class="btn btn-xs btn-outline" title="Lihat PDF"
                        onclick="previewItem({{ $item->id_gallery }}, 'document', '{{ route("{$routeName}.gallery.serve", [$project->id_project, $item->id_gallery]) }}', @js($item->original_name))">
                        <i class="bi bi-eye"></i>
                    </button>
                    <a href="{{ route("{$routeName}.gallery.serve", [$project->id_project, $item->id_gallery]) }}"
                       target="_blank" class="btn btn-xs btn-outline" title="Buka di tab baru">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                @else
                    <button class="btn btn-xs btn-outline" title="Lihat"
                        onclick="previewItem({{ $item->id_gallery }}, '{{ $item->file_type }}', '{{ route("{$routeName}.gallery.serve", [$project->id_project, $item->id_gallery]) }}', @js($item->original_name))">
                        <i class="bi bi-eye"></i>
                    </button>
                @endif
                @if($canEdit)
                    <form action="{{ route("{$routeName}.gallery.destroy", [$project->id_project, $item->id_gallery]) }}"
                          method="POST" data-confirm="Hapus file {{ $item->original_name }}?">
                        @csrf
                        <button type="submit" class="btn btn-xs btn-ghost" style="color:var(--danger);" title="Hapus">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>
@endif

{{-- Modal Upload --}}
@if($canEdit)
<div class="modal-backdrop" id="addGalleryModal">
    <div class="modal modal-md">
        <form action="{{ route("{$routeName}.gallery.store", $project->id_project) }}"
              method="POST" enctype="multipart/form-data" id="galleryUploadForm">
            @csrf
            <div class="modal-header">
                <h3><i class="bi bi-cloud-upload"></i> Unggah File ke Galeri</h3>
                <button type="button" class="modal-close" onclick="closeModal('addGalleryModal')">×</button>
            </div>
            <div class="modal-body">
                {{-- File Input --}}
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">File <span style="color:var(--danger)">*</span></label>
                    <div id="dropZone" class="gallery-drop-zone" onclick="document.getElementById('galleryFile').click()">
                        <i class="bi bi-cloud-upload" style="font-size:2rem;color:var(--text-muted);display:block;margin-bottom:8px;"></i>
                        <div id="dropZoneText">
                            <strong>Klik untuk pilih file</strong> atau drag &amp; drop<br>
                            <span class="form-hint" style="display:inline;font-size:12px;">Foto: jpg/png/webp (maks 5MB) &middot; Video: mp4/mov (maks 50MB) &middot; PDF (maks 10MB)</span>
                        </div>
                        <div id="filePreviewWrap" style="display:none;margin-top:12px;"></div>
                    </div>
                    <input type="file" id="galleryFile" name="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,application/pdf" style="display:none;" required>
                </div>

                {{-- Label --}}
                <div class="form-group" style="margin-bottom:16px;position:relative;">
                    <label class="form-label">Kategori / Label <span class="req">*</span></label>
                    <input type="text" name="label" id="galleryLabel" class="form-input"
                           placeholder="Contoh: Foto Progress, Bukti Transfer, Kontrak..."
                           maxlength="100" required autocomplete="off">
                    <div id="labelDropdown" style="display:none;position:absolute;left:0;right:0;top:100%;z-index:100;background:var(--card-bg,#fff);border:1px solid var(--border);border-top:none;border-radius:0 0 6px 6px;max-height:200px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,0.1);"></div>
                    <div class="form-hint">Ketik bebas atau pilih dari label yang pernah dipakai.</div>
                </div>

                {{-- Caption --}}
                <div class="form-group">
                    <label class="form-label">Keterangan <span style="color:var(--text-muted);font-weight:normal;">(opsional)</span></label>
                    <input type="text" name="caption" class="form-input"
                           placeholder="Deskripsi singkat file ini..." maxlength="500">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addGalleryModal')">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnUploadGallery">
                    <i class="bi bi-cloud-upload"></i> Unggah
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Lightbox Preview Modal --}}
<div class="modal-backdrop" id="galleryPreviewModal" onclick="closeGalleryPreview(event)">
    <div class="modal modal-lg" style="max-width:900px;background:transparent;box-shadow:none;padding:0;" onclick="event.stopPropagation()">
        <div style="position:relative;">
            <button type="button" onclick="closeModal('galleryPreviewModal')"
                    style="position:absolute;top:-40px;right:0;background:none;border:none;color:#fff;font-size:28px;cursor:pointer;z-index:10;">×</button>
            <div id="previewContent" style="display:flex;justify-content:center;align-items:center;min-height:200px;"></div>
            <div id="previewName" style="text-align:center;color:rgba(255,255,255,0.7);font-size:13px;margin-top:8px;"></div>
        </div>
    </div>
</div>

@push('styles')
<style>
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.gallery-item {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.15s;
}
.gallery-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.gallery-thumb {
    height: 150px;
    overflow: hidden;
    cursor: pointer;
    background: var(--bg-secondary, #f8f9fa);
    display: flex;
    align-items: center;
    justify-content: center;
}
.gallery-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.2s;
}
.gallery-thumb:hover img {
    transform: scale(1.04);
}
.gallery-thumb-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    font-size: 3rem;
}
.gallery-thumb-placeholder.video { color: #3b82f6; background: #eff6ff; }
.gallery-thumb-placeholder.document { color: #ef4444; background: #fef2f2; }
.gallery-info {
    padding: 10px 12px 4px;
    flex: 1;
}
.gallery-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 4px;
}
.gallery-size {
    font-size: 11px;
    color: var(--text-muted, #9ca3af);
}
.gallery-caption {
    font-size: 12px;
    color: var(--text-secondary);
    margin-bottom: 4px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.gallery-meta {
    font-size: 11px;
    color: var(--text-muted, #9ca3af);
}
.gallery-actions {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 8px 12px;
    border-top: 1px solid var(--border);
    background: var(--bg-secondary, #f8f9fa);
}
.gallery-actions form { margin: 0; }
.gallery-drop-zone {
    border: 2px dashed var(--border);
    border-radius: var(--radius);
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
}
.gallery-drop-zone:hover,
.gallery-drop-zone.dragover {
    border-color: var(--primary, #3b82f6);
    background: var(--primary-light, #eff6ff);
}
#previewContent img {
    max-width: 100%;
    max-height: 75vh;
    border-radius: 8px;
}
#previewContent video {
    max-width: 100%;
    max-height: 75vh;
    border-radius: 8px;
}
</style>
@endpush

@push('scripts')
<script>
// ---- Upload modal: file preview ----
document.getElementById('galleryFile')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const wrap = document.getElementById('filePreviewWrap');
    const text = document.getElementById('dropZoneText');
    wrap.innerHTML = '';
    wrap.style.display = 'block';
    text.style.display = 'none';

    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
            wrap.innerHTML = `<img src="${e.target.result}" style="max-height:140px;max-width:100%;border-radius:6px;object-fit:contain;">
                <div style="font-size:12px;color:var(--text-secondary);margin-top:6px;">${file.name}</div>`;
        };
        reader.readAsDataURL(file);
    } else if (file.type.startsWith('video/')) {
        wrap.innerHTML = `<div style="font-size:2rem;color:#3b82f6;"><i class="bi bi-film"></i></div>
            <div style="font-size:12px;color:var(--text-secondary);margin-top:4px;">${file.name}</div>`;
    } else {
        wrap.innerHTML = `<div style="font-size:2rem;color:#ef4444;"><i class="bi bi-file-earmark-pdf-fill"></i></div>
            <div style="font-size:12px;color:var(--text-secondary);margin-top:4px;">${file.name}</div>`;
    }
});

// ---- Drag and drop ----
const dropZone = document.getElementById('dropZone');
if (dropZone) {
    ['dragenter','dragover'].forEach(ev => dropZone.addEventListener(ev, e => {
        e.preventDefault(); dropZone.classList.add('dragover');
    }));
    ['dragleave','drop'].forEach(ev => dropZone.addEventListener(ev, e => {
        e.preventDefault(); dropZone.classList.remove('dragover');
    }));
    dropZone.addEventListener('drop', e => {
        const file = e.dataTransfer.files[0];
        if (file) {
            const input = document.getElementById('galleryFile');
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            input.dispatchEvent(new Event('change'));
        }
    });
}

// ---- Label autocomplete custom dropdown ----
(function() {
    const input = document.getElementById('galleryLabel');
    const dropdown = document.getElementById('labelDropdown');
    if (!input || !dropdown) return;

    // Label default + label dari server
    const defaultLabels = ['Foto Progress', 'Bukti Transfer', 'Bukti Pembayaran', 'Kontrak', 'Foto Sebelum', 'Foto Sesudah', 'Dokumen Lainnya'];
    let serverLabels = @json($labels->values());
    // Gabung, hilangkan duplikat
    let allLabels = [...new Set([...serverLabels, ...defaultLabels])];

    function showDropdown(query) {
        const q = query.toLowerCase().trim();
        const filtered = q
            ? allLabels.filter(l => l.toLowerCase().includes(q))
            : allLabels;

        if (filtered.length === 0) { dropdown.style.display = 'none'; return; }

        dropdown.innerHTML = filtered.map(l =>
            `<div class="label-option" style="padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border);"
                  onmousedown="event.preventDefault();"
                  onclick="selectLabel(this, '${l.replace(/'/g,"\\'")}')">
                ${l}
             </div>`
        ).join('');
        dropdown.style.display = 'block';
    }

    input.addEventListener('focus', () => showDropdown(input.value));
    input.addEventListener('input', () => showDropdown(input.value));
    input.addEventListener('blur', () => setTimeout(() => { dropdown.style.display = 'none'; }, 150));

    // Fetch dari server untuk update real-time
    fetch('{{ route("{$routeName}.gallery.labels", $project->id_project) }}')
        .then(r => r.json())
        .then(labels => {
            serverLabels = labels;
            allLabels = [...new Set([...labels, ...defaultLabels])];
        })
        .catch(() => {});
})();

function selectLabel(el, val) {
    document.getElementById('galleryLabel').value = val;
    document.getElementById('labelDropdown').style.display = 'none';
}

// ---- Lightbox preview ----
function previewItem(id, type, url, name) {
    const content = document.getElementById('previewContent');
    const nameEl = document.getElementById('previewName');
    content.innerHTML = '';
    nameEl.textContent = name;

    if (type === 'image') {
        const img = document.createElement('img');
        img.src = url;
        img.alt = name;
        content.appendChild(img);
    } else if (type === 'video') {
        const video = document.createElement('video');
        video.src = url;
        video.controls = true;
        video.autoplay = false;
        content.appendChild(video);
    } else if (type === 'document') {
        const wrap = document.createElement('div');
        wrap.style.cssText = 'width:100%;display:flex;flex-direction:column;align-items:center;gap:12px;';
        wrap.innerHTML = `
            <iframe src="${url}" style="width:min(860px,90vw);height:75vh;border:none;border-radius:8px;background:#fff;"></iframe>
            <a href="${url}" target="_blank" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,0.4);">
                <i class="bi bi-box-arrow-up-right"></i> Buka di tab baru
            </a>`;
        content.appendChild(wrap);
    }

    openModal('galleryPreviewModal');
}

function closeGalleryPreview(event) {
    if (event.target.id === 'galleryPreviewModal') {
        // Stop video playback before closing
        const video = document.querySelector('#previewContent video');
        if (video) { video.pause(); video.src = ''; }
        closeModal('galleryPreviewModal');
    }
}

// Stop video when modal closed via × button
document.getElementById('galleryPreviewModal')?.addEventListener('modal:close', function() {
    const video = document.querySelector('#previewContent video');
    if (video) { video.pause(); video.src = ''; }
});
</script>
@endpush
@endsection
