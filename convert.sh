convert $1 -paint 5 -colors 2 +dither -type bilevel -negate bmp:- | /usr/local/bin/potrace -o temp.json --turdsize 30 -b geojson
ogr2ogr -f "GeoJSON" /vsistdout/ temp.json -simplify 1.5
