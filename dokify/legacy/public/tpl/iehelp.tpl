<div style="width: 550px; margin:0 auto; text-align: left;">
	<h3 style="font-size: 16px; color: red;"> {$lang.navegador_muy_antiguo} </h3>
	<br />
	<p style="color: #444; line-height: 1.4em;"> {$lang.navegador_mensaje_seguridad} </p>
	<br />
	<p style="color: #444; line-height: 1.4em;"> {$lang.navegador_mejor_experiencia} </p>


	<div style="font-size: 14px; margin-top: 30px;">
		<div>
			<img src="https://ssl.gstatic.com/ui/v1/icons/mail/browser_chrome.png" width="46px" heigt="46px" style="vertical-align: middle; margin-right: 10px;  float:left" />

			<div style="float:left;"> 
				<span>Google Chrome</span>
				<br />
				<a href="https://www.google.com/intl/{$locale}/chrome/browser/">{$lang.instalar_ahora}</a>
			</div>

			<div style="clear:both"></div>
		</div>


		<div style="margin-top: 20px; ">
			<img src="http://www.mozilla.org/media/img/sandstone/buttons/firefox-small.png" width="46px" heigt="46px" style="vertical-align: middle; margin-right: 10px;  float:left" />

			<div style="float:left;"> 
				<span>Mozilla Fifreox</span>
				<br />
				<a href="http://www.mozilla.org/es-ES/firefox/new/">{$lang.instalar_ahora}</a>
			</div>

			<div style="clear:both"></div>
		</div>
	</div>


	{if !is_ie6()}
		<br />

		<div>
			<div style="font-size: 16px; color: #CD0A0A;border:1px solid #CD0A0A; background-color: #FEF1EC; padding:3px 6px">
				{$lang.fin_soporte_ie7}
			</div >
			<br />
			<a href="#home.php" id="continue" style="float:right">{$lang.no_gracias}</a>
		</div>
	{/if}
</div>