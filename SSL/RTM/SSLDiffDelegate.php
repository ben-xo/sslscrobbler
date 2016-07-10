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
 * Various concrete implementations of SSLDiffDelegate exist for
 * different types of Diff (HistoryDiff, HistoryIndexDiff, etc.).
 * These correspond to different types of SSL file. The DiffObserver
 * uses SSLDiffDelegates to decide what a diff means, whilst keeping
 * the diff-monitoring machinery abstract.
 * 
 * This is different from SSLDiffObserver in that it has nothing to do
 * with event handling, even though the two interfaces look sort of similar
 * and are named sort of similarly as well. (Oops - never mind).
 * 
 * Typically, an SSLDiffDelegate will be some sort of SSL*Observable.
 */
interface SSLDiffDelegate
{
    public function onDiff(SSLDom $changes);
}