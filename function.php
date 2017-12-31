<?php

function get_asn($target) {
  if (function_exists("geoip_asnum_by_name")) {
    return geoip_asnum_by_name($target);
  } else {
    return "NoGeo";
  }
}
function get_country($target) {
  if (function_exists("geoip_country_code_by_name")) {
    return geoip_country_code_by_name($target);
  } else {
    return "NoGeo";
  }
}

function get_dnslog() {
  if (!file_exists("/var/log/daemon.log")) {
    return "";
  }
  if (isset($_SESSION['dnstrace'])) {
    $ip = $_SESSION['dnstrace'];
    $out = `tail -1000 /var/log/daemon.log | grep query.A | grep $ip | sed 's/.* query.*] //' | sed 's/ from / /' | cut -d ' ' -f 1 | sort -u | tail -12`;
    $next = ", ";
    $ipx = str_replace('.', '_', $ip);
    unset($_SESSION['dnstrace']);
  } else {
    $ip = "";
    $out = `tail -50 /var/log/daemon.log | grep query.A | sed 's/.* query.*]//' | sed 's/ from / /' | tail -6`;
    $next = "<br/>";
    $ipx = "";
  }
  $dns = "";
  foreach( preg_split('/\n/', $out) as $line ) {
    chop($line);
    $dns .= $line . $next;
  }
  if ( strlen($dns) < 10 ) {
    $dns = "Keine DNS-Logs gefunden.";
  }
  return "\"dnslog_$ipx\": \"<span class='dnslog'>$ip: $dns</span>\",";
}
function get_cpudisk() {
  if (!isset($_SESSION['cpu'])) {
    $_SESSION['cpu'] = "";
  } 
  if (!isset($_SESSION['disk'])) {
    $_SESSION['disk'] = "";
  } 
  $cpu = "\"cpu\":\"" . sys_getloadavg()[0] . "\",";
  if ( $_SESSION['cpu'] != $cpu ) {
    $_SESSION['cpu'] = $cpu;
  } else {
    $cpu = "";
  }

  $out = ` df --output=source,target | tr -s ' ' | grep "^/"`;
  $index = 0;
  $disk = "";
  foreach( preg_split('/\n/', $out) as $line ) {
    $mnt = strtok($line, " ");
    $mnt = chop(strtok(" "), "\n");
    if ( $mnt != "") {
      $disk .= "\"disk$index\": \"$mnt:" . round((disk_free_space("$mnt")/1024/1024), 0) . " MB\",";
    }
    $index++;
  }

  
  return $cpu . $disk;
}

function get_proxy() {
  if (isset($_SESSION['proxy'])) {
    return "";
  } 

    $file = @fsockopen("127.0.0.1", 8080);
    $p8080 = "P8080";
    if (!$file) { 
      $p8080 = "";
    } else {
      fclose($file);
    }

    $file = @fsockopen("127.0.0.1", 8118);
    $p8118 = "Privoxy";
    if (!$file) { 
      $p3128 = "";
    } else {
      fclose($file);
    }

    $file = @fsockopen("127.0.0.1", 3128);
    $p3128 = "Squid";
    if (!$file) { 
      $p3128 = "";
    } else {
      fclose($file);
    }

  $wpad = chop(`host wpad | grep has | cut -d " " -f 4`, "\n");
  $proxy = chop(`host proxy | grep has | cut -d " " -f 4`, "\n");
  if ($proxy != "" || $wpad != "") {
    $if = "\"proxy\":\"<img src='squid.png' align='left'>WPAD: $wpad<br/>PROXY: $proxy<br/>MANUAL:<br/>NONE<br>Ports: $p3128 $p8080 $p8118\","; 
  } else {
    $if = "";
  }
  $_SESSION['proxy'] = ".";
  return $if;
}

function get_route() {
  if (isset($_SESSION['route'])) {
    return "";
  } 
  $out = chop(`ip route list | grep default`, "\n");
  $if = "\"route\":\"<a href='#' onclick='sendcmd(\\\"route=check\\\")'><img src='route.png' title='Routing Informationen neu abfragen.' align='left'></a>$out\",";
  $_SESSION['route'] = "";
  return $if;
}

function get_trace() {
  if (!isset($_SESSION['trace'])) {
    $_SESSION['trace'] = "DONE";
    if (!is_file("1.trace")) {
      return "\"traceroute\":\"<a href='#' onclick='sendcmd(\\\"trace=check\\\")'><img src='trace.png' title='Traceroute noch mal ausf&uuml;hren.' align='left'></a>\",";
    } else {
      return show_trace();
    }
  } 
  if ($_SESSION['trace'] == "DONE") {
    return "";
  }
  `/usr/bin/traceroute.db -w 1 -I -n 46.20.46.243 >1.trace`;
  return show_trace();
}

function show_trace() {
  $trace = "";
  $handle = fopen("1.trace", "r");
  if ($handle) {
    $trace = "\"traceroute\":\"<a href='#' onclick='sendcmd(\\\"trace=check\\\")'><img src='trace.png' title='Traceroute noch mal ausf&uuml;hren.' align='left'></a>Target:";
    $array = array();
    while (($line = fgets($handle)) !== false) {
      $line = trim(chop($line));
      $nr = strtok($line, " ");
      if ( intval($nr) > 0){
        $ms = -1;
        $pp = strtok(" ");
        while (strlen($pp) > 0) {
          if (strlen($pp) < 8 && floatval($pp) > 0.001) {
            if ($ms == -1 || $ms > floatval($pp)) {
              $ms = floatval($pp);
            }
          }
          $pp = strtok(" ");
        }
        $array[$nr] = $ms;
      } else {
        strtok(" ");
        strtok(" ");
        $trace .= strtok(" ") . "<br/>";
      }
    }
    $lowms = 99999;
    # Kurze Zeiten nach vorne ziehen.
    for ($i=count($array); $i>=1; $i--) {
      if ($array[$i] > 0) {
        if ($array[$i] > $lowms) {
          $array[$i] = $lowms;
        } else {
          $lowms = $array[$i];
        }
      }
    }
    $lastms = 0;
    $dots = "";
    for ($i=1; $i <= count($array) ; $i++) {
      $time = $array[$i] - $lastms;
      if ($array[$i] <= 0) {
        if ($i == count($array) || $array[$i+1] > 0) {
          $array[$i] = "";
          $typ = "0";
        } else {
          $typ = "";
          if (strlen($dots) < 5 ) {
            $dots .= ".";
          }
        }
      } elseif($time < 1) {
        if ($i < count($array)) {
          if ($array[$i+1] == $array[$i]) {
            if ($i > 1) {
              $array[$i] = $array[$i-1];
            }
            $typ = "";
            $dots .= ".";
          } else {
            $typ = "1";
            $time = round($array[$i] - $lastms, 2);
          }
        } else {
          $typ = "1";
          $time = round($array[$i] - $lastms, 2);
        }
      } elseif($time < 5) {
        $typ = "2";
        $time = round($array[$i] - $lastms, 2);
      } elseif($time < 10) {
        $typ = "3";
        $time = round($array[$i] - $lastms, 1);
      } elseif($time < 100) {
        $typ = "4";
        $time = round($array[$i] - $lastms, 0);
      } else {
        $typ = "5";
        $time = round($array[$i] - $lastms, 0);
      }
      if ($typ != "" && $array[$i] > 0) {
        $lastms = $array[$i];
        //$array[$i] = $time;
        $array[$i] = round($array[$i], 1);
      }
      if ($typ != "") {
        $trace .= "<div class='traceroute$typ'>&#9632;" . $array[$i] . "$dots</div>";
        $dots = "";
      }
    }
    fclose($handle);
    $trace .= "\",";
  } 
  $_SESSION['trace'] = "DONE";
  return $trace;
}

function get_pubip() {
  if (!isset($_SESSION['publicip'])) {
    $_SESSION['publicip1'] = "?";
    $_SESSION['publicip2'] = "?";
    $_SESSION['publicip3'] = "?";
    $_SESSION['publicip'] = "1";
    $dns1 = `dig TXT +retry=1 +tries=1 +short o-o.myaddr.l.google.com @ns1.google.com | head -1 | tr -d '"' >dns1.txt &`;
    $dns2 = `dig TXT +retry=1 +tries=1 +short o-o.myaddr.l.google.com >dns2.txt &`;
  } elseif ($_SESSION['publicip'] == "1") {
    $_SESSION['publicip'] = "2";
    $_SESSION['publicip1'] = "2";
    $dns1 = file_get_contents("dns1.txt");
    $dns1 = trim(chop($dns1,"\n"));
    if (strpos($dns1, "timed out") > 0) {
      $dns1 = "TimeOut";
      $countrydns1 = "Unk.";
    } elseif (strlen($dns1) >= 7) {
      $countrydns1 = get_country($dns1);
    } else {
      $countrydns1 = "Unk.";
    }
    $_SESSION['publicip1'] = $dns1 ."[$countrydns1]";
    $dns = file("dns2.txt",  FILE_IGNORE_NEW_LINES );
   
    if (sizeof($dns) == 2) {
      $dns2 = $dns[1];
    } elseif (sizeof($dns) == 1) {
      $dns2 = $dns[0];
    } else {
      $dns2 = "???" . sizeof($dns);
    }
    $dns2 = str_replace('"', '', $dns2);
    if (strpos($dns2, "client-subnet ") > 0) {
      $dns2 = strtok($dns2, " ");
      $dns2 = strtok(" ");
      $dns2 = strtok($dns2, "/");
    }
    if (strpos($dns2, "timed out") > 0) {
      $dns2 = "TimeOut";
      $countrydns1 = "Unk.";
    } elseif (strlen($dns2) >= 7) {
      $countrydns2 = get_country($dns2);
    } else {
      $countrydns2 = "Unk.";
    }
    $_SESSION['publicip2'] = $dns2 ."[$countrydns2]";
  } elseif ($_SESSION['publicip'] == "2") {
    $_SESSION['publicip'] = "X";
    $url = 'http://dynupdate.no-ip.com/ip.php';

    if (function_exists("curl_init")) {
      $proxy = '192.168.1.2:3128';
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL,$url);
      if ($proxy != "") {
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
      }
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, 1); //timeout in seconds
      $pip = curl_exec($ch);
      curl_close($ch);
      if (strlen($pip) > 17) { $pip = "unknown"; }
    } else {
      $pip = 'No CURL';
    }
    $_SESSION['publicip3'] = $pip;
  } else {
    return "";
  }
  $pip = "\"public_ip\": \"Public-IP:<br/>dDNS: " . $_SESSION['publicip1'] . "<br/>DNS:" . $_SESSION['publicip2'] . "<br/>TCP: " . $_SESSION['publicip3'] . "\",";
  return $pip;
}

function get_ping() {
  if (isset($_SESSION['ping'])) {
    $max = $_SESSION['ping'];
  } else {
    $max = 11; 
  }
  $max--;
  if ($max < 1) {
    $max = 10;
  }
  $_SESSION['ping'] = $max;
  if ($max != 10) {
    return "";
  }
  $p1 = "";
  $handle = fopen("ping.txt", "r");
  if ($handle) {
    $nr = 0;
    while (($line = fgets($handle)) !== false) {
        $p1 .= check_ping(chop($line, "\n"), $nr++);
    }
    fclose($handle);
  }
  return $p1;
}

function check_ping($target, $nr) {
  if (!isset($_SESSION['ping_' . $target])) {
    $_SESSION['ping_' . $target] = "999|0|0|0";
  }
  $dtx = $_SESSION['ping_' . $target];
  $min = strtok($dtx, "|");
  $max = strtok("|");
  $anz = strtok("|");
  $timeout = strtok("|");
  $anz++;

  $geo = get_country($target) . "</br/>" . get_asn($target);
  $if = "\"ping_$nr\":\"<a href='#' onclick='sendcmd(\\\"ping=check\\\")'><img src='ping.png' title='Ping noch mal ausf&uuml;hren.' align='left'></a>$target $geo<br/>";
  $out = `ping -c 1 -w 1 -W 1 $target | grep ttl | head -1`;
  if (substr($out, 0, 8) == "64 bytes") {
    strtok($out, " ");
    strtok(" ");
    strtok(" ");
    strtok(" ");
    strtok(" ");
    $ttl = strtok(" ");
    $time = strtok(" ");
    $t = strtok($time, "=");
    $t = strtok("=");
    if ($t < $min) { $min = $t; }
    if ($t > $max) { $max = $t; }
    $if .= "$ttl $time";
  } else {
    $if .= "TIMEOUT"; 
    $timeout++;
  }
  $if .= "<br/>$min-$max ms #$timeout/$anz";
  
  $_SESSION['ping_' . $target] = "$min|$max|$anz|$timeout";
  return $if . "\",";
}

function get_if() {
  if (isset($_SESSION['if'])) {
    return "";
  }
  $out = `ip addr show`;

  $if = "";
  $mix = "";
  $miy = "<br/>";
  $nr = "";
  $ifname = "";
  foreach( preg_split('/\n/', $out) as $line ) {
    if ($line == "") {
      continue;
    }
    if (substr($line, 0,1) != " " ) {
      if ( $mix != "" ) {
        $if .= $mix . $miy . "<br/><span id='if_" . $nr . "_rxtx'>.</span>\",";
        $mix = "";
        $miy = "<br/>";
      }
      $nr = intval(strtok($line, " "));
      $ifname = strtok(" ");
      if ( $ifname != "lo:") {
        $mix .= "\"if_$nr\":\"<img src='netcard.png' title='Noch keine Funktion.' align='left'>$ifname<br/>";
      }
    } else  {
      $val = strtok($line, " ");
      if ( $val == "link/ether" ) {
        if ( $ifname != "lo:") {
          $ifx = strtok(" ");
          $mix .= $ifx;
        }
      } elseif ( $val == "inet" ) {
        if ( $ifname != "lo:") {
          $ipaddr = strtok(" ");
          $miy = "<br/>$ipaddr";
        }
      }
    }
  }
  if ( $mix != "" ) {
    $if .= $mix . $miy . "<br/><span id='if_" . $nr . "_rxtx'>.</span>\",";
  }
  $_SESSION['if'] = $if;
  return $if;
}

function get_rxtx() {
  # Übertragung anzeigen
  $out = `ip -s link show up`;
  $RX = "";
  $TX = "";
  $if = "";
  foreach( preg_split('/\n/', $out) as $line ) {
    if ($line == "") {
      continue;
    }
    if ($RX == "#") {
      $RX = strtok($line, " ");
    } elseif ($TX == "#") {
      $TX = strtok($line, " ");
      if ($RX > 0 && $TX > 0) {
        $XX = round($RX / $TX, 1);
      } else {
        $XX = "?";
      }
      if (isset($_SESSION["interface_time_$nr"])) {
        $dtime = microtime(true) - $_SESSION["interface_time_$nr"];
        $drx = ($RX - $_SESSION["interface_rx_$nr"])/$dtime;
        $dtx = ($TX - $_SESSION["interface_tx_$nr"])/$dtime;
        # TODO: Die Anzeige sollte konfigurierbar sein
        # -6.9 damit unter <1Kb nichts angezeigt wird.
	# Alle 60 Pixel hat sich der Wert verzehnfacht.
        $rwidth = round((log($drx)-6.9)*26,0);
        $twidth = round((log($dtx)-6.9)*26,0);
        if ($rwidth < 1) { $rwidth = 1; }
        if ($twidth < 1) { $twidth = 1; }
        if ( $rwidth > 300 ) { $rwidth = 300; }
        if ( $twidth > 300 ) { $twidth = 300; }
        $drx = $drx / 1024;
        $dtx = $dtx / 1024;
        if ($drx > 1024 or $dtx > 1024) {
          $drx = $drx / 1024;
          $dtx = $dtx / 1024;
          if ($drx > 1024 or $dtx > 1024) {
            $drx = $drx / 1024;
            $dtx = $dtx / 1024;
            $xb = "GB";
          } else {
            $xb = "MB";
          }
        } else {
          $xb = "KB";
        }
        $drx = round($drx, 2);
        $dtx = round($dtx, 2);
        $if .= "\"if_" . $nr . "_rxtx\":\"$xb &darr;$drx/&uarr;$dtx ($XX)<br><img src='down.png' width=$rwidth height=4><br/><img src='scale.png' title='Log Skalierung bis 10kB, 100kB, 1MB, 10MB, 100MB'><br/><img src='up.png' width=$twidth height=4>\",";
      }
      $_SESSION["interface_time_$nr"] = microtime(true);
      $_SESSION["interface_rx_$nr"] = $RX;
      $_SESSION["interface_tx_$nr"] = $TX;
      $RX = "";
      $TX = "";
    } elseif (substr($line, 0,1) != " " ) {
      $nr = intval(strtok($line, " "));
      $ifname = strtok(" ");
    } else  {
      $val = strtok($line, " ");
      if ( $ifname != "lo:") {
        if ( $val == "RX:" ) {
          if ( $ifname != "lo:") {
            $RX = "#";
          }
        } elseif ( $val == "TX:" ) {
          if ( $ifname != "lo:") {
            $TX = "#";
          }
        }
      }
    }
  }
  return $if;
}

function get_arp() {
  $if = "";
  if (!isset($_SESSION['arp']) || $_SESSION['arp'] == "disable") {
    $out = `ip neighbou`;
    $line = strtok($out, "\n");
    $nr = 0;
    while ($line !== false) {
      if (strpos($line, "REACH") > 0 || strpos($line, "STALE") > 0) {
        $nr++;
      }
      $line = strtok("\n");
    }
    return "\"arp_count\":\"ARP $nr\",";
  }
  if ($_SESSION['arp'] == "disable_") {
    $_SESSION['arp'] = "disable";
    return "\"img_arp\":\"wait_arp.svg\",";
  }
  if ($_SESSION['arp'] == "enable_") {
    $_SESSION['arp'] = "enable";
    $if = "\"img_arp\":\"scan_arp.svg\",";
  }
  $out = `ip neighbou`;

  $if .= "\"arp_count\":\"SCAN\",";
  $mix = "";
  foreach( preg_split('/\n/', $out) as $line ) {
    if ($line == "") {
      continue;
    }
    $ip = strtok($line, " ");
    strtok(" ");
    $dev = strtok(" ");
    $typ = strtok(" ");
    if ($typ == "lladdr" ) {
      $mac = strtok(" ");
      $typ = strtok(" ");
      $id = str_replace(".", "", $ip);
      if ( $ip == $_SERVER['REMOTE_ADDR'] ) {
        $ownip = "ownip";
      } else {
        $ownip = "";
      }
      $mac6 = substr($mac,0,2) . substr($mac,3,2) . substr($mac,6,2);
      $element = "\"arp$id\":\"<div class='$ownip'><img src='client.png' title='Noch keine Funktion.' align='left'>$dev - $mac<br/>$ip";
      if (!isset($_SESSION['arp' . $id]) || $_SESSION['arp' . $id] != $element) {
        $vendor = chop(`grep -i $mac6 oui.txt | cut -f 2`, "\n");
        if ($vendor == "") {
          $vendor = "";
        }
        $hn = gethostbyaddr($ip);
	if ( $ip == $hn) { $hn = ""; }
        $if .= $element . "<br/><span class='arp_vendor'>$vendor</span></br>$hn<br/><a target='new' href='http://$ip/'> HTTP </a> <a target='new' href='https://$ip/'> HTTPS </a> <a href='#' onclick='sendcmd(\\\"dnstrace=$ip\\\")'> DNS </a></div>\",";
        $_SESSION['arp' . $id] = $element;
      }
    }
  }
  $_SESSION['arp'] = $if;
  
  return $if;
}
function get_tor() {
//  if (!isset($_SESSION['tor'])) {
//      $file = @fsockopen("127.0.0.1", 9050);
//      $status = 0;
//    if (!$file) { 
//      $status = -1;
//    } else {
//      fclose($file);
//    }
//    if ($status == 0) {
//      $if = "\"tor\":\"<img src='tor.png' title='Noch keine Funktion.' align='left'>FOUND\",";
//    } else {
//      $if = "\"no_tor\":\"TOR\",";
//    }
//    $_SESSION['tor'] = ".";
//    return $if;
//  } elseif ($_SESSION['tor'] == "." ) {
//    return "";
//  }
  return "";
}
function get_openvpn() {
//  if (!isset($_SESSION['openvpn'])) {
//    if (is_dir("/etc/openvpn")) {
//      $if = "\"openvpn\":\"<img src='openvpn.png' title='Noch keine Funktion.' align='left'>FOUND\",";
//    } else {
//      $if = "\"no_openvpn\":\"OpenVPN\",";
//    }
//    $_SESSION['openvpn'] = ".";
//    return $if;
//  } elseif ($_SESSION['openvpn'] == "." ) {
//    return "";
//  }
  return "";
}

function get_functions() {
  if (isset($_SESSION['functions'])) {
    return "";
  }
  $if = "";
  if (!function_exists("geoip_asnum_by_name")) {
    $if .= "\"no_geo\":\"GEO\",";
  }
  $_SESSION['functions'] = "";
  return $if;
}

function get_netstat() {
  if (isset($_SESSION['netstat'])) {
    return "";
  }
  $_SESSION['netstat'] = ".";
  $out = `netstat -tn | grep ESTA | grep -v 127.0.0.1 | tr -s ' ' | cut -d " " -f 5 | grep -v ::1 | cut -d ":" -f 1 | sort -u | head -5`;
  $if = "";
  foreach( preg_split('/\n/', $out) as $line ) {
    $if .= trim($line) . "<br/>";
  }
  return "\"netstat\":\"$if\",";
}

function get_wifi() {
  if (!isset($_SESSION['wifi']) || $_SESSION['wifi'] == "disable") {
    return "";
  }
  $if = "";
  if ($_SESSION['wifi'] == "disable_") {
    $_SESSION['wifi'] = "disable";
    return "\"img_wifi\":\"wait_wifi.svg\",\"wifi_detail\":\"Inactive\",";
  }
  if ($_SESSION['wifi'] == "enable_") {
    $_SESSION['wifi'] = "enable";
    return "\"img_wifi\":\"scan_wifi.svg\",\"wifi_detail\":\"Scanning\",";
  }

  $out = `/sbin/iwlist scan 2>/dev/null`;
  $mix = "";
  foreach( preg_split('/\n/', $out) as $line ) {
    $line = trim($line);
    if ($line == "") {
      continue;
    }
    if (substr($line, 0,4) == "Cell" ) {
      if ( $mix != "" ) {
        $if .= "\",";
        $mix = "";
      }
      strtok($line, " ");
      strtok(" ");
      strtok(" ");
      strtok(" ");
      $ifname = strtok(" ");
      $id = str_replace(":", "", $ifname);
      $id6 = substr($id, 0, 6);
      $vendor = chop(`grep -i $id6 oui.txt | cut -f 2`, "\n");
      $if = $if . "\"wifi_$id\":\"<img src='wifi_ap.png' title='Nich keine Funktion.' align='left'>$ifname<br/>$vendor";
      $mix = ".";
      $ecolor = "green";
    } else if (substr($line, 0,8) == "Quality=" ) {
      $quality = strtok(substr($line,8), " ");
      strtok(" ");
      $level = substr(strtok(" "),6);
      if ( $level < -90)  { $color = "red"; $anz = 2; $bullets = "&#9632;&#9632;"; }
      elseif ( $level < -70 ) { $color = "orange"; $anz = 3; $bullets = "&#9632;&#9632;&#9632;"; }
      elseif ( $level < -50 ) { $color = "yellow"; $anz = 4; $bullets = "&#9632;&#9632;&#9632;&#9632;"; }
      elseif ( $level < -30 ) { $color = "green"; $anz = 5; $bullets = "&#9632;&#9632;&#9632;&#9632;&#9632;";}
      else { $color = "white"; $anz = 6; $bullets = "&#9632;&#9632;&#9632;&#9632;&#9632;&#9632;";}
      $aktquali = strtok($quality, "/");
      $maxquali = strtok(" ");
      $quali = $aktquali / $maxquali;
      if ( $quali < 0.2 ) {
        $quali = "&#9675;";
      } elseif ( $quali < 0.4 ) {
        $quali = "&#9684;";
      } elseif ( $quali < 0.6 ) {
        $quali = "&#9681;";
      } elseif ( $quali < 0.8 ) {
        $quali = "&#9685;";
      } else {
        $quali = "&#9679;";
      }
      $if .= "<br/>$quali $quality <span style='color:$color'>$bullets</span>";
    } else if (substr($line, 0,17) == "Encryption key:on" ) {
      $ecolor = "red";
    } else if (substr($line, 0,5) == "ESSID" ) {
      $if = $if . "<span style='color:$ecolor'>" . htmlspecialchars(substr($line, 6)) . "</span>";
    } else  {
    }
  }
  if ( $mix != "" ) {
    $if .= "\",";
  }
  $_SESSION['wifi'] = $if;
  
  return $if;
}

?>
