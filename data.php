<?php
$startms = microtime(true);
session_start();
include 'function.php';
$functions = get_functions();
$publicip = get_pubip();
$if = get_if();
$rxtx = get_rxtx();
//$route = get_route();
$route = "";
$proxy = get_proxy();
$arp = get_arp();
$wifi = get_wifi();
$ping = get_ping();
$trace = get_trace();
$netstat = get_netstat();
$cpudisk = get_cpudisk();
$openvpn = get_openvpn();
$tor = get_tor();
$dnslog = get_dnslog();
$e = round((microtime(true) - $startms)*1000,3);
$json = "{" . $functions . $if . $rxtx . $openvpn . $tor . $route . $publicip . $wifi . $cpudisk . $proxy . $dnslog . $arp . $ping . $trace . $netstat . "\"rand\":$e}";
print $json;
?>
