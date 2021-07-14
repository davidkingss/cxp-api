<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemDefinitions extends Model
{
    use HasFactory;

    protected $table = 'item_definitions';

    // Llave primaria de la tabla
    protected $primaryKey = 'iv_id';

    // Constantes personalizadas de los campos created_at y updated_at
    const CREATED_AT = 'iv_fecha_creacion';
    const UPDATED_AT = 'iv_fecha_modificacion';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'iv_id',
        'iv_guid',
        'iv_data',
        'iv_estado',
        'iv_fecha_creacion',
        'iv_fecha_modificacion'
    ];

    public static $requiredXml = [
        'PartNumber',
        'Description',
        'SKUNumbers',
        'Client'
    ];
}
