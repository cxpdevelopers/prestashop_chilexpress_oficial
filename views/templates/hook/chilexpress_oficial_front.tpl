<script type="text/javascript">
	var chilexpress_oficial_ajax_url = '{$chilexpress_oficial_ajax_url}';
</script>


<div id="trackingModal"  class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        
        <h4 class="modal-title">Orden de transporte N º <span class="orden_transporte"></span></h4>
      </div>
      <div class="modal-body" style="font-size:14px;">
        <div class="row">
          <div class="col-md-6" style="width:50%; float:left;">
            <ul>
              <li><strong>Producto:</strong> <span class="producto"></span></li>
              <li><strong>Servicio</strong> <span class="servicio"></span></li>
              <li><strong>Estado</strong> <span class="status"></span></li>
            </ul>
          </div>

          <div class="col-md-6"  style="width:50%; float:left;">
            <ul>
              <li><strong>Dimensiones</strong> <span class="dimensiones"></span>cm</li>
              <li><strong>Peso</strong> <span class="peso"></span>Kg</li>
            </ul>
          </div>

        </div>
        <div style="clear:both;"></div>

        <div class="row">
          
          <div class="col-md-12">
            <h3>Datos de Entrega</h3>
            <ul>
              <li><strong>Rut Receptor:</strong> <span class="rut_receptor"></span> </li>
              <li><strong>Nombre Receptor</strong> <span class="nombre_receptor"></span></li>
              <li><strong>Fecha de Entrega</strong> <span class="fecha_entrega"></span></li>
              <li><strong>Hora Entrega</strong> <span class="hora_entrega"></span></li>
            </ul>
          </div>
          
          <div class="col-md-12">
            <h3>Eventos</h3>
            <table id="wc-chilexpress-events" class="widefat striped" style="width:100%;">
              <thead>
                <tr>
                  <th scope="column" width="25%">Fecha</th>
                  <th scope="column" width="25%">Hora</th>
                  <th scope="column" width="50%">Descripción</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td colspan="3" style="text-align:center;">Pendiente...</td>
                </tr><tr>
              </tr></tbody>
            </table>
          </div>

        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
