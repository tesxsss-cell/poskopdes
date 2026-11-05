@extends('layouts.app')
@section('title', $expense->exists ? 'Ubah Biaya' : 'Catat Biaya')
@section('content')
<section class="panel expense-form-card">
    <h1>{{ $expense->exists ? 'Ubah biaya' : 'Catat biaya usaha' }}</h1>
    <p>Catat biaya untuk periode yang sesuai. Jangan memasukkan pembelian barang dagangan di sini: biayanya sudah diperhitungkan dalam HPP ketika barang terjual.</p>
    <form method="POST" action="{{ $expense->exists ? route('reports.expenses.update', $expense) : route('reports.expenses.store') }}">
        @csrf
        @if ($expense->exists) @method('PUT') @endif
        <label for="expense-date">Tanggal biaya<input class="field" type="date" id="expense-date" name="expense_date" value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d') ?? now()->toDateString()) }}" required></label>
        <label for="expense-category">Kategori<select class="field" id="expense-category" name="category" required>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $code => $label)<option value="{{ $code }}" @selected(old('category', $expense->category) === $code)>{{ $label }}</option>@endforeach
        </select></label>
        <label for="expense-description">Keterangan<input class="field" id="expense-description" name="description" value="{{ old('description', $expense->description) }}" maxlength="255" required></label>
        <label for="expense-amount">Jumlah (Rp)<input class="field" id="expense-amount" name="amount" type="number" min="0.01" max="999999999999" step="0.01" inputmode="decimal" value="{{ old('amount', $expense->amount) }}" required></label>
        <p class="expense-form-note">Masukkan angka tanpa pemisah ribuan; gunakan titik untuk desimal, misalnya 125000.50. Pastikan biaya belum pernah dicatat.</p>
        <div class="expense-form-actions"><button class="btn-primary" type="submit">Simpan biaya</button><a class="btn-secondary" href="{{ route('reports.complete') }}">Batal</a></div>
    </form>
</section>
@endsection
