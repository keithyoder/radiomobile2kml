radiomobile2kml
===============

Script to convert Radio Mobile coverage maps to kml or geoJSON

This php script uses the following Ubuntu programs:
ImageMagick
Potrace
GDAL version 1.10

```
apt-get install imagemagick
apt-get install potrace
add-apt-repository ppa:ubuntugis/ubuntugis-unstable
apt-get install gdal-bin
```

Currently it accepts two files (the kml and png from RadioMobile) posted via an HTML form.
