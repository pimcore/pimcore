<?php

// update .htaccess to the latest version if not changed manually

$htaccessFile = PIMCORE_DOCUMENT_ROOT . "/.htaccess";
$currentFileMd5 = md5_file($htaccessFile);

// old standard md5's // all from git history
$defaultMd5s = [
    "19b5c81f05805ee30f694db894d3f420",
    "0ea7e1c0cdede8235d8d76bc100fb212",
    "4b8f081f3f4705e2cb519edfaaaadb1b",
    "93c9ae0d2211a46db278bdfe5011fff2",
    "0bdde18a3484599a9b9ae4a42ee6d1f8",
    "215a941f43f4057c11e71da46906e64b",
    "d23dbe518c7516942a873c1e468da3ac",
    "a3db8d1e655e0e63a21fbcad48ced1c3",
    "1c656a396801827236a3af3c63638070",
    "64fcf5d7266000f01ed7c64e4793b407",
    "bdf1652ff1c76d0251d8f4afe73d1e0f",
    "e769830d7e4f2d7b79481237052df0ec",
    "cc05bacbc951d4afc1224ec3a4191bbd",
    "6375537320664103ff1d9b1f22852b63",
    "d0597f11ef5a6d1e999d772c9f4b4341",
    "254fc7e808732c5bd3008b92ebce8cae",
    "42c4974dff95764f3cf0e5b7a6e8df0d",
    "18fede900eb01b921a522b7e80a61bfd",
    "2716d910eef4ff1e887b4fc8bc865f7d",
    "e173dc2b9c278baa8e6bb3fca4676e23",
    "1dc23f9666512f20743e718b7e54fed9",
    "5f8d678fdc15a4552d8d3c99d657b844",
    "5c5a899c5c5b190300764b75f2492aef",
    "9c22252dc36769323ab15c036c055afa",
    "d537868f2efaa31d6048478086d2e13d",
    "7ed41d6a7e9118a8479f01603879080b",
    "dc982ed9a52360e506756f1a83ed7f23",
    "d41d8cd98f00b204e9800998ecf8427e",
    "dc982ed9a52360e506756f1a83ed7f23"
];

if(in_array($currentFileMd5, $defaultMd5s)) {
    // this instance is using a default .htaccess, so we can update it
    $oldData = file_get_contents($htaccessFile);
    $data = \Pimcore\Tool::getHttpData("https://raw.githubusercontent.com/pimcore/pimcore/efe2c38e018fa9d341f25ca1f43f07c7c0926e2f/.htaccess");
    if(strpos($data, "RewriteEngine On")) { // check for a certain string in the content, that has to be in the file
        file_put_contents($htaccessFile, $data);

        if(md5_file($htaccessFile) != "fc937743972846cae8cfffa772e8811b") {
            // something went wrong, write back the old contents
            file_put_contents($htaccessFile, $oldData);
        }
    }
}

