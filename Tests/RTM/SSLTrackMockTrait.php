<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2010 Ben XO
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.html)
 */

trait SSLTrackMockTrait
{
    public function trackMock($id, $length=300, $played=false, $playtime=null)
    {
        $t = $this->createStub('SSLTrack');
        $t->method('getRow')             ->willReturn($id);
        $t->method('getLengthInSeconds') ->willReturn($length);
        $t->method('getPlayed')          ->willReturn($played);
        $t->method('getPlaytime')        ->willReturn($playtime);
        return $t;
    }
}
