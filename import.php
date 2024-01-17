<?php
require(__DIR__ . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");

$url = "https://dom.gosuslugi.ru/information-disclosure/api/rest/services/disclosures/org/mkd";

$data = array(
    "orgRootGuid" => "9e96a547-3f89-449e-a3cc-4f8aa873f40e",
    "pageIndex" => 1,
    "elementsPerPage" => 20,
    "terminate" => false
);

$headers = array(
    "Accept: application/json; charset=utf-8",
    "Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
    "Cache-Control: no-cache",
    "Content-Type: application/json;charset=UTF-8",
    "Pragma: no-cache",
    "Request-Guid: e042d0aa-fa7e-42cb-a65d-6255e881525d",
    "Sec-Fetch-Dest: empty",
    "Sec-Fetch-Mode: cors",
    "Sec-Fetch-Site: same-origin",
    "Session-Guid: f1bf0dd1-287a-44a8-bada-bc38278fb9bb",
    "State-Guid: /org-info-mkd"
);

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_REFERER, "https://dom.gosuslugi.ru/");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIESESSION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
}

curl_close($ch);

$jsonData = json_decode($response, true);

$iblockHouse = CIBlock::GetList([], ['TYPE' => 'content', 'SITE_ID' => SITE_ID, "CODE" => 'house'], true)->Fetch();

$arGuilds = array();
$arSelect = array("ID", "IBLOCK_ID", "PROPERTY_ADDRESS_HOUSE_GUID");
$arFilter = array("IBLOCK_ID" => $iblockHouse["ID"]);
$res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
while ($ob = $res->GetNextElement()) {
    $arHouseFields = $ob->GetFields();
    $arGuilds[] = $arHouseFields["PROPERTY_ADDRESS_HOUSE_GUID_VALUE"];
}

foreach ($jsonData["managedHouses"] as $house){
    if(!in_array($house["houseGuid"], $arGuilds)) {
        $addressArray = explode(", ", $house["address"]);
        foreach ($addressArray as $key => $value) {
            $addressArray[$key] = explode(" ", $value);
        }

        $arHouse = array(
            "ADDRESS_HOUSE_GUID" => $house["houseGuid"],
            "MANAGEMENT_CONTRACT_MANAGEMENT_REASON" => $house["managementBase"],
            "MANAGEMENT_CONTRACT_DATE_START" => $house["managedPeriod"]["beginDate"],
            "GIS_GUID" => $house["guid"],
            "ADDRESS_CITY1_FORMAL_NAME" => $addressArray[1][0],
            "ADDRESS_CITY1_SHORT_NAME" => $addressArray[1][1],
            "ADDRESS_STREET_FORMAL_NAME" => $addressArray[2][1],
            "ADDRESS_STREET_SHORT_NAME" => $addressArray[2][0],
            "ADDRESS_HOUSE_NUMBER" => $addressArray[3][1],
            "ADDRESS_BLOCK" => !empty($addressArray[4]) ? $addressArray[4][1] : ""
        );

        $el = new CIBlockElement;
        $arLoadProductArray = array(
            "IBLOCK_ID" => $iblockHouse["ID"],
            "PROPERTY_VALUES" => $arHouse,
            "NAME" => $house["address"],
            "ACTIVE" => "Y",
        );
        $PRODUCT_ID = $el->Add($arLoadProductArray);
    }
}
