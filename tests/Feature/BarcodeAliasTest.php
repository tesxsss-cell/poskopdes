<?php
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\Stock;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
uses(RefreshDatabase::class);
require_once __DIR__.'/../Support/PosFixture.php';
it('alias barcode tidak mengubah harga master barang', function () {
    [$u,$p,$shift,$payload]=posIncrementFixture();
    $this->actingAs($u)->postJson(route('pos.barcode.store'),['product_id'=>$p->id,'barcode'=>'ALIAS-001'])->assertOk();
    $this->getJson(route('pos.lookup',['q'=>'ALIAS-001']))->assertOk()->assertJsonPath('data.0.sell_price',15000);
    expect(ProductBarcode::where('barcode','ALIAS-001')->first()->sell_price)->toBeNull();
    $this->postJson(route('pos.barcode.store'),['product_id'=>$p->id,'barcode'=>'ALIAS-002','sell_price'=>1])->assertUnprocessable();
});
