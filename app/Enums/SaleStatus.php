<?php
namespace App\Enums;
enum SaleStatus: string
{
    case Selesai = 'selesai';
    public function label(): string { return ucfirst($this->value); }
}
