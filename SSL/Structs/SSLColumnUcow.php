<?php

/* Autogenerated by XoupCompiler */

class XOUPSSLColumnUcowUnpacker extends Unpacker
{

    private $out_buffer = '';
    private $context = array();

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
        /* r*b>iwidth */
        if($ptr >= $binlen) return false;
        $datum = substr($bin, $ptr); // to eof
        $this->context['width'] = (int) $this->unpacksint($datum);
        return false; // this must be the end as we read to eof
        return true;
    }

    private function lookup($sub, $bin, $binlen, &$acc, &$ptr)
    {
        switch($sub) {
            case 'main': return $this->_main($bin, $binlen, $acc, $ptr);
            default:
                throw new RuntimeException('No such subroutine ' . $sub);
        }
    }

}
