<?php

namespace Drupal\forecast_weather\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Base handler for weather forms.
 *
 * @internal - forcast the weather
 */
class Forecast_weather extends FormBase
{
  public function getFormId()
  {
    return 'forecast_weather_form';
  }
  
 /**
 * get weather from the city.
 *
 * @action
 */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your City'),
      '#autocomplete_route_name' => 'forecast_weather.autocomplete',
      '#autocomplete_route_parameters' => array('city_name' => 'city'),
    ];
    $form['actions'] = [
      '#type' => 'button',
      '#value' => $this->t('Search'),
      '#ajax' => [
        'callback' => '::setMessage',
      ]
    ];
    $form['massage'] = [
      '#type' => 'markup',
      '#markup' => '<div class="result_message"></div>',
    ];
    return $form;
  }
  
  /**
  * Get the list of weather in each city.
  */
  public function setMessage(array &$form, FormStateInterface $form_state)
  {
    $url = drupal_get_path('module', 'forecast_weather') . '/city.list.json';
    $string = file_get_contents("$url");
    $cities = json_decode($string);
    $city_id = '';
    foreach ($cities as $city) {
      if ($city->name == $form_state->getValue('city')) {
        $city_id = $city->id;
        break;
      }
    }
    $data = file_get_contents("http://api.openweathermap.org/data/2.5/weather?id=$city_id&appid=60c383195bce83b1bbc30f9bff97a4dc");
    $data = json_decode($data);
    $response = new AjaxResponse();
    if (isset($data)) {
      $response->addCommand(
        new HtmlCommand(
          '.result_message',
          '<div class="my_top_message"><h2>' . $this->t('@city_name', ['@city_name' => $data->name]) . 'Weather Status</h2></div>
           <div class="time">
              <div>' . $this->t('@description', ['@description' => ucwords($data->weather[0]->description)]) . '</div>
              <div class="weather-forecast"><img
                src="http://openweathermap.org/img/wn/' . $this->t('@icon', ['@icon' => $data->weather[0]->icon]) . '.png"
                class="weather-icon" />' . $this->t('@max', ['@max' => intval($data->main->temp_max / 10)]) . '°C</div>
            </div>      
            <div class="time">
              <div>Humidity: ' . $this->t('@humidity', ['@humidity' => $data->main->humidity])  . '%</div>
              <div>Wind: ' . $this->t('@wind', ['@wind' => $data->wind->speed]) . ' km/h</div>
        </div>'
        )
      );
    } else {
      $response->addCommand(
        new HtmlCommand(
          '.result_message',
          '<div class="my_top_message">Not Found'
        )
      );
    }
    return $response;
  }
  public function submitForm(array &$form, FormStateInterface $form_state)
  { }
}
