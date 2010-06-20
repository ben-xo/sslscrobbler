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
 * Interpreter for the binary unpacking language "XOUP", that I invented for this
 * project :-)
 *  
 * XOUP is a simple procedural language with tokens separated by whitespace. (It 
 * looks sort of similar to some kind of weird assembler). The execution path of 
 * a XOUP programs is modelled with a consume-only binary input, and a key/value 
 * output. There is also a single read/write accumulator (called '_') that can be 
 * used as a read source, read destination, read-length and as a direct substitute 
 * in named sub-routine calls. 
 * 
 * XOUP offers several conversions from binary strings into useful PHP types such 
 * as string and integer, and some convenience conversions such as hexdump and 
 * formatted-timestamp.
 * 
 * XOUP programs are structured as sub-routines with comments (delimited with # and 
 * newline). Sub-routines are labelled; labels consist of the letters A-Z followed
 * by a ':'. Every label encountered starts the definition of a new sub-routine. 
 * It's an error to have statements outside of a sub-routine. It's also an error to
 * have a program with no sub-routine called 'main'.
 * 
 * XOUP has 3 commands: 'read', 'copy', and 'call', explained below.
 *  
 * Read takes the form "r[length][read-type]>[write-type][dest]" e.g. r1l>i_ or r_b>s_
 * Copy takes the form "c>[write-type][dest]"                    e.g. c>field
 * Where,
 * * [length] is an integer (or '_' to use the integer in the accumulator)
 * * [read-type] is one of 'b', 'w' or 'l' (for byte, word or longword). 
 *   'b' is most appropriate for strings; 'l' is often appropriate for integer values.
 * * [write-type] is one of 'r', 's', 'i', 'h', 't'.
 *   'r' leaves the value as read (a binary string)
 *   's' converts the string from UTF-16 to UTF-8
 *   'i' unpacks a binary string into a PHP integer (supports byte, word and longword).
 *   'h' converts the binary string to a formatted hexdump using Hexdumper
 *   't' unpacks into a PHP integer (like 'i') and then formats it as a timestamp string.
 * * [dest] is either '_' for the accumulator, or the name of a key in the output.
 * 
 * Call take the form "[label]." where [label] is any valid sub-routine name. Also, if
 * [label] contains a '_', the value from the accumulator will be substituted into the 
 * name. (e.g. "field_." could call sub-routine field10 if the accumulator contained 
 * integer 10). 
 * 
 * @author ben
 */
class XoupInterpreter extends Unpacker
{
    protected $subs = array();
    
    public function __construct($program)
    {
        $this->subs = $this->parse($program);
    }
    
    public function parse($program)
    {
        $parser = new XoupParser();
        return $parser->parse($program);
    }
    
    public function unpack($bin)
    {
        $context = array();
        $acc = 0; // accumulator
        $ptr = 0;
        
        $this->sub('main', $bin, $context, $acc, $ptr);
        return $context;
    }
    
    protected function sub($sub, $bin, &$context, &$acc, &$ptr)
    {
        if(!isset($this->subs[$sub])) 
        {
            throw new RuntimeException("No such subroutine $sub");
        }
            
        $opcount = count($this->subs[$sub]);
        do
        {
            $loop = false;
            
            foreach($this->subs[$sub] as $opindex => $op)
            {                
                if(!preg_match('/^(?:([a-zA-Z0-9_]+)\.|(c|(r)(\d+|_|\*)(b|w|l))(>)(s|i|h|t|r)(_|([a-zA-Z]+)))/', $op, $matches))
                    throw new RuntimeException("Could not parse Unpacker op '$op' in sub '$sub'");
                                
                $callsub = $matches[1];
                if($callsub)
                {
                    $callsub = str_replace('_', $acc, $callsub);
                    
                    if($callsub == 'bp')
                    {
                        var_dump('ACC', $acc, 'PTR', $ptr);
                    }
                    elseif($callsub == 'exit')
                    {
                        return false;
                    }
                    else
                    {
                        if(($callsub == $sub) && ($opindex == $opcount - 1))
                        {
                            // do efficient tail-recursion
                            $loop = true;
                            continue 2;
                        }
                        
                        $rc = $this->sub($callsub, $bin, $context, $acc, $ptr);
                        if($rc === false) 
                        {
                            // exit condition
                            return false;
                        }
                    }
                    continue;
                }
                
                $copy_action = $matches[2];
                if($copy_action == 'c')
                {
                    $read_action = $copy_action;
                }
                else
                {
                    $read_action = $matches[3];
                    $read_length = $matches[4];
                    $read_width = $matches[5];
                    
                    if('_' == $read_length)
                    {
                        $read_length = $acc;
                    }
                    elseif('*' == $read_length)
                    {
                        $read_length = strlen($bin) - $ptr;
                    }
                } 
        
                $write_action = $matches[6];
                $type = $matches[7];
                $dest = $matches[8];
                                            
                try
                {
                    switch($read_action)
                    {
                        case 'c':
                            $datum = $acc;
                            break;
                            
                        case 'r':
                            if($ptr >= strlen($bin))
                            {
                                // a read beyong the end of the binary is an exit condition
                                return false;
                            }
                            $datum = $this->read($bin, $ptr, $read_length, $read_width);
                            break;
                                                    
                        default:
                            throw new RuntimeException("Unknown read action '$read_action'. Expected 'r' or 'c'");                    
                    }
        
                    switch($write_action)
                    {
                        case '>':
                            $this->write($datum, $context, $acc, $type, $dest);
                            break;
                            
                        default:
                            throw new RuntimeException("Unknown write action '$write_action'. Expected '>'");
                    }
                }
                catch(Exception $e)
                {
                    $context['_EXCEPTION'] = $e->getMessage() . " in op '$op' in sub '$sub'. ptr: $ptr acc: $acc size: " . strlen($bin);
                    break;
                }              
            }
        }
        while($loop);
      
        return true;
    }
    
    protected function read($bin, &$ptr, $length, $width)
    {
        if($ptr > strlen($bin))
            throw new OutOfBoundsException("Cannot start read past end of data");
        
        switch($width)
        {
            case 'b':
                $length *= 1;
                break;
                
            case 'w':
                $length *= 2;
                break;
                
            case 'l':
                $length *= 4;
                break;
                
            default:
                throw new RuntimeException("Unknown read width '$read_width'. Expected 'b', 'w' or 'l'");
        }
        
        if($ptr + $length > strlen($bin))
            throw new OutOfBoundsException("Cannot end read past end of data");
        
        $datum = substr($bin, $ptr, $length);
        
        $ptr += $length;
        return $datum;
    }
    
    protected function write($datum, array &$context, &$acc, $type, $dest)
    {
        if($dest == '_')
        {
            $to = ' -> ACC';
            $dest =& $acc;
        }
        else
        {
            $to = ' -> ' . $dest;
            $dest =& $context[$dest];
        }

        
        switch($type)
        {
            case 'r': // raw
                $dest = $datum;
                break;
                
            case 's': // string
                $dest = (string) $this->unpackstr($datum);
                break;
                
            case 'i': // int
                $dest = (int) $this->unpackint($datum);
                break;
                
            case 'h': // hexdump
                $hd = new Hexdumper();
                $dest = trim($hd->hexdump($datum));
                break;
                
            case 't': // timestamp -> date
                $dest = date("Y-m-d H:i:s", (int) $this->unpackint($datum));
                break;
                    
            default:
                throw new RuntimeException("Unknown type '$type'. Expected 's' or 'i'");                
        }
        
        // echo "$dest $to\n";
    }
    

}