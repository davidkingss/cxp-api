<?php

namespace App\Traits;

use nusoap_client;
use App\Models\VariableSistema;

trait magayaTrait
{
    public $ipApi      = '';
    public $endPoint   = '';
    public $MagayaUser = '';
    public $MagayaPass = '';
    public $userVentas = '';

    

    public function __construct()
    {
        $this->ipApi      = VariableSistema::where('codvarxx', 'magaya_ep')->value('namevarx');
        $this->endPoint   = VariableSistema::where('codvarxx', 'magaya_url')->value('namevarx');
        $this->MagayaUser = VariableSistema::where('codvarxx', 'magaya_uss')->value('namevarx');
        $this->MagayaPass = VariableSistema::where('codvarxx', 'magaya_pss')->value('namevarx');
        $this->userVentas = VariableSistema::where('codvarxx', 'usu_ventas')->value('namevarx');
    }

    public function inicializar()
    {
        $this->ipApi      = VariableSistema::where('codvarxx', 'magaya_ep')->value('namevarx');
        $this->endPoint   = VariableSistema::where('codvarxx', 'magaya_url')->value('namevarx');
        $this->MagayaUser = VariableSistema::where('codvarxx', 'magaya_uss')->value('namevarx');
        $this->MagayaPass = VariableSistema::where('codvarxx', 'magaya_pss')->value('namevarx');
        $this->userVentas = VariableSistema::where('codvarxx', 'usu_ventas')->value('namevarx');
    }

    /**
     * Inicializa el @nusoap_client y modifica el endPoint
     * 
     * @global type $ipApi
     * @global type $endPoint
     * @return boolean|\nusoap_client
     */
    public function f_initSoap()
    {
        $client = new nusoap_client($this->ipApi, 'wsdl', '', '', '', '', 0, 360);
        $client->soap_defencoding = 'UTF-8';
        $client->decodeUTF8(false);
        $client->setEndPoint($this->endPoint);
        $err = $client->getError();

        if ($err)
            return false;
        return $client;
    }

    /**
     * Funcion de inicio de sesion
     * @return type integer, retorna el acces_key
     */
    public function f_StartSession()
    {
        $client = $this->f_initSoap();
        $params = [
            'user' => $this->MagayaUser,
            'pass' => $this->MagayaPass
        ];

        $client->operation = 'StartSession';
        $response = $client->call('StartSession', $params, '', '', false, true);
        $err = $client->getError();
        if ($err)
            print_r($err);
        if (isset($response['access_key'])) {
            return $response['access_key'];
        }
        dd("Error en la conexion API");
        return 0;;
    }

    /**
     * Querylog de Magaya
     * @return type simplexml, retorna la lista de trasacciones
     */
    public function f_QueryLog($Type = "", $TypeItem = "", $initDate = "", $endDate = "")
    {
        $client = $this->f_initSoap();
        $access_key = $this->f_StartSession();
        $from = str_replace(array('U', 'C', 'O'), array('', '', ''), $initDate);
        $to = str_replace(array('U', 'C', 'O'), array('', '', ''), $endDate);
        $to   = str_replace(" ", "T", $to);
        $from = str_replace(" ", "T", $from);
        switch ($TypeItem) {
            case 'Creation':
                $logItem = 0x01;
                break;
            case 'Edition':
                $logItem = 0x04;
                break;
            case 'Deletion':
                $logItem = 0x02;
                break;
            default:
                echo ("Error en el LogItem, elija una opcion correcta");
                return null;
                break;
        }
        $params = [
            'access_key' => (int) $access_key,
            'start_date' => (string) $from,
            'end_date' => (string) $to,
            'log_entry_type' => $logItem,
            'trans_type' => $Type,
            'flags' => 0
        ];
        $client->operation = 'QueryLog';
        $response = $client->call('QueryLog', $params, '', '', false, true);
        $err = $client->getError();
        if (isset($response['trans_list_xml']) && !empty($response['trans_list_xml'])) {
            $list = simplexml_load_string($response['trans_list_xml']);
            $this->f_EndSession($access_key);
            return $list;
        } else {
            $this->f_EndSession($access_key);
            return null;
        }
    }

    public function f_SetTransaction($xml, $type)
    {
        $client = $this->f_initSoap();
        $access_key = $this->f_StartSession();
        $params = [
            'access_key' => $access_key,
            'flags' => 0,
            'type' => $type,
            'trans_xml' => $xml
        ];
        $client->operation = 'SetTransaction';
        $response = $client->call('SetTransaction', $params, '', '', false, true);
        $err = $client->getError();
        $this->f_EndSession($access_key);
        if ($err)
            return ["resonse" => false, "error" => $err];;
        return $response;
    }

    public function f_SetEntity($xml)
    {
        $client = $this->f_initSoap();
        $access_key = $this->f_StartSession();
        $params = [
            'access_key' => $access_key,
            'flags' => 0,
            'entity_xml' => $xml
        ];
        $client->operation = 'SetEntity';
        $response = $client->call('SetEntity', $params, '', '', false, true);
        $err = $client->getError();
        $this->f_EndSession($access_key);
        if ($err)
            return $err;
        return $response;
    }

    /**
     * Finaliza la sesion del WebService
     * @param type $access_key (llave de acceso al sistema)
     * @return type
     */
    function f_EndSession($access_key = "")
    {
        $client = $this->f_initSoap();
        $params = [
            'access_key' => $access_key
        ];
        $client->operation = 'EndSession';
        $response = $client->call('EndSession', $params, '', '', false, true);
        $err = $client->getError();
        if ($err)
            print_r($err);
        return $response;
    }

    /**
     * 
     */
    function f_GetEntities($name = "")
    {
        $client = $this->f_initSoap();
        $access_key = $this->f_StartSession();
        $params = [
            'access_key' => $access_key,
            'flags' => 0,
            'start_with' => $name
        ];
        $client->operation = 'GetEntities';
        $response = $client->call('GetEntities', $params, '', '', false, true);
        $err = $client->getError();
        $this->f_EndSession($access_key);
        if ($err)
            return $err;
        if (isset($response['entity_list_xml']) && !empty($response['entity_list_xml'])) {
            $response['entity_list_xml'] = utf8_decode($response['entity_list_xml']);
            $transaction = utf8_encode($response['entity_list_xml']);
            $transaction = simplexml_load_string($transaction);
            return ($transaction);
        } else {
            return "NOT TRANSACTION";
        }
        return $response;
    }

    /**
     * 
     */
    function f_GetTransaction($guid = "", $type = "", $xml = false, $flags = 0, $validate = true)
    {
        $client = $this->f_initSoap();
        $access_key = $this->f_StartSession();
        $params = [
            'access_key' => $access_key,
            'flags' => $flags,
            'type' => $type,
            'number' => $guid
        ];
        $client->operation = 'GetTransaction';
        $response = $client->call('GetTransaction', $params, '', '', false, true);
        $err = $client->getError();
        $this->f_EndSession($access_key);
        if ($err)
            return ["resonse" => false, "error" => $err];
        if (isset($response['trans_xml']) && !empty($response['trans_xml'])) {
            $response['trans_xml'] = utf8_decode($response['trans_xml']);
            $transaction = utf8_encode($response['trans_xml']);
            if ($xml)
                return $transaction;
            $transaction = simplexml_load_string($transaction);
            return ($transaction);
        } else {
            if (isset($response['return']) && !empty($response['return'])) {
                if ($response['return'] == "transaction_not_found") {
                    return "NOT TRANSACTION";
                }
            }
            if ($validate) {
                return $this->f_GetTransaction($guid, $type, $xml, $flags, false);
            }
            return "Error";
        }
        return $response;
    }

    function f_SetTransactionEvents($guid = "", $type = "", $details = '', $typeEvent = 'Api Client Event')
    {
        $client = $this->f_initSoap();
        $access_key = $this->f_StartSession();
        $from = date('Y-m-d H:i:s', strtotime('-10 minutes', time()));
        $from = str_replace(" ", "T", $from);
        $params = [
            'access_key' => $access_key,
            'flags' => 0,
            'type' => $type,
            'number' => $guid,
            'event_list_xml' => "<Events xmlns = \"http://www.magaya.com/XMLSchema/V1\">
      <Event>
        <Date>$from</Date>
        <Details>$details</Details>
        <EventDefinition>
          <Name>$typeEvent</Name>
          <IncludeInTracking>true</IncludeInTracking>
        </EventDefinition>
        <IncludeInTracking>true</IncludeInTracking>
      </Event>                                             
    </Events>"
        ];
        $client->operation = 'SetTransactionEvents';
        $response = $client->call('SetTransactionEvents', $params, '', '', false, true);
        $err = $client->getError();
        $this->f_EndSession($access_key);
        if ($err)
            return $err;
        if (isset($response['trans_xml']) && !empty($response['trans_xml'])) {
            $response['trans_xml'] = utf8_decode($response['trans_xml']);
            $transaction = utf8_encode($response['trans_xml']);
            $transaction = simplexml_load_string($transaction);
            return ($transaction);
        } else {
            return $response;
        }
        return $response;
    }

    function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function f_GetAllAttachments($guid = "", $type = "")
    {
        $client = $this->f_initSoap();
        $access_key = $this->f_StartSession();
        $params = [
            'access_key' => $access_key,
            'flags' => 0,
            'type' => $type,
            'number' => $guid
        ];
        $client->operation = 'GetAllAttachments';
        $response = $client->call('GetAllAttachments', $params, '', '', false, true);
        $err = $client->getError();
        $this->f_EndSession($access_key);
        if ($err)
            return $err;
        if (isset($response['attach_list_xml']) && !empty($response['attach_list_xml'])) {
            $response['attach_list_xml'] = utf8_decode($response['attach_list_xml']);
            $transaction = utf8_encode($response['attach_list_xml']);
            $transaction = simplexml_load_string($transaction);
            return ($transaction);
        } else {
            return $response;
        }
        return $response;
    }

    function f_GetAttachment($guid = "", $type = "", $attach_id = "")
    {
        $client = $this->f_initSoap();
        $params = [
            'app' => $type,
            'trans_uuid' => $guid,
            'attach_id' => $attach_id,
        ];
        $client->operation = 'GetAttachment';
        $response = $client->call('GetAttachment', $params, '', '', false, true);
        $err = $client->getError();
        if ($err)
            return $err;
        $response = utf8_decode($response);
        $transaction = utf8_encode($response);
        $transaction = simplexml_load_string($transaction);
        return $transaction;
    }

    function f_SetAttachment($guid = "", $type = "", $nameAttachment = "", $extensionAttachment = "", $base64Attachment = "", $isImageAttachment = "false")
    {
        $attachment = "<Attachment xmlns=\"http://www.magaya.com/XMLSchema/V1\">
    <Name>$nameAttachment</Name>
    <Extension>$extensionAttachment</Extension>
    <IsImage>$isImageAttachment</IsImage>
    <Data>
      $base64Attachment
    </Data>
    </Attachment>";
        $access_key = $this->f_StartSession();
        $client = $this->f_initSoap();
        $params = [
            'access_key' => $access_key,
            'flags' => 0,
            'type' => $type,
            'number' => $guid,
            'attach_xml' => $attachment
        ];
        $client->operation = 'SetAttachment';
        $response = $client->call('SetAttachment', $params, '', '', false, true);
        $err = $client->getError();
        if ($err)
            return $err;
        return $response['return'];
    }

    function f_SetCustomFieldValue($guid = "", $type = "", $nameField = "", $value = "")
    {
        $client = $this->f_initSoap();
        $access_key = $this->f_StartSession();
        $params = [
            'access_key' => $access_key,
            'type' => $type,
            'number' => $guid,
            'field_internal_name' => $nameField,
            'field_value' => $value
        ];
        $client->operation = 'SetCustomFieldValue';
        $response = $client->call('SetCustomFieldValue', $params, '', '', false, true);
        return $response;
    }

    public function f_getCustomFieldsValues($obj = null)
    {
        $return = [];
        if (isset($obj->CustomFields->CustomField)) {
            foreach ($obj->CustomFields->CustomField as $customField) {
                $internalName = end($customField->CustomFieldDefinition->InternalName);
                $value = end($customField->Value);
                $return[$internalName] = $value;
            }
        }
        return $return;
    }

    public function xml2array($xmlObject, $out = array())
    {
        foreach ((array) $xmlObject as $index => $node) {
            $index = str_replace("@", "", $index);
            $out[$index] = (is_object($node)) ? $this->xml2array($node) : $node;
        }
        return $out;
    }

    public function object_to_array($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = $this->object_to_array($value);
            }
            return $result;
        }
        return $data;
    }

    /**
     * Realizaz la peticion con cURL
     *
     * @access public
     * @param string $link 
     * @return XML
     */
    public function peticionCurl($link = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
