define(function(require, exports, module) {

	function uploader (input) {
		var _this, xhr, callback, uuid, maxsize;

		_this		= this;
		callback	= 'async_upload_';
		uuid 		= 0;
		xhr 		= null;
		maxsize		= 0;

		function onComplete (res, status) {
			$(_this).trigger('complete', [res, status]);
		};

		function onProgress (XMLHttpRequestProgressEvent) {
			var progress = Math.round((XMLHttpRequestProgressEvent.loaded * 100) / XMLHttpRequestProgressEvent.total);
			$(_this).trigger('progress', [progress, XMLHttpRequestProgressEvent]);
		};

		function uploadByXHR (url, file) {
			xhr = new XMLHttpRequest();

			if (maxsize && file.size > maxsize) {
				return onComplete(null, 413); // --- http standar response
			}

			xhr.upload.onprogress = onProgress;
			// xhr.upload.onload = options.onload;
			// xhr.upload.onerror = function () {};

			xhr.open('put', url, true);
			xhr.setRequestHeader("Content-Type", "multipart/form-data");
			xhr.setRequestHeader("X-File-Name", encodeURIComponent(file.name));
			xhr.setRequestHeader("X-File-Size", file.size);
			xhr.setRequestHeader("X-File-Type", file.type || "");

			xhr.onreadystatechange = function(){ 
				if (xhr.readyState == 4) {
					var response, contentType;

					response 	= xhr.responseText;
					contentType = xhr.getResponseHeader('Content-type');

					if (contentType.indexOf('application/json') !== -1) {
						response = $.parseJSON(response);
					}

					if (xhr.status==200) {
						onComplete(response, xhr.status)
					} else {
						onComplete(response, xhr.status);
					}
				}
			};

			xhr.send(file);
		};

		function uploadByForm (url, input) {
			var form, action, enctype, method, target, events, onSubmitEvents, cncat, frame, callbackName, i;

			if (input.form) {
				form = input.form;
				action = form.action;
				enctype = form.enctype;
				method = form.method;
				target = $(form).attr('target')
				events = $._data( form, "events" );
				cncat = url.indexOf('?') === -1 ? '?' : '&';
				callbackName = callback + (uuid++);
				onSubmitEvents = [];
				frame = $(document.createElement('iframe')).attr({id:callbackName, name:callbackName}).appendTo('body');

				// -- use in other methods
				xhr = frame;

				// --- if we have a submit controller
				if (events && events.submit) {
					for (i in events.submit) {
						if (!isNaN(i) && events.submit.hasOwnProperty(i)) {
							onSubmitEvents.push(events.submit[i].handler);
						}
					};

					$(form).off('submit');
				};

				// --- send error after load without message
				$(frame).one('load', function () {
					frame.remove();
					setTimeout(function () {
						onComplete();
					}, 1000);
				});

				window[callbackName] = function (res, code) {
					code = code || 500;

					// -- not trigger error
					$(frame).unbind('load').remove();

					onComplete(res, code);
	
					window[callbackName] = undefined;
				};



				$(form).attr({
					action: url + cncat + 'callback=' + callbackName,
					enctype: 'multipart/form-data',
					method: 'post',
					target: callbackName
				}).submit();


				// --- restore inmediatly after submit
				$(form).attr({
						action: action,
						enctype: enctype,
						method: method,
						target: target
				});

				for (i in onSubmitEvents) {
					$(form).on('submit', onSubmitEvents[i]);
				};
			}
		};

		this.setMaxSize = function (size) {
			maxsize = size;
		};

		this.abort = function () {
			if (xhr) {
				if (window.File && input.files) {
					xhr.onreadystatechange = function () {};
					xhr.abort();
				} else {
					$(xhr).remove();
				}
			}
		};

		this.submit = function (url) {
			if (window.File && input.files) {
				return uploadByXHR(url, input.files[0]);
			} else {
				return uploadByForm(url, input);
			}
		}

	};

	uploader.init = function () {
		return new uploader(this);
	};

	return uploader;
});
