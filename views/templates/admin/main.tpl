{if isset($message) && $message}{$message}{/if}
{assign var="cstatus" value='selected="selected"'}
<div id="ajax-message-ok" class="conf ajax-message alert alert-success" style="display: none">
	<span class="message"></span>
</div>
<div id="ajax-message-ko" class="error ajax-message alert alert-danger" style="display: none">
	<span class="message"></span>
</div>


<div class="panel">
	<h3><i class="icon-info"></i> {l s='Welcome to AutoProcess Module' mod='autoprocess'}</h3>
	<div id="welcome">
		{l s='Autoprocess is a powerfull module, made to save time on the daily routines. You can use it for daily things like sending orders to warehouse, dropship supplier, remote printing, accounting, withdrawing payment and automatic sending messages to customers and change order statuses etc.' mod='autoprocess'}
	</div>
</div>

<div class="panel">
	<h3><i class="icon-cogs"></i> {l s='Process' }<span class="badge">{$process_list|@count}</span></h3>
	{if $process_list|@count > 0}
		<div class="row">
			<table class="table">
				<thead>
					<tr>
						<th class="fixed-width-xs text-left"><span>{l s='Name' mod='autoprocess'}</span></th>
						<th class="fixed-width-xs center"><span class="title_box">{l s='status' mod='autoprocess'}</span></th>
						<th class="fixed-width-xs center"><span class="title_box">{l s='allow global cron link' mod='autoprocess'}</span></th>
						<th class="fixed-width-xs center"><span class="title_box">{l s='sort by (executed by, lowest first)' mod='autoprocess'}</span></th>
						<th class="fixed-width-xs center"><span class="title_box">{l s='simulate' mod='autoprocess'}</span></th>
						<th class="fixed-width-xs center"><span class="title_box">{l s='trigger' mod='autoprocess'}</span></th>
						<th class="fixed-width-sm"><span class="title_box text-right">{l s='Actions' mod='autoprocess'}</span></th>
					</tr>
				</thead>
				<tbody>
					{foreach $process_list as $process}
						<tr>
							<td class="text-left"><a href="{$module_url}&amp;edit_process=1&amp;id_process={(int)$process['id_process']}">{$process['name']}</a></td>
							<td class="text-center">
								{if ($process['process_active'])}
									<a title="ON" class="list-action-enable ajax_table_link action-enabled" href="{$module_url}&amp;toggle_active_process=off&amp;id_process={(int)$process['id_process']}"><i class="icon-check"></i></a>
								{else}
									<a title="OFF" class="list-action-enable ajax_table_link action-disabled" href="{$module_url}&amp;toggle_active_process=on&amp;id_process={(int)$process['id_process']}"><i class="icon-remove"></i></a>
								{/if}
							</td>
							<td class="text-center">
								{if $process['global_link_active']}
									<a title="ON" class="list-action-enable ajax_table_link action-enabled" href="{$module_url}&amp;toggle_global_link_active=off&amp;id_process={(int)$process['id_process']}"><i class="icon-check"></i></a>
								{else}
									<a title="OFF" class="list-action-enable ajax_table_link action-disabled" href="{$module_url}&amp;toggle_global_link_active=on&amp;id_process={(int)$process['id_process']}"><i class="icon-remove"></i></a>
								{/if}
							</td>
							
							
							<!-- TODO2 make drag and drop sort by - more userfriendly and intuitive /-->
							<td class="text-center">{$process['sort_by']}</td>
							

							<td class="text-center"><a href="{$process['simulate_url']}" target="_blank" title="{l s='Simulate this process - opens in new window' mod='autoprocess'}">{l s='SIMULATE' mod='autoprocess'}</a></td>
							<td class="text-center"><a href="{$process['trigger_url']}" target="_blank" title="{l s='Trigger this process - opens in new window' mod='autoprocess'}">{l s='TRIGGER' mod='autoprocess'}</a></td>
							


							<td>
								<div class="btn-group-action">
									<div class="btn-group pull-right">
										<a href="{$module_url}&amp;edit_process=1&amp;id_process={(int)$process['id_process']}" class="btn btn-default">
											<i class="icon-pencil"></i> {l s='Edit' mod='autoprocess'}
										</a> 
										<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
											<span class="caret"></span>&nbsp;
										</button>
										<ul class="dropdown-menu">
											<li>
												<a href="{$module_url}&amp;edit_process=1&amp;id_process={(int)$process['id_process']}">
													<i class="icon-pencil"></i> {l s='Edit' mod='autoprocess'}
												</a>
											</li>
											<li>
												<a href="{$module_url}&amp;copy_process=1&amp;id_process={(int)$process['id_process']}">
													<i class="icon-pencil"></i> {l s='Copy' mod='autoprocess'}
												</a>
											</li>
											<li>
												<a href="{$module_url}&amp;deleteProcess=1&amp;id_process={(int)$process['id_process']}" onclick="return confirm('{l s='Do you really want to delete this process' mod='autoprocess'}');">
													<i class="icon-trash"></i> {l s='Delete' mod='autoprocess'}
												</a>
											</li>
										</ul>
									</div>
								</div>
							</td>
							
						</tr>
					{/foreach}
				</tbody>
			</table>
			<div class="clearfix">&nbsp;</div>
		</div>
	{else}
		<div class="row alert alert-warning">{l s='No process found.'}</div>
	{/if}
	<div class="panel-footer">
		<a class="btn btn-default pull-right" href="{$module_url}&amp;add_new_process=1"><i class="process-icon-plus"></i> {l s='Add new process' mod='autoprocess'}</a>
	</div>
</div>


<div class="panel">
	<h3><i class="icon-filter"></i> {l s='Filter' mod='autoprocess'}<span class="badge">{$filter_list|@count}</span></h3>
	{if $filter_list|@count > 0}
		<div class="row">
			<table class="table">
				<thead>
					<tr>
						<th><span class="title_box text-left">{l s='Name' mod='autoprocess'}</span></th>
						<th class="fixed-width-sm"><span class="title_box text-right">{l s='Actions' mod='autoprocess'}</span></th>
					</tr>
				</thead>
				<tbody>
					{foreach $filter_list as $filter}
					<tr>
						<td class="text-left"><a href="{$module_url}&amp;edit_filter=1&amp;id_filter={(int)$filter['id_filter']}">{$filter['name']}</a></td>
						<td>
							<div class="btn-group-action">
								<div class="btn-group pull-right">
									<a href="{$module_url}&amp;edit_filter=1&amp;id_filter={(int)$filter['id_filter']}" class="btn btn-default">
										<i class="icon-pencil"></i> {l s='Edit' mod='autoprocess'}
									</a> 
									<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
										<span class="caret"></span>&nbsp;
									</button>
									<ul class="dropdown-menu">
										<li>
											<a href="{$module_url}&amp;edit_filter=1&amp;id_filter={(int)$filter['id_filter']}">
												<i class="icon-pencil"></i> {l s='Edit' mod='autoprocess'}
											</a>
										</li>
										<li>
											<a href="{$module_url}&amp;copy_filter=1&amp;id_filter={(int)$filter['id_filter']}">
												<i class="icon-pencil"></i> {l s='Copy' mod='autoprocess'}
											</a>
										</li>
										<li>
											<a href="{$module_url}&amp;delete_filter=1&amp;id_filter={(int)$filter['id_filter']}" onclick="return confirm('{l s='Do you really want to delete this filter' mod='autoprocess'}');">
												<i class="icon-trash"></i> {l s='Delete' mod='autoprocess'}
											</a>
										</li>
									</ul>
								</div>
							</div>
						</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
			<div class="clearfix">&nbsp;</div>
		</div>
	{else}
		<div class="row alert alert-warning">{l s='No filters found.' mod='autoprocess'}</div>
	{/if}
	<div class="panel-footer">
		<a class="btn btn-default pull-right" href="{$module_url}&amp;add_new_filter=1"><i class="process-icon-plus"></i> {l s='Add new filter' mod='autoprocess'}</a>
	</div>
</div>

<div class="panel">
	<h3><i class="icon-link"></i> {l s='Global Cron Link' mod='autoprocess'}</h3>
	{l s='Here is the global link you should add to some kind of cron service. Its normally set to be triggered every 10 minutes, but that depends on the routines you want Autoprocess to perform.' mod='autoprocess'}<br/>
	<b>{$global_url}</b> <a href="{$global_url}" target="_blank">link</a>
</div>

<div class="panel">
	<h3><i class="icon-eye"></i> {l s='Simulation' mod='autoprocess'}</h3>
	{l s='This link can be used to safely simulate and see for each process a list of those orders that match its filters. This is recommended to use untill your fully familiar with this module. NOTE that simulate link will not pay attention to whether a process is active or not. That is practical, because you can simulate first and activate when your finished tesing and satisfied with result.' mod='autoprocess'}<br/>
	<b>{$simulate_url}</b> <a href="{$simulate_url}" target="_blank">link</a>
</div>

<div class="panel">
	<h3><i class="icon-cogs"></i> {l s='Payment modules' mod='autoprocess'}</h3>
	<form action="{$module_url}" method="post" class="form-horizontal" autocomplete="off">
		<input type="text" id="payment_modules" name="payment_modules" value="{$payment_modules}" />
		<p class="help-block">{l s='If your payment module is not on the list, you can add it here. Do not change, unless youre absolutely sure about the consequences!' mod='autoprocess'}</p> 
		<p class="help-block">{l s='' mod='autoprocess'}</p> 
		<div class="panel-footer" id="toolbar-footer">
			<button class="btn btn-default pull-right" id="submit_payment_modules" name="submitPaymentModules" type="submit"><i class="process-icon-save"></i> <span>{l s='Save' mod='autoprocess'}</span></button>
		</div>
	</form>
</div>

<div class="panel">
	<h3><i class="icon-cogs"></i> {l s='Activate log' mod='autoprocess'}</h3>
	<form action="{$module_url}" method="post" class="form-horizontal" autocomplete="off">
		<select name="ad_to_log" id="ad_to_log">
			<option {if $ad_to_log == 'on'}{$cstatus}{/if} value="on">{l s='On (recommended)'}</option>
			<option {if $ad_to_log == 'off' || $ad_to_log != 'on'}{$cstatus}{/if} value="off">{l s='Off'}</option>
		</select>
		<p class="help-block">{l s='If you want to turn on log. Nice to have! Make you able to track better this module behavior.' mod='autoprocess'}</p> 
		<div class="panel-footer" id="toolbar-footer">
			<button class="btn btn-default pull-right" id="submit_ad_to_log" name="submitAdToLog" type="submit"><i class="process-icon-save"></i> <span>{l s='Save' mod='autoprocess'}</span></button>
		</div>
	</form>
</div>

<div class="panel">
	<h3><i class="icon-waring"></i> {l s='WARNING!' mod='autoprocess'}</h3>
	<div id="warning" style="color:red;font-weight:bold;">
		{l s='Autoprocess is a very powerfull module. So be carefull and be sure You understand its functionality and ask Your developer to check code for conflicts with your Prestashop settings/modules/etc! It can potentially SPAM all your customers, it can MESS UP YOUR DATABASE, it can create chaos for you, IF you however use it without fully understanding the range of your processes and the filters. Please use with great causion and think really all scenarios before you activate a process. If you need help or someone to take a second look at your processes and filters, dont hesitate to buy a support ticket. They can be bought here. Also note that everytime you change an order status manually, it might mean that order will be cought up by one of your Autoprocess processes. Also if you add or remove modules from Prestashop, you should think How will this effect my Autoprocesses? If you are not a well disciplined person, you should be very careull using this tool. We take Absolutely NO responsability for the side effects you may encounter from this module and we encourage to always let Your professional developer run tests, to make sure that this module is fully trustworthy. NOTE also that this module is still young in age which means that we havent secured all scenarios and that it works with all presta settings/modules combinations. As an example you should test if you have a module installed, that effects the things happening when order statuses is changed. A good way to test all scenarios is to use the simulation tool on each process, and if youre satisfied with the effected orders, then trigger the cron link manually.' mod='autoprocess'}
	</div>
</div>



<div class="panel">
	<h3><i class="icon-link"></i> {l s='Direct links to trigger functions/scripts' mod='autoprocess'}<span class="badge">{$direct_call_actions|@count}</span></h3>
	{if $direct_call_actions|@count > 0}
		<div class="row">
			<table class="table">
				<thead>
					<tr>
						<th><span class="title_box text-left">{l s='Name and link' mod='autoprocess'}</span></th>
					</tr>
				</thead>
				<tbody>
					{foreach $direct_call_actions as $function_name => $function_description}
					<tr>
						<td class="text-left"><a href="{$global_url}&trigger_execute_function={$function_name}" target="_blank">{$function_description}</a></td>
					</tr>
					{/foreach}
				</tbody>
			</table>
			<div class="clearfix">&nbsp;</div>
		</div>
	{else}
		<div class="row alert alert-warning">{l s='No direct links.' mod='autoprocess'}</div>
	{/if}
</div>
