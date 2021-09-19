<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Demo: Route avoidance with the Directions API and Turf.js</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <script src="https://api.tiles.mapbox.com/mapbox-gl-js/v2.4.1/mapbox-gl.js"></script>
    <link href="https://api.tiles.mapbox.com/mapbox-gl-js/v2.4.1/mapbox-gl.css" rel="stylesheet" />

    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-directions/v4.0.2/mapbox-gl-directions.js"></script>
    <link rel="stylesheet" href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-directions/v4.0.2/mapbox-gl-directions.css" type="text/css" />

    <script src="https://npmcdn.com/@turf/turf/turf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mapbox-polyline/1.1.1/polyline.js"></script>

    <script src='https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-language/v1.0.0/mapbox-gl-language.js'></script>

    <script src="js/jquery.js"></script>

    <style>
        body {
            color: #404040;
            font: 400 15px/22px 'Source Sans Pro', 'Helvetica Neue', sans-serif;
            margin: 0;
            padding: 0;
        }

        * {
            box-sizing: border-box;
        }

        #map {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
        }

        .sidebar {
            position: absolute;
            width: 15%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            border-right: 1px solid rgba(0, 0, 0, 0.25);
        }

        h1 {
            font-size: 22px;
            margin: 0;
            font-weight: 400;
        }

        a {
            color: #404040;
            text-decoration: none;
        }

        a:hover {
            color: #101010;
        }

        .heading {
            background: #fff;
            border-bottom: 1px solid #eee;
            min-height: 60px;
            line-height: 60px;
            padding: 0 10px;
        }

        .reports {
            height: 100%;
            overflow: auto;
            padding-bottom: 60px;
        }

        .reports .item {
            display: block;
            border-bottom: 1px solid #eee;
            padding: 10px;
            text-decoration: none;
        }

        .reports .item:last-child {
            border-bottom: none;
        }

        .reports .item .title {
            display: block;
            color: #00853e;
            font-weight: 700;
        }

        .reports .item .warning {
            display: block;
            color: red;
            font-weight: 700;
        }

        .reports .item .title small {
            font-weight: 400;
        }

        .reports .item.active .title,
        .reports .item .title:hover {
            color: #8cc63f;
        }

        .reports .item.active {
            background-color: #f8f8f8;
        }

        ::-webkit-scrollbar {
            width: 3px;
            height: 3px;
            border-left: 0;
            background: rgba(0, 0, 0, 0.1);
        }

        ::-webkit-scrollbar-track {
            background: none;
        }

        ::-webkit-scrollbar-thumb {
            background: #00853e;
            border-radius: 0;
        }

        .directions-control-instructions,
        .mapboxgl-ctrl-bottom-right {
            display: none;
        }

        .mapbox-directions-origin,
        .mapbox-directions-destination {
            padding: 10px;
            display: flex;
            align-items: center;

        }
    </style>
</head>

<body>
    <div id="map"></div>
    </div>

    <script>
        mapboxgl.accessToken = 'pk.eyJ1IjoiYmFkYmFkbm90Z29vZCIsImEiOiJja3RxMTdqdHkwcnRxMm5vYWVvcXVia3J5In0.97Gsy4fkvJQsrkD8_XeFLA';

        const map = new mapboxgl.Map({
            attribution: '',
            container: 'map',
            style: 'mapbox://styles/mapbox/dark-v10',
            center: [37.62457846707394, 55.75475001630599],
            zoom: 11
        });

        const directions = new MapboxDirections({
            accessToken: mapboxgl.accessToken,
            unit: 'metric',
            profile: 'mapbox/walking',
            alternatives: 'false',
            geometries: 'geojson'
        });

        const language = new MapboxLanguage();
        map.addControl(language);

        map.scrollZoom.enable();
        map.addControl(directions, 'bottom-left');

        var clearances;

        $.get('data/camera.json', function(data) {
            $.get('data/police.json', function(data) {

                var clearances2 = data;

                const obstacle2 = turf.buffer(clearances2, 0.05, {
                    units: 'kilometers'
                });

                map.on('load', () => {
                    map.addLayer({
                        id: 'clearances2',
                        type: 'fill',
                        source: {
                            type: 'geojson',
                            data: obstacle2
                        },
                        layout: {},
                        paint: {
                            'fill-color': '#546de5',
                            'fill-opacity': 0.5,
                            'fill-outline-color': '#546de5'
                        }
                    });

                    for (let i = 0; i < 3; i++) {
                        map.addSource(`route${i}`, {
                            type: 'geojson',
                            data: {
                                type: 'Feature'
                            }
                        });

                        map.addLayer({
                            id: `route${i}`,
                            type: 'line',
                            source: `route${i}`,
                            layout: {
                                'line-join': 'round',
                                'line-cap': 'round'
                            },
                            paint: {
                                'line-color': '#cccccc',
                                'line-opacity': 0.5,
                                'line-width': 13,
                                'line-blur': 0.5
                            }
                        });
                    }
                });
            });
            clearances = data;

            const obstacle = turf.buffer(clearances, 0.025, {
                units: 'kilometers'
            });

            map.on('load', () => {
                map.addLayer({
                    id: 'clearances',
                    type: 'fill',
                    source: {
                        type: 'geojson',
                        data: obstacle
                    },
                    layout: {},
                    paint: {
                        'fill-color': '#f03b20',
                        'fill-opacity': 0.5,
                        'fill-outline-color': '#f03b20'
                    }
                });

                for (let i = 0; i < 3; i++) {
                    map.addSource(`route${i}`, {
                        type: 'geojson',
                        data: {
                            type: 'Feature'
                        }
                    });

                    map.addLayer({
                        id: `route${i}`,
                        type: 'line',
                        source: `route${i}`,
                        layout: {
                            'line-join': 'round',
                            'line-cap': 'round'
                        },
                        paint: {
                            'line-color': '#cccccc',
                            'line-opacity': 0.5,
                            'line-width': 13,
                            'line-blur': 0.5
                        }
                    });
                }
            });

            directions.on('route', ({
                route
            }) => {
                const routes = route.map((route, index) => ({
                    ...route,
                    id: index
                }));

                for (let i = 0; i < 3; i++) {
                    map.setLayoutProperty(`route${i}`, 'visibility', 'none');
                }

                for (const {
                        id,
                        geometry
                    } of routes) {
                    map.setLayoutProperty(`route${id}`, 'visibility', 'visible');

                    const routeLine = polyline.toGeoJSON(geometry);

                    map.getSource(`route${id}`).setData(routeLine);

                    const isClear = turf.booleanDisjoint(obstacle, routeLine) === true;

                    const collision = isClear ? 'is good!' : 'is bad.';
                    const emoji = isClear ? '✔️' : '⚠️';
                    const detail = isClear ? 'does not go' : 'goes';

                    if (isClear) {
                        map.setPaintProperty(`route${id}`, 'line-color', '#74c476');
                    } else {
                        map.setPaintProperty(`route${id}`, 'line-color', '#de2d26');
                    }
                }
            });
        });

        $('.mapboxgl-ctrl-bottom-left').find('a').remove().parent('.mapboxgl-ctrl').remove()
        $('.mapbox-directions-component-keyline').css({
            paddingLeft: '10px',
            paddingRight: '10px'
        }).find('button').remove();

        $('.mapboxgl-ctrl-bottom-left').css({
            width: '100%'
        });
        $('.mapboxgl-ctrl-directions').css({
            maxWidth: 'unset',
            width: '100%',
            margin: 'unset',
            marginBottom: '0px',
            position: 'absolute',
            bottom: '0px',
            left: '0px',
            right: '0px',
            backdropFilter: 'blur(5px)'
        });
        $('.mapbox-directions-origin-input').css({
            width: '100%'
        });
        $('.mapboxgl-ctrl-geocoder').css({
            width: '100%'
        }).find('input').css({
            width: '100%'
        })
        var elm = $('.mapboxgl-ctrl-geocoder').find('input');
        console.log(elm);
        elm.attr('placeholder', 'Откуда').css({
            fontSize: '14px'
        });
        $('.mapbox-directions-origin, .mapbox-directions-destination').find('label').css({
            top: 'unset'
        })
        $('.mapboxgl-ctrl-geocoder').parent().css({
            width: '100%'
        })
        elm.last().attr('placeholder', 'Куда');
    </script>
</body>

</html>