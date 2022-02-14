<?php

namespace App\Enums;

enum CacheKeyEnum: string
{
    case Artist = 'artist';
    case AudioFeature = 'audiofeature';

    const CACHE_KEY_SEPARATOR = '_';
}
