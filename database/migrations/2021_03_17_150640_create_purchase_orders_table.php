<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->increments     ('po_id')                 ->comment('Id Auto Incremental');
            $table->string         ('po_guid')               ->comment('GUID Magaya');
            $table->json           ('po_data')               ->comment('JSON Data');
            $table->string         ('po_from_api')           ->comment('Transaccion creada desde API');
            $table->string         ('po_estado')             ->comment('Estado');
            $table->datetime       ('po_fecha_creacion')     ->comment('Fecha de Creacion');
            $table->datetime       ('po_fecha_modificacion') ->comment('Fecha de Modificacion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
}
