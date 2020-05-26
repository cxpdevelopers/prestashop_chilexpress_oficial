jQuery(document).ready(function($) {

	// Dado que es exactamente el mismo código pero en distintas vistas
	// Creamos esta forma sencilla de reutilizar las reglas ya escritas
	var pares = [
		{
			"name": "delivery",
			"$city": $("#delivery-address input[name=city]"),
			"$state": $("#delivery-address select[name=id_state]")
		},
		{
			"name": "invoice",
			"$city": $("#invoice-address input[name=city]"),
			"$state": $("#invoice-address select[name=id_state]")
		},
		{
			"name": "account_address",
			"$city": $("body#address .js-address-form input[name=city]"),
			"$state": $("body#address .js-address-form select[name=id_state]")
		},
	];	

	pares.forEach( function (item) {
		var $city = item.$city;
		var $state = item.$state;
		if($city.length) {
			$city.parent().siblings('label').text('Comuna'); // Esto podría ser una opción del Módulo
			$state.parent().siblings('label').text('Región'); // Esto podría ser una opción del Módulo
			////////////////
			city_val = $city.val();
			var $city_parent = $city.parent('div');
			var $new_city = $('<select name="city" class="form-control form-control-select"><option value="'+city_val+'"  selected>-- por favor seleccione un estado/región --</select>')
			$city.remove();
			$city_parent.append($new_city);
			$city = $new_city;


			$state.on('change', function(ev){
				updateCity();
			});
			updateCity();

			function updateCity() {
				var state_val = $state.val();
				city_val = $city.val();
				$city.html('<option value="" disabled="" selected="selected">Cargando...</selected>');
		        $.ajax({
			    	type: 'POST',
				    url: chilexpress_oficial_ajax_url.replace(/&amp;/gi,'&'),
				    cache: false,
				    dataType: 'json',
				    data: {
				        action : 'obtener_comunas_con_id',
				        region : state_val,
				        ajax: true
				    },
				    success: function (result) {
				        var html = '';
				        Object.keys(result.comunas).forEach( function(key) {
				        	html += '<option value="' + key + '" '+( (key === city_val)?'selected="selected"':'' )+'>' + result.comunas[key] + '</option>';
				        });
				        if ($city) {
				        	$city.html(html);
				        }
				    }
				});
			}
			///////////////
			// Dejamos esto aqui por si eventualmente seguimos la ruta de cargar las regiones desde la api
			/***

			if ( $("#delivery-address select[name=id_state]").length ) {

				var state_temp = $state.html();

				$.ajax({
				    type: 'POST',
				    url: chilexpress_oficial_ajax_url.replace(/&amp;/gi,'&'),
				    cache: false,
				    dataType: 'json',
				    data: {
				        action : 'obtener_regiones',
				        ajax: true
				    },
				    success: function (result) {
				        var html = '';
				        Object.keys(result.regiones).forEach( function(key) {
				        	html += '<option value="' + key + '">' + result.regiones[key] + '</option>';
				        });
				        $("#delivery-address select[name=id_state]").html(html);

				        var state_val = $state.val();
				        $city.html('<option value="" disabled="" selected="selected">Cargando...</selected>');
				        $.ajax({
					    	type: 'POST',
						    url: chilexpress_oficial_ajax_url.replace(/&amp;/gi,'&'),
						    cache: false,
						    dataType: 'json',
						    data: {
						        action : 'obtener_comunas',
						        region : state_val,
						        ajax: true
						    },
						    success: function (result) {
						        var html = '';
						        Object.keys(result.comunas).forEach( function(key) {
						        	html += '<option value="' + key + '">' + result.comunas[key] + '</option>';
						        });
						        if ($city) {
						        	$city.html(html);
						        }
						    }
						});

				    }
				});
			}
			***/
		}
	});


	if ($("body#history #content table").length) {

		$(document).on('click', '.tracking-link', function(ev) {
			var $el = $(ev.currentTarget);
			var tracking = $(ev.currentTarget).data('tracking');
			var order_id = $(ev.currentTarget).data('orderid');
			if ($el.text() == 'Cargando...') return false;
			var oldText = $el.text();
			$el.text('Cargando...');


			jQuery.ajax({
						type: "post",
						url: chilexpress_oficial_ajax_url.replace(/&amp;/gi,'&'),
						dataType: 'json',
						data: {
					        action : 'track_order',
					        order_id : order_id,
					        tracking_code: tracking,
					        ajax: true
					    },
					    error: function(result){
							$el.text(oldText);
							alert('Hubo un error al cargar la información de la Orden de Transporte');					    	
					    },
						success: function(result){
							$el.text(oldText);
							var data = {};
							if (result.error) {
								alert(result.error);
								return;
							}
							if (result.response && result.response.data) {
								data = result.response;
							} else {
								data = result;
							}

							var dimensions = data.data.transportOrderData.dimensions.split("x");
							dimension_1 = parseInt(dimensions[0]) * 100;
							dimension_2 = parseInt(dimensions[1]) * 100;
							dimension_3 = parseInt(dimensions[2]) * 100;

							truncated_dimensions = "" + dimension_1 + "x" + dimension_2 + "x" + dimension_3;

							$("#trackingModal").modal('show');
							$("#trackingModal .orden_transporte").text(data.data.transportOrderData.transportOrderNumber);
							$("#trackingModal .producto").text(data.data.transportOrderData.product);
							$("#trackingModal .servicio").text(data.data.transportOrderData.service);
							$("#trackingModal .status").text(data.data.transportOrderData.status);

							$("#trackingModal .dimensiones").text(truncated_dimensions);
							$("#trackingModal .peso").text(data.data.transportOrderData.weight);

							$("#trackingModal .rut_receptor").text(data.data.deliveryData.receptorRut);
							$("#trackingModal .nombre_receptor").text(data.data.deliveryData.receptorName);
							$("#trackingModal .fecha_entrega").text(data.data.deliveryData.receptorDate);
							$("#trackingModal .hora_entrega").text(data.data.deliveryData.receptorHour);

							if (data.data.trackingEvents.length) {
								var html = '';
								$.each(data.data.trackingEvents,function(index, item){
									html += '<tr><td align="center">'+item.eventDate+'</td><td align="center">'+item.eventHour.split('.')[0]+'</td><td align="center">'+item.description+'</td></tr>'
								});
								$("#wc-chilexpress-events > tbody").html(html);
							} else {
								$("#wc-chilexpress-events > tbody > tr > td").text('No existen eventos aún para este envio.');
							}
						}
					});

		});

		$("body#history #content table thead tr").append($("<th>Tracking</th>"));
		var references = [];
		$("body#history #content table tbody tr").each(function(index, el){ 
			var reference = $(el).find('th').text();
			references.push(reference);
  			$(el).append('<td align="center" data-reference="'+reference+'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>');
		});
		$.ajax({
	    	type: 'POST',
		    url: chilexpress_oficial_ajax_url.replace(/&amp;/gi,'&'),
		    cache: false,
		    dataType: 'json',
		    data: {
		        action : 'get_tracking_code_from_reference',
		        references : references,
		        ajax: true
		    },
		    success: function (result) {
		        Object.keys(result.response).forEach(function(key, index){
		        	var tracking = result.response[key]['tracking'];
		        	var order_id = result.response[key]['order_id'];
		        	var out = [];
		        	tracking.split(',').forEach(function (code) {
		        		out.push('<a href="javascript:;" class="tracking-link" data-orderid="'+(order_id)+'" data-tracking="'+(code)+'">'+(code)+'</a>');
		        	});
		        	if (out.length > 0) {
		        		$('td[data-reference='+key+']').html(out.join('<br />'));
		        	} else {
		        		$('td[data-reference='+key+']').html('-');
		        	}
		        });
		    }
		});
	}
	

});