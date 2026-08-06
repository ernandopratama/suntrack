<?php
$c = file_get_contents("C:/Users/THINKPAD/Desktop/Project/suntrack/resources/js/pages/CampaignDetail.vue");
$checks = [
    "import AddProductToCampaignForm" => "component import",
    "isAddProductModalOpen" => "modal ref",
    "openAddProductModal" => "open function",
    "Add Product" => "button text",
    "loadCampaignProducts" => "reload after save",
    "pricingForm" => "pricing form",
    "isProductPricingModalOpen" => "pricing modal",
    "submitPricingForm" => "pricing save",
    "Back to Campaigns" => "back button",
    "discountPercent" => "discount %",
    "loadCampaignProducts" => "load function",
];
$pass = true;
foreach ($checks as $str => $label) {
    $found = strpos($c, $str) !== false;
    if (!$found) $pass = false;
    echo ($found ? "OK" : "FAIL") . ": $label\n";
}
echo $pass ? "\nALL GOOD" : "\nISSUES FOUND";
