
	


	{foreach from=$ots item=ot}
	<div class="panel" Id="fieldset_0">
		<div class="panel-heading"><i class="icon-print"></i> Etiqueta</div>
		<div class="row">
			<div class="col-md-6">
				<div class="form-wrapper">
					<div class="row">
					<div class="form-group" style="overflow: hidden;">
						<label class="control-label col-lg-3">Número de OT</label>
						<div class="col-lg-9">
							<p style="font-size: 1.2em;margin-top: 0.4em; margin-bottom: -0.4em;">{$ot.transportOrderNumber}</p>
						</div>
					</div>
					<div class="form-group" style="overflow: hidden;">
						<label class="control-label col-lg-3 ">Referencia</label>
						<div class="col-lg-9">
							<p style="font-size: 1.2em;margin-top: 0.4em; margin-bottom: -0.4em;">{$ot.reference}</p>
						</div>
					</div>
					<div class="form-group" style="overflow: hidden;">
						<label class="control-label col-lg-3 ">Descripción del producto</label>
						<div class="col-lg-9">
							<p style="font-size: 1.2em;margin-top: 0.4em; margin-bottom: -0.4em;">{$ot.productDescription}</p>
						</div>
					</div>
					<div class="form-group" style="overflow: hidden;">
						<label class="control-label col-lg-3 ">Descripción adicional</label>
						<div class="col-lg-9">
							<p style="font-size: 1.2em;margin-top: 0.4em; margin-bottom: -0.4em;">{$ot.serviceDescription}</p>
						</div>
					</div>
					<div class="form-group" style="overflow: hidden;">
						<label class="control-label col-lg-3 ">Código de barras</label>
						<div class="col-lg-9">
							<p style="font-size: 1.2em;margin-top: 0.4em; margin-bottom: -0.4em;">{$ot.barcode}</p>
						</div>
					</div>
					<div class="form-group" style="overflow: hidden;">
						<label class="control-label col-lg-3 ">Clasificación</label>
						<div class="col-lg-9">
							<p style="font-size: 1.2em;margin-top: 0.4em; margin-bottom: -0.4em;">{$ot.classificationData}</p>
						</div>
					</div>
					<div class="form-group" style="overflow: hidden;">
						<label class="control-label col-lg-3 ">Compañía</label>
						<div class="col-lg-9">
							<p style="font-size: 1.2em;margin-top: 0.4em; margin-bottom: -0.4em;">{$ot.companyName}</p>
						</div>
					</div>
					<div class="form-group" style="overflow: hidden;">
						<label class="control-label col-lg-3 ">Recibe</label>
						<div class="col-lg-9">
							<p style="font-size: 1.2em;margin-top: 0.4em; margin-bottom: -0.4em;">{$ot.recipient}</p>
						</div>
					</div>
					<div class="form-group" style="overflow: hidden;">
						<label class="control-label col-lg-3 ">Dirección</label>
						<div class="col-lg-9">
							<p style="font-size: 1.2em;margin-top: 0.4em; margin-bottom: -0.4em;">{$ot.address}</p>
						</div>
					</div>
					<div class="form-group" style="overflow: hidden;">
						<label class="control-label col-lg-3 ">Fecha de Impresión</label>
						<div class="col-lg-9">
							<p style="font-size: 1.2em;margin-top: 0.4em; margin-bottom: -0.4em;">{$ot.createdDate}</p>
						</div>
					</div>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<h4>Imagen de la Etiqueta</h4>
				<img src="data:image/jpg;base64,{$ot.labelData}" />
				<br />
				<a href="{$pdfurl}" class="btn btn-primary" "><i class="icon-print"></i> Imprimir Pdf de Etiquetas</a>
			</div>
		</div>		
	</div>
	{/foreach}

	