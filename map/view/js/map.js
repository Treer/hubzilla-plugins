(function ($) {

    function init() {
        model = new Backbone.Model();
        map = new Map({
            model: model,
            el: $('body')
        });
    }

    var Map = Backbone.View.extend({
        events: {
            'click #toggle-tracking': 'toggleLocation',
            'click #save-location': 'saveLocation',
            'click #autosave-location': 'autoSaveLocation',
            'click #share-location': 'shareLocation',
            'click #show-shared-data-btn': 'displaySharedData',
            'click #recenter-control': 'zoomToCurrentPosition',
            'click #add-marker': 'addMarker',
            'click #generic-dialog-cancel': 'closeGenericDialog',
            'click #history-dialog': 'displayHistoryDialog',
            'click #delete-all-dynamic-shares': 'revokeAllLocationShares'

        },
        initialize: function () {
            _(this).bindAll(
                    'toggleLocation',
                    'addPosition',
                    'getCenterWithHeading',
                    'render',
                    'radToDeg',
                    'degToRad',
                    'mod',
                    'locationUpdate'
                    );
            
            var handle = this;
            // Set map size based on current viewport size
            $(window).on("resize", this.winResize);
            $('#expand-aside').on('click', function () {
                handle.winResize();
            });
            this.winResize();
            // Close menu if anything is clicked outside
            $('#region_1').css('background-color', 'rgba(255,255,255,0.8)');
            $(document).mouseup(function (e)
            {
                var container = $("#region_1");

                //if (!container.is(e.target) // if the target of the click isn't the container...
                //if (!container.is(e.target)
                //    && container.has(e.target).length === 0
                if ($('#region_2').is(e.target)
                    && $("main").hasClass('region_1-on')) // ... nor a descendant of the container
                {
                    $("main").removeClass('region_1-on');
                    setTimeout(function () {$('#map').css('zIndex','auto');},300);
                    
                }
            });
            
            $('#autosave-location-container').hide();
            $('#recenter-control').hide();
            $('#add-marker').hide();
            $('#add-event').hide();
            $('#generic-dialog').hide();
            $('#dynamic-location-share-link-container').hide();
            
            var view = new ol.View({
                center: [0, 0],
                zoom: 2
            });

            var geolocation = new ol.Geolocation(/** @type {olx.GeolocationOptions} */ ({
                projection: view.getProjection(),
                trackingOptions: {
                    maximumAge: 10000,
                    enableHighAccuracy: true,
                    timeout: 600000
                }
            }));

            // LineString to store the different geolocation positions. This LineString
            // is time aware.
            // The Z dimension is actually used to store the rotation (heading).
            var positions = new ol.geom.LineString([],
                    /** @type {ol.geom.GeometryLayout} */ ('XYZM'));

            // Listen to position changes
            geolocation.on('change', this.locationUpdate);

            geolocation.on('error', function () {
                alert('geolocation error');
                // FIXME we should remove the coordinates in positions
            });
            /**
             * Elements that make up the popup.
             */
            var container = document.getElementById('marker-popup');
            var content = document.getElementById('marker-popup-content');
            var closer = document.getElementById('marker-popup-closer');
            /**
             * Add a click handler to hide the popup.
             * @return {boolean} Don't follow the href.
             */
            closer.onclick = function () {
                overlay.setPosition(undefined);
                closer.blur();
                return false;
            };
            /**
             * Create an overlay to anchor the popup to the map.
             */
            var overlay = new ol.Overlay(/** @type {olx.OverlayOptions} */ ({
                element: container,
                autoPan: true,
                autoPanAnimation: {
                    duration: 250
                }
            }));
            this.model.set('markerPopupOverlay', overlay);

            var map = new ol.Map({
                target: 'map',
                layers: [
                    new ol.layer.Tile({
                        source: new ol.source.MapQuest({layer: 'osm'}) //or 'sat', hyb'
                    })
                ],
                overlays: [overlay],
                view: view,
                //controls: [new ol.control.ZoomSlider(), new ol.control.FullScreen()]
                controls: [new ol.control.ZoomSlider()]
            });

            var accuracyFeature = new ol.Feature();
            accuracyFeature.bindTo('geometry', geolocation, 'accuracyGeometry');

            var positionFeature = new ol.Feature();
            positionFeature.setStyle(new ol.style.Style({
                image: new ol.style.Circle({
                    radius: 6,
                    fill: new ol.style.Fill({
                        color: '#3399CC'
                    }),
                    stroke: new ol.style.Stroke({
                        color: '#fff',
                        width: 2
                    })
                })
            }));

            positionFeature.bindTo('geometry', geolocation, 'position')
                    .transform(function () {
                    }, function (coordinates) {
                        return coordinates ? new ol.geom.Point(coordinates) : null;
                    });

            // Create the user location layer
            var userLocationFeatures = new ol.source.Vector({
                features: [positionFeature, accuracyFeature]
            });
            var userLocationLayer = new ol.layer.Vector({
                source: userLocationFeatures,
                name: 'MyLocation',
                type: 'userLocation',
                LayerID: ''
            });
            map.addLayer(userLocationLayer);

            // Create the staticMarkers layer
            var staticMarkersFeatures = new ol.source.Vector({
                features: []
            });
            var staticMarkersLayer = new ol.layer.Vector({
                source: staticMarkersFeatures,
                name: 'myMarkers',
                type: 'staticMarkers',
                LayerID: ''
            });
            map.addLayer(staticMarkersLayer);

            // Create the dynamicMarkers layer
            var dynamicMarkersFeatures = new ol.source.Vector({
                features: []
            });
            var dynamicMarkersLayer = new ol.layer.Vector({
                source: dynamicMarkersFeatures,
                name: 'dynamicMarkers',
                type: 'dynamicMarkers',
                LayerID: ''
            });
            map.addLayer(dynamicMarkersLayer);

            // Create the sharedStaticMarkers layer
            var sharedStaticMarkersFeatures = new ol.source.Vector({
                features: []
            });
            var sharedStaticMarkersLayer = new ol.layer.Vector({
                source: sharedStaticMarkersFeatures,
                name: 'sharedStaticMarkers',
                type: 'staticMarkers',
                LayerID: ''
            });
            map.addLayer(sharedStaticMarkersLayer);

            // Create the eventMarkers layer
            var eventMarkersFeatures = new ol.source.Vector({
                features: []
            });
            var eventMarkersLayer = new ol.layer.Vector({
                source: eventMarkersFeatures,
                name: 'eventMarkers',
                type: 'staticMarkers',
                LayerID: ''
            });
            map.addLayer(eventMarkersLayer);

            /**
             * Add a click handler to the map to render the popup.
             */
            var mapClickHandlerID = map.on('singleclick', function (evt) {
                var coordinate = evt.coordinate;
                overlay.setPosition(coordinate);
                //overlay.setOffset([-$('#map').offset().left, -$('#map').offset().top]);
                var evtPxl = evt.pixel;
                //var offset = [-$('#map').offset().left, -$('#map').offset().top];
                //var adjustedCoords = [evtPxl[0] + offset[0], evtPxl[1] + offset[1]];
                var adjustedCoords = evtPxl;
                handle.model.set('eventPixel', adjustedCoords);
                handle.mapClicked(map.getCoordinateFromPixel(adjustedCoords));
            });

            // Configure modal dialog interactions
            this.model.set('aclContainerElement', '#acl-container');
            var aclContainerElement = this.model.get('aclContainerElement');
            var aclContainerContents = $(aclContainerElement).html();
            this.model.set('aclContainerContents', aclContainerContents);

            $('#aclModal').on('show.bs.modal', function (e) {
                if ($('#generic-dialog').is(':visible')) {
                    $('#generic-dialog').modal('hide');
                    $('#aclModal').on('hidden.bs.modal', function (e) {
                        $('#generic-dialog').modal('show');
                    });
                } else {
                    $('#aclModal').off('hidden.bs.modal');
                }
            });

            this.model.set('mapClickHandlerID', mapClickHandlerID);
            this.model.set('map', map);
            this.model.set('geolocation', geolocation);
            this.model.set('initialZoom', true);
            this.model.set('initialCenter', true);
            this.model.set('view', view);
            this.model.set('geolocation', geolocation);
            this.model.set('positions', positions);
            this.model.set('tracking', 0);
            this.model.set('previousM', 0);
            this.model.set('deltaMean', 10000);  // the geolocation sampling period mean in ms
            this.model.set('autoSaveUpdateInterval', 15); // seconds
            this.model.set('autoUpdateDynamicMarkersInterval', 30); // seconds
            // Do other initialization tasks
            var cache = $('#data-cache').html();
            // If the user has invoked the API via a GET request, there should be
            // information stored in the DOM 
            if (typeof (cache) !== 'undefined' && cache !== '') {
                var cachejson = JSON.parse(cache);
                if (typeof (cachejson.authenticated) !== 'undefined' && cachejson.authenticated !== '') {
                    (cachejson.authenticated === 1 ? this.model.set('authenticated', true) : this.model.set('authenticated', false));
                    if (typeof (cachejson.autotrack) !== 'undefined' && cachejson.autotrack !== '') {
                        (cachejson.autotrack === 1 ? this.model.set('autotrack', true) : this.model.set('autotrack', false));
                    }
                    if (typeof (cachejson.autosave) !== 'undefined' && cachejson.autosave !== '') {
                        (cachejson.autosave === 1 ? this.model.set('autosave', true) : this.model.set('autosave', false));
                    }                    
                    this.configureControls();
                }
                if (typeof (cachejson.token) !== 'undefined' && cachejson.token !== '') {
                    if (typeof (cachejson.apiaction) !== 'undefined' && cachejson.apiaction !== '') {
                        switch (cachejson.apiaction) {
                            case 'getLatestLocation':
                                this.getLatestLocation(cachejson.token);
                                break;
                            case 'getStaticMarker':
                                this.getStaticMarker(cachejson.token);
                                break;
                            default:
                                break;
                        }
                    }
                }
            }
        },
        displayHistoryDialog: function () {
            var handle = this;
            $('#generic-dialog-submit').off('click');
            $('#generic-dialog-submit').on('click', function (event) {
                handle.getLocationHistory();
                return false;
            });
            //var dialogMessage = '<h1>Select a time window</h1><div class=\'date\'><input type=\'text\' placeholder=\'yyyy-mm-dd HH:MM\' name=\'start_text\' id=\'start_text\' value="2015-09-18 00:00" /><span class="required" title="Required" >*</span></div><script type=\'text/javascript\'>$(function () {var picker = $(\'#start_text\').datetimepicker({step:5,format:\'Y-m-d H:i\' ,minDate: new Date(1442536216*1000), yearStart: 2015 ,maxDate: new Date(1600389016*1000), yearEnd: 2020  ,defaultDate: new Date(1442548800*1000)}); })</script><div class="clear"></div><br />';
            var dialogMessage = '<div class="date text-center">Start time: <input type="text" placeholder="yyyy-mm-dd HH:MM" name="start_text" id="start_text" value="" /><span class="required" title="Required" >*</span></div><script type="text/javascript">$(function () {var picker = $("#start_text").datetimepicker({step:5,format:"Y-m-d H:i" , yearStart: 2015 , yearEnd: 2020  ,defaultDate: new Date()}); })</script><div class="clear"></div><br />';
            //dialogMessage += '<div class=\'date\'><input type=\'text\' placeholder=\'yyyy-mm-dd HH:MM\' name=\'finish_text\' id=\'finish_text\' value="2015-09-18 00:30" /></div><script type=\'text/javascript\'>$(function () {var picker = $(\'#finish_text\').datetimepicker({step:5,format:\'Y-m-d H:i\' ,minDate: new Date(1442536216*1000), yearStart: 2015 ,maxDate: new Date(1600389016*1000), yearEnd: 2020  ,defaultDate: new Date(1442550600*1000)}); $(\'#start_text\').data(\'xdsoft_datetimepicker\').setOptions({onChangeDateTime: function (currentDateTime) { $(\'#finish_text\').data(\'xdsoft_datetimepicker\').setOptions({minDate: currentDateTime})}})})</script></div>';
            dialogMessage += '<div class="date text-center">Stop time: <input type="text" placeholder="yyyy-mm-dd HH:MM" name="finish_text" id="finish_text" value="" /></div><script type="text/javascript">$(function () {var picker = $("#finish_text").datetimepicker({step:5,format:"Y-m-d H:i" , yearStart: 2015 ,yearEnd: 2020  ,defaultDate: new Date()}); $("#start_text").data("xdsoft_datetimepicker").setOptions({onChangeDateTime: function (currentDateTime) { $("#finish_text").data("xdsoft_datetimepicker").setOptions({minDate: currentDateTime})}})})</script></div>';
            this.showDialog('Select a time window', dialogMessage, 'Show History');
        },
        getLocationHistory: function () {
            var startTime = $('#start_text').val();
            var stopTime = $('#finish_text').val();
            //window.console.log('start-stop: ' + startTime + ' to ' + stopTime);            
            var handle = this;
            var payload = {
                start: startTime,
                stop: stopTime
            };
            var apiAction = 'getLocationHistory';
            handle.queryServerSynchronous(payload, apiAction, function (response) {
                var result = JSON.parse(response);
                if (result.status) {
                    handle.model.set('locationHistory', result.locationHistory);
                    //window.console.log('locationHistory: ' + JSON.stringify(handle.model.get('locationHistory')));
                    if (result.locationHistory.length > 0) {
                        handle.closeGenericDialog();
                        handle.viewLocationHistory();
                    } else {
                        alert('No data found in that date range.');
                    }
                } else {
                    window.console.log('Error retrieving location history');
                }
            });
        },
        viewLocationHistory: function () {
            var map = this.model.get('map');
            var locations = this.model.get('locationHistory');
            this.removeHistoryLayer();
            var numPts;
            if (locations.length < 10000) {
                numPts = locations.length;
            } else {
                numPts = 10000;
            }
            var newLayerSource = new ol.source.Vector();
            var coords = [];
            for (var j = 0; j < numPts; j++) {
                var marker = locations[j];
                coords.push([marker.lat, marker.lon]);
            }
            var lineSegments = [];
            for (var j = 1; j < coords.length - 1; j++) {
                lineSegments.push([coords[j], coords[j - 1]]);
            }
            var pathGeometry = new ol.geom.MultiLineString(lineSegments);
            var pathFeature = new ol.Feature({
                geometry: pathGeometry
            });
            //window.console.log('Coords: '+JSON.stringify(coords));
            newLayerSource.addFeature(pathFeature);
            var newLayer = new ol.layer.Vector({
                source: newLayerSource,
                style: new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#FF0000',
                        width: 3
                    })
                }),
                name: 'Location history'
            });
            map.addLayer(newLayer);
            var view = map.getView();
            view.fitGeometry(pathGeometry, map.getSize());
            //this.model.set('map', map);
        },
        removeHistoryLayer: function () {
            var map = this.model.get('map');
            var currentMapLayers = map.getLayers().getArray();
            for (var i = 1; i < currentMapLayers.length; i++) {
                var layer = currentMapLayers[i];
                if (layer.get('name') === 'Location history') {
                    map.removeLayer(layer);
                    return;
                }
            }
        },
        closeGenericDialog: function () {
            var aclContainerElement = this.model.get('aclContainerElement');
            var aclContainerContents = this.model.get('aclContainerContents');
            $(aclContainerElement).empty();
            $('#acl-container').html(aclContainerContents);
            this.model.set('aclContainerElement', '#acl-container');

            //$('#aclModal').off('show.bs.modal');
            //$('#aclModal').off('hidden.bs.modal');
            $('#generic-dialog').modal('hide');
            $('#generic-dialog-submit').off('click');
        },
        configureControls: function () {
            if (!this.model.has('authenticated')) {
                return;
            }
            if (this.model.get('authenticated')) {
                // Apply autotrack setting
                if (this.model.has('autotrack') && this.model.get('autotrack')) {
                    this.toggleLocation();
                }
                // Apply autosave setting
                if (this.model.has('autosave') && this.model.get('autosave')) {
                    $('#autosave-location').click();
                    this.autoSaveLocation();
                }
                // Populate list of saved markers and markers shared with you
                this.refreshMarkerList();
            } else {
                $('#save-location-group').hide();
                $('#show-history-group').hide();
                $('#map-controls-sharing').hide();
                $('#map-controls-markers').hide();
                $('#map-controls-shared').hide();
                $('#map-controls-events').hide();
                var handle = this;
                $('#generic-dialog-submit').on('click', function (event) {
                    handle.closeGenericDialog();
                    return false;
                });
                $('#login-main').css('margin-left', 'auto');
                $('#login-main').css('margin-right', 'auto');
                var message = '<p class="text-center lead">You are not authenticated, so the functionality of this map is \n\
                reduced.</p>';
                message += $('#map-login-form').html();
                var urlParams = this.getURLParams();
                window.console.log('urlParams: ' + JSON.stringify(urlParams['login']));
                if (!(typeof (urlParams['login']) !== 'undefined' && urlParams['login'] === "0")) {
                    this.showDialog('Welcome to Hubzilla Location Services', message, 'Remain non-authenticated');
                }
            }
        },
        getSharedStaticMarkers: function () {
            var handle = this;
            // The more general API action "getSharedData" will take arguments
            // that filter the returned information.
            var payload = {
                type: 'staticMarker',
                filter: 'all'
            };
            var apiAction = 'getSharedData';
            handle.queryServerSynchronous(payload, apiAction, function (response) {
                var result = JSON.parse(response);
                if (result.status) {
                    handle.model.set('sharedStaticMarkers', {
                        sharedData: result.sharedData,
                        channels: result.channels,
                        markers: result.markers
                    });
                } else {
                    window.console.log('Error retrieving markers');
                }
            });

        },
        getMyMarkers: function () {
            var handle = this;
            handle.model.unset('myMarkers');
            var payload = {
            };
            var apiAction = 'getMyMarkers';
            handle.queryServerSynchronous(payload, apiAction, function (response) {
                var result = JSON.parse(response);
                if (result.status) {
                    handle.model.set('myMarkers', result.markers);
                } else {
                    handle.model.set('myMarkers', []);
                    window.console.log('Error retrieving markers');
                }
            });
        },
        refreshMarkerList: function () {
            // Retrieve marker data from server, then populate the control panel
            this.getMyMarkers();
            this.getSharedStaticMarkers();
            if (!this.model.has('myMarkers') && !this.model.has('sharedStaticMarkers')) {
                return;
            }
            var myMarkers = this.model.get('myMarkers');
            $('#my-markers-list').html('');
            // FIXME: If there are no markers found the script crashes here
            for (var i = 0; i < myMarkers.length; i++) {
                var marker = myMarkers[i];
                var pulldownMenu = '<div class="dropdown pull-right">' +
                        '<button class="btn btn-default btn-xs" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                        '<i class="icon-caret-down"></i>' +
                        '</button>' +
                        '<ul class="dropdown-menu" aria-labelledby="dLabel">' +
                        '<li><a id="center-marker-' + marker.resource_id + '" href="" title="Center" onclick="return false;">Center</a></li>' +
                        '<li class="divider"></li>' +
                        '<li><a id="dropdown-share-marker-' + marker.resource_id + '" href="" title="Share" onclick="return false;">Share</a></li>' +
                        '<li id="delete-marker-' + marker.resource_id + '"><a  href="" title="Delete" onclick="return false;">Delete</a></li>' +
                        '</ul>' +
                        '</div>';

                $('#my-markers-list').append(
                        '<li>' +
                        pulldownMenu +
                        '<h4>' + marker.title + '</h4>' +
                        '</li>');
                this.addCenterMarkerListener('center-marker-' + marker.resource_id, marker.resource_id);
                this.addDeleteMarkerListener('delete-marker-' + marker.resource_id, marker.resource_id);
                this.addShareMarkerListener('dropdown-share-marker-' + marker.resource_id, marker.resource_id);
            }

            var sharedStaticMarkers = this.model.get('sharedStaticMarkers');
            $('#shared-markers-list').html('');
            //window.console.log('sharedStaticMarkers: ' + JSON.stringify(sharedStaticMarkers));
            for (var i = 0; i < sharedStaticMarkers.sharedData.length; i++) {
                var channel = sharedStaticMarkers.channels[i];
                var data = sharedStaticMarkers.sharedData[i];
                var marker = sharedStaticMarkers.markers[i];
                if (marker === null)
                    continue;  // The marker owner must have deleted the marker

                var pulldownMenu = '<div class="dropdown pull-right">' +
                        '<button class="btn btn-default btn-xs" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                        '<i class="icon-caret-down"></i>' +
                        '</button>' +
                        '<ul class="dropdown-menu" aria-labelledby="dLabel">' +
                        '<li><a id="center-shared-marker-' + marker.resource_id + '" href="" title="Center" onclick="return false;">Center</a></li>' +
                        '<li class="divider"></li>' +
                        '<li id="save-shared-marker-' + marker.resource_id + '"><a  href="" title="Save" onclick="return false;">Save</a></li>' +
                        '<li id="remove-shared-marker-' + marker.resource_id + '"><a  href="" title="Remove" onclick="return false;">Remove</a></li>' +
                        '</ul>' +
                        '</div>';

                $('#shared-markers-list').append(
                        '<li>' +
                        pulldownMenu +
                        '<h4>' + marker.title + '</h4>' +
                        '<p class="text-left">Owner: ' + channel.name + '</p>' +
                        '</li>');
                this.addCenterMarkerListener('center-shared-marker-' + marker.resource_id, marker.resource_id);
            }
            // Get events with properly formatted coordinate locations and display them on the map
            this.getEvents();

            var eventMarkers = this.model.get('eventMarkers');
            $('#events-markers-list').html('');
            //window.console.log('sharedStaticMarkers: ' + JSON.stringify(sharedStaticMarkers));
            for (var i = 0; i < eventMarkers.length; i++) {
                var marker = eventMarkers[i];
                if (marker === null)
                    continue;  // The marker owner must have deleted the marker
                var resource_id = marker.event_hash.split("@")[0];
                var pulldownMenu = '<div class="dropdown pull-right">' +
                        '<button class="btn btn-default btn-xs" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                        '<i class="icon-caret-down"></i>' +
                        '</button>' +
                        '<ul class="dropdown-menu" aria-labelledby="dLabel">' +
                        '<li><a id="center-event-marker-' + resource_id + '" href="" title="Center" onclick="return false;">Center</a></li>' +
                        '<li class="divider"></li>' +
                        '<li id="save-event-marker-' + resource_id + '"><a  href="" title="Save" onclick="return false;">Save</a></li>' +
                        '<li id="remove-event-marker-' + resource_id + '"><a  href="" title="Remove" onclick="return false;">Remove</a></li>' +
                        '</ul>' +
                        '</div>';

                $('#events-markers-list').append(
                        '<li>' +
                        pulldownMenu +
                        '<h4>' + marker.summary + '</h4>' +
                        '<p class="text-left">Date: ' + marker.start + '</p>' +
                        '</li>');
                this.addCenterMarkerListener('center-event-marker-' + resource_id, resource_id);
            }
            this.updateStaticMarkersOnMap();
        },
        getEvents: function () {
            var handle = this;
            handle.model.unset('eventMarkers');
            var payload = {
            };
            var apiAction = 'getEvents';
            handle.queryServerSynchronous(payload, apiAction, function (response) {
                var result = JSON.parse(response);
                if (result.status) {
                    handle.model.set('eventMarkers', result.markers);
                    //window.console.log('eventMarkers: ' + JSON.stringify(result.markers));
                } else {
                    handle.model.set('eventMarkers', []);
                    window.console.log('Error retrieving event markers');
                }
            });

        },
        addDeleteMarkerListener: function (markerElementID, markerID) {
            var handle = this;
            $('#' + markerElementID).on('click', function (event) {
                var answer = confirm("Remove marker?");
                if (!answer) {
                    return false;
                }
                handle.deleteStaticMarker(markerID);
                return false;
            });
        },
        addCenterMarkerListener: function (markerElementID, markerID) {
            var handle = this;
            $('#' + markerElementID).on('click', function (event) {
                handle.centerStaticMarker(markerID);
                return false;
            });
        },
        addShareMarkerListener: function (markerElementID, markerID) {
            var handle = this;
            $('#' + markerElementID).on('click', function (event) {
                handle.shareStaticMarkerDialog(markerID);
                return false;
            });
        },
        shareStaticMarkerDialog: function (markerID) {
            //$('#map-controls-sharing').click();
            var handle = this;
            $('#generic-dialog').on('hide.bs.modal', function (e) {
                if (handle.model.get('aclContainerElement') !== '#acl-container') {
                    var aclContainerElement = handle.model.get('aclContainerElement');
                    var aclContainerContents = handle.model.get('aclContainerContents');
                    $(aclContainerElement).empty();
                    $('#acl-container').html(aclContainerContents);
                    handle.model.set('aclContainerElement', '#acl-container');
                }
            });
            $('#generic-dialog').on('show.bs.modal', function (e) {
                if (handle.model.get('aclContainerElement') !== '#generic-dialog-acl-container') {
                    var aclContainerElement = handle.model.get('aclContainerElement');
                    var aclContainerContents = handle.model.get('aclContainerContents');
                    $(aclContainerElement).empty();
                    $('#generic-dialog-acl-container').html(aclContainerContents);
                    handle.model.set('aclContainerElement', '#generic-dialog-acl-container');
                }
            });
            var marker = this.getMarkerByID(markerID);
            $('#generic-dialog-submit').off('click');
            $('#generic-dialog-submit').on('click', function (event) {
                handle.shareStaticMarker(markerID);
                return false;
            });
            var dialogMessage = '<h1 class="text-center">' + marker.title + '</h1>';
            dialogMessage += '<p class="text-center"><textarea id="share-marker-message" class="form-control" rows="3" placeholder="Add a message..." style="width: 100%; height: 100px;"></textarea></p>';
            dialogMessage += '                                                         \n\
                    <ul class="nav nav-pills nav-stacked">                                                      \n\
                        <li>                                                                                    \n\
                            <div id="marker-post-visible-container" class="form-group field checkbox">                 \n\
                            <span>Send notification post?</span>                                                \n\
                            <div class="pull-right">                                                            \n\
                                <input type="checkbox" name="marker-post-visible" id="marker-post-visible" value="0" />       \n\
                                <label class="switchlabel" for="marker-post-visible">                                  \n\
                                    <span class="onoffswitch-inner" data-on="Post" data-off="None"></span>      \n\
                                    <span class="onoffswitch-switch"></span>                                    \n\
                                </label>                                                                        \n\
                            </div>                                                                              \n\
                            </div>                                                                              \n\
                        </li>                                                                                   \n\
                    </ul>';
            this.showDialog('Share marker', dialogMessage, 'Share');
        },
        shareStaticMarker: function (resource_id) {
            this.closeGenericDialog();
            var shareMessage = $('#share-marker-message').val()
            var handle = this;
            this.getACL();
            var visible = 0;
            if ($('#marker-post-visible').is(':checked')) {
                visible = 1;
            }
            var payload = {
                resource_id: resource_id,
                message: shareMessage,
                contact_allow: this.model.get('contact_allow'),
                group_allow: this.model.get('group_allow'),
                contact_deny: this.model.get('contact_deny'),
                group_deny: this.model.get('group_deny'),
                visible: visible
            };
            window.console.log('shareStaticMarker payload: ' + JSON.stringify(payload));
            var apiAction = 'shareStaticMarker';
            handle.queryServer(payload, apiAction, function (response) {
                var result = JSON.parse(response);
                if (result.status) {
                    window.console.log('shareStaticMarker response: ' + JSON.stringify(result));
                    var shareLink = JSON.stringify(JSON.parse(result.item.object).url).slice(1,-1).replace("&","%26") + '%26login=0';
                    //var shareLink = JSON.stringify(JSON.parse(result.item.object).url).slice(1,-1) + '&login=0';
                    $('#generic-dialog-submit').off('click');
                    $('#generic-dialog-submit').on('click', function (event) {
                        handle.closeGenericDialog();
                        return;
                    });
                    var subject = 'New shared location';
                    var dialogMessage = '<p class="text-center"><a href=mailto:?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(shareMessage + '\r\n\r\n') + shareLink + '>Email this link</a></p>';
                    handle.showDialog('Share marker link', dialogMessage, 'Close');
                } else {
                    window.console.log('Error sharing marker' + resource_id);
                }
            });

        },
        getMarkerByID: function (markerID) {
            var markers = this.model.get('myMarkers');
            for (var i = 0; i < markers.length; i++) {
                var marker = markers[i];
                if (marker.resource_id === markerID) {
                    return marker;
                }
            }
        },
        centerStaticMarker: function (markerID) {
            if (!this.model.has('myMarkers')) {
                return;
            }
            var myMarkers = this.model.get('myMarkers');
            var coords = null;
            for (var i = 0; i < myMarkers.length; i++) {
                var marker = myMarkers[i];
                if (marker.resource_id === markerID) {
                    coords = [parseFloat(marker.lat), parseFloat(marker.lon)];
                }
            }
            if (coords === null) {
                var sharedStaticMarkers = this.model.get('sharedStaticMarkers').markers;
                for (var i = 0; i < sharedStaticMarkers.length; i++) {
                    var marker = sharedStaticMarkers[i];
                    if (marker === null)
                        continue;  // The marker owner must have deleted the marker
                    if (marker.resource_id === markerID) {
                        coords = [parseFloat(marker.lat), parseFloat(marker.lon)];
                    }
                }
                if (coords === null) {
                    var eventMarkers = this.model.get('eventMarkers');
                    for (var i = 0; i < eventMarkers.length; i++) {
                        var marker = eventMarkers[i];
                        if (marker === null)
                            continue;  // The marker owner must have deleted the marker
                        if (marker.event_hash.split("@")[0] === markerID) {
                            //window.console.log('marker.location[0] = ' + marker.location[0]);
                            coords = JSON.parse(marker.location);
                        }
                    }
                    if (coords === null) {
                        return false;
                    }
                }

            }
            var map = this.model.get('map');
            var view = map.getView();
            var duration = 2000;
            var start = +new Date();
            var pan = ol.animation.pan({
                duration: duration,
                source: view.getCenter(),
                start: start
            });
            var bounce = ol.animation.bounce({
                duration: duration,
                resolution: 3 * view.getResolution(),
                start: start
            });
            var zoom = ol.animation.zoom({
                duration: duration,
                resolution: 2 * view.getResolution(),
                start: start
            });
            map.beforeRender(bounce, pan);
            map.getView().setCenter(coords);
            map.getView().setZoom(13);
        },
        updateStaticMarkersOnMap: function () {
            var map = this.model.get('map');
            var layers = map.getLayers();

            var myMarkers = this.model.get('myMarkers');
            var layerSource = null;
            layers.forEach(function (layer, idx, layers) {
                if (layer.get('name') === 'myMarkers') {
                    layer.getSource().clear();
                    layerSource = layer.getSource();
                }
            }, this);

            for (var i = 0; i < myMarkers.length; i++) {
                var marker = myMarkers[i];
                var staticMarker = new ol.Feature({
                    title: marker.title,
                    body: marker.body,
                    type: 'staticMarker',
                    resource_id: marker.resource_id
                });
                staticMarker.setGeometry(new ol.geom.Point([marker.lat, marker.lon]));
                staticMarker.setStyle(new ol.style.Style({
                    image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
                        anchor: [0.5, 30],
                        anchorXUnits: 'fraction',
                        anchorYUnits: 'pixels',
                        opacity: 0.75,
                        src: 'addon/map/marker_red_30px.png'
                    }))
                }));
                layerSource.addFeature(staticMarker)
            }


            var sharedStaticMarkers = this.model.get('sharedStaticMarkers');
            var sharedLayerSource = null;
            layers.forEach(function (layer, idx, layers) {
                if (layer.get('name') === 'sharedStaticMarkers') {
                    sharedLayerSource = layer.getSource();
                    sharedLayerSource.clear();
                }
            }, this);

            for (var i = 0; i < sharedStaticMarkers.markers.length; i++) {
                var marker = sharedStaticMarkers.markers[i];
                if (marker === null)
                    continue;  // The marker owner must have deleted the marker
                var staticMarker = new ol.Feature({
                    title: marker.title,
                    body: marker.body,
                    type: 'staticMarker',
                    resource_id: marker.resource_id
                });
                staticMarker.setGeometry(new ol.geom.Point([marker.lat, marker.lon]));
                staticMarker.setStyle(new ol.style.Style({
                    image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
                        anchor: [0.5, 30],
                        anchorXUnits: 'fraction',
                        anchorYUnits: 'pixels',
                        opacity: 0.75,
                        src: 'addon/map/marker_blue_30px.png'
                    }))
                }));
                sharedLayerSource.addFeature(staticMarker)
            }


            var eventMarkers = this.model.get('eventMarkers');
            var eventLayerSource = null;
            layers.forEach(function (layer, idx, layers) {
                if (layer.get('name') === 'eventMarkers') {
                    eventLayerSource = layer.getSource();
                    eventLayerSource.clear();
                }
            }, this);

            for (var i = 0; i < eventMarkers.length; i++) {
                var event = eventMarkers[i];
                if (event === null)
                    continue;  // The marker owner must have deleted the marker
                var eventDesc = event.description;
                if (event.description.length > 140) {
                    eventDesc = eventDesc.slice(0, 140) + '...';
                }
                var staticMarker = new ol.Feature({
                    title: event.summary,
                    body: eventDesc,
                    type: 'staticMarker',
                    resource_id: event.event_hash.split("@")[0]  //Discard @hub.address from event_hash
                });
                //var lat = JSON.parse(event.location)[0];
                //var lon = JSON.parse(event.location)[1];
                staticMarker.setGeometry(new ol.geom.Point(JSON.parse(event.location)));
                staticMarker.setStyle(new ol.style.Style({
                    image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
                        anchor: [0.5, 30],
                        anchorXUnits: 'fraction',
                        anchorYUnits: 'pixels',
                        opacity: 0.75,
                        src: 'addon/map/marker_blue_30px.png'
                    }))
                }));
                eventLayerSource.addFeature(staticMarker)
            }

            map.getView().fitExtent(layerSource.getExtent(), map.getSize());
            map.getView().setZoom(map.getView().getZoom() - 1)
            if (map.getView().getZoom() > 14) {
                map.getView().setZoom(14);
            }
        },
        deleteStaticMarker: function (resource_id) {
            var handle = this;
            var payload = {
                resource_id: resource_id
            };
            var apiAction = 'deleteStaticMarker';
            handle.queryServerSynchronous(payload, apiAction, function (response) {
                var result = JSON.parse(response);
                if (result.status) {
                    handle.refreshMarkerList();
                } else {
                    window.console.log('Error deleting marker' + resource_id);
                }
            });
        },
        zoomToCurrentPosition: function () {
            var map = this.model.get('map');
            map.getView().setCenter(this.model.get('coords'));
            map.getView().setZoom(16);
        },
        mapClicked: function (coordinate) {
            this.model.set('clickedCoordinate', coordinate);
            if (this.showMarkerMenu(coordinate)) {
                $('#add-marker').hide();
                $('#add-event').hide();
                return;
            }
            var handle = this;
            $('#add-marker').off('click');
            $('#add-marker').on('click', function (event) {
                $('#marker-popup-closer').click();
                handle.addMarker();
                return false;
            });
            $('#add-marker').show();
            $('#add-event').off('click');
            $('#add-event').on('click', function (event) {
                $('#marker-popup-closer').click();
                handle.createEvent();
                return false;
            });
            $('#add-event').show();
        },
        createEvent: function () {
            var coords = this.model.get('clickedCoordinate');
            window.location = (this.getURL() + '/events/new?location=' + JSON.stringify(coords));
            return false;
        },
        showMarkerMenu: function (coords) {
            var map = this.model.get('map');
            var eventPixel = this.model.get('eventPixel');
            //var offset = [$('#map').offset().left, $('#map').offset().top];
            //var adjustedPixel = [eventPixel[0] + offset[0], eventPixel[1] + offset[1]];
            var adjustedPixel = eventPixel;
            var menu = $('#marker-popup-context-menu');
            var feature = map.forEachFeatureAtPixel(eventPixel,
                    function (feature, layer) {
                        return feature;
                    });
            if (feature) {
                var popup = this.model.get('markerPopupOverlay');
                popup.setPosition(map.getCoordinateFromPixel(adjustedPixel));
                if (feature.get('type') === 'staticMarker') {
                    menu.html('');
                    menu.append('<h1>' + feature.get('title') + '</h1>');
                    menu.append('<p class="text-center">' + feature.get('body') + '</p>');
                    menu.append('<div id="popup-delete-marker-' + feature.get('resource_id') + '" class="btn btn-danger"><a href="" class="delete-marker" onclick="return false;" style="color: #FFFFFF;">Delete</a></div>');
                    this.addDeleteMarkerListener('popup-delete-marker-' + feature.get('resource_id'), feature.get('resource_id'));
                    menu.append('<div id="share-marker-' + feature.get('resource_id') + '" class="btn btn-warning"><a href="" class="share-marker" onclick="return false;" style="color: #FFFFFF;">Share</a></div>');
                    this.addShareMarkerListener('share-marker-' + feature.get('resource_id'), feature.get('resource_id'));
                    menu.show();
                    return true;
                }
                // TODO: Get name of owner to display in the popup
                if (feature.get('type') === 'dynamicMarker') {
                    menu.html('');
                    menu.append('<h1>' + 'Shared Location' + '</h1>');
                    menu.append('<p class="text-center">' + feature.get('resource_id') + '</p>');
                    menu.show();
                    return true;
                }
                return false;
            } else {
                menu.hide();
                return false;
            }
        },
        /**
         * getStaticMarker sends a token to retrieve a static marker
         * @param {type} token
         * @returns {undefined}
         */
        getStaticMarker: function (token) {
            var handle = this;
            var payload = {
                token: token
            };
            var apiAction = 'getStaticMarker';
            handle.queryServer(payload, apiAction, function (response) {
                var result = JSON.parse(response);
                if (result.status) {
                    handle.model.set('token', result.token);
                    handle.model.set('sharedStaticMarker', result.sharedStaticMarker);
                    //window.console.log('Retrieved sharedStaticMarker: ' + JSON.stringify(result.sharedStaticMarker));
                    handle.showSharedStaticMarker();
                } else {
                    window.console.log('Error exchanging token');
                    $('#generic-dialog-submit').off('click');
                    $('#generic-dialog-submit').on('click', function (event) {
                        var zidStr = 'zid=' + $('#zid-input').val();
                        if (location.href.indexOf("?") === -1) {
                            window.location = location.href += "?" + zidStr;
                        }
                        else {
                            window.location = location.href += "&" + zidStr;
                        }
                        return false;
                    });

                    var message = '<p class="text-center">Error retrieving data. You might not have permission. Enter your ZID here to authenticate:</p>';
                    message += '<p class="text-center">zid: <input type="text" id="zid-input" placeholder="user@my.remote.hub" length="20"></input></p>'
                    handle.showDialog('Error', message, 'OK');
                }
            });

        },
        /**
         * getLatestLocation sends a token to retrieve the latest dynamic marker
         * position
         * @param {type} token
         * @returns {undefined}
         */
        getLatestLocation: function (token) {
            // TODO: Allow token to be an array of tokens that need updating.
            var handle = this;
            var payload = {
                token: token
            };
            var apiAction = 'getLatestLocation';
            handle.queryServer(payload, apiAction, function (response) {
                var result = JSON.parse(response);
                if (result.status) {
                    handle.model.set('token', result.token);
                    handle.model.set('latestLocation', result.latestLocation[0]);
                    $('#show-shared-data-btn').removeClass('hide');
                    $('#show-shared-data-btn').show();
                    $('#update-shared-data-btn').removeClass('hide');
                    $('#update-shared-data-btn').show();
                    handle.showLatestSharedLocation();
                } else {
                    window.console.log('Error exchanging token');
                    handle.showDialog('Error', 'Error retrieving data. You might not have permission.', 'OK');
                }
            });

        },
        showDialog: function (title, body, submitButton) {
            $('#generic-dialog-title').html(title);
            $('#generic-dialog-body').html(body);
            $('#generic-dialog-submit').html(submitButton);
            $('#generic-dialog').modal('show');
        },
        showSharedStaticMarker: function () {
            var sharedMarker = this.model.get('sharedStaticMarker');
            //window.console.log('sharedMarker: ' + JSON.stringify(sharedMarker));
            var coords = [parseFloat(sharedMarker.lat), parseFloat(sharedMarker.lon)];
            var map = this.model.get('map');
            var newMapMarker = new ol.Feature({
                type: 'staticMarker',
                title: sharedMarker.title,
                body: sharedMarker.body,
                resource_id: this.model.get('token')
            });
            newMapMarker.setGeometry(new ol.geom.Point(coords));
            newMapMarker.setStyle(new ol.style.Style({
                image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
                    anchor: [0.5, 30],
                    anchorXUnits: 'fraction',
                    anchorYUnits: 'pixels',
                    opacity: 0.75,
                    src: 'addon/map/marker_blue_30px.png'
                }))
            }));

            // Add share location to existing dynamicMarker layer
            var layers = map.getLayers();
            layers.forEach(function (layer, idx, layers) {
                if (layer.get('name') === 'sharedStaticMarkers') {
                    //window.console.log('Found sharedStaticMarkers layer in showSharedStaticMarker');
                    layer.getSource().clear();
                    layer.getSource().addFeature(newMapMarker);
                }
            }, this);

            var view = map.getView();
            view.setCenter(coords);
            view.setZoom(12);
        },
        showLatestSharedLocation: function () {
            $('#show-shared-data-btn').removeClass("btn-default").addClass("btn-success");
            var latestLocation = null;
            if (this.model.has('latestLocation')) {
                latestLocation = this.model.get('latestLocation');
            }
            if (latestLocation === null) {
                alert("Location information not yet available. Try again later.");
                return;
            }
            var coords = [parseFloat(latestLocation.lat), parseFloat(latestLocation.lon)];
            var map = this.model.get('map');

            var newMapMarker = new ol.Feature({
                type: 'dynamicMarker',
                resource_id: this.model.get('token')
            });
            newMapMarker.setGeometry(new ol.geom.Point(coords));
            newMapMarker.setStyle(new ol.style.Style({
                image: new ol.style.Circle({
                    radius: 6,
                    fill: new ol.style.Fill({
                        color: '#FF0000'
                    }),
                    stroke: new ol.style.Stroke({
                        color: '#FFFFFF',
                        width: 2
                    })
                })
            }));

            // Add share location to existing dynamicMarker layer
            var layers = map.getLayers();
            layers.forEach(function (layer, idx, layers) {
                if (layer.get('name') === 'dynamicMarkers') {
                    layer.getSource().clear();
                    layer.getSource().addFeature(newMapMarker);
                }
            }, this);

            var view = map.getView();
            var dynamicMarkerToken = this.model.get('token');
            // Begin automatic update of the shared marker
            var handle = this;
            if (!this.model.has('autoUpdateDynamicMarkersID')) {
                view.setCenter(coords);
                view.setZoom(15);
                this.model.set('autoUpdateDynamicMarkersID', setInterval(function () {
                    handle.getLatestLocation(dynamicMarkerToken);
                }, 1000 * this.model.get('autoUpdateDynamicMarkersInterval')));
            }
        },
        /**
         * When the geolocation information is updates, this function saves the 
         * data and updates the interface
         */
        locationUpdate: function (evt) {
            var positions = this.model.get('positions');
            var geolocation = this.model.get('geolocation');
            var deltaMean = this.model.get('deltaMean');
            var view = this.model.get('view');

            var position = geolocation.getPosition();
            var accuracy = geolocation.getAccuracy();
            var heading = geolocation.getHeading() || 0;
            var speed = geolocation.getSpeed() || 0;
            var m = Date.now();

            this.addPosition(position, heading, m, speed);

            var coords = positions.getCoordinates();
            var len = coords.length;
            if (len >= 2) {
                deltaMean = (coords[len - 1][3] - coords[0][3]) / (len - 1);
            }
            var html = [
                'Position: ' + position[0].toFixed(2) + ', ' + position[1].toFixed(2),
                'Accuracy: ' + accuracy,
                'Heading: ' + Math.round(this.radToDeg(heading)) + '&deg;',
                'Speed: ' + (speed * 3.6).toFixed(1) + ' km/h',
                'Delta: ' + Math.round(deltaMean) + 'ms'
            ].join('<br />');
            document.getElementById('info').innerHTML = html;
            this.model.set('coords', position);
            if (this.model.has('map')) {
                this.model.set('zoomBounds', ol.extent.boundingExtent(position));
                if (this.model.get('initialCenter')) {
                    view.setCenter(position);
                    $('#recenter-control').show();
                    this.model.set('initialCenter', false);
                }
                if (this.model.get('initialZoom') === true) {
                    view.setZoom(16);
                    this.model.set('initialZoom', false);
                }
            }


        },
        toggleLocation: function () {
            var model = this.model;
            var geolocation = model.get('geolocation');
            if (model.get('tracking') === 0) {
                model.set('tracking', 1);
                $('#toggle-tracking').html('Stop tracking');
                $('#toggle-tracking').removeClass('btn-primary').addClass('btn-warning');
                $('#save-location').removeClass('disabled');
                $('#autosave-location-container').show();
                geolocation.setTracking(true); // Start position tracking
                if (this.model.has('map')) {
                    this.model.get('map').on('postcompose', this.render);
                    this.model.get('map').render();
                }
            } else {
                model.set('tracking', 0);
                $('#toggle-tracking').html('Track Location');
                $('#toggle-tracking').removeClass('btn-warning').addClass('btn-primary');
                $('#save-location').addClass('disabled');
                $('#autosave-location-container').hide();
                geolocation.setTracking(false); // Stop position tracking
                if (this.model.has('map')) {
                    this.model.get('map').un('postcompose', this.render);
                }
            }
        },
        // postcompose callback
        render: function () {
            var map = this.model.get('map');
            map.render();
        },
        addPosition: function (position, heading, m, speed) {
            var model = this.model;
            var positions = model.get('positions');
            var x = position[0];
            var y = position[1];
            var fCoords = positions.getCoordinates();
            var previous = fCoords[fCoords.length - 1];
            var prevHeading = previous && previous[2];
            if (prevHeading) {
                var headingDiff = heading - mod(prevHeading);

                // force the rotation change to be less than 180°
                if (Math.abs(headingDiff) > Math.PI) {
                    var sign = (headingDiff >= 0) ? 1 : -1;
                    headingDiff = -sign * (2 * Math.PI - Math.abs(headingDiff));
                }
                heading = prevHeading + headingDiff;
            }
            positions.appendCoordinate([x, y, heading, m]);

            // only keep the 20 last coordinates
            positions.setCoordinates(positions.getCoordinates().slice(-20));

            model.set('positions', positions);
        },
        /**
         * Resizes the map to fit the current viewport size
         */
        winResize: function () {
            var viewportHeight = $(window).height() - $("#map").offset().top;
            var centerRegionWidth = $('#region_2').width();
            var leftOffset = 0;
            if($(window).width() > 767) {
                leftOffset = $('#region_1').width() + $('#region_1').offset().left;                
            } else if ($("main").hasClass('region_1-on') ) {
                $('#map').css('zIndex',-100);
            } else {
                $('#map').css('zIndex','auto');
            }    
            $("#map").offset({ left: leftOffset});
            $("#map").css('height', viewportHeight * 1.0);
            $("#map").css('width', centerRegionWidth * 1.0);
        },
        /**
         * recenters the view by putting the given coordinates at 3/4 from the 
         * top of the screen
         */
        getCenterWithHeading: function (position, rotation, resolution) {
            var map = this.model.get('map');
            var view = this.model.get('view');
            var size = map.getSize();
            var height = size[1];

            if (this.model.get('initialZoom') === true) {
                view.setZoom(16);
                this.model.set('initialZoom', false);
            }

            return [
                position[0] - Math.sin(rotation) * height * resolution * 1 / 4,
                position[1] + Math.cos(rotation) * height * resolution * 1 / 4
            ];
        },
        // convert radians to degrees
        radToDeg: function (rad) {
            return rad * 360 / (Math.PI * 2);
        },
        // convert degrees to radians
        degToRad: function (deg) {
            return deg * Math.PI * 2 / 360;
        },
        // modulo for negative values
        mod: function (n) {
            return ((n % (2 * Math.PI)) + (2 * Math.PI)) % (2 * Math.PI);
        },
        /**
         *  This is a test of the JavaScript-to-server communication. It sends
         *  the current coordinates and time to a PHP file that echos the 
         *  information back to be displayed in the JavaScript console log.
         */
        saveLocation: function () {
            var handle = this;
            var geolocation = this.model.get('geolocation');
            var position = geolocation.getPosition();
            var coords = null;
            (position) ? coords = position : coords = [0, 0];
            if (coords[0] === 0 && coords[1] === 0) {
                // Location has not been acquired. Do not store data.
                return;
            }
            var token = '';
            if (this.model.has('token')) {
                token = this.model.get('token');
            }
            var contact_allow = '';
            if (this.model.has('contact_allow')) {
                contact_allow = this.model.get('contact_allow');
            }
            var group_allow = '';
            if (this.model.has('group_allow')) {
                group_allow = this.model.get('group_allow');
            }
            var contact_deny = '';
            if (this.model.has('contact_deny')) {
                contact_deny = this.model.get('contact_deny');
            }
            var group_deny = '';
            if (this.model.has('group_deny')) {
                group_deny = this.model.get('group_deny');
            }
            var payload = {
                coords: coords,
                heading: geolocation.getHeading(),
                speed: geolocation.getSpeed(),
                accuracy: geolocation.getAccuracy(),
                time: (new Date()).getTime(),
                token: token,
                contact_allow: contact_allow,
                group_allow: group_allow,
                contact_deny: contact_deny,
                group_deny: group_deny

            };
            var apiAction = 'storeDynamicMarker';
            handle.queryServer(payload, apiAction, function (response) {
                //window.console.log('saveLocation POST response: ' + response);
                var result = JSON.parse(response);
                if (!result.status) {
                    window.console.log('Error sharing data.')
                }
            });
        },
        autoSaveLocation: function () {
            if (!this.model.has('autoSaveID')) {
                var handle = this;
                this.model.set('autoSaveID', setInterval(function () {
                    handle.saveLocation();
                }, 1000 * this.model.get('autoSaveUpdateInterval')));
                $('#save-location').removeClass("btn-primary").addClass("btn-warning");
            } else {
                clearInterval(this.model.get('autoSaveID'));
                this.model.unset('autoSaveID');
                $('#save-location').removeClass("btn-warning").addClass("btn-primary");
            }
        },
        /**
         *	Communicate with server
         */
        queryServer: function (dataToSend, apiAction, callback) {
            var phpFilename = 'map';
            var params = "action=" + apiAction + "&data=" + JSON.stringify(dataToSend);
            var http = new XMLHttpRequest();
            var url = phpFilename;
            http.open("POST", url, true);
            http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            http.onreadystatechange = function () {
                if (http.readyState == 4 && http.status == 200) {
                    callback(http.responseText);
                }
            }
            http.send(params);
        },
        queryServerSynchronous: function (dataToSend, apiAction, callback) {
            var phpFilename = 'map';
            var params = "action=" + apiAction + "&data=" + JSON.stringify(dataToSend);
            var http = new XMLHttpRequest();
            var url = phpFilename;
            http.open("POST", url, false);
            http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            http.onreadystatechange = function () {
                if (http.readyState == 4 && http.status == 200) {
                    callback(http.responseText);
                }
            }
            http.send(params);
        },
        getACL: function () {
            var contact_allow_elements = document.getElementsByName("contact_allow[]");
            var contact_allow = [];
            for (var i = 0; i < contact_allow_elements.length; i++) {
                contact_allow.push(contact_allow_elements[i].value);
            }
            this.model.set('contact_allow', contact_allow);

            var group_allow_elements = document.getElementsByName("group_allow[]");
            var group_allow = [];
            for (var i = 0; i < group_allow_elements.length; i++) {
                group_allow.push(group_allow_elements[i].value);
            }
            this.model.set('group_allow', group_allow);

            var contact_deny_elements = document.getElementsByName("contact_deny[]");
            var contact_deny = [];
            for (var i = 0; i < contact_deny_elements.length; i++) {
                contact_deny.push(contact_deny_elements[i].value);
            }
            this.model.set('contact_deny', contact_deny);

            var group_deny_elements = document.getElementsByName("group_deny[]");
            var group_deny = [];
            for (var i = 0; i < group_deny_elements.length; i++) {
                group_deny.push(group_deny_elements[i].value);
            }
            this.model.set('group_deny', group_deny);
        },
        getURL: function () {
            var url = document.location.href;
            var rootURL = url.substring(0, url.lastIndexOf("/"));
            return rootURL;
        },
        shareLocation: function () {
            // If the ACL has been set, then unset it and disable sharing
            if (this.model.has('contact_allow')) {
                this.model.unset('contact_allow');
                this.model.unset('group_allow');
                this.model.unset('contact_deny');
                this.model.unset('group_deny');
                $('#share-location').html('Share location');
                $('#share-location').removeClass("btn-warning").addClass("btn-success");
                $('#dynamic-location-share-link-container').hide();
                if (this.model.has('token')) {
                    this.model.unset('token');
                }
                return;
            }
            // Otherwise, the user wants to share based on the current ACL
            var handle = this;
            this.getACL();
            var visible = 0;
            if ($('#post-visible').is(':checked')) {
                visible = 1;
            }
            var payload = {
                contact_allow: this.model.get('contact_allow'),
                group_allow: this.model.get('group_allow'),
                contact_deny: this.model.get('contact_deny'),
                group_deny: this.model.get('group_deny'),
                visible: visible
            };
            var apiAction = 'shareUserLocation';
            this.queryServer(payload, apiAction, function (response) {
                var result = JSON.parse(response);
                if (result.status) {
                    handle.model.set('token', result.item.resource_id);
                    $('#dynamic-location-share-link').val(handle.getURL() + '/map?login=0&action=getLatestLocation&token=' + result.item.resource_id);
                    $('#dynamic-location-share-link-container').show();
                    $('#share-location').html('Stop sharing location');
                    $('#share-location').removeClass("btn-success").addClass("btn-warning");
                } else {
                    window.console.log('Error getting location sharing access token');
                }
            });
        },
        revokeAllLocationShares: function () {
            var answer = confirm("Revoke all previous personal location shares?");
            if (!answer) {
                return false;
            }
            var payload = {
            };
            var apiAction = 'revokeAllDynamicShares';
            this.queryServer(payload, apiAction, function (response) {
                var result = JSON.parse(response);
                if (result.status) {
                    window.console.log('All dynamic marker shares revoked.');
                } else {
                    window.console.log('Error revoking dynamic location shares');
                }
            });

        },
        displaySharedData: function () {
            $('#show-shared-data-btn').removeClass("btn-default").addClass("btn-success");
            var geoloc = this.model.get('sharedLocations');
            var map = this.model.get('map');
            var numPts;
            if (geoloc.length < 1000) {
                numPts = geoloc.length;
            } else {
                numPts = 1000;
            }
            var pathLayerSource = new ol.source.Vector();
            var coords = [];
            var newLayerMarkers = [];
            for (var j = 0; j < numPts; j++) {
                var marker = geoloc[j];
                coords.push([marker.lat, marker.lon]);

                var newMapMarker = new ol.Feature({
                    geometry: new ol.geom.Point([marker.lat, marker.lon]),
                    style: new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 6,
                            fill: new ol.style.Fill({
                                color: '#3399CC'
                            }),
                            stroke: new ol.style.Stroke({
                                color: '#fff',
                                width: 2
                            })
                        })
                    })
                });
                newLayerMarkers.push(newMapMarker);
            }
            var newLayerSource = new ol.source.Vector({
                features: newLayerMarkers
            });
            var newLayer = new ol.layer.Vector({
                source: newLayerSource
            });
            map.addLayer(newLayer);
            var lineSegments = [];
            for (var j = 1; j < coords.length - 1; j++) {
                lineSegments.push([coords[j], coords[j - 1]]);
            }
            var pathGeometry = new ol.geom.MultiLineString(lineSegments);
            var pathFeature = new ol.Feature({
                geometry: pathGeometry
            });
            pathLayerSource.addFeature(pathFeature);

            var pathLayer = new ol.layer.Vector({
                source: pathLayerSource,
                style: new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#FF0000',
                        width: 3
                    })
                }),
                name: 'Shared locations'
            });
            map.addLayer(pathLayer);
            var view = map.getView();
            view.fitGeometry(pathGeometry, map.getSize());
            if (view.getZoom() > 16) {
                view.setZoom(16);
            }
        },
        addMarker: function () {
            var handle = this;
            var coords = this.model.get('clickedCoordinate');
            $('#generic-dialog-submit').off('click');
            $('#generic-dialog-submit').on('click', function (event) {
                handle.closeGenericDialog();
                handle.saveMarker(coords);
                return false;
            });
            var coordStr = JSON.stringify(coords);
            var dialogMessage = '<p class="text-center"> Add a marker at ' + coordStr + '</p>';
            dialogMessage += '<p class="text-center"><input type="text" id="add-marker-name" placeholder="marker name" length="12"></input></p>';
            dialogMessage += '<p class="text-center"><input type="textarea" id="add-marker-description" rows="3" cols="20" placeholder="Brief description"></input></p>';
            this.showDialog('Add marker', dialogMessage, 'Create Marker');
        },
        saveMarker: function (coords) {
            var payload = {
                newMarker: {
                    lat: parseFloat(coords[0]),
                    lon: parseFloat(coords[1]),
                    name: $('#add-marker-name').val(),
                    description: $('#add-marker-description').val()
                }
            };
            var handle = this;
            var apiAction = 'saveNewMarker';
            this.queryServer(payload, apiAction, function (response) {
                var result = JSON.parse(response);
                if (result.status) {
                    handle.model.set('newMarkerToken', result.token);
                    handle.refreshMarkerList();
                } else {
                    window.console.log('Error saving new marker');
                }
            });
        },
        getURLParams: function ()
        {
            var vars = [], hash;
            var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
            for (var i = 0; i < hashes.length; i++)
            {
                hash = hashes[i].split('=');
                vars.push(hash[0]);
                vars[hash[0]] = hash[1];
            }
            return vars;
        }
    });
    // Start the engines
    $(init);
})(jQuery);

// Set the map viewport size after the document is loaded
$(window).load(function (){   
    $('#expand-aside').click();
});