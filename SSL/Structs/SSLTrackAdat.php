<?php

/* Autogenerated by XoupCompiler */

class XOUPSSLTrackAdatUnpacker extends Unpacker
{

    private $out_buffer = '';
    private $context = array();
    private $data = array(
        "EOS" => "",
        "COLON" => ": ",
        "EUNKNOWN" => "Unknown field ",
    );

    public function unpack($bin)
    {
        $binlen = strlen($bin);
        $acc = 0;
        $ptr = 0;
        $this->_main($bin, $binlen, $acc, $ptr);
        return $this->context;
    }

    public function flushBuffer()
    {
        L::level(L::INFO) &&
            L::log(L::INFO, __CLASS__, $this->out_buffer,
                array());
    }

    private function _main($bin, $binlen, &$acc, &$ptr)
    {
        /* field. */
        if(!$this->_field($bin, $binlen, $acc, $ptr)) return false;
        return true;
    }

    private function _ascii($bin, $binlen, &$acc, &$ptr)
    {
        /* r1l>i_ */
        if($ptr >= $binlen) return false;
        $datum = substr($bin, $ptr, 4);
        $ptr += 4;
        $acc = (int) $this->unpacksint($datum);
        /* r_b>r_ */
        if($ptr >= $binlen) return false;
        $datum = substr($bin, $ptr, $acc);
        $ptr += $acc;
        $acc = $datum;
        return true;
    }

    private function _string($bin, $binlen, &$acc, &$ptr)
    {
        /* r1l>i_ */
        if($ptr >= $binlen) return false;
        $datum = substr($bin, $ptr, 4);
        $ptr += 4;
        $acc = (int) $this->unpacksint($datum);
        /* r_b>s_ */
        if($ptr >= $binlen) return false;
        $datum = substr($bin, $ptr, $acc);
        $ptr += $acc;
        $acc = (string) $this->unpackstr($datum);
        return true;
    }

    private function _int($bin, $binlen, &$acc, &$ptr)
    {
        /* r1l>i_ */
        if($ptr >= $binlen) return false;
        $datum = substr($bin, $ptr, 4);
        $ptr += 4;
        $acc = (int) $this->unpacksint($datum);
        /* r_b>i_ */
        if($ptr >= $binlen) return false;
        $datum = substr($bin, $ptr, $acc);
        $ptr += $acc;
        $acc = (int) $this->unpacksint($datum);
        return true;
    }

    private function _hex($bin, $binlen, &$acc, &$ptr)
    {
        /* r1l>i_ */
        if($ptr >= $binlen) return false;
        $datum = substr($bin, $ptr, 4);
        $ptr += 4;
        $acc = (int) $this->unpacksint($datum);
        /* r_b>h_ */
        if($ptr >= $binlen) return false;
        $datum = substr($bin, $ptr, $acc);
        $ptr += $acc;
        $hd = new Hexdumper();
        $acc = trim($hd->hexdump($datum));
        return true;
    }

    private function _float($bin, $binlen, &$acc, &$ptr)
    {
        /* r1l>i_ */
        if($ptr >= $binlen) return false;
        $datum = substr($bin, $ptr, 4);
        $ptr += 4;
        $acc = (int) $this->unpacksint($datum);
        /* r_b>f_ */
        if($ptr >= $binlen) return false;
        $datum = substr($bin, $ptr, $acc);
        $ptr += $acc;
        $acc = (float) $this->unpackfloat($datum);
        return true;
    }

    private function _timestamp($bin, $binlen, &$acc, &$ptr)
    {
        /* int. */
        if(!$this->_int($bin, $binlen, $acc, $ptr)) return false;
        return true;
    }

    private function _field($bin, $binlen, &$acc, &$ptr)
    {
        /* r1l>i_ */
        if($ptr >= $binlen) return false;
        $datum = substr($bin, $ptr, 4);
        $ptr += 4;
        $acc = (int) $this->unpacksint($datum);
        /* field_. */
        if(!$this->lookup('field' . $acc . '', $bin, $binlen, $acc, $ptr)) return false;
        /* field. */
        if(!$this->_field($bin, $binlen, $acc, $ptr)) return false;
        return true;
    }

    private function _trap($bin, $binlen, &$acc, &$ptr)
    {
        /* lEUNKNOWN>r! */
        $datum = $this->data['EUNKNOWN'];
        if($datum === '') $this->flushBuffer();
        else $this->out_buffer .= $datum;
        /* c>r! */
        $datum = $acc;
        if($datum === '') $this->flushBuffer();
        else $this->out_buffer .= $datum;
        /* lCOLON>r! */
        $datum = $this->data['COLON'];
        if($datum === '') $this->flushBuffer();
        else $this->out_buffer .= $datum;
        /* hex. */
        if(!$this->_hex($bin, $binlen, $acc, $ptr)) return false;
        /* c>r! */
        $datum = $acc;
        if($datum === '') $this->flushBuffer();
        else $this->out_buffer .= $datum;
        /* c>rUNKNOWN */
        $datum = $acc;
        $this->context['UNKNOWN'] = $datum;
        /* lEOS>r! */
        $datum = $this->data['EOS'];
        if($datum === '') $this->flushBuffer();
        else $this->out_buffer .= $datum;
        return true;
    }

    private function _field1($bin, $binlen, &$acc, &$ptr)
    {
        /* int. */
        if(!$this->_int($bin, $binlen, $acc, $ptr)) return false;
        /* c>rrow */
        $datum = $acc;
        $this->context['row'] = $datum;
        return true;
    }

    private function _field2($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rfullpath */
        $datum = $acc;
        $this->context['fullpath'] = $datum;
        return true;
    }

    private function _field3($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rlocation */
        $datum = $acc;
        $this->context['location'] = $datum;
        return true;
    }

    private function _field4($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rfilename */
        $datum = $acc;
        $this->context['filename'] = $datum;
        return true;
    }

    private function _field6($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rtitle */
        $datum = $acc;
        $this->context['title'] = $datum;
        return true;
    }

    private function _field7($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rartist */
        $datum = $acc;
        $this->context['artist'] = $datum;
        return true;
    }

    private function _field8($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>ralbum */
        $datum = $acc;
        $this->context['album'] = $datum;
        return true;
    }

    private function _field9($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rgenre */
        $datum = $acc;
        $this->context['genre'] = $datum;
        return true;
    }

    private function _field10($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rlength */
        $datum = $acc;
        $this->context['length'] = $datum;
        return true;
    }

    private function _field11($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rfilesize */
        $datum = $acc;
        $this->context['filesize'] = $datum;
        return true;
    }

    private function _field13($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rbitrate */
        $datum = $acc;
        $this->context['bitrate'] = $datum;
        return true;
    }

    private function _field14($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rfrequency */
        $datum = $acc;
        $this->context['frequency'] = $datum;
        return true;
    }

    private function _field15($bin, $binlen, &$acc, &$ptr)
    {
        /* int. */
        if(!$this->_int($bin, $binlen, $acc, $ptr)) return false;
        /* c>rbpm */
        $datum = $acc;
        $this->context['bpm'] = $datum;
        return true;
    }

    private function _field17($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rcomments */
        $datum = $acc;
        $this->context['comments'] = $datum;
        return true;
    }

    private function _field18($bin, $binlen, &$acc, &$ptr)
    {
        /* ascii. */
        if(!$this->_ascii($bin, $binlen, $acc, $ptr)) return false;
        /* c>rlang */
        $datum = $acc;
        $this->context['lang'] = $datum;
        return true;
    }

    private function _field19($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rgrouping */
        $datum = $acc;
        $this->context['grouping'] = $datum;
        return true;
    }

    private function _field20($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rremixer */
        $datum = $acc;
        $this->context['remixer'] = $datum;
        return true;
    }

    private function _field21($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rlabel */
        $datum = $acc;
        $this->context['label'] = $datum;
        return true;
    }

    private function _field22($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rcomposer */
        $datum = $acc;
        $this->context['composer'] = $datum;
        return true;
    }

    private function _field23($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>ryear */
        $datum = $acc;
        $this->context['year'] = $datum;
        return true;
    }

    private function _field28($bin, $binlen, &$acc, &$ptr)
    {
        /* timestamp. */
        if(!$this->_timestamp($bin, $binlen, $acc, $ptr)) return false;
        /* c>rstarttime */
        $datum = $acc;
        $this->context['starttime'] = $datum;
        return true;
    }

    private function _field29($bin, $binlen, &$acc, &$ptr)
    {
        /* timestamp. */
        if(!$this->_timestamp($bin, $binlen, $acc, $ptr)) return false;
        /* c>rendtime */
        $datum = $acc;
        $this->context['endtime'] = $datum;
        return true;
    }

    private function _field31($bin, $binlen, &$acc, &$ptr)
    {
        /* int. */
        if(!$this->_int($bin, $binlen, $acc, $ptr)) return false;
        /* c>rdeck */
        $datum = $acc;
        $this->context['deck'] = $datum;
        return true;
    }

    private function _field45($bin, $binlen, &$acc, &$ptr)
    {
        /* int. */
        if(!$this->_int($bin, $binlen, $acc, $ptr)) return false;
        /* c>rplaytime */
        $datum = $acc;
        $this->context['playtime'] = $datum;
        return true;
    }

    private function _field48($bin, $binlen, &$acc, &$ptr)
    {
        /* int. */
        if(!$this->_int($bin, $binlen, $acc, $ptr)) return false;
        /* c>rsessionId */
        $datum = $acc;
        $this->context['sessionId'] = $datum;
        return true;
    }

    private function _field50($bin, $binlen, &$acc, &$ptr)
    {
        /* int. */
        if(!$this->_int($bin, $binlen, $acc, $ptr)) return false;
        /* c>rplayed */
        $datum = $acc;
        $this->context['played'] = $datum;
        return true;
    }

    private function _field51($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rkey */
        $datum = $acc;
        $this->context['key'] = $datum;
        return true;
    }

    private function _field52($bin, $binlen, &$acc, &$ptr)
    {
        /* int. */
        if(!$this->_int($bin, $binlen, $acc, $ptr)) return false;
        /* c>radded */
        $datum = $acc;
        $this->context['added'] = $datum;
        return true;
    }

    private function _field53($bin, $binlen, &$acc, &$ptr)
    {
        /* timestamp. */
        if(!$this->_timestamp($bin, $binlen, $acc, $ptr)) return false;
        /* c>rupdatedAt */
        $datum = $acc;
        $this->context['updatedAt'] = $datum;
        return true;
    }

    private function _field64($bin, $binlen, &$acc, &$ptr)
    {
        /* string. */
        if(!$this->_string($bin, $binlen, $acc, $ptr)) return false;
        /* c>rcommentname */
        $datum = $acc;
        $this->context['commentname'] = $datum;
        return true;
    }

    private function _field16($bin, $binlen, &$acc, &$ptr)
    {
        /* ascii. */
        if(!$this->_ascii($bin, $binlen, $acc, $ptr)) return false;
        return true;
    }

    private function _field33($bin, $binlen, &$acc, &$ptr)
    {
        /* ascii. */
        if(!$this->_ascii($bin, $binlen, $acc, $ptr)) return false;
        return true;
    }

    private function _field39($bin, $binlen, &$acc, &$ptr)
    {
        /* int. */
        if(!$this->_int($bin, $binlen, $acc, $ptr)) return false;
        return true;
    }

    private function lookup($sub, $bin, $binlen, &$acc, &$ptr)
    {
        switch($sub) {
            case 'main': return $this->_main($bin, $binlen, $acc, $ptr);
            case 'ascii': return $this->_ascii($bin, $binlen, $acc, $ptr);
            case 'string': return $this->_string($bin, $binlen, $acc, $ptr);
            case 'int': return $this->_int($bin, $binlen, $acc, $ptr);
            case 'hex': return $this->_hex($bin, $binlen, $acc, $ptr);
            case 'float': return $this->_float($bin, $binlen, $acc, $ptr);
            case 'timestamp': return $this->_timestamp($bin, $binlen, $acc, $ptr);
            case 'field': return $this->_field($bin, $binlen, $acc, $ptr);
            case 'trap': return $this->_trap($bin, $binlen, $acc, $ptr);
            case 'field1': return $this->_field1($bin, $binlen, $acc, $ptr);
            case 'field2': return $this->_field2($bin, $binlen, $acc, $ptr);
            case 'field3': return $this->_field3($bin, $binlen, $acc, $ptr);
            case 'field4': return $this->_field4($bin, $binlen, $acc, $ptr);
            case 'field6': return $this->_field6($bin, $binlen, $acc, $ptr);
            case 'field7': return $this->_field7($bin, $binlen, $acc, $ptr);
            case 'field8': return $this->_field8($bin, $binlen, $acc, $ptr);
            case 'field9': return $this->_field9($bin, $binlen, $acc, $ptr);
            case 'field10': return $this->_field10($bin, $binlen, $acc, $ptr);
            case 'field11': return $this->_field11($bin, $binlen, $acc, $ptr);
            case 'field13': return $this->_field13($bin, $binlen, $acc, $ptr);
            case 'field14': return $this->_field14($bin, $binlen, $acc, $ptr);
            case 'field15': return $this->_field15($bin, $binlen, $acc, $ptr);
            case 'field17': return $this->_field17($bin, $binlen, $acc, $ptr);
            case 'field18': return $this->_field18($bin, $binlen, $acc, $ptr);
            case 'field19': return $this->_field19($bin, $binlen, $acc, $ptr);
            case 'field20': return $this->_field20($bin, $binlen, $acc, $ptr);
            case 'field21': return $this->_field21($bin, $binlen, $acc, $ptr);
            case 'field22': return $this->_field22($bin, $binlen, $acc, $ptr);
            case 'field23': return $this->_field23($bin, $binlen, $acc, $ptr);
            case 'field28': return $this->_field28($bin, $binlen, $acc, $ptr);
            case 'field29': return $this->_field29($bin, $binlen, $acc, $ptr);
            case 'field31': return $this->_field31($bin, $binlen, $acc, $ptr);
            case 'field45': return $this->_field45($bin, $binlen, $acc, $ptr);
            case 'field48': return $this->_field48($bin, $binlen, $acc, $ptr);
            case 'field50': return $this->_field50($bin, $binlen, $acc, $ptr);
            case 'field51': return $this->_field51($bin, $binlen, $acc, $ptr);
            case 'field52': return $this->_field52($bin, $binlen, $acc, $ptr);
            case 'field53': return $this->_field53($bin, $binlen, $acc, $ptr);
            case 'field64': return $this->_field64($bin, $binlen, $acc, $ptr);
            case 'field16': return $this->_field16($bin, $binlen, $acc, $ptr);
            case 'field33': return $this->_field33($bin, $binlen, $acc, $ptr);
            case 'field39': return $this->_field39($bin, $binlen, $acc, $ptr);
            default:
                return $this->_trap($bin, $binlen, $acc, $ptr);
        }
    }

}
