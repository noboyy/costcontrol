@php
    $invRow = auth()->user()->investorProject()->with('project')->first();
    $invProj = $invRow?->project;
    $links = [
        ['route' => 'investor.index', 'label' => 'Dashboard', 'icon' => 'grid-1x2'],
        ['route' => 'investor.costs', 'label' => 'Biaya', 'icon' => 'arrow-down-circle'],
        ['route' => 'investor.incomes', 'label' => 'Pendapatan', 'icon' => 'arrow-up-circle'],
        ['route' => 'investor.report', 'label' => 'Laporan', 'icon' => 'file-earmark-bar-graph'],
    ];
@endphp
@if($invProj)
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px;">
    @foreach($links as $link)
    <a href="{{ route($link['route']) }}" class="btn btn-sm {{ request()->routeIs($link['route']) ? 'btn-primary' : 'btn-outline' }}">
        <i class="bi bi-{{ $link['icon'] }}"></i> {{ $link['label'] }}
    </a>
    @endforeach
    <a href="{{ route('cost-centers.gallery', $invProj->id_project) }}" class="btn btn-sm {{ request()->routeIs('cost-centers.gallery') ? 'btn-primary' : 'btn-outline' }}">
        <i class="bi bi-images"></i> Galeri
    </a>
</div>
@endif
