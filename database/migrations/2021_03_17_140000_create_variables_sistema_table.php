<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVariablesSistemaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variables_sistema', function (Blueprint $table) {
            $table->increments     ('idvarxxx')               ->comment('Id Auto Incremental');
            $table->string         ('codvarxx')               ->comment('Numero de tipo de Servicio');
            $table->string         ('namevarx')               ->comment('Nombre del Tipo de Servicio');
            $table->string         ('usrcreac')               ->comment('Usuario que Creo el Servicio');
            $table->integer        ('usrmodif')               ->comment('Usuario que modifico el Registro');
            $table->datetime       ('feccreac')               ->comment('Fecha de creacion del registro');
            $table->datetime       ('fecmodif')               ->comment('Usuario que modificacion el Registro');
            $table->string         ('estadoxx')               ->comment('Estado del registro');
            $table->datetime       ('regestxx')               ->comment('Fecha de ultima Modificacion del Registro');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variables_sistema');
    }
}
