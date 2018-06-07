<div id="home">

	<div class="box">
		<div class="title" style="padding-left: 10px;">
			Accesos directos
		</div>
		<div>
			<div style="padding: 20px 10px; text-align:center">
				<span style="line-height: 2em">
					<a href="#badlist.php" style="text-decoration:none">
						<img src="{$smarty.const.RESOURCES_DOMAIN}/img/common/big-red-list.png" width="128" height="128" />
					</a>
					<br />
					<a href="#badlist.php">Ver elementos en mal estado</a>
				</span>
			</div>
		</div>
	</div>
	<div style="clear:both"></div>

	<div class="box">
		<div class="title" style="padding-left: 10px;">
			{$lang.noticias}
		</div>
		
		<div>
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
									<img src="{$smarty.const.RESOURCES_DOMAIN}/img/symbol.png" style="float:right; margin: 0 10px 0 25px" height="42" width="42" />
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
	</div>

	<div style="clear: both"><br /></div>
</div>
