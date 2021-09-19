<html>

<head>
    <!-- <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.5/leaflet.css" /> -->
    <!-- <link rel="stylesheet" href="https://rawgit.com/perliedman/leaflet-routing-machine/master/dist/leaflet-routing-machine.css" /> -->
    <!-- <script src="http://cdn.leafletjs.com/leaflet-0.7.5/leaflet.js"></script> -->
    <!-- <script src="https://rawgit.com/perliedman/leaflet-routing-machine/3.0.1/dist/leaflet-routing-machine.js"></script> -->
    <!-- <script src="js/leaflet-routing-openroute.js"></script> -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@latest/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet@latest/dist/leaflet.js"></script>
    <!-- Leaflet Routing Machine -->
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.min.js"></script>
    <!-- Leaflet Routing Machine - OpenRoute Service -->
    <script src="https://unpkg.com/@gegeweb/leaflet-routing-machine-openroute@latest/dist/leaflet-routing-openroute.min.js"></script>

    <link rel="stylesheet" href="css/index.css">
    <script src="js/shapely.js"></script>
    <script src="js/proj4.js"></script>
    <script src="js/jquery.js"></script>
    <script src="js/ors-js-client.js"></script>
    <script src="js/script.js"></script>
</head>

<body>
    <div id="mapid">

    </div>
    <button class="build"> Построить маршрут</button>
    <script>
        var map = L.map('mapid').setView([55.753569, 37.61911], 13);

        var api_key = "5b3ce3597851110001cf6248e87c2dfdbf3e409e844eee0f255b6c4b"

        L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token=pk.eyJ1IjoiYmFkYmFkbm90Z29vZCIsImEiOiJja3RxMTdqdHkwcnRxMm5vYWVvcXVia3J5In0.97Gsy4fkvJQsrkD8_XeFLA', {
            attribution: '',
            maxZoom: 22,
            id: 'mapbox/dark-v10',
            tileSize: 512,
            zoomOffset: -1,
            accessToken: 'your.mapbox.access.token'
        }).addTo(map);

        const osrRouter = L.Routing.openrouteservice(api_key, {
            "timeout": 30 * 1000, // 30",
            "format": "json", // default, gpx not yet supported
            "host": "https://api.openrouteservice.org", // default if not setting
            "service": "directions", // default (for routing) 
            "api_version": "v2", // default
            "profile": "cycling-road", // default
            "routingQueryParams": {
                "attributes": [
                    "avgspeed",
                    "percentage"
                ],
                "language": "fr-fr",
                "maneuvers": "true",
                "preference": "recommended",
            }
        });

        L.Routing.control({
            router: osrRouter,
            formatter: L.routing.formatterORS({
                language: 'fr', // language of instructions & control ui
                steptotext: true, // force using internal formatter instead of ORS instructions
            }),
            waypoints: [
                L.latLng(57.74, 11.94),
                L.latLng(57.6792, 11.949)
            ]
        }).addTo(map);

        var leftcord = 37.583356,
            rightcord = 37.670292,
            topcord = 55.76756,
            bottomcord = 55.725251;

        var simpleIcon = L.icon({
            iconUrl: 'img/сamred.svg',

            iconSize: [20, 20],
        })

        $.get('/data/camera.json', function(data) {
            data.forEach(function(element, index) {
                if (index % 1 == 0) {
                    console.log(index);
                    var coord1 = element.coords[0],
                        coord2 = element.coords[1];
                    if (coord1 < topcord && coord1 > bottomcord && coord2 > leftcord && coord2 < rightcord) {
                        marker = new L.marker([coord1, coord2], {
                                icon: simpleIcon
                            })
                            .bindPopup('Camera : ' + index + ' coord1 : ' + coord1 + ' coord2 : ' + coord2)
                            .addTo(map);
                    }
                }
            });
        })

        // map.on('click', function(e) {
        //     onMapClick(e);
        // });

        var loc1, loc2;

        $('.build').click(function() {
            var lat1 = loc1._latlng.lat,
                lng1 = loc1._latlng.lng,
                lat2 = loc2._latlng.lat,
                lng2 = loc2._latlng.lng;
            console.log(lat1);
            buttonClick(lat1, lng1, lat2, lng2);
        });


        function onMapClick(e) {
            if (loc1 == null) {
                loc1 = new L.marker(e.latlng, {
                    draggable: 'true'
                });
                loc1.on('dragend', function(event) {
                    //отправляем запрос маршрута
                });
                map.addLayer(loc1);
            } else if (loc2 == null) {
                loc2 = new L.marker(e.latlng, {
                    draggable: 'true'
                });
                loc2.on('dragend', function(event) {
                    //отправляем запрос марурута
                });
                map.addLayer(loc2);
                //отправляем запрос маршрута
            }
        };

        function buttonClick(lat1, lng1, lat2, lng2) {

            var groups = {};

            var layercontrol = L.control.layers(null, groups).addTo(map);

            //var router = L.Routing.osrm({});
            // using the "new", because of the bug on L.Routing.osrmv1
            var router = new L.Routing.OSRMv1({
                language: 'ru',
                profil: 'car'
            });

            var route1waypoints = [
                    L.Routing.waypoint(L.latLng(lat1, lng1)),
                    L.Routing.waypoint(L.latLng(lat2, lng2))
                ],
                route1plan = L.Routing.plan(route1waypoints);

            router.route(route1waypoints, function(error, routes) {
                var route1line = L.Routing.line(routes[0]);
                var route1group = L.layerGroup([route1plan, route1line]).addTo(map);
                console.log(route1group);
                layercontrol.addOverlay(route1group, "LayerRoute1");
            }, null, {});

            // Add your api_key here
            let orsDirections = new Openrouteservice.Directions({
                api_key: "5b3ce3597851110001cf6248e87c2dfdbf3e409e844eee0f255b6c4b"
            });

            orsDirections.calculate({
                    coordinates: [
                        [8.690958, 49.404662],
                        [8.687868, 49.390139]
                    ],
                    profile: "driving-car",
                    extra_info: ["waytype", "steepness"],
                    format: "json"
                })
                .then(function(json) {
                    // Add your own result handling here
                    console.log(json);
                })
                .catch(function(err) {
                    console.error(err);
                }).addTo(map);

            // var api_key = "5b3ce3597851110001cf6248e87c2dfdbf3e409e844eee0f255b6c4b"

            // L.Routing.control({
            //     waypoints: [
            //         L.latLng(57.74, 11.94),
            //         L.latLng(57.6792, 11.949)
            //     ],
            //     router: new L.Routing.OSRMv1({
            //         serviceUrl: url_to_your_service
            //     })
            // }).addTo(map);

            // let orsDirections = new Openrouteservice.Directions({ api_key: api_key});

            // orsDirections.calculate({
            //         coordinates: [
            //             [8.690958, 49.404662],
            //             [8.687868, 49.390139]
            //         ],
            //         profile: "driving-car",
            //         extra_info: ["waytype", "steepness"],
            //         format: "json"
            //     })
            //     .then(function(json) {
            //         // Add your own result handling here
            //         console.log(JSON.stringify(json));
            //     })
            //     .catch(function(err) {
            //         console.error(err);
            //     });

            // let router = L.Routing.control({
            //     router: new L.Routing.openrouteserviceV2(api_key),
            //     waypoints: [
            //         L.latLng(lat1, lng1),
            //         L.latLng(lat2, lng2)
            //     ],
            //     routeWhileDragging: false,
            //     show: false,
            //     fitSelectedRoutes: false,
            //     createMarker: function(i, waypoint, n) {
            //         return null;
            //     },
            //     lineOptions: {
            //         styles: [{
            //             color: '#9f150b',
            //             opacity: 1,
            //             weight: 4
            //         }]
            //     }
            // });

            // router.addTo(map);

            // orsDirections.calculate({
            //         coordinates: [
            //             [lat1, lng1],
            //             [lat2, lng2]
            //         ],
            //         profile: "driving-car",
            //         extra_info: ["waytype", "steepness"],
            //         format: "json"
            //     })
            //     .then(function(json) {
            //         // Add your own result handling here
            //         console.log(JSON.stringify(json));
            //     })
            //     .catch(function(err) {
            //         console.error(err);
            //     });
        }
    </script>
</body>

</html>