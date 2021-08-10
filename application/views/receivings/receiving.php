<?php $this->load->view("partial/header"); ?>

<?php
if (isset($error)) {
	echo "<div class='alert alert-dismissible alert-danger'>" . $error . "</div>";
}

if (!empty($warning)) {
	echo "<div class='alert alert-dismissible alert-warning'>" . $warning . "</div>";
}

if (isset($success)) {
	echo "<div class='alert alert-dismissible alert-success'>" . $success . "</div>";
}
?>

<div id="register_wrapper">

	<!-- Top register controls -->

	<?= form_open($controller_name . "/change_mode", array('id' => 'mode_form', 'class' => 'form-horizontal panel panel-default')); ?>
	<div class="panel-body form-group">
		<ul>
			<li class="pull-left first_li">
				<label class="control-label"><?= $this->lang->line('receivings_mode'); ?></label>
			</li>
			<li class="pull-left">
				<?= form_dropdown('mode', $modes, $mode, array('onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-outline-secondary', 'data-width' => 'fit')); ?>
			</li>

			<?php
			if ($show_stock_locations) {
			?>
				<li class="pull-left">
					<label class="control-label"><?= $this->lang->line('receivings_stock_source'); ?></label>
				</li>
				<li class="pull-left">
					<?= form_dropdown('stock_source', $stock_locations, $stock_source, array('onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-outline-secondary', 'data-width' => 'fit')); ?>
				</li>

				<?php
				if ($mode == 'requisition') {
				?>
					<li class="pull-left">
						<label class="control-label"><?= $this->lang->line('receivings_stock_destination'); ?></label>
					</li>
					<li class="pull-left">
						<?= form_dropdown('stock_destination', $stock_locations, $stock_destination, array('onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-outline-secondary', 'data-width' => 'fit')); ?>
					</li>
			<?php
				}
			}
			?>
		</ul>
	</div>
	<?= form_close(); ?>

	<?= form_open($controller_name . "/add", array('id' => 'add_item_form', 'class' => 'form-horizontal panel panel-default')); ?>
	<div class="panel-body form-group">
		<ul>
			<li class="pull-left first_li">
				<label for="item" , class='control-label'>
					<?php
					if ($mode == 'receive' or $mode == 'requisition') {
					?>
						<?= $this->lang->line('receivings_find_or_scan_item'); ?>
					<?php
					} else {
					?>
						<?= $this->lang->line('receivings_find_or_scan_item_or_receipt'); ?>
					<?php
					}
					?>
				</label>
			</li>
			<li class="pull-left">
				<?= form_input(array('name' => 'item', 'id' => 'item', 'class' => 'form-control input-sm', 'size' => '50', 'tabindex' => '1')); ?>
			</li>
			<li class="pull-right">
				<button id='new_item_button' class='btn btn-primary pull-right modal-dlg' data-btn-submit='<?= $this->lang->line('common_submit') ?>' data-btn-new='<?= $this->lang->line('common_new') ?>' data-href='<?= site_url("items/view"); ?>' title='<?= $this->lang->line('sales_new_item'); ?>'>
					<i class="bi bi-tag pe-1"></i><?= $this->lang->line('sales_new_item'); ?>
				</button>
			</li>
		</ul>
	</div>
	<?= form_close(); ?>

	<!-- Receiving Items List -->

	<table class="sales_table_100" id="register">
		<thead>
			<tr>
				<th style="width:5%;"><?= $this->lang->line('common_delete'); ?></th>
				<th style="width:15%;"><?= $this->lang->line('sales_item_number'); ?></th>
				<th style="width:23%;"><?= $this->lang->line('receivings_item_name'); ?></th>
				<th style="width:10%;"><?= $this->lang->line('receivings_cost'); ?></th>
				<th style="width:8%;"><?= $this->lang->line('receivings_quantity'); ?></th>
				<th style="width:10%;"><?= $this->lang->line('receivings_ship_pack'); ?></th>
				<th style="width:14%;"><?= $this->lang->line('receivings_discount'); ?></th>
				<th style="width:10%;"><?= $this->lang->line('receivings_total'); ?></th>
				<th style="width:5%;"><?= $this->lang->line('receivings_update'); ?></th>
			</tr>
		</thead>

		<tbody id="cart_contents">
			<?php
			if (count($cart) == 0) {
			?>
				<tr>
					<td colspan='9'>
						<div class='alert alert-dismissible alert-info'><?= $this->lang->line('sales_no_items_in_cart'); ?></div>
					</td>
				</tr>
				<?php
			} else {
				foreach (array_reverse($cart, TRUE) as $line => $item) {
				?>
					<?= form_open($controller_name . "/edit_item/$line", array('class' => 'form-horizontal', 'id' => 'cart_' . $line)); ?>
					<tr>
						<td><?= anchor($controller_name . "/delete_item/$line", '<i class="bi bi-trash"></i>'); ?></td>
						<td><?= $item['item_number']; ?></td>
						<td style="align:center;">
							<?= $item['name'] . ' ' . implode(' ', array($item['attribute_values'], $item['attribute_dtvalues'])); ?><br /> <?= '[' . to_quantity_decimals($item['in_stock']) . ' in ' . $item['stock_name'] . ']'; ?>
							<?= form_hidden('location', $item['item_location']); ?>
						</td>

						<?php
						if ($items_module_allowed && $mode != 'requisition') {
						?>
							<td><?= form_input(array('name' => 'price', 'class' => 'form-control input-sm', 'value' => to_currency_no_money($item['price']), 'onClick' => 'this.select();')); ?></td>
						<?php
						} else {
						?>
							<td>
								<?= $item['price']; ?>
								<?= form_hidden('price', to_currency_no_money($item['price'])); ?>
							</td>
						<?php
						}
						?>

						<td><?= form_input(array('name' => 'quantity', 'class' => 'form-control input-sm', 'value' => to_quantity_decimals($item['quantity']), 'onClick' => 'this.select();')); ?></td>
						<td><?= form_dropdown('receiving_quantity', $item['receiving_quantity_choices'], $item['receiving_quantity'], array('class' => 'form-control input-sm')); ?></td>

						<?php
						if ($items_module_allowed && $mode != 'requisition') {
						?>
							<td>
								<div class="input-group">
									<?= form_input(array('name' => 'discount', 'class' => 'form-control input-sm', 'value' => $item['discount_type'] ? to_currency_no_money($item['discount']) : to_decimals($item['discount']), 'onClick' => 'this.select();')); ?>
									<span class="input-group-btn">
										<?= form_checkbox(array('id' => 'discount_toggle', 'name' => 'discount_toggle', 'value' => 1, 'data-bs-toggle' => "toggle", 'data-size' => 'small', 'data-onstyle' => 'success', 'data-on' => '<b>' . $this->config->item('currency_symbol') . '</b>', 'data-off' => '<b>%</b>', 'data-line' => $line, 'checked' => $item['discount_type'])); ?>
									</span>
								</div>
							</td>
						<?php
						} else {
						?>
							<td><?= $item['discount']; ?></td>
							<?= form_hidden('discount', $item['discount']); ?>
						<?php
						}
						?>
						<td>
							<?= to_currency(($item['discount_type'] == PERCENT) ? $item['price'] * $item['quantity'] * $item['receiving_quantity'] - $item['price'] * $item['quantity'] * $item['receiving_quantity'] * $item['discount'] / 100 : $item['price'] * $item['quantity'] * $item['receiving_quantity'] - $item['discount']); ?></td>
						<td><a href="javascript:$('#<?= 'cart_' . $line ?>').submit();" title=<?= $this->lang->line('receivings_update') ?>><i class="bi bi-arrow-repeat"></i></a></td>
					</tr>
					<tr>
						<?php
						if ($item['allow_alt_description'] == 1) {
						?>
							<td style="color: #2F4F4F;"><?= $this->lang->line('sales_description_abbrv') . ':'; ?></td>
						<?php
						}
						?>
						<td colspan='2' style="text-align: left;">
							<?php
							if ($item['allow_alt_description'] == 1) {
								echo form_input(array('name' => 'description', 'class' => 'form-control input-sm', 'value' => $item['description']));
							} else {
								if ($item['description'] != '') {
									echo $item['description'];
									echo form_hidden('description', $item['description']);
								} else {
									echo "<i>" . $this->lang->line('sales_no_description') . "</i>";
									echo form_hidden('description', '');
								}
							}
							?>
						</td>
						<td colspan='7'></td>
					</tr>
					<?= form_close(); ?>
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
		if (isset($supplier)) {
		?>
			<table class="sales_table_100">
				<tr>
					<th style='width: 55%;'><?= $this->lang->line("receivings_supplier"); ?></th>
					<th style="width: 45%; text-align: right;"><?= $supplier; ?></th>
				</tr>
				<?php
				if (!empty($supplier_email)) {
				?>
					<tr>
						<th style='width: 55%;'><?= $this->lang->line("receivings_supplier_email"); ?></th>
						<th style="width: 45%; text-align: right;"><?= $supplier_email; ?></th>
					</tr>
				<?php
				}
				?>
				<?php
				if (!empty($supplier_address)) {
				?>
					<tr>
						<th style='width: 55%;'><?= $this->lang->line("receivings_supplier_address"); ?></th>
						<th style="width: 45%; text-align: right;"><?= $supplier_address; ?></th>
					</tr>
				<?php
				}
				?>
				<?php
				if (!empty($supplier_location)) {
				?>
					<tr>
						<th style='width: 55%;'><?= $this->lang->line("receivings_supplier_location"); ?></th>
						<th style="width: 45%; text-align: right;"><?= $supplier_location; ?></th>
					</tr>
				<?php
				}
				?>
			</table>

			<?= anchor(
				$controller_name . "/remove_supplier",
				'<i class="bi bi-x pe-1"></i>' . $this->lang->line('common_remove') . ' ' . $this->lang->line('suppliers_supplier'),
				array('class' => 'btn btn-danger', 'id' => 'remove_supplier_button', 'title' => $this->lang->line('common_remove') . ' ' . $this->lang->line('suppliers_supplier'))
			); ?>
		<?php
		} else {
		?>
			<?= form_open($controller_name . "/select_supplier", array('id' => 'select_supplier_form', 'class' => 'form-horizontal')); ?>
			<div class="form-group" id="select_customer">
				<label id="supplier_label" for="supplier" class="control-label" style="margin-bottom: 1em; margin-top: -1em;"><?= $this->lang->line('receivings_select_supplier'); ?></label>
				<?= form_input(array('name' => 'supplier', 'id' => 'supplier', 'class' => 'form-control input-sm', 'value' => $this->lang->line('receivings_start_typing_supplier_name'))); ?>

				<button id='new_supplier_button' class='btn btn-primary modal-dlg' data-btn-submit='<?= $this->lang->line('common_submit') ?>' data-href='<?= site_url("suppliers/view"); ?>' title='<?= $this->lang->line('receivings_new_supplier'); ?>'>
					<i class="bi bi-person pe-1"></i><?= $this->lang->line('receivings_new_supplier'); ?>
				</button>

			</div>
			<?= form_close(); ?>
		<?php
		}
		?>

		<table class="sales_table_100" id="sale_totals">
			<tr>
				<?php
				if ($mode != 'requisition') {
				?>
					<th style="width: 55%;"><?= $this->lang->line('sales_total'); ?></th>
					<th style="width: 45%; text-align: right;"><?= to_currency($total); ?></th>
				<?php
				} else {
				?>
					<th style="width: 55%;"></th>
					<th style="width: 45%; text-align: right;"></th>
				<?php
				}
				?>
			</tr>
		</table>

		<?php
		if (count($cart) > 0) {
		?>
			<div id="finish_sale">
				<?php
				if ($mode == 'requisition') {
				?>
					<?= form_open($controller_name . "/requisition_complete", array('id' => 'finish_receiving_form', 'class' => 'form-horizontal')); ?>
					<div class="form-group form-group-sm">
						<label id="comment_label" for="comment"><?= $this->lang->line('common_comments'); ?></label>
						<?= form_textarea(array('name' => 'comment', 'id' => 'comment', 'class' => 'form-control input-sm', 'value' => $comment, 'rows' => '4')); ?>

						<div class="btn btn-danger pull-left" id='cancel_receiving_button'><i class="bi bi-x pe-1"></i><?= $this->lang->line('receivings_cancel_receiving'); ?></div>

						<div class="btn btn-success pull-right" id='finish_receiving_button'><i class="bi bi-check pe-1"></i><?= $this->lang->line('receivings_complete_receiving'); ?></div>
					</div>
					<?= form_close(); ?>
				<?php
				} else {
				?>
					<?= form_open($controller_name . "/complete", array('id' => 'finish_receiving_form', 'class' => 'form-horizontal')); ?>
					<div class="form-group form-group-sm">
						<label id="comment_label" for="comment"><?= $this->lang->line('common_comments'); ?></label>
						<?= form_textarea(array('name' => 'comment', 'id' => 'comment', 'class' => 'form-control input-sm', 'value' => $comment, 'rows' => '4')); ?>
						<div id="payment_details">
							<table class="sales_table_100">
								<tr>
									<td><?= $this->lang->line('receivings_print_after_sale'); ?></td>
									<td>
										<?= form_checkbox(array('name' => 'recv_print_after_sale', 'id' => 'recv_print_after_sale', 'class' => 'checkbox', 'value' => 1, 'checked' => $print_after_sale)); ?>
									</td>
								</tr>
								<?php
								if ($mode == "receive") {
								?>
									<tr>
										<td><?= $this->lang->line('receivings_reference'); ?></td>
										<td>
											<?= form_input(array('name' => 'recv_reference', 'id' => 'recv_reference', 'class' => 'form-control input-sm', 'value' => $reference, 'size' => 5)); ?>
										</td>
									</tr>
								<?php
								}
								?>
								<tr>
									<td><?= $this->lang->line('sales_payment'); ?></td>
									<td>
										<?= form_dropdown('payment_type', $payment_options, array(), array('id' => 'payment_types', 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-outline-secondary', 'data-width' => 'auto')); ?>
									</td>
								</tr>
								<tr>
									<td><?= $this->lang->line('sales_amount_tendered'); ?></td>
									<td>
										<?= form_input(array('name' => 'amount_tendered', 'value' => '', 'class' => 'form-control input-sm', 'size' => '5')); ?>
									</td>
								</tr>
							</table>
						</div>

						<div class='btn btn-danger pull-left' id='cancel_receiving_button'><i class="bi bi-x pe-1"></i><?= $this->lang->line('receivings_cancel_receiving') ?></div>

						<div class='btn btn-success pull-right' id='finish_receiving_button'><i class="bi bi-check pe-1"></i><?= $this->lang->line('receivings_complete_receiving') ?></div>
					</div>
					<?= form_close(); ?>
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
	$(document).ready(function() {
		$("#item").autocomplete({
			source: '<?= site_url($controller_name . "/stock_item_search"); ?>',
			minChars: 0,
			delay: 10,
			autoFocus: false,
			select: function(a, ui) {
				$(this).val(ui.item.value);
				$("#add_item_form").submit();
				return false;
			}
		});

		$('#item').focus();

		$('#item').keypress(function(e) {
			if (e.which == 13) {
				$('#add_item_form').submit();
				return false;
			}
		});

		$('#item').blur(function() {
			$(this).attr('value', "<?= $this->lang->line('sales_start_typing_item_name'); ?>");
		});

		$('#comment').keyup(function() {
			$.post('<?= site_url($controller_name . "/set_comment"); ?>', {
				comment: $('#comment').val()
			});
		});

		$('#recv_reference').keyup(function() {
			$.post('<?= site_url($controller_name . "/set_reference"); ?>', {
				recv_reference: $('#recv_reference').val()
			});
		});

		$("#recv_print_after_sale").change(function() {
			$.post('<?= site_url($controller_name . "/set_print_after_sale"); ?>', {
				recv_print_after_sale: $(this).is(":checked")
			});
		});

		$('#item,#supplier').click(function() {
			$(this).attr('value', '');
		});

		$("#supplier").autocomplete({
			source: '<?= site_url("suppliers/suggest"); ?>',
			minChars: 0,
			delay: 10,
			select: function(a, ui) {
				$(this).val(ui.item.value);
				$("#select_supplier_form").submit();
			}
		});

		dialog_support.init("a.modal-dlg, button.modal-dlg");

		$('#supplier').blur(function() {
			$(this).attr('value', "<?= $this->lang->line('receivings_start_typing_supplier_name'); ?>");
		});

		$("#finish_receiving_button").click(function() {
			$('#finish_receiving_form').submit();
		});

		$("#cancel_receiving_button").click(function() {
			if (confirm('<?= $this->lang->line("receivings_confirm_cancel_receiving"); ?>')) {
				$('#finish_receiving_form').attr('action', '<?= site_url($controller_name . "/cancel_receiving"); ?>');
				$('#finish_receiving_form').submit();
			}
		});

		$("#cart_contents input").keypress(function(event) {
			if (event.which == 13) {
				$(this).parents("tr").prevAll("form:first").submit();
			}
		});

		table_support.handle_submit = function(resource, response, stay_open) {
			if (response.success) {
				if (resource.match(/suppliers$/)) {
					$("#supplier").val(response.id);
					$("#select_supplier_form").submit();
				} else {
					$("#item").val(response.id);
					if (stay_open) {
						$("#add_item_form").ajaxSubmit();
					} else {
						$("#add_item_form").submit();
					}
				}
			}
		}

		$('[name="price"],[name="quantity"],[name="receiving_quantity"],[name="discount"],[name="description"],[name="serialnumber"]').change(function() {
			$(this).parents("tr").prevAll("form:first").submit()
		});

		$('[name="discount_toggle"]').change(function() {
			var input = $("<input>").attr("type", "hidden").attr("name", "discount_type").val(($(this).prop('checked')) ? 1 : 0);
			$('#cart_' + $(this).attr('data-line')).append($(input));
			$('#cart_' + $(this).attr('data-line')).submit();
		});

	});
</script>

<?php $this->load->view("partial/footer"); ?>