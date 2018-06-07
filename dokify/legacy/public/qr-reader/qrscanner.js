(function(window){

	var video, scale, canvas, context, $, doc;

	window.URL 				= window.URL || window.webkitURL;
	navigator.getUserMedia 	= navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;


	video = document.getElementById('qr');

	if (!video || video.tagName.toLowerCase() != 'video') {
		console.error('Error! Define a video tag with "qr" as id');
		return false;
	}

	scale	= 0.5;
	canvas	= document.createElement('canvas');
	context = canvas.getContext('2d');
	doc 	= window.document;
	$		= window.$;


	if (top) {
		doc = top.document;
		$	= top.$;
	}


	function onQR () {
		console.log('qr', argumens);
	};

	function scan () {
		var w = video.videoWidth * scale, h = video.videoHeight * scale;
		canvas.width = w;
		canvas.height = h;
		context.drawImage(video, 0, 0, w, h);

		try {
			var data = qrcode.decode(canvas);
			if ($) {
				$(doc).trigger('qr', [data]);
			}

			setTimeout(scan, 1000);
		} catch (err) {
			setTimeout(scan, 100);
		}

		
	}

	function onWebcamStream (stream) {
	    video.src = window.URL.createObjectURL(stream);

	    // document.body.appendChild(canvas);
	    setTimeout(scan, 2000);
	}

	function error () {
		console.error(argumens);
	};

	// qrcode.callback = onQR;

	// Start webcam streaming
	if (navigator.getUserMedia) navigator.getUserMedia({audio: false, video: true}, onWebcamStream, error);
})(window);