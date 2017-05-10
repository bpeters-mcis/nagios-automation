<?php

include('Classes.php');

$thing = new InventoryDB();

$stuff = $thing->GetResolutions('D:\ Drive Space');

print_r($stuff);

?>

