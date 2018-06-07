<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Confirmar email</title>
	</head>
	<body>
		<div style="margin:100px auto;text-align:center;font-family:sans-serif;width: 800px;">
			<p>{$lang.texto_confirmar_email}</p>
			<br /><hr /><br />
			<p>{$lang.confirmar_email}</p>
			<form method="post">
				<input autofocus id="input-email" type="text" name="email" value="{$user->getEmail()}" style="text-align:center;width:550px;font-size:30px;padding:5px;border-radius:4px;border:1px solid #D2D2D2;"/>
				<br />

				{if $error}
					<br />
					<div style="margin-top:10px;color:#CD0A0A;border:1px solid #CD0A0A;display:inline;padding:8px;border-radius:3px">{$lang.$error|default:$error}</div>
					<br />
				{/if}

				<br /><br />
				<button type="submit" style="font-size:20px">Confirm</button>
			</form>
		</div>
	</body>
</html>