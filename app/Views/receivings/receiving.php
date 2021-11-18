<?php echo view("partial/header"); ?>

<?php
if (isset($error))
{
	echo "<div class='alert alert-dismissible alert-danger'>".$error."</div>";
}

if (!empty($warning))
{
	echo "<div class='alert alert-dismissible alert-warning'>".$warning."</div>";
}

if (isset($success))
{
	echo "<div class='alert alert-dismissible alert-success'>".$success."</div>";
}
?>

<div id="register_wrapper">

<!-- Top register controls -->

	<?php echo form_open($controller_name."/change_mode", ['id'=>'mode_form', 'class'=>'form-horizontal panel panel-default']); ?>
		<div class="panel-body form-group">
			<ul>
				<li class="pull-left first_li">
					<label class="control-label"><?php echo lang('Receivings.mode'); ?></label>
				</li>
				<li class="pull-left">
					<?php echo form_dropdown('mode', $modes, $mode, ['onchange'=>"$('#mode_form').submit();", 'class'=>'selectpicker show-menu-arrow', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit']); ?>
				</li>

				<?php 
				if ($show_stock_locations)
				{
				?>
					<li class="pull-left">
						<label class="control-label"><?php echo lang('Receivings.stock_source'); ?></label>
					</li>
					<li class="pull-left">
						<?php echo form_dropdown('stock_source', $stock_locations, $stock_source, ['onchange'=>"$('#mode_form').submit();", 'class'=>'selectpicker show-menu-arrow', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit']); ?>
					</li>
					
					<?php
					if($mode=='requisition')
					{
					?>
						<li class="pull-left">
							<label class="control-label"><?php echo lang('Receivings.stock_destination'); ?></label>
						</li>
						<li class="pull-left">
							<?php echo form_dropdown('stock_destination', $stock_locations, $stock_destination, ['onchange'=>"$('#mode_form').submit();", 'class'=>'selectpicker show-menu-arrow', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit']); ?>
						</li>
				<?php
					}
				}
				?>
			</ul>
		</div>
	<?php echo form_close(); ?>

	<?php echo form_open($controller_name."/add", ['id'=>'add_item_form', 'class'=>'form-horizontal panel panel-default']); ?>
		<div class="panel-body form-group">
			<ul>
				<li class="pull-left first_li">
					<label for="item", class='control-label'>
						<?php
						if($mode=='receive' or $mode=='requisition')
						{
						?>
							<?php echo lang('Receivings.find_or_scan_item'); ?>
						<?php
						}
						else
						{
						?>
							<?php echo lang('Receivings.find_or_scan_item_or_receipt'); ?>
						<?php
						}
						?>			
					</label>
				</li>
				<li class="pull-left">
					<?php echo form_input (['name'=>'item', 'id'=>'item', 'class'=>'form-control input-sm', 'size'=>'50', 'tabindex'=>'1']); ?>
				</li>
				<li class="pull-right">
					<button id='new_item_button' class='btn btn-info btn-sm pull-right modal-dlg'
						data-btn-submit='<?php echo lang('Common.submit') ?>'
						data-btn-new='<?php echo lang('Common.new') ?>'
						data-href='<?php echo site_url("items/view"); ?>'
						title='<?php echo lang('Sales.new_item'); ?>'>
						<span class="glyphicon glyphicon-tag">&nbsp</span><?php echo lang('Sales.new_item'); ?>
					</button>
				</li>
			</ul>
		</div>
	<?php echo form_close(); ?>
	
<!-- Receiving Items List -->

	<table class="sales_table_100" id="register">
		<thead>
			<tr>
				<th style="width:5%;"><?php echo lang('Common.delete'); ?></th>
				<th style="width:15%;"><?php echo lang('Sales.item_number'); ?></th>
				<th style="width:23%;"><?php echo lang('Receivings.item_name'); ?></th>
				<th style="width:10%;"><?php echo lang('Receivings.cost'); ?></th>
				<th style="width:8%;"><?php echo lang('Receivings.quantity'); ?></th>
				<th style="width:10%;"><?php echo lang('Receivings.ship_pack'); ?></th>
				<th style="width:14%;"><?php echo lang('Receivings.discount'); ?></th>
				<th style="width:10%;"><?php echo lang('Receivings.total'); ?></th>
				<th style="width:5%;"><?php echo lang('Receivings.update'); ?></th>
			</tr>
		</thead>

		<tbody id="cart_contents">
			<?php
			if(count($cart) == 0)
			{
			?>
				<tr>
					<td colspan='9'>
						<div class='alert alert-dismissible alert-info'><?php echo lang('Sales.no_items_in_cart'); ?></div>
					</td>
				</tr>
			<?php
			}
			else
			{
				foreach(array_reverse($cart, TRUE) as $line=>$item)
				{
			?>
					<?php echo form_open($controller_name."/edit_item/$line", ['class'=>'form-horizontal', 'id'=>'cart_'.$line]); ?>
						<tr>
							<td><?php echo anchor($controller_name."/delete_item/$line", '<span class="glyphicon glyphicon-trash"></span>');?></td>
							<td><?php echo $item['item_number']; ?></td>
							<td style="align:center;">
								<?php echo $item['name'] . ' '. implode(' ', [$item['attribute_values'], $item['attribute_dtvalues']]); ?><br /> <?php echo '[' . to_quantity_decimals($item['in_stock']) . ' in ' . $item['stock_name'] . ']'; ?>
								<?php echo form_hidden('location', $item['item_location']); ?>
							</td>

							<?php 
							if ($items_module_allowed && $mode !='requisition')
							{
							?>
								<td><?php echo form_input ([
									'name' => 'price',
									'class' => 'form-control input-sm',
									'value' => to_currency_no_money($item['price']),
									'onClick' => 'this.select();'
								]);?></td>
							<?php
							}
							else
							{
							?>
								<td>
									<?php echo $item['price']; ?>
									<?php echo form_hidden('price', to_currency_no_money($item['price'])); ?>
								</td>
							<?php
							}
							?>
							
							<td><?php echo form_input (['name'=>'quantity', 'class'=>'form-control input-sm', 'value'=>to_quantity_decimals($item['quantity']),'onClick'=>'this.select();']); ?></td>
							<td><?php echo form_dropdown(
									'receiving_quantity',
									$item['receiving_quantity_choices'],
									$item['receiving_quantity'],
									['class'=>'form-control input-sm']
								);?></td>

							<?php       
							if ($items_module_allowed && $mode!='requisition')
							{
							?>
								<td>
								<div class="input-group">
									<?php echo form_input (['name'=>'discount', 'class'=>'form-control input-sm', 'value'=>$item['discount_type'] ? to_currency_no_money($item['discount']) : to_decimals($item['discount']), 'onClick'=>'this.select();']); ?>
									<span class="input-group-btn">
										<?php echo form_checkbox ([
											'id' => 'discount_toggle',
											'name' => 'discount_toggle',
											'value' => 1,
											'data-toggle' => "toggle",
											'data-size' => 'small',
											'data-onstyle' => 'success',
											'data-on' => '<b>'.$this->appconfig->get('currency_symbol').'</b>',
											'data-off' => '<b>%</b>',
											'data-line' => $line,
											'checked' => $item['discount_type']
										]); ?>
									</span>
								</div> 
							</td>
							<?php
							}
							else
							{
							?>
								<td><?php echo $item['discount'];?></td>
								<?php echo form_hidden('discount',$item['discount']); ?>
							<?php
							}
							?>
							<td>
							<?php echo to_currency(($item['discount_type'] == PERCENT) ? $item['price']*$item['quantity']*$item['receiving_quantity'] - $item['price'] * $item['quantity'] * $item['receiving_quantity'] * $item['discount'] / 100 : $item['price']*$item['quantity']*$item['receiving_quantity'] - $item['discount']); ?></td> 
							<td><a href="javascript:$('#<?php echo 'cart_'.$line ?>').submit();" title=<?php echo lang('Receivings.update')?> ><span class="glyphicon glyphicon-refresh"></span></a></td>
						</tr>
						<tr>
							<?php 
							if($item['allow_alt_description']==1)
							{
							?>
								<td style="color: #2F4F4F;"><?php echo lang('Sales.description_abbrv').':';?></td>
							<?php 
							} 
							?>
							<td colspan='2' style="text-align: left;">
								<?php
								if($item['allow_alt_description']==1)
								{
									echo form_input ([
										'name' => 'description',
										'class' => 'form-control input-sm',
										'value' => $item['description']
									]);
								}
								else
								{
									if ($item['description']!='')
									{
										echo $item['description'];
										echo form_hidden('description',$item['description']);
									}
									else
									{
										echo "<i>".lang('Sales.no_description')."</i>";
										echo form_hidden('description','');
									}
								}
								?>
							</td>
							<td colspan='7'></td>
						</tr>
					<?php echo form_close(); ?>
			<?php
				}
			}
			?>
		</tbody>
	</table>
</div>

<!-- Overall Receiving -->

<div id="overall_sale" class="panel panel-default">
	<div class="panel-body">
		<?php
		if(isset($supplier))
		{
		?>
			<table class="sales_table_100">
				<tr>
					<th style='width: 55%;'><?php echo lang('Receivings.supplier'); ?></th>
					<th style="width: 45%; text-align: right;"><?php echo $supplier; ?></th>
				</tr>
				<?php
				if(!empty($supplier_email))
				{
				?>
					<tr>
						<th style='width: 55%;'><?php echo lang('Receivings.supplier_email'); ?></th>
						<th style="width: 45%; text-align: right;"><?php echo $supplier_email; ?></th>
					</tr>
				<?php
				}
				?>
				<?php
				if(!empty($supplier_address))
				{
				?>
					<tr>
						<th style='width: 55%;'><?php echo lang('Receivings.supplier_address'); ?></th>
						<th style="width: 45%; text-align: right;"><?php echo $supplier_address; ?></th>
					</tr>
				<?php
				}
				?>
				<?php
				if(!empty($supplier_location))
				{
				?>
					<tr>
						<th style='width: 55%;'><?php echo lang('Receivings.supplier_location'); ?></th>
						<th style="width: 45%; text-align: right;"><?php echo $supplier_location; ?></th>
					</tr>
				<?php
				}
				?>
			</table>
			
			<?php echo anchor(
					$controller_name."/remove_supplier",
					'<span class="glyphicon glyphicon-remove">&nbsp</span>' . lang('Common.remove').' '.lang('Suppliers.supplier'),
						[
							'class' => 'btn btn-danger btn-sm',
							'id'=>'remove_supplier_button',
							'title'=>lang('Common.remove').' '.lang('Suppliers.supplier')
						]); ?>
		<?php
		}
		else
		{
		?>
			<?php echo form_open($controller_name."/select_supplier", ['id'=>'select_supplier_form', 'class'=>'form-horizontal']); ?>
				<div class="form-group" id="select_customer">
					<label id="supplier_label" for="supplier" class="control-label" style="margin-bottom: 1em; margin-top: -1em;"><?php echo lang('Receivings.select_supplier'); ?></label>
					<?php echo form_input ([
						'name' => 'supplier',
						'id' => 'supplier',
						'class' => 'form-control input-sm',
						'value' => lang('Receivings.start_typing_supplier_name')
					]); ?>

					<button id='new_supplier_button' class='btn btn-info btn-sm modal-dlg' data-btn-submit='<?php echo lang('Common.submit') ?>' data-href='<?php echo site_url("suppliers/view"); ?>'
							title='<?php echo lang('Receivings.new_supplier'); ?>'>
						<span class="glyphicon glyphicon-user">&nbsp</span><?php echo lang('Receivings.new_supplier'); ?>
					</button>

				</div>
			<?php echo form_close(); ?>
		<?php
		}
		?>

		<table class="sales_table_100" id="sale_totals">
			<tr>
				<?php
				if($mode != 'requisition')
				{
				?>
					<th style="width: 55%;"><?php echo lang('Sales.total'); ?></th>
					<th style="width: 45%; text-align: right;"><?php echo to_currency($total); ?></th>
				<?php 
				}
				else
				{
				?>
					<th style="width: 55%;"></th>
					<th style="width: 45%; text-align: right;"></th>
				<?php 
				}
				?>
			</tr>
		</table>

		<?php
		if(count($cart) > 0)
		{
		?>
			<div id="finish_sale">
				<?php
				if($mode == 'requisition')
				{
				?>
					<?php echo form_open($controller_name."/requisition_complete", ['id'=>'finish_receiving_form', 'class'=>'form-horizontal']); ?>
						<div class="form-group form-group-sm">
							<label id="comment_label" for="comment"><?php echo lang('Common.comments'); ?></label>
							<?php echo form_textarea ([
								'name'=>'comment',
								'id'=>'comment',
								'class'=>'form-control input-sm',
								'value' => $comment,
								'rows'=>'4'
							]); ?>

							<div class="btn btn-sm btn-danger pull-left" id='cancel_receiving_button'><span class="glyphicon glyphicon-remove">&nbsp</span><?php echo lang('Receivings.cancel_receiving'); ?></div>
							
							<div class="btn btn-sm btn-success pull-right" id='finish_receiving_button'><span class="glyphicon glyphicon-ok">&nbsp</span><?php echo lang('Receivings.complete_receiving'); ?></div>
						</div>
					<?php echo form_close(); ?>
				<?php
				}
				else
				{
				?>
					<?php echo form_open($controller_name."/complete", ['id'=>'finish_receiving_form', 'class'=>'form-horizontal']); ?>
						<div class="form-group form-group-sm">
							<label id="comment_label" for="comment"><?php echo lang('Common.comments'); ?></label>
							<?php echo form_textarea ([
								'name' => 'comment',
								'id' => 'comment',
								'class' => 'form-control input-sm',
								'value' => $comment,
								'rows' => '4'
							]);?>
							<div id="payment_details" >
								<table class="sales_table_100" >
									<tr>
										<td><?php echo lang('Receivings.print_after_sale'); ?></td>
										<td>
											<?php echo form_checkbox ([
												'name' => 'recv_print_after_sale',
												'id' => 'recv_print_after_sale',
												'class' => 'checkbox',
												'value' => 1,
												'checked' => $print_after_sale
											]); ?>
										</td>
									</tr>
									<?php
									if ($mode == "receive")
									{
									?>
										<tr>
											<td><?php echo lang('Receivings.reference');?></td>
											<td>
												<?php echo form_input ([
													'name' => 'recv_reference',
													'id' => 'recv_reference',
													'class' => 'form-control input-sm',
													'value' => $reference,
													'size' => 5
												]);?>
											</td>
										</tr>
									<?php
									}
									?>
									<tr>
										<td><?php echo lang('Sales.payment'); ?></td>
										<td>
											<?php echo form_dropdown('payment_type', $payment_options, [], [
												'id' => 'payment_types',
												'class' => 'selectpicker show-menu-arrow',
												'data-style' => 'btn-default btn-sm',
												'data-width' => 'auto'
											]); ?>
										</td>
									</tr>
									<tr>
										<td><?php echo lang('Sales.amount_tendered'); ?></td>
										<td>
											<?php echo form_input ([
												'name' => 'amount_tendered',
												'value' => '',
												'class' => 'form-control input-sm',
												'size' => '5'
											]); ?>
										</td>
									</tr>
								</table>
							</div>

							<div class='btn btn-sm btn-danger pull-left' id='cancel_receiving_button'><span class="glyphicon glyphicon-remove">&nbsp</span><?php echo lang('Receivings.cancel_receiving') ?></div>
							
							<div class='btn btn-sm btn-success pull-right' id='finish_receiving_button'><span class="glyphicon glyphicon-ok">&nbsp</span><?php echo lang('Receivings.complete_receiving') ?></div>
						</div>
					<?php echo form_close(); ?>
				<?php
				}
				?>
			</div>
		<?php
		}
		?>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function()
{
	$("#item").autocomplete(
	{
		source: '<?php echo site_url($controller_name."/stock_item_search"); ?>',
		minChars:0,
		delay:10,
		autoFocus: false,
		select:	function (a, ui) {
			$(this).val(ui.item.value);
			$("#add_item_form").submit();
			return false;
		}
	});

	$('#item').focus();

	$('#item').keypress(function (e) {
		if (e.which == 13) {
			$('#add_item_form').submit();
			return false;
		}
	});

	$('#item').blur(function()
	{
		$(this).attr('value',"<?php echo lang('Sales.start_typing_item_name'); ?>");
	});

	$('#comment').keyup(function() 
	{
		$.post('<?php echo site_url($controller_name."/set_comment");?>', {comment: $('#comment').val()});
	});

	$('#recv_reference').keyup(function() 
	{
		$.post('<?php echo site_url($controller_name."/set_reference");?>', {recv_reference: $('#recv_reference').val()});
	});

	$("#recv_print_after_sale").change(function()
	{
		$.post('<?php echo site_url($controller_name."/set_print_after_sale");?>', {recv_print_after_sale: $(this).is(":checked")});
	});

	$('#item,#supplier').click(function()
	{
		$(this).attr('value','');
	});

	$("#supplier").autocomplete(
	{
		source: '<?php echo site_url("suppliers/suggest"); ?>',
		minChars:0,
		delay:10,
		select: function (a, ui) {
			$(this).val(ui.item.value);
			$("#select_supplier_form").submit();
		}
	});

	dialog_support.init("a.modal-dlg, button.modal-dlg");

	$('#supplier').blur(function()
	{
		$(this).attr('value',"<?php echo lang('Receivings.start_typing_supplier_name'); ?>");
	});

	$("#finish_receiving_button").click(function()
	{
		$('#finish_receiving_form').submit();
	});

	$("#cancel_receiving_button").click(function()
	{
		if (confirm('<?php echo lang('Receivings.confirm_cancel_receiving'); ?>'))
		{
			$('#finish_receiving_form').attr('action', '<?php echo site_url($controller_name."/cancel_receiving"); ?>');
			$('#finish_receiving_form').submit();
		}
	});

	$("#cart_contents input").keypress(function(event)
	{
		if (event.which == 13)
		{
			$(this).parents("tr").prevAll("form:first").submit();
		}
	});

	table_support.handle_submit = function(resource, response, stay_open)
	{
		if(response.success)
		{
			if (resource.match(/suppliers$/))
			{
				$("#supplier").val(response.id);
				$("#select_supplier_form").submit();
			}
			else
			{
				$("#item").val(response.id);
				if (stay_open)
				{
					$("#add_item_form").ajaxSubmit();
				}
				else
				{
					$("#add_item_form").submit();
				}
			}
		}
	}

	$('[name="price"],[name="quantity"],[name="receiving_quantity"],[name="discount"],[name="description"],[name="serialnumber"]').change(function() {
		$(this).parents("tr").prevAll("form:first").submit()
	});

	$('[name="discount_toggle"]').change(function() {
		var input = $("<input>").attr("type", "hidden").attr("name", "discount_type").val(($(this).prop('checked'))?1:0);
		$('#cart_'+ $(this).attr('data-line')).append($(input));
		$('#cart_'+ $(this).attr('data-line')).submit();
	});

});

</script>

<?php echo view("partial/footer"); ?>
