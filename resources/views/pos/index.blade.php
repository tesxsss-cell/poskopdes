@extends('layouts.app')
@section('title', 'Kasir & Shift')
@section('content')
    <div class="cashier-original">
        <section class="page-heading">
            <div><span class="eyebrow">RUANG KASIR</span>
                <h2>Siap melayani pelanggan.</h2>
                <p>Cari barang, periksa pembayaran, lalu simpan transaksi.</p>
            </div><span class="state-tag {{ $shift ? 'is-open' : '' }}"><x-icon
                    name="clock" />{{ $shift ? 'Shift #' . $shift->id . ' berlangsung' : 'Shift belum dibuka' }}</span>
        </section>
        <div class="online-note">Transaksi tersimpan setelah server mengonfirmasi. Harga ditetapkan Pengelola; aplikasi ini
            tidak memiliki mode offline.</div>
        <div id="pos-message" class="pos-feedback" role="status" aria-live="polite">
            {{ $shift ? 'Shift aktif. Siap melayani.' : 'Buka shift terlebih dahulu.' }}</div>
        @if (!$shift)
            <section class="panel open-shift-card"><span class="empty-icon"><x-icon name="clock" /></span>
                <h2 class="text-lg font-semibold">Mulai shift Anda</h2>
                <p class="my-3 text-sm text-slate-500">Masukkan uang awal yang benar-benar ada di laci. Sistem tidak membuka
                    shift otomatis.</p>
                <form id="open-shift-form" action="{{ route('pos.shift.open') }}" method="POST">
                    @csrf
                    <p class="text-sm">Pilih nominal cepat atau masukkan jumlah sendiri:</p>
                    <div class="flex flex-wrap gap-2 my-3" aria-label="Pilihan cepat kas awal">
                        <button type="button" class="btn-secondary" data-opening-cash="500000">Rp 500.000</button>
                        <button type="button" class="btn-secondary" data-opening-cash="5000000">Rp 5.000.000</button>
                        <button type="button" class="btn-secondary" data-opening-cash="10000000">Rp 10.000.000</button>
                        <button type="button" class="btn-secondary" data-opening-cash="15000000">Rp 15.000.000</button>
                    </div>
                    <label>Kas awal (Rp)<input id="opening-cash" class="field" data-rupiah
                            name="opening_cash" type="text" inputmode="decimal" autocomplete="off" maxlength="18"
                            pattern="[0-9]+(\.[0-9]{3})*(,[0-9]{1,2})?"
                            title="Masukkan rupiah, gunakan koma untuk desimal"
                            value="{{ old('opening_cash') !== null ? number_format((float) old('opening_cash'), 2, ',', '.') : '0' }}" required></label>
                    <button class="btn-primary mt-4">Buka shift</button>
                </form>
            </section>
        @else
            <div class="pos-layout pos-single-column">
                <section class="panel pos-catalog-panel">
                    <div class="scanner-card">
                        <div class="panel-heading">
                            <div>
                                <h3>Scanner barcode</h3>
                                <p id="scanner-status" role="status">Aktifkan kamera atau gunakan scanner USB.</p>
                            </div><span class="state-tag" id="scanner-state">Nonaktif</span>
                        </div>
                        <div id="scanner-kasir" class="camera-container"></div>
                        <div class="scanner-buttons"><button type="button" id="camera-start" class="btn-primary">Aktifkan
                                kamera</button><button type="button" id="camera-stop" class="btn-secondary"
                                disabled>Nonaktifkan</button></div>
                        <p class="scanner-help">Kamera HP memerlukan HTTPS dan izin kamera. Pencarian manual dan scanner USB
                            tetap tersedia. Setelah barcode ditambahkan, tunggu 3 detik sebelum scan berikutnya.</p>
                    </div>
                    <div class="panel-heading">
                        <div>
                            <h3>Barang &amp; keranjang</h3>
                            <p>Scan barcode USB atau cari nama dan SKU.</p>
                        </div><x-icon name="box" />
                    </div>
                    <form id="search-form" class="pos-search"><label class="sr-only" for="product-search">Cari nama barang,
                            SKU, atau barcode</label><input id="product-search" class="field-search"
                            placeholder="Scan lalu Enter, atau ketik nama/SKU" autocomplete="off" maxlength="150" autofocus
                            required><button class="btn-primary" type="submit"><x-icon
                                name="search" /><span>Cari</span></button></form>
                    <div id="search-results" class="mb-4 space-y-2"></div>
                    <button type="button" id="cart-toggle" class="cart-toggle" aria-expanded="false"
                        aria-controls="cart-detail"><strong>Keranjang <span id="cart-count">0 barang</span></strong><span
                            id="cart-toggle-label">Lihat detail</span></button>
                    <div id="cart-detail" hidden><button type="button" id="clear-cart" class="text-link">Kosongkan
                            keranjang</button>
                        <div class="table-scroll cart-region">
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th>Harga</th>
                                        <th>Jumlah</th>
                                        <th>Subtotal</th>
                                        <th><span class="sr-only">Aksi</span></th>
                                    </tr>
                                </thead>
                                <tbody id="cart-body"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="cart-total-block"><span>Total belanja</span>
                        <p id="cart-total" class="pos-total">Rp 0</p>
                    </div>
                </section>
                <aside class="pos-payment-column">
                    <section class="panel payment-panel" id="payment-panel">
                        <div class="panel-heading">
                            <div>
                                <h3>Pembayaran</h3>
                                <p>Periksa barang dan nominal. Transaksi tersimpan tidak dapat dibatalkan.</p>
                            </div><x-icon name="calculator" />
                        </div>
                        <form id="checkout-form" class="space-y-3">
                            <label class="block">Nama pelanggan (opsional)<input id="customer-name" class="field"
                                    maxlength="100"></label>
                            <label class="block">Metode pembayaran<select id="payment-method" class="field">
                                    <option value="tunai">Tunai</option>
                                    <option value="qris">QRIS (verifikasi manual)</option>
                                    <option value="transfer">Transfer (verifikasi manual)</option>
                                    <option value="debit">Debit (verifikasi manual)</option>
                                </select></label>
                            <div id="cash-field" class="block">
                                <label for="paid-amount" class="block">Uang diterima (Rp)</label>
                                <div class="flex flex-wrap gap-2 my-2" aria-label="Pilihan cepat uang diterima">
                                    <button type="button" class="btn-secondary" data-paid-amount="50000">Rp 50.000</button>
                                    <button type="button" class="btn-secondary" data-paid-amount="100000">Rp 100.000</button>
                                    <button type="button" class="btn-secondary" data-paid-amount="500000">Rp 500.000</button>
                                    <button type="button" class="btn-secondary" data-paid-amount="1000000">Rp 1.000.000</button>
                                </div>
                                <input id="paid-amount" class="field" type="text" inputmode="numeric"
                                    autocomplete="off" maxlength="15" pattern="[0-9]+(\.[0-9]{3})*"
                                    title="Masukkan jumlah dalam rupiah tanpa desimal" placeholder="Contoh: 50.000" required>
                            </div>
                            <label id="noncash-field" class="hidden text-sm"><input id="payment-confirmed"
                                    type="checkbox"> Pembayaran non-tunai sudah benar-benar diterima dan diperiksa.</label>
                            <label class="block">Catatan (opsional)<input id="sale-note" class="field"
                                    maxlength="255"></label>
                            <div class="payment-change"><span>Perkiraan kembalian</span><strong id="change-preview">Rp
                                    0</strong></div>
                            <button id="checkout-button" type="submit" class="btn-primary-lg">Simpan transaksi <x-icon
                                    name="check" /></button>
                        </form>
                        <button id="retry-button" class="btn-primary mt-3 hidden" type="button">Periksa / kirim ulang
                            permintaan yang sama</button>
                        <p class="mt-3 text-xs text-slate-500">Jika respons terputus, gunakan tombol periksa/kirim ulang.
                            Jangan membuat transaksi baru atau menutup halaman sebelum status jelas.</p>
                    </section>
                    <section class="panel shift-panel" id="shift-panel">
                        <div class="panel-heading">
                            <div>
                                <h3>Kas shift #{{ $shift->id }}</h3>
                                <p>Hitung uang fisik sebelum menutup.</p>
                            </div><x-icon name="clock" />
                        </div>
                        <div class="shift-cash-summary" aria-label="Ringkasan kas shift">
                            <div><span>Kas awal</span><strong>Rp {{ number_format((float) $shift->opening_cash, 2, ',', '.') }}</strong></div>
                            <div><span>Uang tunai diterima</span><strong id="shift-received">Rp {{ number_format($cashSummary['cash_received'], 2, ',', '.') }}</strong></div>
                            <div><span>Kembalian diberikan</span><strong id="shift-change">− Rp {{ number_format($cashSummary['cash_change'], 2, ',', '.') }}</strong></div>
                            <div class="shift-cash-total"><span>Kas menurut sistem</span><strong id="shift-expected">Rp {{ number_format($cashSummary['expected_cash'], 2, ',', '.') }}</strong></div>
                        </div>
                        <p class="shift-cash-help">Kas awal + tunai diterima − kembalian selama shift ini. Pembayaran non-tunai tidak masuk laci.</p>
                        <a href="{{ route('pos.index') }}" class="text-sm text-brand-700 underline"
                            id="refresh-shift">Segarkan data shift</a>
                        <form id="close-shift-form" method="POST" action="{{ route('pos.shift.close') }}"
                            class="mt-3 space-y-3" novalidate>@csrf
                            <label class="block">Kas fisik saat tutup (Rp)<input id="actual-cash" name="actual_cash" class="field" data-rupiah
                                    type="text" inputmode="decimal" autocomplete="off" maxlength="18"
                                    pattern="[0-9]+(\.[0-9]{3})*(,[0-9]{1,2})?"
                                    aria-describedby="shift-cash-note shift-difference shift-form-message"
                                    title="Masukkan rupiah, gunakan koma untuk desimal" placeholder="Contoh: 50.000"
                                    value="{{ old('actual_cash') !== null ? number_format((float) old('actual_cash'), 2, ',', '.') : '' }}" required></label>
                            <button type="button" id="use-expected-cash" class="btn-secondary w-full" disabled>Gunakan nominal kas sistem</button>
                            <p id="shift-cash-note" class="shift-cash-note">Pilihan cepat hanya acuan. Tetap hitung dan sesuaikan dengan uang fisik di laci.</p>
                            <p id="shift-difference" class="shift-difference" aria-live="polite">Masukkan kas fisik untuk melihat selisih.</p>
                            <label class="block">Catatan selisih<input name="note" class="field"
                                    maxlength="255"></label>
                            <p id="shift-form-message" class="shift-form-message" role="alert" hidden></p>
                            <button type="submit" id="close-shift-button" class="btn-secondary w-full" disabled><x-icon
                                    name="clock" /> Tutup shift</button>
                            <noscript>Aktifkan JavaScript untuk menutup shift.</noscript>
                        </form>
                        <p class="mt-3 text-xs text-slate-500">Setiap akun memakai laci kas terpisah. Pastikan seluruh
                            transaksi sudah dikonfirmasi server sebelum shift ditutup.</p>
                    </section>
                </aside>
            </div>
            <dialog id="close-shift-dialog" class="shift-confirm-dialog" aria-labelledby="close-shift-title"
                aria-describedby="close-shift-description">
                <div class="shift-confirm-content">
                    <span class="shift-confirm-icon" aria-hidden="true"><x-icon name="clock" /></span>
                    <h2 id="close-shift-title">Tutup shift ini?</h2>
                    <p id="close-shift-description">Pastikan semua transaksi selesai dan uang fisik di laci sudah dihitung.</p>
                    <div class="shift-confirm-summary">
                        <span>Kas fisik yang dicatat</span>
                        <strong id="close-shift-cash"></strong>
                    </div>
                    <p id="close-shift-comparison" class="shift-confirm-hint"></p>
                    <p class="shift-confirm-hint">Setelah ditutup, Anda perlu membuka shift baru untuk melayani transaksi.</p>
                    <div class="shift-confirm-actions">
                        <button type="button" id="close-shift-cancel" class="btn-secondary">Batal</button>
                        <button type="button" id="close-shift-confirm" class="btn-primary">Ya, tutup shift</button>
                    </div>
                </div>
            </dialog>
        @endif

    </div>
@endsection
@push('scripts')
    <script>
        // Tampilan rupiah memakai titik ribuan dan koma desimal; server menerima angka biasa.
        window.ShiftRupiah = {
            raw(input) {
                return input.value.replace(/\./g, '').replace(',', '.');
            }
        };
        document.querySelectorAll('[data-rupiah]').forEach(input => {
            input.addEventListener('input', () => {
                const position = input.selectionStart ?? input.value.length;
                const before = input.value.slice(0, position).replace(/[^0-9,]/g, '').length;
                const parts = input.value.split(',');
                let integer = parts[0].replace(/\D/g, '').slice(0, 12);
                const decimal = parts.slice(1).join('').replace(/\D/g, '').slice(0, 2);
                if (parts.length > 1 && integer === '') integer = '0';
                input.value = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
                    + (parts.length > 1 ? ',' + decimal : '');
                let index = 0, count = 0;
                while (index < input.value.length && count < before) {
                    if (/[0-9,]/.test(input.value[index])) count++;
                    index++;
                }
                input.setSelectionRange(index, index);
            });
        });
        document.getElementById('open-shift-form')?.addEventListener('submit', () => {
            const input = document.getElementById('opening-cash');
            input.value = window.ShiftRupiah.raw(input);
        });
    </script>
@endpush
@if ($shift)
    @push('scripts')
        <script>
            window.POS_ONLINE_CONFIG = {
                lookup: @json(route('pos.lookup')),
                registerPage: @json(route('pos.products')),
                history: @json(route('pos.history')),
                checkout: @json(route('pos.checkout')),
                shift: {{ $shift->id }},
                cashSummary: @json($cashSummary),
                csrf: @json(csrf_token())
            };
        </script>
        <script src="{{ asset('js/barcode-decoder.js') }}"></script>
        <script src="{{ asset('js/barcode-scanner.js') }}"></script>
        <script src="{{ asset('js/pos-online.js') }}?v=shift-cash-dialog-4"></script>
    @endpush
@endif

@if (!$shift)
    @push('scripts')
        <script>
            document.querySelectorAll('[data-opening-cash]').forEach(button => {
                button.addEventListener('click', () => {
                    const input = document.getElementById('opening-cash');
                    input.value = button.dataset.openingCash;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.focus();
                });
            });
        </script>
    @endpush
@endif
