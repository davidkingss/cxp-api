<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\magayaTrait;
use App\Models\PurchaseOrder;

class cronPO extends Command
{
    use magayaTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronPO';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cronPO';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        date_default_timezone_set('America/Bogota');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $this->inicializar();
        $tr = $this->queryLogBk();
        $this->info(implode(' ', $tr));
        foreach ($tr as $in) {
            $this->info('Voy por ' . $in);
            $data = $this->f_GetTransaction($in, 'PO', false, 0x00000080);
            if ($data == "NOT TRANSACTION") {
                $this->info('Inactivando ' . $in);
                PurchaseOrder::where('po_guid', $in)->update(['po_estado' => 'INACTIVO']);
            } else {
                $customFields = ($this->f_getCustomFieldsValues($data));
                $fromAPI = isset($customFields['from_api']) ? $customFields['from_api'] : "false";
                if (PurchaseOrder::where('po_guid', $in)->count() == 0) {
                    $this->info('Creando transaccion con GUID ' . $in);
                    $transaction = new PurchaseOrder;
                    $transaction->po_guid = $in;
                    $transaction->po_data = json_encode($this->xml2array($data));
                    $transaction->po_estado = 'ACTIVO';
                    $transaction->po_from_api = $fromAPI;
                    $transaction->po_fecha_creacion = date('Y-m-d H:i:s');
                    $transaction->po_fecha_modificacion = date('Y-m-d H:i:s');
                    $transaction->save();
                    $id = PurchaseOrder::where('po_guid', $in)->first();
                    $this->f_SetCustomFieldValue($in, 'PO', 'id_en_base_de_datos', $id->po_guid);
                    $this->info('Cree transaccion con ID ' . $id->po_guid);
                } else {
                    $this->info('Actualizando transaccion con GUID ' . $in);
                    $rate = PurchaseOrder::where('po_guid', $in)
                        ->update([
                            'po_data' => json_encode($this->xml2array($data)),
                            'po_estado' => 'ACTIVO',
                            'po_fecha_modificacion' => date('Y-m-d H:i:s')
                        ]);
                    $id = PurchaseOrder::where('po_guid', $in)->first();
                    $this->f_SetCustomFieldValue($in, 'PO', 'id_en_base_de_datos', $id->po_guid);
                    $this->info('Actualice transaccion con ID ' . $id->po_guid);
                }
            }
        }
    }

    /**
     * Traigo los guid de las tarifas creadas o actualizadas en el sistema.
     */
    public function queryLogBk()
    {

        date_default_timezone_set("America/New_York");
        $from = date('Y-m-d H:i:s', strtotime('-10 minutes', time()));
        $to   = date('Y-m-d H:i:s', strtotime('+10 minutes', time()));
        $this->info($from . " " . $to);
        $createSH = $this->queryLog2array($this->f_QueryLog('PO', 'Creation', $from, $to));
        $editetSH = $this->queryLog2array($this->f_QueryLog('PO', 'Edition', $from, $to), $createSH);
        $deleteSH = $this->queryLog2array($this->f_QueryLog('PO', 'Deletion', $from, $to), $editetSH, true);

        return $deleteSH;
    }

    /**
     * Convierto el querylog a un array.
     */
    public function queryLog2array($queryLog, $vSH = array(), $del = false)
    {
        //Valido los GUID y los cargo en el array
        if (isset($queryLog) && !empty($queryLog)) {
            foreach ($queryLog as $key => $in) {
                if ($del) {
                    //Inactivo registros si hay
                    if (PurchaseOrder::where('po_guid', end($in->GUID))->count() > 0) {
                        PurchaseOrder::where('po_guid', end($in->GUID))->update(['po_estado' => 'INACTIVO']);
                    }
                    //Si es un query de Deletion, cuando encuentro coincidencias los elimino del array
                    if (in_array(end($in->GUID), $vSH)) {
                        unset($vSH[end($in->GUID)]);
                    }
                } else {
                    if (!in_array(end($in->GUID), $vSH)) {
                        array_push($vSH, end($in->GUID));
                    }
                }
            }
        }
        return $vSH;
    }
}
