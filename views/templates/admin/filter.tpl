{if isset($message) && $message}{$message}{/if}
{assign var="cstatus" value='selected'}
<div class="panel">
	<h3><i class="icon-cogs"></i> {l s='New filters' mod='autoprocess'}</h3>
	<form action="{$module_url}" method="post" class="form-horizontal" onsubmit="return checkForm();">
		<input type="hidden" name="id_filter" id="id_filter" value="{if $id_filter && !$copy_filter}{$id_filter}{/if}" />
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Filter name:'}</label>
			<div class="col-lg-5">
				<input type="text" id="name" name="name" maxlength="150" value="{$name}" />
				<p class="help-block">{l s='Give it a name that indicates which orders will match. eg. All new orders with a comment' mod='autoprocess'}</p>        
			</div>
		</div>
		
		<hr>
		<h3>{l s='Status related' }</h3>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Orders With Current Status' }</label>
			<div class="col-lg-5">
				<select name="current_states[]" multiple >
					{foreach $status_list as $k=>$v}
						{if !empty($current_states)}
							<option {foreach $current_states as $key=>$val}{if ($val eq $v['id_order_state'])}{$cstatus}{/if}{/foreach} value="{$v['id_order_state']}">{$v['name']}</option>
						{else}
							<option value="{$v['id_order_state']}">{$v['name']}</option>
						{/if}
					{/foreach}
				</select>
			</div>
			<p class="help-block">*none selected=no effect (use ctrl+click)</p>
		</div>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Orders That Previously Had Status' }</label>
			<div class="col-lg-5">
				<select name="previous_states[]" multiple >
					{foreach $status_list as $k=>$v}
						{if !empty($previous_states)}
							<option {foreach $previous_states as $key=>$val}{if ($val eq $v['id_order_state'])}{$cstatus}{/if}{/foreach} value="{$v['id_order_state']}">{$v['name']}</option>
						{else}
							<option value="{$v['id_order_state']}">{$v['name']}</option>
						{/if}
					{/foreach}
				</select>
			</div>
			<p class="help-block">*none selected=no effect (use ctrl+click)</p>
		</div>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Orders That Never Had Status' }</label>
			<div class="col-lg-5">
				<select name="never_had_states[]" multiple >
					{foreach $status_list as $k=>$v}
						{if !empty($never_had_states)}
							<option  {foreach $never_had_states as $key=>$val}{if ($val eq $v['id_order_state'])}{$cstatus}{/if}{/foreach} value="{$v['id_order_state']}">{$v['name']}</option>
						{else}
							<option value="{$v['id_order_state']}">{$v['name']}</option>
						{/if}
					{/foreach}
				</select>
			</div>
			<p class="help-block">*none selected=no effect (use ctrl+click)</p>
		</div>
		
		
		<hr>
		<h3>{l s='Delivery address related' }</h3>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Delivery Address Country'}</label>
			<div class="col-lg-5">
				<select name="delivery_countries[]" multiple >
					{foreach $country_list as $k=>$v}
						{if !empty($delivery_countries)}
							<option  {foreach $delivery_countries as $key=>$val}{if ($val eq $v['id_country'])}{$cstatus}{/if}{/foreach} value="{$v['id_country']}">{$v['name']}</option>
						{else}
							<option value="{$v['id_country']}">{$v['name']}</option>
						{/if}
					{/foreach}
				</select>
			</div>
			<p class="help-block">*none selected=no effect (use ctrl+click)</p>
		</div>
		
		
		<hr>
		<h3>{l s='Payment related' }</h3>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Payment Method Used'}</label>
			<div class="col-lg-5">
				<select name="payment_modules[]" multiple >
					{foreach $payment_method_list as $k=>$v}
						{if !empty($payment_modules)}
							<option {foreach $payment_modules as $key=>$val}{if ($val eq $k)}{$cstatus}{/if}{/foreach} value="{$k}">{$v}</option>
						{else}
							<option value="{$k}">{$v}</option>
						{/if}
					{/foreach}
				</select>
			</div>
			<p class="help-block">*none selected=no effect (use ctrl+click)</p>
		</div>
		

		<hr>
		<h3>{l s='Customer account related' }</h3>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Minimum number of orders'}</label>
			<div class="col-lg-2">
				<input type="number" min="0" id="min_num_orders" name="min_num_orders" value="{if $min_num_orders}{$min_num_orders}{else}0{/if}" />
				{l s='orders'}
				<p class="help-block">{l s='*only positive integer allowed - 0=no effect' mod='autoprocess'}</p>       
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Maximum number of orders'}</label>
			<div class="col-lg-2">
				<input type="number" min="0" id="max_num_orders" name="max_num_orders" value="{if $max_num_orders}{$max_num_orders}{else}0{/if}" />
				{l s='orders'}
				<p class="help-block">{l s='*only positive integer allowed - 0=no effect' mod='autoprocess'}</p>       
			</div>
		</div>
		
		
		<hr>
		<h3>{l s='Order age related' }</h3>
		
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Order is'}</label>
			<div class="col-lg-2">
				<select name="order_age_more_or_less_than" >
					<option value="">{l s='-- criteria not used  --' mod='autoprocess'}</option>
					<option {if $order_age_more_or_less_than eq 'more'}{$cstatus}{/if} value="more">{l s='More Than' mod='autoprocess'}</option>
					<option {if $order_age_more_or_less_than eq 'less'}{$cstatus}{/if} value="less">{l s='Less Than' mod='autoprocess'}</option>
				</select>
			</div>
			<div class="col-lg-1">
				<input type="number" min="0" id="order_age_number" name="order_age_number" maxlength="64" value="{if $order_age_number}{$order_age_number}{else}0{/if}" />
				<p class="help-block">{l s='*only positive integer allowed' mod='autoprocess'}</p>       
			</div>
			<div class="col-lg-1">
				<select name="order_age_type" >
					<option {if $order_age_type == 'DAY'}{$cstatus}{/if} value="DAY">Day(s)</option>
					<option {if $order_age_type == 'HOUR'}{$cstatus}{/if} value="HOUR">Hour(s)</option>
					<option {if $order_age_type == 'SECOND'}{$cstatus}{/if} value="SECOND">Second(s)</option>
				</select>
			</div>
			<label class="control-label col-lg-1" style="text-align:left">{l s='Old'}</label>
		</div>
		
		<hr>
		<h3>{l s='Order date related' }</h3>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Order is created on, or after this date'}</label>
			<div class="col-lg-1">
				<input type="text" class="datepicker" id="from_date" name="from_date" value="{$from_date}" placeholder="{l s='After'}" />
				<p class="help-block">{l s='blank=no effect'}</p>
			</div>
			<div class="col-lg-1">
				<a href="#" onclick="$('#from_date').val('');return false;"><i class="icon-trash"></i></a>
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Order is created before date'}</label>
			<div class="col-lg-1">
				<input type="text" class="datepicker" id="to_date" name="to_date" value="{$to_date}" placeholder="{l s='Before'}" />
				<p class="help-block">{l s='blank=no effect'}</p>
			</div>
			<div class="col-lg-1">
				<a href="#" onclick="$('#to_date').val('');return false;"><i class="icon-trash"></i></a>
			</div>
		</div>
		
		<hr>
		<h3>{l s='Order Comments Related' mod='autoprocess'}</h3>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Order Comment Value'}</label>
			<div class="col-lg-2">
				<select name="order_comment_option" id="commentOption">
					<option value=""{if !$order_comment_option}{$cstatus}{/if}>{l s='-- criteria not used  --' mod='autoprocess'}</option>
					<option {if $order_comment_option eq 'isempty'}{$cstatus}{/if} value="isempty">{l s='Is empty' mod='autoprocess'}</option>
					<option {if $order_comment_option eq 'isnotempty'}{$cstatus}{/if} value="isnotempty">{l s='Is not empty' mod='autoprocess'}</option>
					<option {if $order_comment_option eq 'contain'}{$cstatus}{/if} value="contain">{l s='contain' mod='autoprocess'}</option>
					<option {if $order_comment_option eq 'notcontain'}{$cstatus}{/if} value="notcontain">{l s='does not contain' mod='autoprocess'}</option>
				</select>
			</div>
			<div class="col-lg-2" id="containField">
				<input type="text" class="col-lg-2" name="words_in_comment" value="{if $words_in_comment}{$words_in_comment}{/if}" />
				<p class="help-block">{l s='commaseperated if more than one word (comma works like OR. "word1,word2" = word1 OR word2 is in comment)' mod='autoprocess'}</p> 
			</div>
		</div>
		
		
		<hr>
		<h3>{l s='Order Line Related (Product Related)' }</h3>
		<h4>{l s='Please understand that if this filter is used by a process, that uses the "OR" between filers, that it will totally deactivate the order line related filter settings here below, and result in returning all order product lines. The reason is, that system cannot know which filter to use, for knowing which order lines to return. So this below will only have effect if you select "AND" between filters, and then it will return the order product lines that match all the selected filters.' }</h4>
		
		<div class="form-group">
			<label class="control-label col-lg-3"></label>
			<div class="col-lg-5">
				<select name="manufacturer_order_line">
					<option {if ($manufacturer_order_line eq "minimumOneLine")}{$cstatus}{/if} value="minimumOneLine">{l s='One or more product line is from the selected manufacturers'}</option>
					<option {if ($manufacturer_order_line eq "allLines")}{$cstatus}{/if} value="allLines">{l s='ALL order product lines is from ONE of the selected manufacturers'}</option>
				</select>
				<select name="manufacturers[]" multiple >
					{foreach $manufacturer_list as $k=>$v}
						{if !empty($manufacturers)}
							<option {foreach $manufacturers as $key=>$val}{if ($val eq $v['id_manufacturer'])}{$cstatus}{/if}{/foreach} value="{$v['id_manufacturer']}">{$v['name']}</option>
						{else}
							<option value="{$v['id_manufacturer']}">{$v['name']}</option>
						{/if}
					{/foreach}
				</select>
				<p class="help-block">*none selected=no effect (use ctrl+click)</p>
			</div>
		</div>
		
		<hr>
		
		<div class="form-group">
			<label class="control-label col-lg-3"></label>
			<div class="col-lg-5">
				<select name="supplier_order_line">
					<option {if ($supplier_order_line eq "minimumOneLine")}{$cstatus}{/if} value="minimumOneLine">{l s='One or more product line is from the selected suppliers'}</option>
					<option {if ($supplier_order_line eq "allLines")}{$cstatus}{/if} value="allLines">{l s='ALL order product lines is from ONE of the selected suppliers'}</option>
				</select>
				<select name="suppliers[]" multiple >
					{foreach $supplier_list as $k=>$v}
						{if !empty($suppliers)}
							<option  {foreach $suppliers as $key=>$val}{if ($val eq $v['id_supplier'])}{$cstatus}{/if}{/foreach} value="{$v['id_supplier']}">{$v['name']}</option>
						{else}
							<option value="{$v['id_supplier']}">{$v['name']}</option>
						{/if}
					{/foreach}
				</select>
				<p class="help-block">*none selected=no effect (use ctrl+click)</p>
			</div>
		</div>
		
		<hr>
		
		<div class="form-group">
			<label class="control-label col-lg-3"></label>
			<div class="col-lg-5">
				<select name="warehouse_order_line">
					<option {if ($warehouse_order_line eq "minimumOneLine")}{$cstatus}{/if} value="minimumOneLine">{l s='One or more product line is from the selected warehouses'}</option>
					<option {if ($warehouse_order_line eq "allLines")}{$cstatus}{/if} value="allLines">{l s='ALL order product lines is from ONE of the selected warehouses'}</option>
				</select>
				
				<select name="warehouses[]" multiple >
					{foreach $warehouse_list as $k=>$v}  
						{if !empty($warehouses)}
							<option  {foreach $warehouses as $key=>$val}{if ($val eq $v['id_warehouse'])}{$cstatus}{/if}{/foreach} value="{$v['id_warehouse']}">{$v['name']}</option>
						{else}
							<option value="{$v['id_warehouse']}">{$v['name']}</option>
						{/if}
					{/foreach}
				</select>
				<p class="help-block">*none selected=no effect (use ctrl+click)</p>
			</div>
		</div>
		
		<div class="panel-footer" id="toolbar-footer">
			<button class="btn btn-default pull-right" id="submit-filter"  name="SubmitFilter" type="submit"><i class="process-icon-save"></i> <span>{l s='Save' mod='autoprocess'}</span></button>
			<button class="btn btn-default pull-right" id="submit-filter"  name="SubmitFilterStay" type="submit"><i class="process-icon-save"></i> <span>{l s='Save and stay' mod='autoprocess'}</span></button>
			<a class="btn btn-default" href="{$module_url}"><i class="process-icon-cancel "></i> <span>{l s='Cancel' mod='autoprocess'}</span></a>
		</div>
	</form>
</div>
{literal}
<script type="text/javascript">
	$(document).ready(function() {
		/*
		$('#submit-filter').on( "click", function(){
			var myArray = [];
			$('select').each(function(){
				var test = $(this).find('option:selected').val();
				myArray.push(test);
			});
			for (var i = 0; i < myArray.length; i++) {
				if( myArray[ i ] == '0'){
					alert('Please select option');
					return false;
				}
			}
			return true;
		});
		*/

		$("#from_date").datepicker({dateFormat:'yy-mm-dd'});
		$("#to_date").datepicker({dateFormat:'yy-mm-dd'});

		$('#commentOption').on('change',function(){
			if ($(this).find('option:selected').val() === 'contain' || $(this).find('option:selected').val() === 'notcontain') {
				$('#containField').css('display','block');
			} else {
				$('#containField').css('display','none');
			}
		});
		$('#commentOption').trigger('change');		
	});
</script>
{/literal}