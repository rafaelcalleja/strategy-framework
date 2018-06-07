<div id="passCont" style="float:left;width:100%;padding:10px">
	<form action="?step=2" method="post">
		<h2>{$lang.title_form_step_one}</h2>

		<table class="form">

			<div class="containerHr">
				{$lang.signup_form_definition_user}
			</div>

			<table class="form">
				<tr>
					<td>{$lang.usuario}<br/>{if isset($error.usuario)}<span style="color:red;font-size:14px">{$lang.signup_form_error_username}<span>{/if}</td>
					<td><input name="usuario" {if isset($error.usuario)}class="error"{/if} type="text" {if isset($data.usuario)}value="{$data.usuario}"{/if} autocomplete="off"></td>
				</tr>

				<tr>
					<td>{$lang.nombre}<br/>{if isset($error.nombre)}<span style="color:red;font-size:14px">{$lang.signup_form_error_nombre}<span>{/if}</td>
					<td><input name="nombre" type="text"  {if isset($error.nombre)}class="error"{/if} {if isset($data.nombre)}value="{$data.nombre}"{/if} autocomplete="off"/></td></tr>
				</tr>
				<tr>
					<td>{$lang.apellidos}<br/>{if isset($error.apellidos)}<span style="color:red;font-size:14px">{$lang.signup_form_error_apellidos}<span>{/if}</td>
				 	<td><input name="apellidos" type="text"  {if isset($error.apellidos)}class="error"{/if} {if isset($data.apellidos)}value="{$data.apellidos}"{/if} autocomplete="off"/></td>
				 </tr>
				
				<tr>
					<td>{$lang.telefono}<br/>{if isset($error.telefono)}<span style="color:red;font-size:14px">{$lang.signup_form_error_telefono}<span>{/if}</td>
					 <td><input name="telefono" type="text"  {if isset($error.telefono)}class="error"{/if} {if isset($data.telefono)}value="{$data.telefono}"{/if} autocomplete="off" /></td>
				</tr>
				<tr>
					<td>{$lang.email}<br/>{if isset($error.email)}<span style="color:red;font-size:14px">{$lang.signup_form_error_email}<span>{/if}</td>
					<td><input name="email" {if isset($error.email)}class="error"{/if} type="text" autocomplete="off" {if isset($data.email)}value="{$data.email}"{/if} /></td>
				</tr>
				<tr>
					<td>{$lang.dni}<br/><span style="color:black;font-size:11px">* {$lang.form_optional_field}<span></td>
					<td><input name="id" {if isset($error.id)}class="error"{/if} type="text" autocomplete="off" {if isset($data.id)}value="{$data.id}"{/if} /></td>
				</tr>
			</table>
			
			<div class="containerHr">
				{$lang.signup_form_definition_password}
			</div>
	
			<table class="form password-container">
				<tr>
					<td>{$lang.pass}<br/>{if isset($error.pass)}<span style="color:red;font-size:14px">{$lang.signup_form_error_pass}<span>{/if}</td>
				 	<td><input id="pass" name="pass" type="password" class="strong-password {if isset($error.pass)}error{/if} " data-debil="{$lang.contrasena_debil}" data-aceptable="{$lang.contrasena_aceptable}" data-fuerte="{$lang.contrasena_fuerte}" autocomplete="off"  {if isset($data.pass)}value="{$data.pass}"{/if}  /></td></tr>
				<tr>
					<td>{$lang.repite_pass}</td> <td><input id="pass2" name="pass2" type="password"  autocomplete="off" {if isset($data.pass2)}value="{$data.pass2}"{/if}  class="strong-password"/></td>
	          	</tr>
	          	<tr>
					<td></td>
					 <td>
					 	<div class="strength-container strength">
							<div class="meter-container meter-strength"></div>
	          		 	</div>
	          		 </td>
	          	</tr>
          </table>
		</table>

		


		<div style="text-align:right;margin:50px 0px 0px 0px;clear:both">
			<input type="hidden" name="send" value="1" />
			<button class="continue">{$lang.siguiente}</button>
		</div>
	</form>
</div>
