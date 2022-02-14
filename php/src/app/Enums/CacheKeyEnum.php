<?php

namespace App\Enums;

enum CacheKeyEnum: string
{
    case Artist = 'artist';
    case Track = 'track';
    case AudioFeature = 'audiofeature';
}
