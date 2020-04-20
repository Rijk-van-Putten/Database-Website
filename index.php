<?php
    include 'includes/dbh.inc.php';
?>
<!DOCTYPE html >
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <title>Speeltuinen Website</title>
    <!-- Leaflet -->
    <link rel="stylesheet" type="text/css" href="leaflet/leaflet.css" />
    <script src="leaflet/leaflet.js"></script>
    <!-- Own CSS Stylesheet -->
    <link rel="stylesheet" type="text/css" href="css/styles.css" />
    <!-- Library for custom sliders -->
    <link href="noUiSlider/nouislider.min.css" rel="stylesheet">
    <script src="noUiSlider/nouislider.min.js"></script>
  </head>
  <body>
    <header>
    <nav>
        <ul>
            <li class="active" id="logo"><a href="index.php">Speeltuinen</a></li>
            <li class="active"><a href="index.php">Home</a></li>
            <li><a href="add_playground.php">Toevoegen</a></li>
        </ul>
        </nav>
    </header>
    <section id="main">
        <div id="filters">
            <h2>Filters</h2>
            <b>Waardering:</b>
            <br>
            <div id="ratingSlider"></div>
            <span id="ratingSliderValue">1.0</span>
            <br>
            <b>Onderdelen:</b>
            <br>
            <div id="minPartsSlider"></div>
            <span id="minPartsSliderValue">0</span>
            <br>
            <?php
                 include 'includes/parts.inc.php';
                 foreach ($parts as $part)
                 {
                     $inputname = "part".$part[0];
                     echo('<label for="'.$inputname.'">Aantal onderdelen van "'.$part[1].'"</label>');
                     echo('<input type="number" name="'.$inputname.'" min="0" max="10" value="0">');
                     echo("<br>");
                 }
            ?>
            <br>
            <b>Leeftijd:</b>
            <div id="ageSlider"></div>
            <div id="ageSliderValue"></div>
            
            <br>
            <b>Voorzieningen</b>
            <br>
            <label for="alwaysOpen">Altijd geopend</label>
            <input type="checkbox" name="alwaysOpen" onclick="changeAlwaysOpen(this)">
            <br>
            <label for="cateringAvailable">Horeca aanwezig</label>
            <input type="checkbox" name="cateringAvailable" onclick="changeCateringAvailable(this)">
            <script>
                function applyFilters()
                {
                    requestFilteredPlaygroundData(minRatingFilter, minPartsFilter, minAgeFilter, maxAgeFilter, alwaysOpenFilter, cateringAvailableFilter);
                }

                var minRatingFilter = 1;
                var minPartsFilter = 0;
                var minAgeFilter = 0;
                var maxAgeFilter = 18;
                var alwaysOpenFilter = false;
                var cateringAvailableFilter = false;

                function changeAlwaysOpen(value) {
                    alwaysOpenFilter = value.checked;
                    applyFilters();
                }
                function changeCateringAvailable(value) {
                    cateringAvailableFilter = value.checked;
                    applyFilters();
                }

                var ratingSlider = document.getElementById('ratingSlider');
                noUiSlider.create(ratingSlider, {
                    start: 1,
                    step: 0.1,
                    connect: 'lower',
                    range: {
                        'min': 1,
                        'max': 5,
                    }
                });
                ratingSlider.noUiSlider.on('change', applyFilters);
                ratingSlider.noUiSlider.on('update', function(value) {
                    minRatingFilter = value;
                    document.getElementById('ratingSliderValue').innerHTML = "Minimaal " + parseFloat(value).toFixed(1);
                });

                var minPartsSlider = document.getElementById('minPartsSlider');
                noUiSlider.create(minPartsSlider, {
                    start: 0,
                    step: 1,
                    connect: 'lower',
                    range: {
                        'min': 0,
                        'max': 20,
                    }
                });
                minPartsSlider.noUiSlider.on('change', applyFilters);
                minPartsSlider.noUiSlider.on('update', function(value) {
                    minPartsFilter = value;
                    document.getElementById('minPartsSliderValue').innerHTML = "Minimaal " + Math.round(value);
                });

                var ageSlider = document.getElementById('ageSlider');

                noUiSlider.create(ageSlider, {
                    start: [0, 18],
                    connect: true,
                    step: 1,
                    range: {
                        'min': 0,
                        'max': 18,
                    }
                });
                ageSlider.noUiSlider.on('change', applyFilters);
                ageSlider.noUiSlider.on('update', function(value) {
                    minAgeFilter = Math.round(value[0]);
                    maxAgeFilter = Math.round(value[1]);
                    document.getElementById('ageSliderValue').innerHTML = minAgeFilter + " - " + maxAgeFilter;
                });
            </script>
        </div>
        <div id="map">
        </div>
    </section>
    <script>
        function requestPlaygroundData()
        {
            // Get all of the playgrounds from the database with a GET request
            var http = new XMLHttpRequest();
            http.open("GET", "includes/get_playgrounds.inc.php", true);
            http.send();
            http.onload = () => displayMarkers(http.responseText);
        }

        function requestFilteredPlaygroundData(minRating, minParts, minAge, maxAge, alwaysOpen, cateringAvailable)
        {
            var http = new XMLHttpRequest();
            http.open("GET", "includes/get_playgrounds.inc.php?minRating="+minRating+"&minParts="+minParts+"&minAge="+minAge+"&maxAge="+maxAge + "&alwaysOpen="+alwaysOpen+"&cateringAvailable="+cateringAvailable, true);
            http.send();
            http.onload = () => displayMarkers(http.responseText);
        }

        requestPlaygroundData();

        var current_lat;
        var current_lng;
        var map = L.map('map').setView([52.43, 5.42], 8);
        var tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        });
        map.addLayer(tileLayer);
        var layerGroup = L.layerGroup().addTo(map);

        var popup = L.popup();

        var customIcon = L.icon({
            iconUrl: 'img/marker-icon.png',

            iconSize:     [32, 32], // size of the icon
            iconAnchor:   [16, 16], // point of the icon which will correspond to marker's location
            popupAnchor:  [0, 0] // point from which the popup should open relative to the iconAnchor
        });

        function openAddPlaygroundPopup(e) {
            popup.setLatLng(e.latlng)
                .setContent(e.latlng.lat.toFixed(3) + " " + e.latlng.lng.toFixed(3) + "<button onClick='addPlayground(" + e.latlng.lat + ", " + e.latlng.lng + ")'>Voeg speeltuin toe </button>")
                .openOn(map);
        }

        function addPlayground(lat, lng) {
            console.log("Add playground " + lat + " " + lng);
            window.location.replace("add_playground.php?lat="+lat+"&lng="+lng);
        }

        function displayMarkers(data)
        {
            // Decode the JSON
            layerGroup.clearLayers();
            var playgrounds = JSON.parse(data);
            for (var i = 0; i < playgrounds.length; i++)
            {
                var customOptions =
                {
                    'height': '400',
                    'maxWidth': '800',
                    'width': '500',
                }
                var customPopup = L.popup(customOptions)
                .setContent('<div class="popup"><img src="https://picsum.photos/60/60"><b>' + playgrounds[i][1] +
                 '</b><p>Onderdelen: ' + playgrounds[i][6] +'</p><p>Leeftijd: '
                 + playgrounds[i][4] + " t/m " + playgrounds[i][5] +' jaar</p><p>'
                 + parseFloat(playgrounds[i][7]).toFixed(1) + makeStarLayout(parseFloat(playgrounds[i][7])) + ' ('
                 + playgrounds[i][8] 
                 + ' reviews) </p><p><a href="playground.php?id=' + playgrounds[i][0] + '">Meer informatie</a></p></div>');
                
                L.marker([playgrounds[i][2], playgrounds[i][3]], {icon: customIcon}).addTo(layerGroup)
                .bindPopup(customPopup)
                .openPopup();
            }
        }

        function makeStarLayout(value)
        {
            var filledStars = Math.round(value);
            var emptyStars = 5 - filledStars
            var string = "";
            for(var i = 0; i < filledStars; i++)
            {
                string += "&#9733";
            }
            for(var i = 0; i < emptyStars; i++)
            {
                string += "&#9734";
            }             
            return '<span class="rating">' + string +"</span>";
        }

        map.on('click', openAddPlaygroundPopup);        
    </script>
    </body>
</html>