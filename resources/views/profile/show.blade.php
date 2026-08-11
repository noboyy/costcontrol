@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Profil</span>
@endsection

@section('content')
<script>window.location.replace('{{ route('profil') }}');</script>
<noscript>
    <meta http-equiv="refresh" content="0; url={{ route('profil') }}">
    <div class="card"><div class="card-body"><a href="{{ route('profil') }}">Buka halaman profil</a></div></div>
</noscript>
@endsection
