<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemDefinitionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_definitions', function (Blueprint $table) {
            $table->increments     ('iv_id')                 ->comment('Id Auto Incremental');
            $table->string         ('iv_guid')               ->comment('GUID Magaya');
            $table->json           ('iv_data')               ->comment('JSON Data');
            $table->text           ('iv_xml')                ->comment('XML Data');
            $table->string         ('iv_estado')             ->comment('Estado');
            $table->datetime       ('iv_fecha_creacion')     ->comment('Fecha de Creacion');
            $table->datetime       ('iv_fecha_modificacion') ->comment('Fecha de Modificacion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('item_definitions');
    }
}
