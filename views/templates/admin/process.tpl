{if isset($message) && $message}{$message}{/if}
{assign var="cstatus" value='selected'}
{assign var="is_checked" value='checked'}

		
<div class="panel">
	<h3><i class="icon-cogs"></i> {l s='Process' mod='autoprocess'}</h3>
	<form action="{$module_url}" id="processEditTemp" method="post" class="form-horizontal" onsubmit="return checkForm();">
		<input type="hidden" name="id_process" id="id_process" value="{if $id_process && !$copy_process}{$id_process}{/if}" />
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Process name:'}</label>
			<div class="col-lg-5">
				<input type="text" id="name" name="name" maxlength="150" value="{$name}" />
				<p class="help-block">{l s='*Give it a good long name that explains and makes sense. eg. Send track and trace message to shipped orders'}</p>
			</div>
		</div>
		
		
		<!-- TODO2 hide/remove this sort by and in stead make it drag and drop on main page /-->
		<hr>
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Processes executed by (sort by, lowest first):'}</label>
			<div class="col-lg-2">
				<input type="number" id="sort_by" min="0" name="sort_by" maxlength="20" value="{$sort_by}" style="width:90px;" />
				<p class="help-block">{l s='"*enter a positive integer(a number). Explanation: Which process to run first, second, third, etc. Lowest number will be executed first. eg. you want to run process --if package is delivered set status to Delivered-- before you run process --Warn customer service that package been more than 3 days on the road--'}</p>
			</div>
		</div>
		



		<hr>
		<h3>{l s='Is Process active?' mod='autoprocess'}</h3>
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Active:'}</label>
			<div class="col-lg-2">
				<input type="checkbox" value="1" name="process_active" {if $process_active}{$is_checked}{/if} />                
				<p class="help-block">{l s='unchecking means that it will never be runned'}</p> 
			</div>
		</div>
		
		
		<hr>
		<h3>{l s='When is this process allowed to run?' mod='autoprocess'}</h3>
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Main date/time criteria to meet' mod='autoprocess'}</label>
			<div class="col-lg-3">
				<select name="unique_time_criteria_method" >
					<option value=""></option>
					{foreach $unique_time_criteria_method_list as $k=>$v}
						<option {if $unique_time_criteria_method eq $k}{$cstatus}{/if} value="{$k}">{$v}</option>
					{/foreach}
				</select>
				<p class="help-block">{l s='*Optional. (blank=no effect)'}</p>
				<p class="help-block">{l s='Note! both this AND the time ranges below have to be fulfilled, in order for this process to be runned.'}</p>
			</div>
		</div>
		
		
		
		{foreach from=$weekdays key=key item=item}
			<div class="form-group">
				<label class="control-label col-lg-3">{l s=$item}:</label>
				<div class="col-lg-1">
					<input type="checkbox" name="weekdays[{$item}]" {if !$id_process} checked="checked"{else}{foreach from=$process_from key=k item=v}{if $k eq $item } checked="checked"{/if}{/foreach}{/if} value="1">
				</div>
				
				<label class="control-label col-lg-1">{l s='From:'}</label>
				<div class="col-lg-2">
					<select name="from_hour_{$item}" style="width:50%;float:left;">
						{if $process_from|@count neq 0}
							{foreach from=$process_from key=k item=v name="fromHours"}
								{assign var=time value=':'|explode:$v.0}
								{if $k neq $item }
									{assign var=hour value='0'}
								{else}
									{assign var=hour value=$time.0} {break} 
								{/if}
							{/foreach}
							{html_options values=$hours options=$hours selected=$hour}
						{else}
							 {html_options values=$hours options=$hours selected='0'}
						{/if}
					</select>
					<select name="from_min_{$item}" style="width:50%;float:left;">
						{if $process_from|@count neq 0}
							{foreach from=$process_from key=km item=vm name="fromMin"}
								{assign var=time value=':'|explode:$vm.0}
								{if $km neq $item }
									{assign var=minute value='0'}
								{else}
									{assign var=minute value=$time.1} {break} 
								{/if}
							{/foreach}
							{html_options values=$minutes options=$minutes selected=$minute}
						{else}
							{html_options values=$minutes options=$minutes selected='0'}
						{/if}
					</select>
				</div>
				
				<label class="control-label col-lg-1">{l s='To:'}</label>
				<div class="col-lg-2">
					<select name="to_hour_{$item}" style="width:50%;float:left;">
						{if $process_to|@count neq 0}
							{foreach from=$process_to key=k item=v}
								{assign var=time value=':'|explode:$v.0}
								{if $k neq $item }
									{assign var=hour value='23'}
								{else}
									{assign var=hour value=$time.0} {break} 
								{/if}
							{/foreach}
							{html_options values=$hours options=$hours selected=$hour}
						{else}
							{html_options values=$hours options=$hours selected='23'}
						{/if}
					</select>
					<select name="to_min_{$item}" style="width:50%;float:left;">
						{if $process_to|@count neq 0}
							{foreach from=$process_to key=km item=vm name="toMin"}
								{assign var=time value=':'|explode:$vm.0}
								{if $km neq $item }
									{assign var=minute value='50'}
								{else}
									{assign var=minute value=$time.1} {break} 
								{/if}
							{/foreach}
							{html_options values=$minutes options=$minutes selected=$minute}
						{else}
							{html_options values=$minutes options=$minutes selected='50'}
						{/if}
					</select>
				</div>
			</div>
		{/foreach}
		
		<hr>
		<h3>{l s='Which Filters should be used?' mod='autoprocess'}</h3>
		<div class="form-group">
			<div class="col-lg-3">{l s='Select the filters orders should match' mod='autoprocess'}</div>
			<div class="col-lg-5">
				<select name="filters[]" multiple >
					{foreach $filter_list as $k=>$v}  
						{if isset($filters) && !empty($filters)}
							<option  {foreach $filters as $key=>$val}{if ($val eq $v['id_filter'])}{$cstatus}{/if}{/foreach} value="{$v['id_filter']}">{$v['name']}</option>
						{else}
							<option value="{$v['id_filter']}">{$v['name']}</option>
						{/if}
					{/foreach}
				</select>
				<p class="help-block">{l s='use ctrl+click'}</p>
			</div>
		</div>
		
		
		<hr>
		<h3>{l s='How will above Filters be used?' mod='autoprocess'}</h3>
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='AND & OR Operators between filters:' mod='autoprocess'}</label>
			<div class="col-lg-2">
				<select name="and_or_between_filters" >
					<option {if (isset($and_or_between_filters) && $and_or_between_filters eq "AND" || $and_or_between_filters neq "OR")}{$cstatus}{/if} value="AND">AND</option>
					<option {if ($and_or_between_filters eq "OR")}{$cstatus}{/if} value="OR">OR ({l s='will deactivate product line filters'})</option>
				</select>
				<p class="help-block">{l s='*will only have effect if you picked more than one filter'}</p>
				<p class="help-block">{l s='eg. AND: select orders where filter1 AND filter2 AND filter3 is matching'}</p>
				<p class="help-block">{l s='WARNING! if you pick OR, It will deactivate the product line filters. System will allways return all product lines.'}</p>
			</div>
		</div>
			
		
		<hr>
		<h3>{l s='Will global cron link run/trigger this process?' mod='autoprocess'}</h3>
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Allow to be triggered by global cron link:'}</label>
			<div class="col-lg-2">
				<input type="checkbox" value="1" name="global_link_active" {if $global_link_active}{$is_checked}{/if}>
				<p class="help-block">{l s='unchecking means that it can only be triggered by the process cron link (which you find below)'}</p>
			</div>
		</div>
		
		
		<hr>
		<h3>{l s='How to trigger this Proces' mod='autoprocess'}</h3>
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='URL to trigger this process' mod='autoprocess'}</label>
			<div class="col-lg-9">
				{if $id_process && !$copy_process}
					{$cron_url} <a href="{$cron_url}" target="_blank">link</a>
					<p class="help-block">{l s='copy this link and add it to your cron job service or trigger URL manually whenever you want. Note that if global link is not activated, this will be the only way you can trigger this process. Also note that this link will only run/trigger this process, if its set to active!'}</p>
				{else}
					{l s='You need to save first time, to see URL here.' mod='autoprocess'}
				{/if}
			</div>
		</div>
		
		{if $id_process && !$copy_process}
			<hr>
			<h3>{l s='Simulation' mod='autoprocess'}</h3>
			<div class="form-group">
				<label class="control-label col-lg-3">{l s='URL simulate this process' mod='autoprocess'}</label>
				<div class="col-lg-9">
					{$simulate_url} <a href="{$simulate_url}" target="_blank">link</a>
					<p class="help-block">{l s='Use this link to safely simulate and see for this particular process a list of those orders that match its filters. This is recommended to do untill your fully familiar with this module. NOTE that simulate link will not pay attention to whether this process is active or not. That is practical, because you can simulate first and activate your process when your finished tesing and satisfied with result.' mod='autoprocess'}</p>
				</div>
			</div>
		{/if}
		
		
		<hr>
		<h3>{l s='Actions! What to do with matching orders?' }</h3>
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='Call PHP Class method (high risk!)' }</label>
			<div class="col-lg-5">
				<select name="action_call_method" >
					<option value="">{l s='Disabled (recommended! will return True without doing anything)' }</option>
					{foreach $action_call_method_list as $k=>$v}
						<option {if isset($action_call_method) && $action_call_method eq $k}{$cstatus}{/if} value="{$k}">{$v}</option>
					{/foreach}
				</select>
				<p class="help-block">{l s='if no Class method is selected=moving on to next which is order status change' }</p>
				<p class="help-block">{l s='NOTE! if a Class method is selected, process will ONLY move on to next action if True is returned from the called Class method' }</p>
				<p class="help-block">{l s='WARNING! do not use if you dont fully understand the consequiences!' }</p>
				<p class="help-block">{l s='If you want to add your own php Class method, this can be done in the autoprocess module php files. Ask a professional developer to help you.' }</p>
			</div>
		</div>
		
		
		{* if Class method returns True, move on to set status *}
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='(if above return True) Set Order Status to' }</label>
			<div class="col-lg-5">
				<select name="action_set_order_state_succeded" >
					<option value="">--</option>
					{foreach $status_list as $k=>$v}
						<option {if isset($action_set_order_state_succeded) && $action_set_order_state_succeded eq $v['id_order_state']}{$cstatus}{/if} value="{$v['id_order_state']}">{$v['name']}{if isset($v['template']) && $v['template']} ({$v['template']}){/if}</option>
					{/foreach}
				</select>
				<p class="help-block">{l s='*none selected=order status will not be changed' }</p>
				<p class="help-block">{l s='NOTE be carefull here! You must always change order somehow, so it doesnt enter a loop. So either change something to order using a Class method or move it to a new status, so that this process will not loop orders.' }</p>
			</div>
			<div class="col-lg-2">
				<input type="checkbox" value="1" name="action_alert_on_true" {if $action_alert_on_true}{$is_checked}{/if} />
				<label class="control-label">{l s='and trigger an email alert to shop email'}</label>
				<p class="help-block">{l s='checking will trigger an email alert to shop email'}</p> 
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='(if above return False) Set Order Status to' }</label>
			<div class="col-lg-5">
				<select name="action_set_order_state_failed" >
					<option value="">--</option>
					{foreach $status_list as $k=>$v}
						<option {if isset($action_set_order_state_failed) && $action_set_order_state_failed eq $v['id_order_state']}{$cstatus}{/if} value="{$v['id_order_state']}">{$v['name']}{if isset($v['template']) && $v['template']} ({$v['template']}){/if}</option>
					{/foreach}
				</select>
				<p class="help-block">{l s='*none selected=order status will not be changed' }</p>
				<p class="help-block">{l s='NOTE be carefull here! You must always change order somehow, so it doesnt enter a loop. So either change something to order using a Class method or move it to a new status, so that this process will not loop orders.' }</p>
			</div>
			<div class="col-lg-2">
				<input type="checkbox" value="1" name="action_alert_on_false" {if $action_alert_on_false}{$is_checked}{/if} />
				<label class="control-label">{l s='and trigger an email alert to shop email'}</label>
				<p class="help-block">{l s='checking will trigger an email alert to shop email'}</p> 
			</div>
		</div>
		
		<div class="form-group">
			<label class="control-label col-lg-3">{l s='*using employee' }</label>
			<div class="col-lg-5">
				<select name="id_employee" >
					{foreach $employee_list as $k=>$v}
						<option {if isset($id_employee) && $id_employee eq $v['id_employee']}{$cstatus}{/if} value="{$v['id_employee']}">{$v['firstname']} {$v['lastname']} ({$v['email']})</option>
					{/foreach}
				</select>
			</div>
		</div>
		
		
		
		<div class="panel-footer" id="toolbar-footer">
			<button class="btn btn-default pull-right" id="submit-process"  name="SubmitProcess" type="submit"><i class="process-icon-save"></i> <span>{l s='Save' mod='autoprocess'}</span></button>
			<button class="btn btn-default pull-right" id="submit-process"  name="SubmitProcessStay" type="submit"><i class="process-icon-save"></i> <span>{l s='Save and stay' mod='autoprocess'}</span></button>
			<a class="btn btn-default" href="{$module_url}"><i class="process-icon-cancel "></i> <span>Cancel</span></a>
		</div>
	</form>
</div>