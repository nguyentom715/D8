<?php

namespace Drupal\forecast_weather\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for watches autocomplete form elements for each city.
 */
class CityAutoCompleteController extends ControllerBase
{
    public function handleAutocomplete(request $request)
    {
        $url = drupal_get_path('module', 'forecast_weather') . '/city.list.json';
        $string = file_get_contents("$url");
        $cities = json_decode($string);

        if ($city_name = $request->query->get('q')) {
            foreach ($cities as $city) {
                if ($city->country == "US" && strpos($city->name, $city_name) !== false) {
                    $city_name_array[] = $city->name;
                }
            }
            $city_name_array = array_slice($city_name_array, 0, 5);
            return new JsonResponse($city_name_array);
        }
    }
}
