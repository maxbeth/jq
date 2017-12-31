<?php
  session_start();
  if (isset($_GET['reset']) && $_GET['reset'] == "session") {
    session_destroy();
  }
  if (isset($_GET['arp']) && $_GET['arp'] == "enable") {
    if (!isset($_SESSION['arp']) || $_SESSION['arp'] == "disable") {
      $_SESSION['arp'] = "enable_";
    } else {
      $_SESSION['arp'] = "disable_";
    }
  }
  if (isset($_GET['publicip']) && $_GET['publicip'] == "check") {
      unset($_SESSION['publicip']); 
  }
  if (isset($_GET['route']) && $_GET['route'] == "check") {
      unset($_SESSION['route']); 
  }
  if (isset($_GET['dnstrace']) && $_GET['dnstrace'] != "") {
      $_SESSION['dnstrace'] = $_GET['dnstrace']; 
  }
  if (isset($_GET['trace']) && $_GET['trace'] == "check") {
      $_SESSION['trace'] = "check"; 
  }
  if (isset($_GET['ping']) && $_GET['ping'] == "check") {
      unset($_SESSION['ping']); 
  }
  if (isset($_GET['wifi']) && $_GET['wifi'] == "enable") {
    if (!isset($_SESSION['wifi']) || $_SESSION['wifi'] == "disable" ) {
      $_SESSION['wifi'] = "enable_";
    } else {
      $_SESSION['wifi'] = "disable_";
    }
  }
?>
