{assign var="empresa" value=$user->getCompany()}
{if isset($smarty.get.step) && $smarty.get.step > 0 }
	{if $smarty.get.step == 1}
		<p>
			Welcome to dokify. This is the assistant where we are going to introduce you to the app.
		</p>
		<p>			 	
			At the main menu, which it is split in tabs, you can find the main options. 
		</p>
		<p>
			Let's get started with an example: Click in the tab <a href="#empleado/listado.php"><strong>Staff</strong></a>.
		</p>
		
	{/if}
	
	{if $smarty.get.step == 2}
		<p>
			If you want to add a new employee, you must find the icon you can see at the left and click it, after that fill in the form in order to add a new staff member.
			<br /><br />
			<img class="box-it" href="empleado/nuevo.php?poid={$empresa->getUID()}" src="{$resources}/img/48x48/iface/boxadd.png" style="cursor:pointer; float: left; margin-left: 10px;" height="34px" />
			<br /><br />
		</p>
		<p>
			When you have added a staff member, you can carry on with the next step.
		</p>
	{/if}

	{if $smarty.get.step == 3}
		{assign var=mustPayCompanies value=$empresa->pagoPorSubcontratacion()}
		{assign var=noPay value=$empresa->obtenerDato('pago_no_obligatorio')}
		{assign var=isFree value=$empresa->isFree()}
		{if !$noPay && $isFree && $mustPayCompanies}
			<p>
				The client that invites you ask you to the dokify Certificate. Besides the dokify Certificate, you can benefit from many advantages: express upload, multiuser, higher upload capacity, or employees access, among other advantages.
			</p>
			<p>
				To hire the premium plan click here: <a class="btn" href="/app/payment/license"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/common/certified.png" /> {$lang.contratar_plan_premium}</span></span></a> and you complete the pay process in order to benefit from all the dokify advantages.				
			</p>
		{elseif $isFree}
			<p>
				With your Free plan you can upload yours client documentation but you have to do it one by one. If you want to upload the documentation to all your clients by once, request your own documents and benefit from many advantages sign on the Premium plan.
			</p>
			<p>
				How? click here: <a class="btn" href="/app/payment/license"><span><span> <img src="{$smarty.const.RESOURCES_DOMAIN}/img/common/certified.png" /> {$lang.contratar_plan_premium}</span></span></a> and you complete the pay process in order to benefit from all the dokify advantages.
			</p>
			<p>
				Click <a href="/app/payment/license">here</a> if you want to know more.	
			</p>			
		{else}
			<p>
				You have the Premium plan already. Now we are going to learn how to send documentation.
			</p>
		{/if}
	{/if}

	{if $smarty.get.step == 4}
		<div style="margin: 10px">
			We continue with the example. Now you have to choose what kind of work your staff do. 
			<br /><br />
			Without leave the <strong>Staff</strong> tab, click the button <div class="select" style="display:inline"><ul style="display:inline"><li style="margin:1px;padding:1px 3px;line-height:18px;display:inline">Options</li></ul></div> (at the right) in the employee you want to configure. Now you can see a new menu. Pick the <div class="select" style="display:inline"><ul style="display:inline"><li style="margin:1px;padding:1px 3px;line-height:18px;display:inline">Assignments</li></ul> option</div>.
		</div>
	{/if}

	{if $smarty.get.step == 5}
		<div style="margin: 8px">
			You must move into the <strong>“Assigned”</strong> area all the elements that should be associated with each employee. For example their jobs or projects they are working in.
			<br /><br />
			Click <div class="select" style="display:inline"><ul style="display:inline"><li style="margin:1px;padding:1px 3px;line-height:18px;display:inline">Save</li></ul></div> button when you have finished. 
			To see the requested documents click <div class="select" style="display:inline"><ul style="display:inline"><li style="margin:1px;padding:1px 3px;line-height:18px;display:inline">Options</li></ul></div> > <div class="select" style="display:inline"><ul style="display:inline"><li style="margin:1px;padding:1px 3px;line-height:18px;display:inline">Documents</li></ul></div>.
		</div>
	{/if}

	{if $smarty.get.step == 6}
		<div style="margin: 8px">
			These are the requested documents. The label colors inform us about the document status (validated, expired...)
			<br /><br />		
			In order to send a document you must click <div class="select" style="display:inline"><ul style="display:inline"><li style="margin:1px;padding:1px 3px;line-height:18px;display:inline">Options</li></ul></div> > <div class="select" style="display:inline"><ul style="display:inline"><li style="margin:1px;padding:1px 3px;line-height:18px;display:inline">Upload</li></ul></div>
			and fill in the form before click upload button.			
		</div>
	{/if}
{else}
	<div style="margin: 8px">
		<br /><br /><br /><br />
		<center>{$lang.mensaje_ocultar_asistente}</center> 									
	</div>
{/if}
