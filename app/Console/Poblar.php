<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VariableSistema;

class Poblar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Poblar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inicializa tabla de variables del sistema';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sys0001 = array(
            array('idvarxxx' => '1', 'codvarxx' => 'magaya_url', 'namevarx' => 'https://36451.magayacloud.com/api/Invoke?Handler=CSSoapService', 'usrcreac' => '666', 'usrmodif' => '666', 'feccreac' => '2020-03-12 05:31:41', 'fecmodif' => '2020-03-12 05:31:41', 'estadoxx' => 'ACTIVO', 'regestxx' => '2020-03-29 02:24:09'),
            array('idvarxxx' => '2', 'codvarxx' => 'magaya_ep', 'namevarx' => 'https://36451.magayacloud.com/api/CSSoapService?wsdl', 'usrcreac' => '666', 'usrmodif' => '666', 'feccreac' => '2020-03-12 05:31:41', 'fecmodif' => '2020-03-12 05:31:41', 'estadoxx' => 'ACTIVO', 'regestxx' => '2020-03-29 02:24:09'),
            array('idvarxxx' => '3', 'codvarxx' => 'magaya_uss', 'namevarx' => 'Admin', 'usrcreac' => '666', 'usrmodif' => '666', 'feccreac' => '2020-03-12 05:31:41', 'fecmodif' => '2020-03-12 05:31:41', 'estadoxx' => 'ACTIVO', 'regestxx' => '2020-03-29 02:24:09'),
            array('idvarxxx' => '4', 'codvarxx' => 'magaya_pss', 'namevarx' => 'Innobo123*', 'usrcreac' => '666', 'usrmodif' => '666', 'feccreac' => '2020-03-12 05:31:41', 'fecmodif' => '2020-03-12 05:31:41', 'estadoxx' => 'ACTIVO', 'regestxx' => '2020-03-29 02:24:09')            
        );

        // Itera sobre array de registros para poder crearlos
        foreach ($sys0001 as $key => $insert) {
            $sys0001 = VariableSistema::select(['codvarxx'])
                ->where('codvarxx', $insert['codvarxx'])
                ->first();

            if ($sys0001) {
                $this->info("Ya existe el sys0001 con codigo: {$insert['codvarxx']}");
            } else {
                $this->info("Insertando el sys0001 con codigo: {$insert['codvarxx']}");
                VariableSistema::create($insert);
            }
        }
    }
}
