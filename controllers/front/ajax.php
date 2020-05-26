<?php
include_once(dirname( __FILE__ )  . '/../../chilexpress_oficial_api.php');
class Chilexpress_OficialAjaxModuleFrontController extends ModuleFrontController
{
 
    public function initContent()
    {
        $this->ajax = true;
        $this->api = new Chilexpress_Oficial_API();
        // your code here
        parent::initContent();
    }

    private function get_local_file_contents( $file_path ) {
	    ob_start();
	    include $file_path;
	    $contents = ob_get_clean();

	    return $contents;
	}

    public function displayAjax()
    {
    	header('Content-Type: application/json');
    	$action = 'obtener_regiones';
    	if (isset($_POST["action"])) {
    		$action = $_POST["action"];
    	}

    	switch ($action) {

            case 'get_tracking_code_from_reference':

                $references = array();
                if (isset($_POST['references'])) {
                    $references = $_POST['references'];
                }

                $orders = new PrestaShopCollection('Order');
                $orders->where('reference', 'in', $references);

                $response = array();

                foreach ($orders as $order) {
                    if ($this->context->customer->id == $order->id_customer) {
                        $response[$order->reference] = array('tracking' => $order->shipping_number, 'order_id' => $order->id);
                        // fix later: this should be
                        /**
                            $order_carrier = new OrderCarrier($order->id_carrier);
                            $response[$order->reference] = $order_carrier->tracking_number;
                        **/
                    }
                }
                


                die(Tools::jsonEncode( array( 'action' => $action, 'response' => $response, "default" => "ok")));

            break;
            case 'track_order':
                $order_id = "0";
                $tracking_code = "";
                if (isset($_POST["order_id"])) {
                    $order_id = $_POST["order_id"];
                }
                if (isset($_POST["tracking_code"])) {
                    $tracking_code = $_POST["tracking_code"];
                }


                $order = new Order($order_id);
                $order_carrier = new OrderCarrier((int) $order->getIdOrderCarrier());
                $transportOrderNumbers = explode(',', $order_carrier->tracking_number);

               
               
                if (!in_array($tracking_code, $transportOrderNumbers )) {
                    die(Tools::jsonEncode( array( 'action' => $action, "tracking_code" => $tracking_code, 'error'=> "Tracking number Inválido" )));
                }

                

                
                $response = $this->api->obtener_estado_ot($tracking_code, "ORDEN-".$order_id, intval(Configuration::get('rut_marketplace_remitente')) ); 
                
                die(Tools::jsonEncode( array( 'action' => $action, "tracking_code" => $tracking_code, "response"=>$response )));
                
            break;
    		case 'obtener_regiones':
				die(Tools::jsonEncode( array( 'action' => $action, "regiones" => $this->api->obtener_regiones() )));
    		break;
            case 'obtener_comunas_con_id':
                $region = "0";
                if (isset($_POST["region"])) {
                    $region = $_POST["region"];
                }
                $result = Db::getInstance()->getRow('SELECT `iso_code` FROM `'._DB_PREFIX_.'state` WHERE `id_state` = '.intval($region));
                $region_iso = $result["iso_code"];
                
                die(Tools::jsonEncode( array( 'action' => $action, "result" => $region_iso,"comunas" => $this->api->obtener_comunas_desde_region($region_iso) )));
            break;
    		case 'obtener_comunas':

				$region = "R1";
				if (isset($_POST["region"])) {
		    		$region = $_POST["region"];
		    	}
		    	
				die(Tools::jsonEncode( array( 'action' => $action, "comunas" => $this->api->obtener_comunas_desde_region($region) )));
    		break;
    		default;
    			die(Tools::jsonEncode( array( 'action' => $action, "default" => "ok")));
    		break;
    	}        
    }
}