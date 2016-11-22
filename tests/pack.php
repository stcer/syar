<?php

$header = [
    'id' => 1,
    'Version' => 1,
    'MagicNum' => 0x80DFEC60,
    'Reserved' => 1,
    'Provider' => 'json',
    'Token' => 'test',
    'BodyLen' => 123,
    ];


$data = pack('NnNNa32a32N',
    $header['id'],
    $header['Version'],
    $header['MagicNum'],
    $header['Reserved'],
    $header['Provider'],
    $header['Token'],
    $header['BodyLen']
    );

echo strlen($data);
echo "\n";

$array = unpack('Nid/nVersion/NMagicNum/NReserved/a32Provider/a32Token/NBodyLen', $data);
print_r($array);
echo "\n";

if($array['MagicNum'] == 0x80DFEC60){
    echo "ok";
}
echo "\n";