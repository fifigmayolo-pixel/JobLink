<?php

// app/Support/PhilippineLocations.php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class PhilippineLocations
{
    /**
     * Reads the big JSON file. Only called on a cache miss, so it is NOT stored
     * in the cache itself (a multi-MB blob in a database cache is slow to load
     * on every request).
     */
    protected static function load(): array
    {
        return json_decode(file_get_contents(resource_path('data/ph-locations.json')), true) ?: [];
    }

    /**
     * Cache one small result under its own key. Empty results are not cached,
     * so random province/city names typed into the URL can't fill the cache.
     */
    protected static function remember(string $key, callable $resolve): array
    {
        $hit = Cache::get($key);

        if (is_array($hit)) {
            return $hit;
        }

        $value = $resolve();

        if ($value !== []) {
            Cache::forever($key, $value);
        }

        return $value;
    }

    /**
     * All province names across every region, alphabetically sorted.
     */
    public static function provinces(): array
    {
        return self::remember('ph:provinces', function () {
            $provinces = [];

            foreach (self::load() as $regionProvinces) {
                $provinces = array_merge($provinces, array_keys($regionProvinces));
            }

            sort($provinces);

            return $provinces;
        });
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

        return self::remember('ph:cities:' . md5($province), function () use ($province) {
            foreach (self::load() as $regionProvinces) {
                if (array_key_exists($province, $regionProvinces)) {
                    $cities = array_keys($regionProvinces[$province]);
                    sort($cities);

                    return $cities;
                }
            }

            return [];
        });
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

        return self::remember('ph:barangays:' . md5($province . '|' . $city), function () use ($province, $city) {
            foreach (self::load() as $regionProvinces) {
                if (array_key_exists($province, $regionProvinces) && array_key_exists($city, $regionProvinces[$province])) {
                    $barangays = $regionProvinces[$province][$city];
                    sort($barangays);

                    return $barangays;
                }
            }

            return [];
        });
    }
}