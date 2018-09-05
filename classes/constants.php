<?php

class constants
{
    const DEFAULT_CACHE_TIME_MINUTES = 60; // minutes
    const CRITICAL_DAYS_TIL_EVENT = 3; // days
    const CRITICAL_AVAILABILITY = 0.15; // percentage
    
    public static function getDefaultCacheTimeInMinutes()
    {
	return self::DEFAULT_CACHE_TIME_MINUTES;
    }
    
    public static function getCriticalDaysTilEvent()
    {
	return self::CRITICAL_DAYS_TIL_EVENT;
    }
    
    public static function getCriticalAvailability()
    {
	return self::CRITICAL_AVAILABILITY;
    }
}
