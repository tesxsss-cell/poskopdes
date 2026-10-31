<?php
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);
it('draft tidak mengubah stok dan posting kedua ditolak', function () {
    $shop=Branch::sole();
    $u=User::create(['name'=>'Pengelola','email'=>'hpp@pos.test','password'=>'Password2026!','role'=>'pengelola','branch_id'=>$shop->id,'is_active'=>true]);
    $c=Category::create(['code'=>'UJI','name'=>'Uji','is_active'=>true]);
    $p=Product::create(['category_id'=>$c->id,'sku'=>'UJI','name'=>'Uji','unit'=>'pak','sell_price'=>15000,'min_stock'=>1,'is_active'=>true]);
    $supplier=Supplier::create(['code'=>'SUP','name'=>'Pemasok','is_active'=>true]);
    $svc=app(PurchaseService::class);
    $first=$svc->create(['supplier_id'=>$supplier->id,'branch_id'=>$shop->id],[['product_id'=>$p->id,'quantity'=>10,'unit_cost'=>10000]],$u->id);
    expect((int)Stock::sum('quantity'))->toBe(0);
    $svc->post($first,$u->id);
    $second=$svc->create(['supplier_id'=>$supplier->id,'branch_id'=>$shop->id],[['product_id'=>$p->id,'quantity'=>20,'unit_cost'=>13000]],$u->id);
    $svc->post($second,$u->id);
    expect(Stock::first()->quantity)->toBe(30)->and((float)Stock::first()->avg_cost)->toBe(12000.0);
    expect(fn()=>$svc->post($first->fresh(),$u->id))->toThrow(RuntimeException::class);
    expect(Stock::first()->quantity)->toBe(30);
});
