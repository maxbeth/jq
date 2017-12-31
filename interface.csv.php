<?php 
session_start();
print "date,in1,out1\n";
if (isset($_SESSION['interface'])) {
  print $_SESSION['interface'];
} else {
  print (time()*1000) . ",1,2\n";
  print ((time()*1000)+2222) . ",1,2\n";
}

?>
