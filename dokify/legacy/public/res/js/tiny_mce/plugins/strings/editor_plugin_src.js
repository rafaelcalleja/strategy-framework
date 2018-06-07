/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

(function(tinymce) {
	tinymce.create('tinymce.plugins.StringsPlugin', {
		init : function(ed, url) {
			// Register commands
			ed.addCommand('mceStrings', function() {
				ed.windowManager.open({
					file : "configurar/plantillaemail/ayuda.php",
					width : 570 + parseInt(ed.getLang('emotions.delta_width', 0)),
					height : 500 + parseInt(ed.getLang('emotions.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url
				});
			});

			// Register buttons
			ed.addButton('strings', {title : 'emotions.emotions_desc', cmd : 'mceStrings'});
		},

		getInfo : function() {
			return {
				longname : 'Cadenas de texto',
				author : 'Jose Ignacio Andrés',
				authorurl : 'http://dokify.net',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('strings', tinymce.plugins.StringsPlugin);
})(tinymce);
