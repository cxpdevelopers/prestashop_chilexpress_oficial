<?php  
if (!defined('_PS_VERSION_'))
    exit();

include_once(dirname( __FILE__ )  . '/../../classes/ChilexpressOficialOrderMeta.php');
include_once(dirname( __FILE__ )  . '/../../classes/PDFLabel.php');
include_once(dirname( __FILE__ )  . '/../../chilexpress_oficial_api.php');

class ChilexpressOrderController extends AdminController
{
	public function __construct()
	{ 
		$this->bootstrap = true;
        $this->table = 'chilexpressorder';
        $this->className = 'ChilexpressOrder';

        $this->carriers_serviceTypeCode = array(
            'CHILEXPRESS_OFCL_DHS' => 3,
            'CHILEXPRESS_OFCL_DHSS' => 4,
            'CHILEXPRESS_OFCL_3DH' => 5
        );

        $this->api = new Chilexpress_Oficial_API();

        parent::__construct();
	}

	public function postProcess()
    {
        parent::postProcess();

        // We want to be sure that displaying PDF is the last thing this controller will do
        //exit;
    }

 	public function initContent(){
 		if (!isset($_GET["order_id"]) || !isset($_GET["action"])) {
 			return "Orden y/o accion no encontrada";
 		}
 		$order_id = intval($_GET["order_id"]);
		$order = new Order($order_id);
		$action = $_GET["action"];
		if (!$action) {
			$action = "generar_ot";
		}

        if (!Validate::isLoadedObject($order)) {
            return 'Orden no encontrada';
        }
        $address_obj = new Address($order->id_address_delivery);
        $customer = new Customer($address_obj->id_customer);

        $address = array(
        	"city" => $address_obj->city,
        	"address1" => $address_obj->address1,
        	"other" => $address_obj->other,
        );
        $devolucion = array(
        	"city" => Configuration::get('comuna_devolucion'),
        	"address1" => Configuration::get('calle_devolucion'),
        	"address2" => Configuration::get('numero_calle_devolucion'),
        	"other" => Configuration::get('complemento_devolucion')
        );
        $remitente = array(
        	"nombre" => Configuration::get('nombre_remitente'),
        	"telefono" => Configuration::get('telefono_remitente'),
        	"email" => Configuration::get('email_remitente')
        );
        $destinatario = array(
        	"nombre" => $customer->firstname.' '.$customer->lastname,
        	"telefono" => $address_obj->phone,
        	"email" => $customer->email
        );


        $cart = new Cart($order->id_cart);
        $products = $cart->getProducts(true);

	    $products_array = array();
	    
	    foreach($products as $product) {
	        $products_array[] = array(
	            "id" => $product["id_product"],
	            "cart_quantity" => $product["cart_quantity"],
	            "name" => $product["name"],
	            "width" => $product["width"],
	            "height" => $product["height"],
	            "depth" => $product["depth"],
	            "weight" => $product["weight"],
	            "total" => $product["total"],
	        );
	    }
	    if (isset($_POST['submitchilexpress_oficial']) && $_POST['submitchilexpress_oficial'] == 1)
 		{
 			$serviceTypeId  = 0;

 			foreach ($this->carriers_serviceTypeCode as $carrier_key => $stypeid)
 			{
				if (Configuration::get($carrier_key) == $order->id_carrier)
				{
					$serviceTypeId = $stypeid;
				}
			}

			$payload_header = array(
			    "certificateNumber" => 0, //Número de certificado, si no se ingresa se creará uno nuevo
			    "customerCardNumber"=> Configuration::get('numero_tcc_origen'), // Número de Tarjeta Cliente Chilexpress (TCC)
			    "countyOfOriginCoverageCode"=> $address["city"], // Comuna de origen
			    "labelType"=> 2, // Imagen
			    "marketplaceRut"=> intval(Configuration::get('numero_tcc_origen')), // Rut asociado al Marketplace
			    "sellerRut"=> "DEFAULT" // Rut asociado al Vendedor
	  		);
			$payload_address_destino = array(
		  						"addressId" => 0, // Id de la dirección obtenida de la API Validar dirección
						        "countyCoverageCode"=>  $address["city"], // Cobertura de destino obtenido por la API Consultar Coberturas
						        "streetName"=> $address["address1"], // Nombre de la calle
						        "streetNumber"=> $address["other"], // Numeración de la calle
						        # "supplement"=> , // Información complementaria de la dirección
						        "addressType"=> "DEST", // Tipo de dirección; DEST = Entrega, DEV = Devolución.
						        "deliveryOnCommercialOffice"=> false, // Indicador si es una entrega en oficina comercial (true) o entrega en domicilio (false)
						        "commercialOfficeId"=> "",
						        "observation"=> "DEFAULT" // Observaciones adicionales
	  						);
			$payload_address_devolucion = array(
								"addressId"=> 0,
								"countyCoverageCode"=> $devolucion['city'],
								"streetName"=> $devolucion['address1'],
								"streetNumber"=> $devolucion['address2'],
								"supplement"=> $devolucion['other'],
								"addressType"=> "DEV",
								"deliveryOnCommercialOffice"=> false,
								"observation"=> "DEFAULT"
	  						);
			$payload_contact_devolucion = array(
								"name"=> $remitente['nombre'],
								"phoneNumber"=> $remitente['telefono'],
								"mail"=> $remitente['email'],
								"contactType"=> "R" // Tipo de contacto; Destinatario (D), Remitente (R)
	  						);
			$payload_contact_destino = array(
								"name"=> $destinatario["nombre"],
								"phoneNumber"=> $destinatario["telefono"],
								"mail"=> $destinatario["email"],
								"contactType"=> "D" // Tipo de contacto; Destinatario (D), Remitente (R)
	  						);

			$pre_paquetes = array();
			$paquetes = array();
			
			$opcion_paquetes = $_POST["paquetes"];

			foreach($opcion_paquetes as $prodid => $numero_paquete ):
				foreach ($products_array as $product ):
					if ($product["id"] == "$prodid") {
						if (isset($pre_paquetes[$numero_paquete])) {
							$pre_paquetes[$numero_paquete]["weight"] += $product["weight"]*$product["cart_quantity"];
							$pre_paquetes[$numero_paquete]["total"] += $product["total"]*$product["cart_quantity"];
							$pre_paquetes[$numero_paquete]["volumes"]["$item_id"] = $product['height']*$product["cart_quantity"]*$product['width']*$product['depth'];
						} else {
							$pre_paquetes[$numero_paquete] = array(
								"weight"=> $product["weight"]*$product["cart_quantity"],
								"total"=> $product["total"]*$product["cart_quantity"],
								"volumes" => array(
									"$prodid" => $product['height']*$product["cart_quantity"]*$product['width']*$product['depth']
								)
							);
						}
					}
				endforeach;
			endforeach;


			foreach($pre_paquetes as $numero_paquete => $base_paquete ):
				// ordenamos los volumenes en volumen de mayor a menor
				arsort($base_paquete["volumes"]);
				// obtenemos el id del producto 
				$biggest_product_id = $this->array_key_first($base_paquete["volumes"]);
				foreach ($products_array as $product ):
				    $item_id = $product["id"];

				    if ($item_id == $biggest_product_id) {

						if($base_paquete["weight"] == 0)
							$base_paquete["weight"] += 1;

						if($product['height'] == 0)
							$product['height'] += 1;

						if($product['width'] == 0)
							$product['width'] += 1;

						if($product['depth'] == 0)
							$product['depth'] += 1;

				    	$paquetes[] =  array(
								"weight"=> $base_paquete["weight"], // Peso en kilogramos
								"height"=> $product['height']*$product["cart_quantity"], // Altura en centímetros
								"width"=> $product['width'], // Ancho en centímetros
								"length"=> $product['depth'],  // Largo en centímetros
								"serviceDeliveryCode"=> $serviceTypeId, // Código del servicio de entrega, obtenido de la API Cotización
								"productCode"=> "3", // Código del tipo de roducto a enviar; 1 = Documento, 3 = Encomienda
								"deliveryReference"=> "ORDEN-".$order_id, // Referencia que permite identificar el envío por parte del cliente.
								"groupReference"=> "ORDEN-".$order_id."-GRUPO-1", // Referencia que permite identificar un grupo de bultos que va por parte del cliente.
								"declaredValue"=> round($base_paquete["total"]), // Valor declarado del producto
								"declaredContent"=> "string", // Tipo de producto enviado; 1 = Moda, 2 = Tecnologia, 3 = Repuestos, 4 = Productos medicos, 5 = Otros
								"extendedCoverageAreaIndicator"=> false, // Indicador de contratación de cobertura extendida 0 = No, 1 = Si
								"receivableAmountInDelivery"=> 1000 // Monto a cobrar, en caso que el cliente tenga habilitada esta opción.
	  						);
				    }
				endforeach;
			endforeach;				
			

			$payload = array(
				"header" => $payload_header,
	  			"details" => array(
	  				array(
	  					"addresses" => array(
	  						$payload_address_destino,
	  						$payload_address_devolucion
	  					),
	  					"contacts" => array( // Se debe entregar un detalle para los datos de contacto del destinatario (D) y otro para los del remitente (R)
	  						$payload_contact_devolucion,
	  						$payload_contact_destino
	  					),
	  					"packages" => $paquetes

	  				)
	  			)
			);

	        $jresult = $this->api->generar_ot(json_encode($payload));
	        $serviceValue = false;
	        $shipping_numbers = array();
	        if (!$jresult){
	            return 'Error tratando de crear Orden de Transporte';
	        } else { 
	            foreach($jresult['data']['detail'] as $d) {
	            	$OrderMeta = new ChilexpressOficialOrderMeta();
					$OrderMeta->id_order = (int)$order_id;
					$shipping_numbers[] = $d['transportOrderNumber'];

					$OrderMeta->transportOrderNumber = $d['transportOrderNumber'];
		            $OrderMeta->reference = $d['reference'];
		            $OrderMeta->productDescription = $d['productDescription'];
		            $OrderMeta->serviceDescription = $d['serviceDescription'];
		            $OrderMeta->genericString1 = $d['genericString1'];
		            $OrderMeta->genericString2 = $d['genericString2'];
		            $OrderMeta->deliveryTypeCode = $d['deliveryTypeCode'];
		            $OrderMeta->destinationCoverageAreaName = $d['destinationCoverageAreaName'];
		            $OrderMeta->additionalProductDescription = $d['additionalProductDescription'];
		            $OrderMeta->barcode = $d['barcode'];
		            $OrderMeta->classificationData = $d['classificationData'];
		            $OrderMeta->printedDate = $d['printedDate'];
		            $OrderMeta->labelVersion = $d['labelVersion'];
		            $OrderMeta->distributionDescription = $d['distributionDescription'];
		            $OrderMeta->companyName = $d['companyName'];
		            $OrderMeta->recipient = $d['recipient'];
		            $OrderMeta->address = $d['address'];
		            $OrderMeta->groupReference = $d['groupReference'];
		            $OrderMeta->createdDate = $d['createdDate'];
		            $OrderMeta->labelData = $d['label']['labelData'];
		            $OrderMeta->add();
	            }

            	$order_carrier = new OrderCarrier((int) $order->getIdOrderCarrier());
	            $order_carrier->tracking_number = implode(',', $shipping_numbers);
	            $order_carrier->save();

	            $order->shipping_number = implode(',', $shipping_numbers);
	            $order->save();

				$url = ($this->context->link->getAdminLink('AdminOrders', true, [], ['id_order' => $order_id, 'vieworder' => 1]) . '&amp;id_order='.$order_id.'&amp;vieworder');

				Tools::redirect($url);
        }
 		} else if($action == 'generar_ot') {
 			$tpl = $this->context->smarty->createTemplate(_PS_MODULE_DIR_ . 'chilexpress_oficial/views/templates/admin/chilexpress_oficial.tpl', $this->context->smarty);

	        $tpl->assign(array(
	        	'order' => $order,
	        	'destino' => $address,
	        	'devolucion' => $devolucion,
	        	'remitente' => $remitente,
	        	'destinatario' => $destinatario,
	        	'productos' => $products_array,
	        	'cantidad' => count($products_array),
	        	"url" => $this->context->link->getAdminLink('ChilexpressOrder').'&order_id='.$order_id.'&action=generar_ot'
	        ));
	        $this->content .= $tpl->fetch();
	        parent::initContent();
 		} else if($action == 'ver_ot') {
 			$tpl = $this->context->smarty->createTemplate(_PS_MODULE_DIR_ . 'chilexpress_oficial/views/templates/admin/chilexpress_oficial_ver_ot.tpl', $this->context->smarty);

 			$query = new DbQuery();
            $query->from('chilexpress_oficial_ordermeta', 'com');
            $query->where('com.id_order = "'.$order_id.'"');
            $metasArray = Db::getInstance()->executeS($query);


	        $tpl->assign(array(
	        	'ots' => $metasArray,
	        	'pdfurl' => $this->context->link->getAdminLink('ChilexpressOrder').'&order_id='.$order_id.'&action=print_ot'
	        ));
	        $this->content .= $tpl->fetch();
	        parent::initContent();

 		} else if ($action == 'print_ot') {
 			 $query = new DbQuery();
            $query->from('chilexpress_oficial_ordermeta', 'com');
            $query->where('com.id_order = "'.$order_id.'"');
            $metasArray = Db::getInstance()->executeS($query);

            $html = "";
            $otrackings = array();
            foreach ($metasArray as $ot) {
            	$otrackings[] = $ot['transportOrderNumber'];
            	$html .= '<img src="@'.$ot['labelData'].'" width="400" /><br /><br />';
            }

            $pdf = new PDFLabel($this->context->smarty, 'P');
            $pdf->setFilename('chilexpress-ot-'.implode('-', $otrackings).'.pdf');
            $pdf->setLabelsHTML($html);
 			$pdf->render(true);
 			exit();
 		}

    }


    public function viewAccess($disable = false)
    {
        if (version_compare(_PS_VERSION_, '1.5.1.0', '<='))
            return true;
        return true;
        // return parent::viewAccess($disable);
    }


    function array_key_first(array $arr) {
        foreach($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }

}