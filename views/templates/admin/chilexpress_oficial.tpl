<form method="POST" action="{$url}" id="configuration_form" class="defaultForm form-horizontal chilexpress_oficial">
	<input type="hidden" name="submitchilexpress_oficial" value="1">
	<input type="hidden" name="action" value="generar_ot">
	<div class="panel" Id="fieldset_0">
		<div class="panel-heading"><i class="material-icons mi-local_shipping"></i>Dirección de Destino</div>
		<div class="form-wrapper">
			<div class="form-group">
				<label class="control-label col-lg-3">Código de Comuna de Destino</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$destino.city}</p>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-3 ">Nombre de la Calle</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$destino.address1}</p>
				</div>
			</div>
			<!--div class="form-group">
				<label class="control-label col-lg-3 required">Número de la Calle</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$destino.address2}</p>
				</div>
			</div-->
			<div class="form-group">
				<label class="control-label col-lg-3 required">Complemento</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$destino.other}</p>
				</div>
			</div>
		</div>
	</div>

	<div class="panel" id="fieldset_1">
		<div class="panel-heading"><i class="material-icons mi-local_shipping"></i>Direccion de Devolución</div>
		<div class="form-wrapper">
			<div class="form-group">
				<label class="control-label col-lg-3">Código de Comuna de Destino</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$devolucion.city}</p>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-3 ">Nombre de la Calle</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$devolucion.address1}</p>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-3 required">Número de la Calle</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$devolucion.address2}</p>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-3 required">Complemento</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$devolucion.other}</p>
				</div>
			</div>
		</div>
	</div>


	<div class="panel" id="fieldset_2">
		<div class="panel-heading"><i class="material-icons mi-local_shipping"></i>Datos del remitente</div>
		<div class="form-wrapper">
			<div class="form-group">
				<label class="control-label col-lg-3">Nombre</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$remitente.nombre}</p>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-3">Teléfono</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$remitente.telefono}</p>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-3 required">E-mail</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$remitente.email}</p>
				</div>
			</div>
		</div>
	</div>

	<div class="panel" id="fieldset_3">
		<div class="panel-heading"><i class="material-icons mi-local_shipping"></i>Datos del destinatario</div>
		<div class="form-wrapper">
			<div class="form-group">
				<label class="control-label col-lg-3">Nombre</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$destinatario.nombre}</p>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-3">Teléfono</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$destinatario.telefono}</p>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-lg-3 required">E-mail</label>
				<div class="col-lg-9">
					<p style="font-size: 1.4em;margin-top: 0.2em; margin-bottom: -0.2em;">{$destinatario.email}</p>
				</div>
			</div>
		</div>
	</div>

	<div class="panel" id="fieldset_3">
		<div class="panel-heading"><i class="material-icons mi-local_shipping"></i>Armado de Bultos</div>
		<div class="form-wrapper">
			<table class="table">
				<thead>
					<tr>
						<th>Id</th>
						<th>Nombre</th>
						<th>Cantidad</th>
						<th>Dimensiones</th>
						<th>Peso Total</th>
						<th>Bulto</th>
					</tr>
				</thead>
				<tbody>
					{foreach from=$productos item=producto}
					<tr>
						<td>
							<span>{$producto.id}</span>
						</td>
						<td>
							<span>{$producto.name}</span>
						</td>
						<td>
							<span>{$producto.cart_quantity}</span>
						</td>
						<td>
							{$producto.width|string_format:"%.2f"}cm × {$producto.height|string_format:"%.2f"}cm × {$producto.depth|string_format:"%.2f"}cm 
						</td>
						<td>{($producto.weight*$producto.cart_quantity)|string_format:"%.2f"} Kg</td>
						<td data-children-count="1">
							<select name="paquetes[{$producto.id}]">
							{for $index=1 to $cantidad}
								<option value="{$index}">{$index}</option>
							{/for}
							</select>
						</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		<div class="panel-footer">
			<button type="submit" value="1" id="configuration_form_submit_btn_1" name="submitchilexpress_oficial" class="btn btn-primary pull-right">
						<i class="process-icon-save"></i> Generar Orden de Transporte
			</button>
		</div>
	</div>

</form>