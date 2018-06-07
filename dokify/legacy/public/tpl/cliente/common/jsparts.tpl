{if is_ie6() && !isset($smarty.get.forceie)}
	{if $locale=="en"} 
		{assign var="error" value="Your browser won't work properly. <br><br> To get a better access to the application please install this plugin <a href='"|cat:$resources|cat:"/ChromeFrameSetup.exe'>here</a> and restart the browser. You can also use <a href='http://www.mozilla-europe.org/es/firefox/'>Firefox</a> or <a href='"|cat:$resources|cat:"/FirefoxPortable.zip'>Firefox Portable</a> if you are not allowed to install any software. <br><br> If you have no other choice, you can try to access <a href='?forceie=true'>using your own browser</a>, but it is not a recommended option."}
	{else}
		{assign var="error" value="TU NAVEGADOR NO FUNCIONARÁ CORRECTAMENTE.<br><br> PARA ACCEDER A LA APLICACION INSTALA EL SIGUIENTE PLUGIN HACIENDO CLICK <a href='"|cat:$resources|cat:"/ChromeFrameSetup.exe'>AQUI</a> Y REINICIA EL NAVEGADOR.<br><br> TAMBIÉN PUEDES USAR <a href='http://www.mozilla-europe.org/es/firefox/'>FIREFOX</a> O <a href='"|cat:$resources|cat:"/FirefoxPortable.zip'>FIREFOX PORTABLE</a> SI NO TE ESTA PERMITIDO INSTALAR SOFTWARE. <br><br> SI NO TIENES OPCIÓN PUEDES INTENTAR ACCEDER CON TU <a href='?forceie=true'>MISMO NAVEGADOR</a>, PERO NO ES ACONSEJABLE"}
	{/if}
	{literal}
		<script>
			try{
				if( navigator.appVersion.indexOf("MSIE") !== -1 ){

					var container = document.getElementById("formularioAcceso");
					container.className = 'ie';
					container.innerHTML = {/literal}"{$error}"{literal};

					var main = document.getElementById("main");
					main.parentNode.removeChild(main);
				}

			}catch(e){ }
		</script>
	{/literal}
{/if}
