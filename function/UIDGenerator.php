<?php

class UIDGenerator
{
    public static function generateUUID()
    {
        $time = microtime(true);
        $sec = (int)$time;
        $usec = (int)(($time - $sec) * 1000000);
        $part1 = ($sec & 0xffff0000) >> 16;
        $part2 = $sec & 0x0000ffff;
        $part3 = $usec & 0xffff;
        $part4 = (($usec >> 16) & 0x0fff) | 0x4000; 
        $part5 = (mt_rand(0, 0x3fff) | 0x8000); 
        $part6 = mt_rand(0, 0xffff);
        $part7 = mt_rand(0, 0xffff);
        $part8 = mt_rand(0, 0xffff);

        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            $part1,
            $part2,
            $part3,
            $part4,
            $part5,
            $part6,
            $part7,
            $part8
        );
    }
}
