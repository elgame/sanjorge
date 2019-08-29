		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Resguardo de Activos
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-user"></i> Resguardo de Activos</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/resguardos_activos/'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">Buscar</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>"
									class="input-large search-query" placeholder="GAS MENGUC SA DE CV, 5 DE MAYO" autofocus> |

				                <label class="control-label" for="fempresa">Empresa </label>
				                <input type="text" name="fempresa" id="fempresa" class="input-xlarge search-query" value="<?php echo set_value_get('fempresa', $empresa->nombre_fiscal); ?>" placeholder="Nombre">
				                <input type="hidden" name="did_empresa" value="<?php echo set_value_get('did_empresa', $empresa->id_empresa); ?>" id="did_empresa">|

								<label for="fstatus">Estado</label>
								<select name="fstatus">
									<option value="ac" <?php echo set_select('fstatus', 'ac', false, $this->input->get('fstatus')); ?>>ACTIVOS</option>
									<option value="e" <?php echo set_select('fstatus', 'e', false, $this->input->get('fstatus')); ?>>ELIMINADOS</option>
									<option value="todos" <?php echo set_select('fstatus', 'todos', false, $this->input->get('fstatus')); ?>>TODOS</option>
								</select> |

								<label for="fstatus">Tipo</label>
								<select name="ftipo" id="ftipo">
                  <option value="todos">TODOS</option>
                  <option value="SIN FACTURA PAGO EN EFECTIVO" <?php echo set_select('ftipo', 'SIN FACTURA PAGO EN EFECTIVO', false, $this->input->get('ftipo')); ?>>SIN FACTURA PAGO EN EFECTIVO</option>
                  <option value="CON FACTURA PAGO EN EFECTIVO" <?php echo set_select('ftipo', 'CON FACTURA PAGO EN EFECTIVO', false, $this->input->get('ftipo')); ?>>CON FACTURA PAGO EN EFECTIVO</option>
                  <option value="FACTURADOR EMPAQUE SAN JORGE" <?php echo set_select('ftipo', 'FACTURADOR EMPAQUE SAN JORGE', false, $this->input->get('ftipo')); ?>>FACTURADOR EMPAQUE SAN JORGE</option>
                </select>

								<!-- <label for="ftipo_proveedor">Tipo</label>
								<select name="ftipo_proveedor">
									<option value="todos" <?php echo set_select('ftipo_proveedor', 'todos', false, $this->input->get('ftipo_proveedor')); ?>>TODOS</option>
									<option value="in" <?php echo set_select('ftipo_proveedor', 'in', false, $this->input->get('ftipo_proveedor')); ?>>INSUMOS</option>
									<option value="fr" <?php echo set_select('ftipo_proveedor', 'fr', false, $this->input->get('ftipo_proveedor')); ?>>FRUTA</option>
								</select> -->

								<input type="submit" name="enviar" value="Buscar" class="btn">
							</fieldset>
						</form>


						<a href="<?php echo base_url('panel/resguardos_activos/catalogo_xls/?'.MyString::getVarsLink(array('fnombre')) ); ?>"
                class="pull-left">
              <i class="icon-table"></i> Catalogo</a>
						<?php
						echo $this->usuarios_model->getLinkPrivSm('resguardos_activos/agregar/', array(
										'params'   => '',
										'btn_type' => 'btn-success pull-right',
										'attrs' => array('style' => 'margin-bottom: 10px;') )
								);
						 ?>
						<table class="table table-striped table-bordered bootstrap-datatable">
						  <thead>
							  <tr>
							  	<th>Producto</th>
								  <th>Entrego</th>
									<th>Recibió</th>
                  <th>Fecha Entrega</th>
                  <th>Tipo</th>
									<th>Status</th>
								  <th>Opciones</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($resguardos_activos['resguardos_activos'] as $resguardo){ ?>
							<tr>
								<td><?php echo $resguardo->producto; ?></td>
								<td><?php echo $resguardo->entrego; ?></td>
                <td><?php echo $resguardo->recibio; ?></td>
                <td><?php echo $resguardo->fecha_entrega; ?></td>
								<td><?php echo $resguardo->tipo; ?></td>
								<td>
									<?php
										if($resguardo->status == 't'){
											$v_status = 'Activo';
											$vlbl_status = 'label-success';
										}else{
											$v_status = 'Eliminado';
											$vlbl_status = 'label-important';
										}
									?>
									<span class="label <?php echo $vlbl_status; ?>"><?php echo $v_status; ?></span>
								</td>
								<td class="center">
										<?php
										echo $this->usuarios_model->getLinkPrivSm('resguardos_activos/modificar/', array(
												'params'   => 'id='.$resguardo->id_resguardo,
												'btn_type' => 'btn-success')
										);
										if ($resguardo->status == 't') {
											echo $this->usuarios_model->getLinkPrivSm('resguardos_activos/eliminar/', array(
														'params'   => 'id='.$resguardo->id_resguardo,
														'btn_type' => 'btn-danger',
														'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar el resguardo?', 'Resguardos', this); return false;"))
											);
										}else{
											echo $this->usuarios_model->getLinkPrivSm('resguardos_activos/activar/', array(
													'params'   => 'id='.$resguardo->id_resguardo,
													'btn_type' => 'btn-danger',
													'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar el resguardo?', 'Resguardos', this); return false;"))
											);
										}

										?>
								</td>
							</tr>
					<?php }?>
						  </tbody>
					  </table>

					  <?php
						//Paginacion
						$this->pagination->initialize(array(
								'base_url' 			=> base_url($this->uri->uri_string()).'?'.MyString::getVarsLink(array('pag')).'&',
								'total_rows'		=> $resguardos_activos['total_rows'],
								'per_page'			=> $resguardos_activos['items_per_page'],
								'cur_page'			=> $resguardos_activos['result_page']*$resguardos_activos['items_per_page'],
								'page_query_string'	=> TRUE,
								'num_links'			=> 1,
								'anchor_class'	=> 'pags corner-all',
								'num_tag_open' 	=> '<li>',
								'num_tag_close' => '</li>',
								'cur_tag_open'	=> '<li class="active"><a href="#">',
								'cur_tag_close' => '</a></li>'
						));
						$pagination = $this->pagination->create_links();
						echo '<div class="pagination pagination-centered"><ul>'.$pagination.'</ul></div>';
						?>
					</div>
				</div><!--/span-->

			</div><!--/row-->




					<!-- content ends -->
		</div><!--/#content.span10-->


<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
	if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
	$(document).ready(function(){
		noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
	});
</script>
<?php }
}?>
<!-- Bloque de alertas -->


