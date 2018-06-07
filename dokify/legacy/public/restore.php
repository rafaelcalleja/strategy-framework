<?php
	include("config.php");
	$tpl = Plantilla::singleton();

	$selectedUserName = $_REQUEST["u"];
?>
<div class="box-title">
	Usuario inactivo...
</div>
<form method="post" action="login.php" onsubmit="this.action=this.action+'?goto='+encodeURIComponent(location.pathname)+encodeURIComponent(location.hash);">
	<div class="cbox-content">
		<table><tr>
			<td><?=$tpl->getString("pass")?></td> 
			<td style="text-align: right"><input type="password" name="password" style="width: 300px"/></td>
		</tr></table>
	</div>
	<input type="hidden" value="<?=$selectedUserName?>" name="usuario"/>
	<div class="cboxButtons">
		<button class="btn"><span><span><?=$tpl->getString("reiniciar_sesion")?></span></span></button>
	</div>
</form>
