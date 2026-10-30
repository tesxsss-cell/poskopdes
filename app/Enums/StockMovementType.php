<?php
namespace App\Enums;
enum StockMovementType: string
{
    case PembelianMasuk = 'pembelian_masuk';
    case PenjualanKeluar = 'penjualan_keluar';
    public function label(): string { return $this === self::PembelianMasuk ? 'Penerimaan barang' : 'Penjualan'; }
    public function isIncoming(): bool { return $this === self::PembelianMasuk; }
}
