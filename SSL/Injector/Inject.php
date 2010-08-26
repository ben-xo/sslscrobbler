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
 * Dependency Injector. Yields factories for classes.
 * 
 * Yep, I suppose that makes it a Factory Factory doesn't it. \o/
 * 
 * Usage:
 * * To create a factory for XYZs,
 *  
 *   $factory = Inject::the(new XYZFactory()); or
 *   $factory = Inject::the(XYZFactory::instance());
 *   
 *   ...where XYZFactory is defined elsewhere, is a subclass of Factory, and
 *   is also probably a singleton. In fact, the concrete object constructed
 *   here will be passed through verbatim as a default if there is no override
 *   defined for that class.
 *   
 * * To override Inject::the(new XYZFactory()) so that it returns a concrete 
 *   instance of XYZTestFactory, call
 *   
 *   Inject::map('XYZFactory', $instance_of_XYZTestFactory);
 *   
 */
class Inject
{
    // Singleton stuff
    
    private function __construct() {}
    private function __clone() {}
    
    private static $substitutions = array();
    
    public static function the($object)
    {
        $class_name = get_class($object);
        if(isset(self::$substitutions[$class_name]))
        {
            return self::$substitutions[$class_name];
        }
        return $object;
    }
    
    public static function map($class_name, Factory $surrogate)
    {
        self::$substitutions[$class_name] = $surrogate;    
    }
    
    public static function reset()
    {
        self::$substitutions = array();
    }
}