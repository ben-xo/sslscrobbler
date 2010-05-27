<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2010 Ben XO
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.html)
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

/**
 * Prints binary stings as formatted hexdumps. Used as the default output on
 * Chunks where no other decoding is known. 
 *  
 * @author ben
 */
class Hexdumper
{
    public function hexdump($bin, $indent=0, $width=32)
    {
        $hex = $this->printHex($bin, $width);
        $asc = $this->printAsc($bin, $width);
        $out = str_repeat("\t", $indent);
        $out .= str_pad($hex, 80, ' '); 
        $out .= str_pad($asc, 32, ' '); 
        $out .= "\n";
        return $out;
    }
    
    protected function printHex($row, $width)
    {
        $hexstr = bin2hex($row);
        return chunk_split($hexstr, 4, ' ');
    }
    
    protected function printAsc($row, $width)
    {
        $string = '';
        $width = strlen($row);
        $ordchars = '0123456789ABCDEFGHIJKLMNOPQRSTUV';
        for($i = 0; $i < $width; $i++)
        {
            if(ord($row{$i}) == 0)
            {
                // nulls
                $string .= $this->highlight('.', 31); // red  
            }
            elseif(ord($row{$i}) < 32) 
            {
                // <32 is not valid ascii; show these as ints (from the $ordchars array above)
                $string .= $this->highlight($ordchars{ord($row{$i})}, 32); // green
            }
            elseif(ord($row{$i}) > 126)
            {
                // >126 is not valid ascii, show these as ints with an !
                $string .= $this->highlight('!', 33); // yellow
            }
            else
            {
                $string .= $row{$i};
            }
        }
        return $string;
    }
    
    protected function highlight($s, $color=null)
    {
        return sprintf("%s[%sm%s%s[%sm", chr(27), $color ? $color : 1, $s, chr(27), 0); 
    }
}