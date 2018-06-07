{literal} 
	<script src="http://jqueryjs.googlecode.com/files/jquery-1.3.2.min.js" type="text/javascript"></script>
	<script type="text/javascript">
    		$(document).ready(function(){
      		$("dd").hide();
		$("dt").click(function(event){
            		 var desplegable = $(this).next();
             		$('dd').not(desplegable).slideUp('fast');
             		 desplegable.slideToggle('fast');
              		event.preventDefault();
             		})
        	});
	</script>
{/literal} 

{if isset ($preguntas)}
	<dl id="lista">
	{foreach from=$preguntas  item=pregunta}
		<dt>
			<div class="box-title" style="background-color: #FBEC88;border-bottom: 1px solid #FAD42E;font-family: Lucida Grande,sans-serif;font-size: 17px;line-height: 2em;min-width: 500px;text-indent: 10px; cursor:pointer">
			{$pregunta}
			</div><br/>
		</dt>
		<dd>
			Respuesta:<textarea clas="editable" style="resize:vertical; width:99%; padding:2px; border: 1px solid #E5E5E5">Escribe una respuesta</textarea><br/>
			Comentario:<textarea  clas="editable" style="resize:vertical; width:99%; padding:2px; border: 1px solid #E5E5E5">Escribe un comentario para valorar</textarea><br/><br/><hr/>
	 	</dd>
	
	{/foreach}
	</dl>	
{/if}
