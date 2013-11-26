<?php
function point_in_polygon($point, $vertices) {
    // Check if the point is inside the polygon or on the boundary
    $intersections = 0;
    $vertices_count = count($vertices);

    for ($i=1; $i < $vertices_count; $i++) {

        $vertex1 = $vertices[$i-1];
        $vertex2 = $vertices[$i];

        if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $point['y'] and $point['x'] > min($vertex1['x'], $vertex2['x']) and $point['x'] < max($vertex1['x'], $vertex2['x'])) { // Check if point is on an horizontal polygon boundary
            $result = TRUE;
        }

        if ($point['y'] > min($vertex1['y'], $vertex2['y']) and $point['y'] <= max($vertex1['y'], $vertex2['y']) and $point['x'] <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y']) { 

            $xinters = ($point['y'] - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x'];

            if ($xinters == $point['x']) { // Check if point is on the polygon boundary (other than horizontal)
                $result = TRUE;
            }

            if ($vertex1['x'] == $vertex2['x'] || $point['x'] <= $xinters) {
                $intersections++;
            }

        }

    }

    // If the number of edges we passed through is even, then it's in the polygon.
    if ($intersections % 2 != 0) {
        $result = TRUE;
    } else {
        $result = FALSE;
    }
    return $result;
};

function convertPNGtoSVG($file) {
	$tmp = tempnam("/tmp", "png").".png";
	move_uploaded_file($file, $tmp);
	shell_exec("convert -background white -flatten {$tmp} {$tmp}");
	$output = shell_exec("/usr/bin/autotrace --output-format svg --line-threshold 5 --background-color ffffff ".$tmp);
	unlink($tmp);
	return $output;
};

function getKMLbox($file) {
	$kml = file_get_contents($file);
	$p = xml_parser_create();
	xml_parse_into_struct($p, $kml, $vals, $index);
	xml_parser_free($p);
	$box = array();
	foreach($vals as $val) {
		if ($val["tag"] == "NORTH") {
			$box["north"] = $val["value"];
		} elseif ($val["tag"] == "SOUTH") {
                        $box["south"] = $val["value"];
		} elseif ($val["tag"] == "EAST") {
                        $box["east"] = $val["value"];
		} elseif ($val["tag"] == "WEST") {
                        $box["west"] = $val["value"];
		};
	};
	return $box;
};

header('Content-type: application/vnd.google-earth.kml+xml');
header("Content-Disposition: attachment; filename=\"coverage.kml\"");
echo '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://earth.google.com/kml/2.2">
  <Document>
    <Placemark>
      <Style>
        <PolyStyle>
	  <color>a00000ff</color>
	  <outline>0</outline>
        </PolyStyle>
      </Style>
      <MultiGeometry>
';

$box = getKMLbox($_FILES['ufile']['tmp_name'][0]);

//$north = -8.343625;
//$south = -8.388619;
//$east = -36.66716;
//$west = -36.7481;

$y = $box["south"] - $box["north"];
$x = $box["west"] - $box["east"];

$svg = convertPNGtoSVG($_FILES['ufile']['tmp_name'][1]);
$p = xml_parser_create();
xml_parse_into_struct($p, $svg, $vals, $index);
xml_parser_free($p);

$shapes = array();

foreach ($vals as $val) {
        if ($val["tag"] == "SVG") {
		if (isset($val["attributes"]["HEIGHT"])) {
			$height = $val["attributes"]["HEIGHT"];
                        $width = $val["attributes"]["WIDTH"];
			$y_factor = $y / $height;
			$x_factor = $x / $width;
		};
	} else if ($val["tag"] == "PATH") {
		$polygons = preg_split("/[M]+/", $val["attributes"]["D"]);
		foreach($polygons as $polygon) {
	                $coords = preg_split("/[LCz]+/", $polygon);
			if (count($coords) > 1) {
				$p = array();
				foreach ($coords as $coord) {
					if (count($coord) > 0) {
						$points = explode(" ", $coord);
						if (count($points) > 1) {
							while (list($key, $value) = each($points)) {
								list($key1, $value2) = each($points);
		                                                $p[] = array("x"=>$box["east"]+(($width-$value) * $x_factor), "y"=> $box["north"]+($value2 * $y_factor));
							};
						};
					};
				};
				$shapes[] = $p;
			};
		};
	};
};
$inner = array();
foreach ($shapes as $key => $polygon) {
	foreach ($shapes as $key_other => $other) {
		if ($key != $key_other) {
			if (point_in_polygon($polygon[0], $other)) {
				$inner[$key] = $key_other;
				break;
			};
		};
	};
};

foreach ($shapes as $key => $polygon) {
	if (!isset($inner[$key])) {
?>
                        <Polygon>
                                <outerBoundaryIs>
                                        <LinearRing>
                                                <coordinates>
<?php
	foreach($polygon as $point) {
		echo $point["x"] . "," . $point["y"] . " \n";
	};
?>
                                                </coordinates>
                                        </LinearRing>
                                </outerBoundaryIs>
<?php
	foreach($inner as $inner_key => $inner_poly) {
		if ($inner_poly == $key) {
?>
				<innerBoundaryIs>
					<LinearRing>
						<coordinates>
<?php
        foreach($shapes[$inner_key] as $point) {
                echo $point["x"] . "," . $point["y"] . " \n";
        };
?>
						</coordinates>
					</LinearRing>
				</innerBoundaryIs>
<?php
		};
	}
?>
                        </Polygon>
<?php
	};
};
?>
      </MultiGeometry>
    </Placemark>
  </Document>
</kml>
