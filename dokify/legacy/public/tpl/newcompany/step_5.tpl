<div style="float:left;width:100%;padding:10px">
	{assign var=pathImg value=$smarty.const.RESOURCES_DOMAIN|cat:"/img/256x256/green_tick.ico"}
	<h2><img width="20" style="margin-right:10px" src="{$pathImg}">{$lang.title_form_step_five}</h2><br>
	<h4 style="font-size:18px">{$lang.expl_company_easy}</h4><br><br>
	
	<div style="height: 100px">
		<div class="step" style="flaot:left">
			<div style="float:left" class="circle">1</div>
			<div class="step-text"><span>{$lang.expl_company_easy_step1}</span></div>
		</div>

		<div class="step" style="flaot:left">
			<div style="float:left" class="circle">2</div>
			<div class="step-text"><span>{$lang.expl_company_easy_step2}</span></div>
		</div>

		<div class="step" style="flaot:left">
			<div style="float:left" class="circle">3</div>
			<div class="step-text"><span>{$lang.expl_company_easy_step3}</span></div>
		</div>
	</div>
	
	<div style="margin-top:25px"><h3>{$lang.finish_form_expl}</h3></div>
	<div style="text-align:right">
		<form action="/agd/login.php" method="POST"> 
			<input type="hidden" name="usuario" value="{$data.usuario}">
			<input type="hidden" name="password" value="{$data.pass}">
			<button class="finish">{$lang.finish}</button>
		</form>
	</div>
</div>