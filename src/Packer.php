<?php

namespace syar;

use syar\encoder\EncoderInterface;
use syar\encoder\EncoderJson;
use syar\encoder\EncoderPHP;
use syar\encoder\EncoderMsgpack;

/**
 * Class Packer
 * @package syar
 */
class Packer{
    const HEADER_SIZE            = 90;
    const HEADER_STRUCT         = "Nid/nVersion/NMagicNum/NReserved/a32Provider/a32Token/NBodyLen";
    const HEADER_PACK           = "NnNNa32a32N";

    /**
     * @var array Encoder[]
     */
    protected  static $encoder = [];

    const ENCODE_JSON = 'JSON';
    const ENCODE_MSGPACK = 'MSGPACK';
    const ENCODE_PHP = 'PHP';

    protected static $packagers = [
        self::ENCODE_JSON,
        self::ENCODE_MSGPACK,
        self::ENCODE_PHP,
    ];

    function unpack($data){
        $yar = new Yar();
        if(strlen($data) < 90){
            $yar->response['e'] = "Invalid request";
            return $yar;
        }

        $header = substr($data, 0, 82);
        $header = unpack(self::HEADER_STRUCT, $header);

        if(strlen($data) - 82 != $header['BodyLen']){
            $yar->response['e'] = "Invalid body";
            return $yar;
        }

        $packName = substr($data, 82, 8);
        $yar->packer['packData'] = $packName;

        $packName = $this->getPackName($packName);
        $yar->packer['packName'] = $packName;

        $encoder = $this->getEncoder($packName);
        $request = $encoder->decode(substr($data, 90));

        $yar->header = $header;
        $yar->request = $request;
        $yar->packer['encoder'] = $encoder;
        return $yar;
    }

    protected function getPackName($data){
        foreach(self::$packagers as $packer){
            if(strncasecmp($packer, $data, strlen($packer)) == 0){
                return $packer;
            }
        }
        return self::ENCODE_PHP;
    }

    /**
     * @param Yar $yar
     * @return string
     */
    function pack($yar){
        /** @var EncoderInterface $packer */
        $packer = $yar->packer['encoder'];
        $data = $packer->encode($yar->getResponse());

        $header =& $yar->header;
        $header['BodyLen'] = strlen($data) + 8;

        return  pack(self::HEADER_PACK,
                $header['id'],
                $header['Version'],
                $header['MagicNum'],
                $header['Reserved'],
                $header['Provider'],
                $header['Token'],
                $header['BodyLen']
                )
            . $yar->packer['packData']
            . $data
            ;
    }

    /**
     * @param string $type
     * @return EncoderJson|EncoderMsgpack|EncoderPHP
     */
    protected function getEncoder($type = self::ENCODE_JSON){
        if(isset(self::$encoder[$type])){
            return self::$encoder[$type];
        }

        switch($type){
            case  self::ENCODE_MSGPACK :
                $instance = new EncoderMsgpack();
                break;

            case  self::ENCODE_JSON :
                $instance = new EncoderJson();
                break;

            default :
                $instance = new EncoderPHP();
        }

        self::$encoder[$type] = $instance;
        return $instance;
    }
}
