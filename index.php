<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.5,width=device-width, user-scalable=yes">
  <script src="jquery-3.2.1.min.js"></script>
  <script src="d3.v4.min.js"></script>
  <script>
    function startup() {
      var timeout = window.setTimeout(loadjson,20);
    }
    function loadjson() {
      $.getJSON( "data.php", function( data ) {
        $.each( data, function( key, val ) {
          var element = $("#" + key);
	  if (!element.length){	  
             $( "#container" ).append( "<div class='block' id='" + key + "'>xxx</div>" );
	  }
	  if ( $("#" + key).attr("src") != undefined) {
		  $("#" + key).attr("src", val);
	  } else {
            $("#" + key).html(val); 
          }
        });
      });
      timeout = window.setTimeout(loadjson,900);
    }
    function sendcmd(order) {
      $.get( 'command.php?' + order);
    }
  </script>
  <link rel="stylesheet" href="style.css">
</head>
<body onload="startup()">
<div id="container">
<!--<svg width="320" height="96"></svg> -->
<div class="block" id="control"><a href='#' onclick="sendcmd('reset=session')"><img src='reset.png' title='HTML Session zur&uuml;cksetzen.' align='left'></a><span id="no_wifi" class="disabled"></span><span id="no_geo" class="disabled"></span><span id="no_openvpn" class="disabled"></span><span id="no_curl" class="disabled"></span><span id="no_tor" class="disabled"></span><br/>CPU:<span id="cpu">.</span> / <span id="rand"><img src='loading.gif'></span> ms<br/><span id="disk0" class="diskfree">.</span><br/><span id="disk1" class="diskfree">.</span><br/><span id="disk2" class="diskfree">.</span><br/><span id="disk3" class="diskfree">.</span><br/></div>
<div class="block" id="browser">IP: <?php print $_SERVER['REMOTE_ADDR']; ?> [<?php print strtok($_SERVER['HTTP_ACCEPT_LANGUAGE'], ";"); ?>]<br/><?php print $_SERVER['HTTP_USER_AGENT']; ?></div>
<div class="block" id="arp_enable"><a href='#' onclick="javascript:sendcmd('arp=enable')"><img id="img_arp" src='wait_arp.svg' title='Client Infos zur ARP Tabelle anzeigen.'></a>
<a href='#' 
<?php 
    $out = `/sbin/iwconfig 2>/dev/null | grep "Mode" | wc -l`;
        $_SESSION['wifi'] = intval($out);
    if (intval($out) > 0) { 
	    print ' onclick=\'javascript:sendcmd("wifi=enable")\''; } ?>><img id="img_wifi" src='wait_wifi.svg' title='WLAN Scanning aktivieren'></a><img src='tor.png' title='Noch keine Funktion.' ><img src='openvpn.png' title='Noch keine Funktion.'><br/><div id="arp_count" style="width:64px;float:left">ARP</div><div id='wifi_detail' style='width:64px;float:left'>Offline</div><div id="tor_active" style="width:64px;float:left">...</div><div id="openvpn_active" style="width:64px;float:left">...</div></div>
<div class="block" id="publicip"><a href="#" onclick="javascript:sendcmd('publicip=check')"><img src='globe.png' align='left' title='PublicIP noch mal abfragen.'></a><span id="public_ip">Public-IP:</span></div>
</div>
<!--
<script>

var svg = d3.select("svg"),
    margin = {top: 10, right: 40, bottom: 20, left: 50},
    width = svg.attr("width") - margin.left - margin.right,
    height = svg.attr("height") - margin.top - margin.bottom,
    g = svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");

var x = d3.scaleTime().range([0, width]),
    y = d3.scaleLinear().range([height, 0]);
    z = d3.scaleOrdinal(d3.schemeCategory10);

var line = d3.line()
    .curve(d3.curveBasis)
    .x(function(d) { return x(d.date); })
    .y(function(d) { return y(d.temperature); });

d3.csv("interface.csv.php", type, function(error, data) {
  if (error) throw error;

  var cities = data.columns.slice(1).map(function(id) {
    return {
      id: id,
      values: data.map(function(d) {
        return {date: d.date, temperature: d[id]};
      })
    };
  });

  x.domain(d3.extent(data, function(d) { return d.date; }));

  y.domain([
    d3.min(cities, function(c) { return d3.min(c.values, function(d) { return d.temperature; }); }),
    d3.max(cities, function(c) { return d3.max(c.values, function(d) { return d.temperature; }); })
  ]);

  z.domain(cities.map(function(c) { return c.id; }));

  g.append("g")
      .attr("class", "axis axis--x")
      .attr("transform", "translate(0," + height + ")")
      .call(d3.axisBottom(x));

  g.append("g")
      .attr("class", "axis axis--y")
      .call(d3.axisLeft(y))
    .append("text")
      .attr("transform", "rotate(-90)")
      .attr("y", 6)
      .attr("dy", "0.71em")
      .attr("fill", "#000")
      .text("Bytes");

  var city = g.selectAll(".city")
    .data(cities)
    .enter().append("g")
      .attr("class", "city");

  city.append("path")
      .attr("class", "line")
      .attr("d", function(d) { return line(d.values); })
      .style("stroke", function(d) { return z(d.id); });

  city.append("text")
      .datum(function(d) { return {id: d.id, value: d.values[d.values.length - 1]}; })
      .attr("transform", function(d) { return "translate(" + x(d.value.date) + "," + y(d.value.temperature) + ")"; })
      .attr("x", 3)
      .attr("dy", "0.35em")
      .style("font", "10px sans-serif")
      .text(function(d) { return d.id; });
});

function type(d, _, columns) {
  d.date = new Date(d.date*1);
  for (var i = 1, n = columns.length, c; i < n; ++i) d[c = columns[i]] = +d[c];
  return d;
}

</script>-->
</body>
