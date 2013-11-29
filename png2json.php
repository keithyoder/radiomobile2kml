<?php
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

//$tmp = tempnam("/tmp", "png").".png";
$cli = !isset($_FILES['ufile']['tmp_name']);
if ($cli) {
	// if running from command line, get the -f parameter and set the file variables
        $args = getopt("f:");
        $kml = $args["f"] . ".kml";
        $png = $args["f"] . ".png";
        //copy($png, $tmp);
} else {
	// if running from the web form, get the uploaded files
	$kml = $_FILES['ufile']['tmp_name'][0];
	$png = $_FILES['ufile']['tmp_name'][1];
	move_uploaded_file($png, __DIR__ . "/temp.png");
	$png = __DIR__ . "/temp.png";
	header('Content-type: application/json');
	header("Content-Disposition: attachment; filename=\"coverage.geojson\"");
};

$box = getKMLbox($kml);
$y = $box["south"] - $box["north"];
$x = $box["west"] - $box["east"];
$json = json_decode(shell_exec(__DIR__.'/convert.sh "'.$png.'"'));
$imgsize = getimagesize($png);
$y_factor = $y / $imgsize[1];
$x_factor = $x / $imgsize[0];

foreach ($json->features as &$feature) {
	foreach ($feature->geometry->coordinates as &$array) {
		foreach ($array as &$coord) {
			$coord[0] = $box["east"]+(($imgsize[0]-$coord[0]) * $x_factor);
			$coord[1] = $box["north"]+(($imgsize[1]-$coord[1]) * $y_factor);
		};
	};
};

echo json_encode($json);
?>
