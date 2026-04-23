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

class OsascriptPrompt implements Prompt
{
    /**
     * @return string
     */
    public function readline($prompt_text)
    {

        $prompt_text = escapeshellarg($prompt_text);
        // `answer` is pre-initialised to "" so that if the display dialog
        // throws (e.g. the user presses Cancel, or the SystemUIServer
        // tell block fails), the outer `try` silently swallows the error
        // and the final `answer` reference below still resolves to a
        // defined variable. Previously that path produced both an
        // AppleScript "variable answer is not defined" error *and* a
        // null return to trim() (deprecated in PHP 8.1+).
        $command = "osascript -e 'set answer to \"\"\ntry\ntell app \"SystemUIServer\"\n"
                 . "set answer to text returned of (display dialog \"'$prompt_text'\" default answer \"\")\n"
                 . "end\nend\nactivate app (path to frontmost application as text)\nanswer'";

        // shell_exec returns null on failure; cast so trim doesn't trip
        // the PHP 8.1+ "passing null to string parameter" deprecation.
        return trim((string) shell_exec($command));
    }
}