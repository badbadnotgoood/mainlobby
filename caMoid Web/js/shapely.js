shapely = {};
shapely.geom = function(coords, type) {

    var geom = {
        type: type,
        coords: coords,
        area: area,
        length: length,
        geojson: geojson,
        buffer: buffer,
        envelope: envelope

        /*simplify: simplify,
        union: union,
        centroid: centroid,
        difference: difference,
        intersection: intersection,
        within: within,
        contains: contains,
        overlaps: overlaps,
        intersects: intersects*/

    }

    function area() {
        return 0.0;
    }

    function length() {
        return 0.0;
    }

    function buffer(distance, res) {
        return shapely.buffer(geom, distance, res);
    }

    function geojson(properties) {
        return {
            geometry: { coordinates: coords, type: type },
            properties: properties
        }
    }

    function envelope() {
        return shapely.envelope(geom);
    }

    return geom;
};

shapely.point = function(coords) {
    var shape = shapely.geom(coords, 'Point');

    shape.x = coords[0];
    shape.y = coords[1];

    return shape;
}
shapely.line = function(coords) {

    var shape = shapely.geom(coords, 'LineString');

    shape.length = function() {
        var len = 0;

        for (i = 1; i < coords.length; i++) {
            var p1 = coords[i - 1],
                p2 = coords[i];

            len += Math.sqrt((p2[0] -= p1[0]) * p2[0] + (p2[1] -= p1[1]) * p2[1]);
        }

        return len;
    }

    return shape;
}


shapely.polygon = function(coords) {
        // TODO add supposrt for multi geoms

        // closed?
        if (coords[0]) {
            var len = coords[0].length;
            if (coords[0][len - 1][0] != coords[0][0][0] || coords[0][len - 1][1] != coords[0][0][1]) {
                coords[0].push(coords[0][0]);
            }
        }

        var shape = shapely.geom(coords, 'Polygon');

        shape.area = function() {
            var c = coords[0];
            var area = 0,
                len = c.length;
            var j = len - 1;

            for (i = 0; i < len; i++) {
                area += (c[j][0] + c[i][0]) * (c[j][1] - c[i][1]);
                j = i;
            }

            return area / 2;
        }

        return shape;
    }
    // area is no longer used, instead the fn is hung directly 
    // on the polygon since thats the only place its used
shapely.area = function(coords, type) {
    var _area = {
        point: function() {
            return 0.0;
        },
        line: function() {
            return 0.0;
        },
        polygon: function(c) {
            var area = 0,
                len = c.length;
            var j = len - 1;

            for (i = 0; i < len; i++) {
                area += (c[j][0] + c[i][0]) * (c[j][1] - c[i][1]);
                j = i;
            }

            return area / 2;
        }
    }

    return _area[type](coords);

}

shapely.envelope = function(geometry) {

    var minx = 0,
        miny = 0,
        maxx = 0,
        maxy = 0;

    var _envelope = {
        'Point': function() {
            return geometry;
        },
        'LineString': function() {
            return bounds(geometry.coords);
        },
        'Polygon': function() {
            return bounds(geometry.coords[0]);
        }
    }

    function bounds(coords) {
        var len = coords.length;
        while (len--) {
            var c = coords[len];
            minx = (c[0] < minx) ? c[0] : minx;
            miny = (c[1] < miny) ? c[1] : miny;
            maxx = (c[0] > maxx) ? c[0] : maxx;
            maxy = (c[1] > maxy) ? c[1] : maxy;
        }
        return shapely.polygon([
            [
                [minx, miny],
                [minx, maxy],
                [maxx, maxy],
                [maxx, miny]
            ]
        ]);
    }

    return _envelope[geometry.type](geometry.coords);

}

shapely.buffer = function(geometry, distance, res) {

        var quadrants = res || 16;

        var buffer = {
            coords: []
        }

        if (geometry.type == 'Point') {

            if (parseFloat(distance) <= 0.0) return shapely.polygon([]);

            var pnt0 = [geometry.coords[0] + distance, geometry.coords[1]];
            buffer.coords.push(pnt0);
            buffer.coords = buffer.coords.concat(arc(geometry.coords, 0.0, 2.0 * Math.PI, -1));

            return shapely.polygon([buffer.coords]);

        } else if (geometry.type == 'LineString') {

            if (parseFloat(distance) <= 0.0) return shapely.polygon([]);

            var nCoords = geometry.coords.length - 1;

            // first offset point 
            buffer.coords.push([geometry.coords[0][0] - distance, geometry.coords[0][1]]);

            // direct left offset of each point 
            for (var i = 1; i <= nCoords; i++) {
                var os = offset([geometry.coords[i - 1], geometry.coords[i]], -1);
                buffer.coords.push(os[1]);
            }

            // end cap, clock wise
            buffer.coords = buffer.coords.concat(reflex(
                geometry.coords[nCoords],
                buffer.coords[buffer.coords.length - 1],
                offset([geometry.coords[nCoords - 1], geometry.coords[nCoords]], 1)[1], -1
            ));

            // direct right offset of each point 
            for (var i = nCoords; i > 0; i--) {
                var os = offset([geometry.coords[i - 1], geometry.coords[i]], 1);
                buffer.coords.push(os[1]);
            }

            buffer.coords.push([geometry.coords[0][0] + distance, geometry.coords[0][1]]);

            buffer.coords = buffer.coords.concat(reflex(
                geometry.coords[0],
                buffer.coords[buffer.coords.length - 1],
                buffer.coords[0], -1
            ));

            return shapely.polygon([buffer.coords]);


        } else if (geometry.type == 'Polygon') {

            var nCoords = geometry.coords[0].length - 1;
            buffer.segments = [];
            buffer.coords[0] = [];

            for (var i = 0; i < nCoords; i++) {
                var os = offset([geometry.coords[0][i], geometry.coords[0][i + 1]], 1);
                buffer.segments.push(os);
            }

            for (var j = 1; j < buffer.segments.length; j++) {
                var prev = buffer.segments[j - 1];
                var current = buffer.segments[j];
                buffer.coords[0].push(prev[0]);
                buffer.coords[0] = buffer.coords[0].concat(reflex(geometry.coords[0][j], prev[1], current[0], -1));
            }

            buffer.coords[0] = buffer.coords[0].concat(
                reflex(
                    geometry.coords[0][0],
                    buffer.segments[buffer.segments.length - 1][1],
                    buffer.segments[0][0], -1
                )
            );

            return shapely.polygon(buffer.coords);
        }


        function offset(segment, side) {
            var offset = [
                [],
                []
            ];

            var side = side * -1,
                dx = segment[1][0] - segment[0][0],
                dy = segment[1][1] - segment[0][1];

            var len = Math.sqrt(dx * dx + dy * dy);
            var ux = side * distance * dx / len,
                uy = side * distance * dy / len;

            //console.log(ux, uy)

            offset[0][0] = segment[0][0] - uy;
            offset[0][1] = segment[0][1] + ux;
            offset[1][0] = segment[1][0] - uy;
            offset[1][1] = segment[1][1] + ux;

            return offset;
        }


        function arc(pnt, angle0, angle1, direction) {
            var fullAngle = Math.abs(angle0 - angle1);
            var nSegs = parseInt((fullAngle / (Math.PI / 2.0 / quadrants) + 0.5));
            if (nSegs < 1) return;
            var currentAngle = 0.0;
            coords = [];

            while (currentAngle < fullAngle) {
                var angle = angle0 + direction * currentAngle;
                coords.push([pnt[0] + distance * Math.cos(angle), pnt[1] + distance * Math.sin(angle)]);
                currentAngle += (fullAngle / nSegs);
            }
            return coords;
        }


        function reflex(pnt, pnt0, pnt1, direction) {

            var dx0 = pnt0[0] - pnt[0];
            var dy0 = pnt0[1] - pnt[1];
            var startAngle = Math.atan2(dy0, dx0);

            var dx1 = pnt1[0] - pnt[0];
            var dy1 = pnt1[1] - pnt[1];
            var endAngle = Math.atan2(dy1, dx1);

            if (direction === -1) {
                if (startAngle <= endAngle)
                    startAngle += 2.0 * Math.PI;
            } else {
                if (startAngle >= endAngle)
                    startAngle -= 2.0 * Math.PI;
            }

            return arc(pnt, startAngle, endAngle, direction);
        };

    }
    //return shapely;})();