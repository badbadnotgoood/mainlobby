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
            left: 15%;
            top: 0;
            bottom: 0;
            width: 85%;
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
    </style>
</head>

<body>
    <div id="map"></div>
    <div class="sidebar">
        <div class="heading">
            <h1>Routes</h1>
        </div>
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

        map.scrollZoom.enable();
        map.addControl(directions, 'top-right');

        const clearances = {
            'type': 'FeatureCollection',
            'features': [{
                    'type': 'Feature',
                    'geometry': {
                        'type': 'Point',
                        'coordinates': [-84.47426, 38.06673]
                    },
                    'properties': {
                        'clearance': "13' 2"
                    }
                },
                {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'Point',
                        'coordinates': [-84.47208, 38.06694]
                    },
                    'properties': {
                        'clearance': "13' 7"
                    }
                },
                {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'Point',
                        'coordinates': [-84.47184, 38.06694]
                    },
                    'properties': {
                        'clearance': "13' 7"
                    }
                },
                {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'Point',
                        'coordinates': [-84.60485, 38.12184]
                    },
                    'properties': {
                        'clearance': "13' 7"
                    }
                },
                {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'Point',
                        'coordinates': [-84.61905, 37.87504]
                    },
                    'properties': {
                        'clearance': "12' 0"
                    }
                },
                {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'Point',
                        'coordinates': [-84.55946, 38.30213]
                    },
                    'properties': {
                        'clearance': "13' 6"
                    }
                },
                {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'Point',
                        'coordinates': [-84.27235, 38.04954]
                    },
                    'properties': {
                        'clearance': "13' 6"
                    }
                },
                {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'Point',
                        'coordinates': [-84.27264, 37.82917]
                    },
                    'properties': {
                        'clearance': "11' 6"
                    }
                }
            ]
        };

        const obstacle = turf.buffer(clearances, 0.25, {
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



                heading.className = isClear ? 'title' : 'warning';
                heading.innerHTML = `${emoji} Route ${id + 1} ${collision}`;

                details.innerHTML = `This route ${detail} through an avoidance area.`;
            }
        });
    </script>
</body>

</html>