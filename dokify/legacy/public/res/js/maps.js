define(function(require, exports, module) {

	function mapHandler (dom) {
		var geo, src, pollignEnabled, pollingTimeout, activity, address, markers = [], map, hash, bounds, mapTypes;

		src 				= $(dom).data('src');
		address 			= $(dom).data('address');
		activity 			= (activity = $(dom).data('activity')) ? $(activity) : false;
		pollignEnabled		= $(dom).data('polling') || false;
		streetViewControl	= $(dom).data('streetview') == false ? false : true;


		var createTipsy = function () {
			if (!$.tipsy) return;
			var $this = $(this), title = $this.attr('title'), $marker;
			if (title) {
				$this.removeAttr('title');
				if ($marker = $this.parent().parent()) {
					$marker.attr('title', title).addClass('tipsy-map').tipsy().tipsy("show");
				}
			}
		};

		var hideTipsy = function () {
			if (!$.tipsy) return;
			$(dom).find('.tipsy-map').tipsy('hide');
		};

		var loadData = function () {
			if (src) {
				$.getJSON(src, function (res) {
					if (dom.parentNode.parentNode) {
						if (res.hash != hash) {
							drawMarkers(res.markers);

							if (activity && res.activity) activity.html(res.activity);
							hash = res.hash;
						}

						if(pollignEnabled) pollingTimeout = setTimeout(loadData, 5000);
					}
				});
			} else if (address) {
				geocoder.geocode({'address': address}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						var location = results[0].geometry.location;

						addMarker(location);
						map.setCenter(location);
						map.setZoom(13);
					} else {
						$(dom).hide();
					}
			    });
			}
		};

		var createMap = function () {
			mapTypes = $(dom).data('types') || [google.maps.MapTypeId.ROADMAP];

			map = new google.maps.Map(dom, { 
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				mapTypeControlOptions: {
     				mapTypeIds: mapTypes
     			},
				streetViewControl: streetViewControl
			});

			// --- enable tipsy
			$(dom).on('hover', 'area', createTipsy);

			// --- remove tipsy on zoom
			google.maps.event.addListener(map, 'zoom_changed', hideTipsy);

			geocoder = new google.maps.Geocoder();
		};

		var addMarker = function (location, title) {
			title = title || "";
			return marker = new google.maps.Marker({
				map: map,
				title: title,
				position: location,
				optimized: false
			});
		};

		var clearMarkers = function () {
			hideTipsy();

			for (i in markers) {
				markers[i].setMap(null);
			}
		};

		var drawMarkers = function (appendMarkers) {
			clearMarkers();
			bounds = new google.maps.LatLngBounds();
			var len = appendMarkers.length;

			if (len) {
				for (i in appendMarkers) {
					var markerData = appendMarkers[i];
					var location = new google.maps.LatLng(markerData.address[0], markerData.address[1]);
					

					var marker = addMarker(location, markerData.title);
					var onMarkerClick = (function (markerData) {
						return function () {
							top.location.href = markerData.href;
						}
					})(markerData);


					google.maps.event.addListener(marker, 'click', onMarkerClick);


					markers.push(marker);
					bounds.extend(marker.position);
				};
			}

			map.fitBounds(bounds);

			// --- never zoom more than 12 by default
			if (map.getZoom() > 12) {
				map.setZoom(12);
			}
			
			// --- zoom out if no data!
			if (!len) map.setZoom(2);
		};



		// Asociar a un boton los eventos de activar y desactivar el polling
		/* if (pollingButton) {

			function togglePolling () {
				if (pollignEnabled) {
					clearTimeout(pollingTimeout);

					pollingButton.addClass('grey').removeClass('actived green');
					pollignEnabled = false;
				} else {
					loadData();

					pollingButton.addClass('actived green').removeClass('grey');
					pollignEnabled = true;
				}
			};

			pollingButton.click(togglePolling);
		}*/


		var init = function () {
			createMap();
			loadData()
		};

		this.clear = function () {
			hash = null;
		}

		if (google && google.maps && google.maps.Map) {
			init();
		} else {
			window.onMapsLoaded = function () {
				init();
			};
		}
	};

	return mapHandler;
});