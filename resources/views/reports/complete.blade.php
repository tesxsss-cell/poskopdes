@extends('layouts.app')
@section('title', 'Laporan Lengkap Usaha Toko')
@section('content')
<div class="complete-report">
    <header class="report-header">
        <div><p class="report-kicker">RINGKASAN USAHA TOKO</p><h1>Laporan lengkap</h1><p>{{ $branch?->name ?? 'Toko' }} · Periode {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }}–{{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}</p></div>
        <div class="report-actions no-print">
            <a href="{{ route('reports.expenses.create') }}" class="btn-secondary">Catat biaya</a>
            @if ($print)<button type="button" class="btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
            @else<a class="btn-primary" target="_blank" rel="noopener" href="{{ route('reports.complete', ['dari' => $from, 'sampai' => $to, 'cetak' => 1]) }}">Buka cetak / PDF</a>@endif
        </div>
    </header>
    @if ($print)<p class="report-print-help no-print">Pilih “Simpan sebagai PDF” pada dialog cetak. Semua baris barang, pergerakan dan biaya untuk periode ini ditampilkan.</p>@endif
    <div class="report-warning" role="note"><strong>Belum diverifikasi sebagai laba bersih atau SHU.</strong> Nilai setelah biaya hanya mengurangi biaya yang tercatat dalam aplikasi. Periksa biaya yang belum dimasukkan, pendapatan lain, pajak, penyusutan dan penyesuaian sebelum digunakan sebagai laporan resmi. Laporan ini belum diverifikasi terhadap format SIMKOPDES.</div>
    <div class="report-filters no-print">
        <form method="GET" action="{{ route('reports.complete') }}" class="report-filter-form">
            <strong>Rentang tanggal</strong>
            <label>Dari<input class="field" type="date" name="dari" value="{{ $from }}" required></label>
            <label>Sampai<input class="field" type="date" name="sampai" value="{{ $to }}" required></label>
            <button class="btn-secondary" type="submit">Tampilkan</button>
        </form>
        <form method="GET" action="{{ route('reports.complete') }}" class="report-filter-form">
            <strong>Pilih bulan</strong>
            <label>Bulan<input class="field" type="month" name="bulan" value="{{ $month ?: substr($from, 0, 7) }}" required></label>
            <button class="btn-secondary" type="submit">Tampilkan bulan</button>
        </form>
    </div>
    <section aria-labelledby="ringkasan-heading" class="report-section">
        <h2 id="ringkasan-heading">Ringkasan periode</h2>
        <div class="report-metrics">
            @foreach ([['Penjualan', $summary['revenue']], ['HPP barang terjual', $summary['cogs']], ['Laba kotor', $summary['gross_profit']], ['Biaya tercatat', $expenseTotal], ['Hasil setelah biaya tercatat', $summary['gross_profit'] - $expenseTotal]] as [$label, $amount])
                <div class="report-metric"><span>{{ $label }}</span><strong>Rp {{ number_format((float) $amount, 2, ',', '.') }}</strong></div>
            @endforeach
        </div>
        <p class="report-explanation">{{ number_format($summary['transactions'], 0, ',', '.') }} transaksi selesai. Laba kotor = penjualan − HPP. Hasil setelah biaya tercatat = laba kotor − biaya tercatat.</p>
    </section>
    <section class="report-section" aria-labelledby="produk-heading">
        <div class="report-section-heading"><div><h2 id="produk-heading">Barang dan pergerakan</h2><p>Barang masuk dan keluar sesuai periode. Stok dan harga jual menunjukkan kondisi saat laporan dibuka, bukan saldo historis akhir periode.</p></div></div>
        <div class="report-table-wrap" tabindex="0" aria-label="Tabel laporan, dapat digeser ke samping"><table data-server-table class="report-table"><thead><tr><th>SKU / Barcode</th><th>Barang / Kategori</th><th>Satuan</th><th>Harga jual saat ini</th><th>Masuk</th><th>Keluar</th><th>Stok saat ini</th><th>Penjualan</th><th>HPP</th><th>Laba kotor</th></tr></thead><tbody>
            @forelse ($products as $product)
                <tr><td>{{ $product->sku }}<br>{{ $product->barcode ?: '—' }}</td><td>{{ $product->name }}<br><span>{{ $product->category ?? 'Tanpa kategori' }}{{ $product->deleted_at ? ' · Diarsipkan' : ($product->is_active ? '' : ' · Nonaktif') }}</span></td><td>{{ $product->unit }}</td><td>Rp {{ number_format((float) $product->sell_price, 2, ',', '.') }}</td><td>{{ number_format((int) $product->qty_in, 0, ',', '.') }}</td><td>{{ number_format((int) $product->qty_out, 0, ',', '.') }}</td><td>{{ number_format((int) $product->stock_now, 0, ',', '.') }}</td><td>Rp {{ number_format((float) $product->revenue, 2, ',', '.') }}</td><td>Rp {{ number_format((float) $product->cogs, 2, ',', '.') }}</td><td>Rp {{ number_format((float) $product->gross_profit, 2, ',', '.') }}</td></tr>
            @empty <tr><td colspan="10">Belum ada data barang.</td></tr> @endforelse
        </tbody></table></div>
        <p class="report-scroll-hint no-print">Geser tabel ke samping untuk melihat semua kolom.</p>
        @unless($print)<div class="report-pagination no-print">{{ $products->links() }}</div>@endunless
    </section>
    <section class="report-section" aria-labelledby="movement-heading">
        <h2 id="movement-heading">Rincian barang masuk dan keluar</h2><p>Nilai adalah biaya pergerakan yang tercatat. Saldo menunjukkan stok setelah pergerakan tersebut.</p>
        <div class="report-table-wrap" tabindex="0" aria-label="Tabel pergerakan, dapat digeser ke samping"><table data-server-table class="report-table"><thead><tr><th>Waktu</th><th>Barang</th><th>Jenis</th><th>Jumlah</th><th>Saldo setelah</th><th>Nilai biaya</th><th>Petugas / Catatan</th></tr></thead><tbody>
            @forelse ($movementRows as $movement)<tr><td>{{ \Illuminate\Support\Carbon::parse($movement->created_at)->format('d/m/Y H:i') }}</td><td>{{ $movement->sku }} · {{ $movement->name }}</td><td>{{ $movement->quantity > 0 ? 'Masuk' : 'Keluar' }}</td><td>{{ number_format(abs((int) $movement->quantity), 0, ',', '.') }} {{ $movement->unit }}</td><td>{{ number_format((int) $movement->balance_after, 0, ',', '.') }} {{ $movement->unit }}</td><td>{{ $movement->cost_total === null ? 'Belum tercatat' : 'Rp '.number_format((float) $movement->cost_total, 2, ',', '.') }}</td><td>{{ $movement->user_name ?? 'Sistem' }}<br><span>{{ $movement->note ?? '—' }}</span></td></tr>
            @empty<tr><td colspan="7">Belum ada pergerakan pada periode ini.</td></tr>@endforelse
        </tbody></table></div>
        <p class="report-scroll-hint no-print">Geser tabel ke samping untuk melihat semua kolom.</p>
        @unless($print)<div class="report-pagination no-print">{{ $movementRows->links() }}</div>@endunless
    </section>
    <section class="report-section" aria-labelledby="sales-heading">
        <h2 id="sales-heading">Penjualan per hari</h2><p>Hanya transaksi yang telah selesai pada periode pilihan.</p>
        <div class="report-table-wrap" tabindex="0" aria-label="Tabel laporan, dapat digeser ke samping"><table data-server-table class="report-table"><thead><tr><th>Tanggal</th><th>Transaksi</th><th>Penjualan</th><th>HPP</th><th>Laba kotor</th></tr></thead><tbody>
            @forelse ($series as $row)<tr><td>{{ \Illuminate\Support\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td><td>{{ $row->transaksi }}</td><td>Rp {{ number_format((float) $row->penjualan, 2, ',', '.') }}</td><td>Rp {{ number_format((float) $row->hpp, 2, ',', '.') }}</td><td>Rp {{ number_format((float) $row->laba_kotor, 2, ',', '.') }}</td></tr>
            @empty<tr><td colspan="5">Belum ada penjualan dalam periode ini.</td></tr>@endforelse
        </tbody></table></div>
    </section>
    <section class="report-section" aria-labelledby="cost-heading">
        <h2 id="cost-heading">Biaya tercatat</h2><p>Data ini belum dapat memastikan seluruh biaya periode sudah dicatat. Pembelian barang dagangan tidak dicatat sebagai biaya di sini agar HPP tidak dihitung dua kali.</p>
        <div class="report-category-list">
            @forelse($expenseCategories as $category)<div><span>{{ $categories[$category->category] ?? $category->category }}</span><strong>Rp {{ number_format((float) $category->total, 2, ',', '.') }}</strong></div>
            @empty<p>Belum ada biaya tercatat pada periode ini; bukan berarti tidak ada biaya.</p>@endforelse
        </div>
        <div class="report-table-wrap" tabindex="0" aria-label="Tabel laporan, dapat digeser ke samping"><table data-server-table class="report-table"><thead><tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th>Jumlah</th>@unless($print)<th class="no-print">Aksi</th>@endunless</tr></thead><tbody>
            @forelse($expenses as $expense)<tr><td>{{ $expense->expense_date->format('d/m/Y') }}</td><td>{{ $categories[$expense->category] ?? $expense->category }}</td><td>{{ $expense->description }}</td><td>Rp {{ number_format((float) $expense->amount, 2, ',', '.') }}</td>@unless($print)<td class="no-print"><div class="report-row-actions"><a href="{{ route('reports.expenses.edit', $expense) }}">Ubah</a><form method="POST" action="{{ route('reports.expenses.destroy', $expense) }}" onsubmit="return confirm('Hapus biaya ini dari laporan?')">@csrf @method('DELETE')<button type="submit">Hapus</button></form></div></td>@endunless</tr>
            @empty<tr><td colspan="{{ $print ? 4 : 5 }}">Belum ada biaya tercatat.</td></tr>@endforelse
        </tbody></table></div>
        @unless($print)<div class="report-pagination no-print">{{ $expenses->links() }}</div>@endunless
    </section>
    <footer class="report-footer">Dicetak / dilihat {{ now()->format('d/m/Y H:i') }} · Data usaha toko, bukan laporan konsolidasi koperasi.</footer>
</div>
@endsection
