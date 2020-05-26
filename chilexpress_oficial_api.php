<?php 

if ( ! class_exists( 'Chilexpress_Oficial_API' ) ) {
	class Chilexpress_Oficial_API {
		
		private function get_local_file_contents( $file_path ) {
		    ob_start();
		    include $file_path;
		    $contents = ob_get_clean();
		    return $contents;
		}

		/**
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->id  = 'chilexpress_oficial';
			$this->init();
			
			$this->api_staging_base_url = 'https://testservices.wschilexpress.com/';
			$this->api_production_base_url = 'https://services.wschilexpress.com/';

			$module_options["ambiente"] = Configuration::get('ambiente');
			$module_options["api_key_georeferencia_enabled"] = Configuration::get('api_key_georeferencia_enabled');
			$module_options["api_key_generacion_ot_enabled"] = Configuration::get('api_key_generacion_ot_enabled');

			if ($module_options['ambiente'] == 'production') {
				
				$this->api_base_url = $this->api_production_base_url;
			} else
			{
				$this->api_base_url = $this->api_staging_base_url;
			}

			$this->api_key_georeferencia = Configuration::get('api_key_cotizador_value');
			$this->api_key_cobertura = Configuration::get('api_key_georeferencia_value');
			$this->api_key_ot = Configuration::get('api_key_generacion_ot_value');

			$this->api_geo_enabled = isset($module_options['api_key_georeferencia_enabled']) ? $module_options['api_key_georeferencia_enabled'] : false;
			$this->api_ot_enabled  = isset($module_options['api_key_generacion_ot_enabled']) ? $module_options['api_key_generacion_ot_enabled'] : false;
		}

		public function obtener_regiones() {
			
			$directory =  dirname( __FILE__ )  . '/data/regiones/' ;
		    $regiones = array();
		    $file_path = $directory . 'regiones.json';
		    if (file_exists($file_path)) {
		    	$raw_json = $this->get_local_file_contents( $file_path );
			    $data = json_decode( $raw_json );
			    
			    $regiones = array();

				foreach ($data->regions as $region) {
					$regiones[$region->regionId] = $region->regionName;
				}
			} else {
				$regiones = array();
			} 
			return $regiones;
		}


		public function obtener_comunas_desde_region($codigo_region = "R1") {
			$directory =  dirname( __FILE__ )  . '/data/comunas/' ;
			$region = $codigo_region;
	    	$data = array();
		    $comunas = array();
		    $file_path = $directory . $region .'.json';
		    if (file_exists($file_path)) {
		    	$raw_json = $this->get_local_file_contents( $file_path );
			    $data = json_decode( $raw_json );
			    
			    $comunas = array();

				foreach ($data->coverageAreas as $comuna) {
					$comunas[$comuna->countyCode] = $comuna->coverageName;
				}
			} else {
				$comunas = array();
			} 
			return $comunas;
		}

		public function obtener_cotizacion($comuna_origen, $comuna_destino, $weight = 1, $height = 1, $width = 1, $depth = 1, $declaredWorth = 1000) {
			
			$payload = array(
				"originCountyCode" =>	$comuna_origen,
				"destinationCountyCode" => $comuna_destino,
				"package" => array(
					"weight" =>	$weight,
					"height" =>	$height,
					"width" =>	$width,
					"length" =>	$depth
				),
				"productType" => 3,
				"contentType" => 1,
				"declaredWorth" => $declaredWorth,
				"deliveryTime" => 0
			);

			$json_result = $this->internalApiCallPOST(
					json_encode($payload),
					"rating/api/v1.0/rates/courier",
					$this->api_key_georeferencia
				);

			return $json_result;
			
		}

		public function generar_ot($payload_str) {
			$json_result = $this->internalApiCallPOST(
					$payload_str,
					"transport-orders/api/v1.0/transport-orders",
					$this->api_key_ot
				);

			return $json_result;
		}



		public function obtener_estado_ot($trackingId, $reference, $rut ) {
			
			$payload = array(
				"reference"=> $reference,
				"transportOrderNumber"=> $trackingId,
				"rut"=> $rut,
				"showTrackingEvents" => 1
			);
			
			$json_result = $this->internalApiCallPOST(
					json_encode($payload),
					"transport-orders/api/v1.0/tracking",
					$this->api_key_ot
				);

			return $json_result;
		}


		private function internalApiCallPOST($payload_str, $url_ws, $apiKey) {
			$url_ws = $this->api_base_url.$url_ws;
	        $curl = curl_init();
	        $headers = array();
	        $headers[] = 'Content-Type: application/json';
	        $headers[] = 'Ocp-Apim-Subscription-Key: '.$apiKey;
	        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	        curl_setopt($curl, CURLOPT_HEADER, 0);
	        curl_setopt($curl, CURLOPT_URL, $url_ws);
	        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
	        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
	        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload_str);

	        $result = curl_exec($curl);
	        curl_close($curl);

	        if (!$result){
	            return false;
	        } else { 
	            $jresult = json_decode($result,true);
	            return $jresult;
	        }
		}


		/**
		 * Init your settings
		 *
		 * @access public
		 * @return void
		 */
		function init() {
			
		}

		

	}
}