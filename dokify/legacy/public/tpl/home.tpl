<div id="home">
	{*
	<div class="page-options">
		<div class="toggle" target="#home-page-options">
			<button class="btn pulsar"><span><span> Configurar Home </span></span></button>
		</div>
		<div class="options" id="home-page-options" >
			<button class="btn"><span><span> Mas botones </span></span></button>
			<button class="btn"><span><span> Reciente </span></span></button>
			<button class="btn"><span><span> Otras opciones </span></span></button>
			<button class="btn"><span><span> Mis Reportes </span></span></button>
			<button class="btn box-it" href="home/seleccionarbusquedas.php"><span><span> Mis Busquedas </span></span></button>
			| 
		</div>
	</div>
	*}
	<table><tr>
		<td class="left-col" style="width: 65%"><div>
			{*
			<div id="home-activity">

			</div>
			*}
			<div id="home-news">
				{if $isUsuario}
				{assign var=company value=$user->getCompany()}
				<div class="box">
					<div class="title" style="padding-left: 10px;">
						{$lang.mi_empresa}
					</div>
					<div class="content" style="height:150px">
						
						<div style="margin:10px">
							{assign var="info" value=$company->getInfo()}
							{assign var="municipio" value=$company->obtenerMunicipio()}
							{assign var="provincia" value=$company->obtenerProvincia()}
							{assign var="pais" value=$company->obtenerPais()}

							<div class="content-col" style="width: 22%; padding:0 1% 0 0;">
								<ul>
									<li style="padding-bottom: 5px;"><h2>
										{if !$company->needsPay()}
											<img src="{$smarty.const.RESOURCES_DOMAIN}/img/common/certified.png" title="{$lang.empresa_certificado_dokify}" height="16px" width="16px" style="vertical-align:top" />
										{/if}
										<a class="box-it" href="ficha.php?m=empresa&poid={$company->getUID()}"  id="my-company-title">{$company->getUserVisibleName()}</a>
									</h2></li>
									{if !$company->esCorporacion()}
										<li>{$info.cif}</li>
									{/if}
									<li>{$info.direccion}</li>
									<li>{if $municipio}{$municipio->getUserVisibleName()},{/if} {if $provincia}{$provincia->getUserVisibleName()}{/if} {if $pais}({$pais->getUserVisibleName()}){/if}</li>
									<li>{$info.representante_legal}</li>
								</ul>
							</div>

							{assign var="contacto" value=$company->obtenerContactoPrincipal()}
							<div class="content-col" style="width: 21%; padding:0 2% 0 0;">
								<ul>
									<li style="padding-bottom: 5px;">
										<h2>
										<a href="empresa/contacto.php?poid={$company->getUID()}" class="box-it">{$lang.contacto}</a>
										<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/phone.png" style="vertical-align: top" />
										</h2>
									</li>
									{if $contacto}
										{assign var="info" value=$contacto->getInfo()}
										<li>{$info.nombre} {$info.apellidos}</li>
										<li style="overflow-x:hidden"><a href="mailto:{$info.email}" target="_blank">{$info.email}</a></li>
										<li>{$info.telefono}</li>
										<li>{$info.movil}</li>
									{else}
										<li>
											<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/exclamation.png" style="vertical-align: top" />
											<a href="empresa/contacto.php?poid={$company->getUID()}" class="box-it">{$lang.define_un_contacto}</a>
										</li>
									{/if}
								</ul>
							</div>
							

							{assign var="docs" value=$company->getDocsInline($user)}
							<div class="content-col" style="width: 14%;">
								<ul>
									<li style="padding-bottom: 5px;">
										<table><tr><td href="empresa/resumendocumentos.php?oid={$company->getUID()}" height="16px" style="font-family:'Open Sans',​Verdana,​sans-serif; font-size: 12px">
										<strong><a href="#documentos.php?m=empresa&poid={$company->getUID()}">{$lang.documentos}</a></strong>
										<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/folder_add.png" class="extended-cell clickable" title="{$lang.resumen_cumplimentacion}" style="vertical-align: top" />
										</td></tr></table>
									</li>
									{foreach from=$docs item=doc key=i}
									{if is_numeric($i)}
										<li style="padding:3px 0">
											<a class="ucase inline-text {$doc.className}" title="{$doc.title}" href="{$doc.href}" style="font-size: 11px;">{$doc.nombre}</a>
										</li>
									{/if}
									{/foreach}
								</ul>
							</div>

							<div class="content-col" style="width: 17%">
								{assign var="options" value=$company->getAvailableOptions($user, true, false, false)}
								<ul>
									<li style="padding-bottom: 5px;"><h2>
										<a class="box-it" href="ficha.php?m=empresa&poid={$company->getUID()}">{$lang.opciones}</a>
										<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/cog.png" style="vertical-align: top; margin-top:1px" height="15px" />
									</h2></li>
									{if is_traversable($options) }
										{foreach from=$options item=option key=i }
											{if $option.uid_accion != 5 && $option.uid_accion != 4 && $option.uid_accion != 125 && $option.uid_accion != 25 && $option.uid_accion != 10 && $option.uid_accion != 52 && $option.uid_accion != 14 && $option.uid_accion != 50 && $option.uid_accion != 33 && $option.uid_accion != 1 && $option.uid_accion != 2 && $option.uid_accion != 19 && $option.uid_accion != 24}
												{if $option.href[0] == "#"}
													{assign var="optionclass" value="unbox-it"}
												{else}
													{assign var="optionclass" value="box-it"}
												{/if}

												<li style="white-space: nowrap; padding-bottom: 3px;">
													<img style="vertical-align: middle;" src="{$option.img}" /> 
													<a href="{$option.href}" class="{$optionclass}">{$option.innerHTML}</a>
												</li>
											{/if}
										{/foreach}
									{/if}
								</ul>
							</div>


							{assign var="superiores" value=$company->obtenerEmpresasCliente()}
							{assign var="unsuitableItemCompanies" value=$company->getUnsuitableItemClient($company)}
							
							<div class="content-col" style="width: 23%;">
								<h2 style="padding-bottom: 5px;">
									<a href="empresa/clients.php" class="box-it">{$lang.clientes} ({$superiores|count})</a>
									<img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/user_gray.png" style="vertical-align: top" />
									{if true === is_countable($unsuitableItemCompanies) && count($unsuitableItemCompanies)}
										<a href="empresa/clients.php" class="box-it"><img src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/bell_error.png" title="{$lang.title_warning}" style="vertical-align: top" /></a>
									{/if}
								</h2>
								<ul style="height: 98px; overflow:auto; line-height: 1em">
									{if true === is_countable($superiores) && count($superiores)}
										{foreach from=$superiores item=cliente key=i}
											<li style="padding-bottom: 1em; white-space:nowrap; overflow:hidden;">
												{if $user->esStaff()}
												<img href="" class="clickable changeprofile" to="{$cliente->getUID()}" rel="company" src="{$smarty.const.RESOURCES_DOMAIN}/img/famfam/user_go.png", title="Saltar a esta empresa" style="vertical-align:middle" height="14" width="14" />
												{/if}

												{$cliente->getUserVisibleName()}
											</li>
										{/foreach}
									{else}
										<li style="padding-bottom: 1em;list-style:none;">{$lang.no_clientes_dokify}</li>
									{/if}
								</ul>
							</div>
							
						</div>
					</div>
				</div>
				{/if}
				<div class="box">
					<div class="title" style="padding-left: 10px;">
						{$lang.noticias} {if $smarty.get.old} - {$lang.antiguas}{/if}
					</div>
				</div>

				{assign var=texto value=$system->getAvisoHome()}
				{if $texto}
					<div class="news alert">
						<div class="box">
							<div class="content">
								 {$texto}
							</div>
						</div>
					</div>
				{/if}

				<div class="news-column {if true === is_countable($breves) && count($breves)}two{/if}" {*style="float: left; width: {if count($breves)}60%;{else}100%{/if}"*}>
					{if is_traversable($noticias)}
						{foreach from=$noticias item=noticia}
							
								
							{if $noticia instanceof noticia}
								<div class="news">
									<div class="box">
										<div class="title">
											{$noticia->getUserVisibleName()}
											<hr style="margin:2px 0;" />
											{assign var="empresa" value=$noticia->getCompany()}
											<span class="date">{$empresa->getUserVisibleName()} - {$noticia->getDate()}</span> 
										</div>
										<div class="content">
											{$noticia->getHTML()|nl2br}
										</div>
									</div>
								</div>
							{else}
								<div class="news blog">
									<div class="box">
										<div class="title">
											<img src="{$smarty.const.RESOURCES_DOMAIN}/img/logos/dokify.png" style="float:right; margin: 0 10px 0 25px" height="48px" />
											{$noticia.title}
											<hr style="margin:2px 0;" />
											<span class="date">
												dokify Blog - 
												{$noticia.post_date|date_format:"%d"} ·
												{$noticia.post_date|date_format:"%m"|get_month_name} ·
												{$noticia.post_date|date_format:"%Y"}
											</span> 
										</div>
										<div class="content">
											{$noticia.post_content|truncate:'500'|nl2br}

											<br /><br />
											
											<a href="{$noticia.ID|get_permalink}" target="_blank">Ver entrada completa en el blog</a>
										</div>
									</div>
								</div>
							{/if}
								
							
						{/foreach}
					{/if}
				</div>
				<div class="news-column {if true === is_countable($breves) && count($breves)}two{/if} breves" {if (true === is_countable($noticias) && !count($noticias)) && (true === is_countable($breves) && count($breves))}style="width: 100%"{/if}>
					{if is_traversable($breves)}
						{foreach from=$breves item=breves}
						<div class="news">
							<div class="box">
								<div class="title">
									{$breves->getUserVisibleName()}
									<hr style="margin:2px 0;" />
									{assign var="empresa" value=$breves->getCompany()}
									<span class="date">{$empresa->getUserVisibleName()} - {$breves->getDate()}</span> 
								</div>
								<div class="content">
									{$breves->getHTML()|nl2br}
								</div>
							</div>
						</div>
						{/foreach}
					{/if}
				</div>

				<div class="box" >
					<div class="content" style="text-align: center; border-bottom:0px">
						{if (true === is_countable($noticias) && !count($noticias)) && (true === is_countable($breves) && !count($breves))}
							{$lang.sin_noticias_recientes}
						{/if}

						{if !isset($smarty.get.old) && $isUsuario}
							<br />

							<a href="#home.php?old=true">{$lang.ver_noticias_antiguas}</a>
						{/if}
					</div>
				</div>
			</div>
		</div></td>
		<td class="right-col" ><div>
			{assign var=empresa value=$user->getCompany()}
			{if is_traversable($busquedas)}
				<div id="home-search">
					{foreach from=$busquedas item=busqueda}
						<div class="box">
							<div class="title"> <a href="#buscar.php?q={$busqueda->obtenerDato('cadena')}">{$busqueda->obtenerDato('nombre')}</a> </div>
							<div class="content search-data" href="comefrom={'Ilistable::DATA_CONTEXT_HOME'|constant}" src="{$busqueda->obtenerDato('cadena')}">

							</div>
						</div>
					{/foreach}
				

					{if (true === is_countable($busquedas) && !count($busquedas))}
						<div class="box">
							<div class="title">
								<a href="#buscar.php?q=tipo:anexo-empresa empresa:{$empresa->getUID()} docs:sin-anexar+tipo:anexo-empresa empresa:{$empresa->getUID()} docs:caducados+tipo:anexo-empresa empresa:{$empresa->getUID()} docs:anulados">Documentación pendiente</a> 
							</div>
							<div class="content search-data" href="comefrom={'Ilistable::DATA_CONTEXT_HOME'|constant}" src="tipo:anexo-empresa empresa:{$empresa->getUID()} docs:sin-anexar+tipo:anexo-empresa empresa:{$empresa->getUID()} docs:caducados+tipo:anexo-empresa empresa:{$empresa->getUID()} docs:anulados"></div>
						</div>
					{/if}
				</div>
			{/if}


			{if $user instanceof usuario && is_traversable($atributosRelevantes) && true === is_countable($atributosRelevantes) && count($atributosRelevantes)}
				<div>
					<div class="box" style="width: 100%; overflow:hidden;">
						<div class="title" style="padding-left:10px;font-size:16px; font-weight:bold;">{$lang.documentos_relevantes}</div>
						<div class="padded">
							<table class="item-list">
								{foreach from=$atributosRelevantes item=atributo}
									{assign var=nombreElemento value=$atributo->getUserVisibleName()}
									{assign var=modulo value=$atributo->getDestinyModuleName()}
									<tr>
										{if isset($icon)}<td><img src="{$atributo->getIcon()}" alt="folder" /></td>{/if}
										<td class="overflow-text"><label for="uid_{$atributo->getUID()}" title="{$nombreElemento}">
											{$nombreElemento} 

											{if isset($solicitante) && $solicitante->referencia }
												· {$solicitante->referencia->getUserVisibleName()}
											{/if}
										</label></td>
										<td style="text-align: right"> 
											<a target="async-frame" name="{$replace.name}" type="{$replace.type}" href="documentorelevante.php?oid={$atributo->getUID()}&send=1" class={$replace.className}>{$lang.descargar}</a>

											{if $modulo == "empresa"}
												{assign var=documento value=$atributo->obtenerDocumentoViaEjemplo($user->getCompany())|reset}
												{if $documento instanceof documento}
													{assign var=option value=$user->getAvailableOptionsForModule('empresa_documento', 'anexar')}
													{if $option = $option.0}
														· <a class="box-it" title="Después de descargar, imprimir, firmar y escanear el documento, haz click para cargarlo" href="{$option.href}&o={$empresa->getUID()}&poid={$documento->getUID()}">{$lang.anexar}</a>
													{/if}
												{/if}
											{/if}
										</td>
									</tr>
								{/foreach}
							</table>
						</div>
						<div style="text-align: center; margin-top: 1em;"> 
							<a href="#documentos.php?m=empresa&poid={$empresa->getUID()}">Ver todos los documentos que tengo que anexar</a>
						</div>

					</div>
				</div>
			{/if}

			{if $user instanceof usuario && $empresa instanceof empresa}
				{assign var="dataexports" value=$empresa->getPublicDataExports()}
				{if $dataexports && true === is_countable($dataexports) && count($dataexports)}
				<div>
					<div class="box" id="home-dataexports">
						<div class="title">{$lang.descarga_de_informes}</div>
						<ul class="padded">
							{foreach from=$dataexports item=dataexport key=i}
								{assign var="inlineData" value=$dataexport->getInlineArray($user,false,$inlineParams)}
								{assign var="model" value=$dataexport->getDataModel()}

								{if $model->isOK()}
								<li>
									<span style="font-size: 12px">{$dataexport->getUserVisibleName()} </span>
									<span style="margin-left: 1em">
									{foreach from=$inlineData item=inline}
										<a href="{$inline.0.href}" target="{$inline.0.target}"><img src="{$inline.img}" alt="{$inline.0.nombre}" title="{$inline.0.nombre}" /></a>
									{/foreach}
									</span>
								</li>
								{/if}
							{/foreach}
						</ul>
					</div>

				</div>
				{/if}
			{/if}
		</div></td>
	</tr></table>
	<div style="clear: both"><br /></div>
</div>
