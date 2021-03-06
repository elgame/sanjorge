		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/banco/'); ?>">Banco</a> <span class="divider">/</span>
					</li>
					<li>Retirar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Agregar retiro</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/banco/retirar'); ?>" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<div class="span6">
									<span style="display: block; margin: 0 auto;text-align: center;border-bottom: 1px solid #ccc;font-size: 20px;">Origen</span>
									<div class="control-group">
									  <label class="control-label" for="ffecha">Fecha </label>
									  <div class="controls">
											<input type="datetime-local" name="ffecha" id="ffecha" class="span12" value="<?php echo set_value('ffecha', date("Y-m-d\Th:i")); ?>" required autofocus>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fbanco">Banco </label>
									  <div class="controls">
											<select name="fbanco" id="fbanco" required>
									<?php  foreach ($bancos['bancos'] as $key => $value) {
									?>
												<option value="<?php echo $value->id_banco ?>" <?php echo set_select('fbanco', $value->id_banco); ?>><?php echo $value->nombre; ?></option>
									<?php
									}?>
											</select>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fcuenta">Cuenta </label>
									  <div class="controls">
											<select name="fcuenta" id="fcuenta" required>
									<?php
									foreach ($cuentas['cuentas'] as $key => $value) {
										$select = set_select('fcuenta', $value->id_cuenta);
										if($select == ' selected="selected"')
											$cuenta_saldo = $value->saldo;
									?>
												<option value="<?php echo $value->id_cuenta; ?>" data-saldo="<?php echo $value->saldo; ?>" <?php echo $select; ?>><?php echo $value->alias.' - '.MyString::formatoNumero($value->saldo); ?></option>
									<?php
									}?>
											</select>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fmetodo_pago">Metodo de pago </label>
									  <div class="controls">
											<select name="fmetodo_pago" id="fmetodo_pago" required>
									<?php  foreach ($metods_pago as $key => $value) {
									?>
												<option value="<?php echo $value['value']; ?>"><?php echo $value['nombre']; ?></option>
									<?php
									}?>
											</select>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="freferencia">Referencia </label>
									  <div class="controls">
											<input type="text" name="freferencia" id="freferencia" class="span12" maxlength="20"
											value="<?php echo set_value('freferencia'); ?>" placeholder="12352">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fconcepto">Concepto </label>
									  <div class="controls">
											<input type="text" name="fconcepto" id="fconcepto" class="span12" value="<?php echo set_value('fconcepto'); ?>"
												maxlength="100" placeholder="Deposito en efectivo" required>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fmonto">Monto </label>
									  <div class="controls">
											<input type="number" step="any" name="fmonto" id="fmonto" class="span9 pull-left" value="<?php echo set_value('fmonto'); ?>"
												maxlength="12" min="0.0" placeholder="1052" required>
                        <label for="fdesglosa_iva" class="pull-right">Desglosar IVA <input type="checkbox" name="fdesglosa_iva" id="fdesglosa_iva" value="t" data-uniform="false"></label>
									  </div>
									</div>

                  <div class="control-group">
                    <label class="control-label" for="dempresa">Empresa</label>
                    <div class="controls">
                      <input type="text" name="dempresa" class="span12" id="dempresa" value="" size="">
                      <input type="hidden" name="did_empresa" id="did_empresa" value="">
                    </div>
                  </div>

									<div class="control-group">
										<label class="control-label" for="dproveedor">Proveedor</label>
										<div class="controls">
											<input type="text" name="dproveedor" class="span12" id="dproveedor" value="<?php echo set_value('dproveedor'); ?>">
	                  	<input type="hidden" name="did_proveedor" id="did_proveedor" value="<?php echo set_value('did_proveedor'); ?>">
										</div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fcomision">Comision </label>
									  <div class="controls">
											<input type="number" step="any" name="fcomision" id="fcomision" class="span12" value="<?php echo set_value('fcomision'); ?>"
												placeholder="Comisiones bancarias">
									  </div>
									</div>

                  <div class="control-group">
                    <label class="control-label" for="dcuenta_cpi">Cuenta Contpaq</label>
                    <div class="controls">
                      <input type="text" name="dcuenta_cpi" class="span12" id="dcuenta_cpi" value="<?php echo set_value('dcuenta_cpi'); ?>">
                      <input type="hidden" name="did_cuentacpi" id="did_cuentacpi" value="<?php echo set_value('did_cuentacpi'); ?>">
                    </div>
                  </div>

								</div>

								<div class="span5">
									<div style="margin-top: -25px;margin-left: -50px;">
										<label for="ftraspaso">Traspaso <input type="checkbox" name="ftraspaso" id="ftraspaso" value="si" <?php echo set_checkbox('ftraspaso'); ?> data-uniform="false"></label>
									</div>

									<div id="div_destino" style="display:none;">
										<span style="display: block; margin: 0 auto;text-align: center;border-bottom: 1px solid #ccc;font-size: 20px;">Destino</span>
										<div class="control-group">
										  <label class="control-label" for="fbanco_destino">Banco </label>
										  <div class="controls">
												<select name="fbanco_destino" id="fbanco_destino" required>
										<?php  foreach ($bancos['bancos'] as $key => $value) {
										?>
													<option value="<?php echo $value->id_banco ?>" <?php echo set_select('fbanco_destino', $value->id_banco); ?>><?php echo $value->nombre; ?></option>
										<?php
										}?>
												</select>
										  </div>
										</div>

										<div class="control-group">
										  <label class="control-label" for="fcuenta_destino">Cuenta </label>
										  <div class="controls">
												<select name="fcuenta_destino" id="fcuenta_destino">
										<?php
										foreach ($cuentas['cuentas'] as $key => $value) {
											$select = set_select('fcuenta_destino', $value->id_cuenta);
											if($select == ' selected="selected"')
												$cuenta_saldo = $value->saldo;
										?>
													<option value="<?php echo $value->id_cuenta; ?>" data-saldo="<?php echo $value->saldo; ?>" <?php echo $select; ?>><?php echo $value->alias.' - '.MyString::formatoNumero($value->saldo); ?></option>
										<?php
										}?>
												</select>
										  </div>
										</div>
									</div>
								</div> <!-- /span -->

                <div class="row-fluid" id="groupCatalogos" style="display: none">  <!-- Box catalogos-->
                  <div class="box span12">
                    <div class="box-header well" data-original-title>
                      <h2><i class="icon-truck"></i> Catálogos</h2>
                      <div class="box-icon">
                        <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                      </div>
                    </div><!--/box-header -->
                    <div class="box-content">
                      <div class="row-fluid">
                        <div class="span6">
                          <div class="control-group" id="cultivosGrup">
                            <label class="control-label" for="area">Cultivo </label>
                            <div class="controls">
                              <div class="input-append span12">
                                <input type="text" name="area" class="span11" id="area" value="<?php echo set_value('area') ?>" placeholder="Limon, Piña">
                              </div>
                              <input type="hidden" name="areaId" id="areaId" value="<?php echo set_value('areaId') ?>">
                            </div>
                          </div><!--/control-group -->

                          <div class="control-group" id="ranchosGrup">
                            <label class="control-label" for="rancho">Area </label>
                            <div class="controls">
                              <div class="input-append span12">
                                <input type="text" name="rancho" class="span11" id="rancho" value="<?php echo set_value('rancho') ?>" placeholder="Milagro A, Linea 1">
                              </div>
                              <input type="hidden" name="ranchoId" id="ranchoId" value="<?php echo set_value('ranchoId') ?>">
                            </div>
                          </div><!--/control-group -->

                        </div>

                        <div class="span6">
                          <div class="control-group" id="centrosCostosGrup">
                            <label class="control-label" for="centroCosto">Centro de costo </label>
                            <div class="controls">
                              <div class="input-append span12">
                                <input type="text" name="centroCosto" class="span11" id="centroCosto" value="<?php echo set_value('centroCosto') ?>" placeholder="Mantenimiento, Gasto general">
                              </div>
                              <input type="hidden" name="centroCostoId" id="centroCostoId" value="<?php echo set_value('centroCostoId') ?>">
                            </div>
                          </div><!--/control-group -->

                          <div class="control-group" id="activosGrup">
                            <label class="control-label" for="activos">Activos </label>
                            <div class="controls">
                              <div class="input-append span12">
                                <input type="text" name="activos" class="span11" id="activos" value="<?php echo set_value('activos') ?>" placeholder="Nissan FRX, Maquina limon">
                              </div>
                              <input type="hidden" name="activoId" id="activoId" value="<?php echo set_value('activoId') ?>">
                            </div>
                          </div><!--/control-group -->
                        </div>

                      </div>

                     </div> <!-- /box-body -->
                  </div> <!-- /box -->
                </div><!-- /row-fluid -->

								<div class="clearfix"></div>
								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/banco/cuentas/'); ?>" class="btn">Cancelar</a>
								</div>
						  </fieldset>
						</form>

					</div>
				</div><!--/span-->

			</div><!--/row-->


					<!-- content ends -->
		</div><!--/#content.span10-->



<!-- Bloque de alertas -->
<script type="text/javascript" charset="UTF-8">
<?php
if (isset($_GET['id_movimiento']{0}))
	echo "window.open('".base_url('panel/banco/cheque?id=')."{$_GET['id_movimiento']}', 'Print cheque');";

if(isset($frm_errors)){
	if($frm_errors['msg'] != ''){
?>
	$(document).ready(function(){
		noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
	});
<?php }
}?>
</script>
<!-- Bloque de alertas -->

