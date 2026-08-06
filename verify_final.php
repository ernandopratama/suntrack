<?php
$c = file_get_contents("C:/Users/THINKPAD/Desktop/Project/suntrack/resources/js/pages/CampaignDetail.vue");
$checks = [
    "import ModalForm" => "ModalForm import",
    "isProductPricingModalOpen" => "pricing modal ref",
    "loadCampaignProducts" => "load function",
    "discountPercent" => "discount % calc",
];
$pass = true;
foreach ($checks as $str => $label) {
    $found = strpos($c, $str) !== false;
    if (!$found) $pass = false;
    echo ($found ? "OK" : "FAIL") . ": $label\n";
}
echo $pass ? "\nALL PASSED" : "\nSOME FAILED";
