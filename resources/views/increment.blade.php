@extends('layouts.app')
@section('title', 'Beranda')
@section('content')
    <section class="card">
        <h2>POS Kopdes — Increment {{ config('pos.increment') }}</h2>
        <p>Modul tersedia bertahap sesuai rencana pengembangan. Gunakan menu yang sudah aktif.</p>
        @if (auth()->user()->isKasir() && config('pos.increment') < 3)
            <p>POS dan shift kasir tersedia pada Increment 3. Akun Kasir sudah dapat diuji untuk login dan pembatasan hak
                akses.</p>
        @endif
        @if (auth()->user()->isKasir() && config('pos.increment') >= 3)
            <a href="{{ route('pos.index') }}">Buka POS dan shift</a>
        @endif
        @if (config('pos.increment') >= 2 && auth()->user()->isPengelola())
            <h3>Peringatan stok minimum</h3>
            <ul>
                @forelse($lowStocks as $stock)
                <li>{{ $stock->product?->name }}: {{ $stock->quantity }}</li>@empty<li>Tidak ada stok minimum.</li>
                @endforelse
            </ul>
            <a href="{{ route('reports.stock-card') }}">Lihat kartu stok</a>
        @endif
    </section>
@endsection
