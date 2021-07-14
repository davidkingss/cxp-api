<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariableSistema extends Model
{
    use HasFactory;

    // Tabla relacionada con el modelo
    protected $table = 'variables_sistema';

    // Llave primaria de la tabla
    protected $primaryKey = 'idvarxxx';

    // Constantes personalizadas de los campos created_at y updated_at
    const CREATED_AT = 'feccreac';
    const UPDATED_AT = 'fecmodif';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'idvarxxx',
        'codvarxx',
        'namevarx',
        'usrcreac',
        'usrmodif',
        'feccreac',
        'fecmodif',
        'estadoxx',
        'regestxx',
    ];
}
