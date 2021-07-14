<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';

    // Llave primaria de la tabla
    protected $primaryKey = 'po_id';

    // Constantes personalizadas de los campos created_at y updated_at
    const CREATED_AT = 'po_fecha_creacion';
    const UPDATED_AT = 'po_fecha_modificacion';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'po_id',
        'po_guid',
        'po_data',
        'po_from_api',
        'po_estado',
        'po_fecha_creacion',
        'po_fecha_modificacion'
    ];
}
