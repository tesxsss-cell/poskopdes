<?php
namespace App\Enums;
enum PurchaseStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    public function label(): string { return $this === self::Draft ? 'Draft' : 'Diposting'; }
}
