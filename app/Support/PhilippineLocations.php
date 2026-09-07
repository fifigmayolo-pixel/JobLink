<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class PhilippineLocations
{
    protected static function data(): array
    {
        return Cache::rememberForever('ph-locations-data', function () {
            return json_decode(file_get_contents(resource_path('data/ph-locations.json')), true);
        });
    }

    /**
     * All province names across every region, alphabetically sorted.
     */
    public static function provinces(): array
    {
        $provinces = [];

        foreach (self::data() as $regionProvinces) {
            $provinces = array_merge($provinces, array_keys($regionProvinces));
        }

        sort($provinces);

        return $provinces;
    }

    /**
     * Cities/municipalities belonging to the given province, alphabetically sorted.
     * Empty array if the province isn't recognized or wasn't selected yet.
     */
    public static function citiesForProvince(?string $province): array
    {
        if (! $province) {
            return [];
        }

        foreach (self::data() as $regionProvinces) {
            if (array_key_exists($province, $regionProvinces)) {
                $cities = array_keys($regionProvinces[$province]);
                sort($cities);

                return $cities;
            }
        }

        return [];
    }

    /**
     * Barangays belonging to the given city/municipality within the given province.
     * Empty array if either isn't recognized or wasn't selected yet.
     */
    public static function barangaysForCity(?string $province, ?string $city): array
    {
        if (! $province || ! $city) {
            return [];
        }

        foreach (self::data() as $regionProvinces) {
            if (array_key_exists($province, $regionProvinces) && array_key_exists($city, $regionProvinces[$province])) {
                $barangays = $regionProvinces[$province][$city];
                sort($barangays);

                return $barangays;
            }
        }

        return [];
    }
}