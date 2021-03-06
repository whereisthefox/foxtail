<?php
namespace Frameworkless\Controllers;

use Exception;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Cmfcmf\OpenWeatherMap;
use Cmfcmf\OpenWeatherMap\Exception as OWMException;
use Twig_Environment;

//require __DIR__.'/vendor/autoload.php';

/**
 * Handles API requests and responses
 *
 * @author Marin Treselj <marin@pixelipo.com>
 */
class ApiController
{
    public function getData()
    {
        $request = Request::createFromGlobals();
        file_put_contents($_SERVER["DOCUMENT_ROOT"].'/data/json/log.txt', $request->getContent());
        $data = json_decode($request->getContent(), TRUE);

        $status = $this->appendData($data);
        $response = new Response(
            $status,
            Response::HTTP_OK,
            array('Content-Type' => 'application/json')
        );

        $response->send();
    }

/**
  * Usedfortesting
  *
  */
    public function sendData()
    {
        $r = Request::createFromGlobals();
        $r->headers->set(('Content-Type'), 'application/json');
        $r->request->set(('body'), '{"coord":{"lon":139,"lat":35},
"sys":{"country":"JP","sunrise":1369769524,"sunset":1369821049},
"weather":[{"id":804,"main":"clouds","description":"overcast clouds","icon":"04n"}],
"main":{"temp":289.5,"humidity":89,"pressure":1013,"temp_min":287.04,"temp_max":292.04},
"wind":{"speed":7.31,"deg":187.002},
"rain":{"3h":0},
"clouds":{"all":92},
"dt":1369824698,
"id":1851632,
"name":"Shuzenji",
"cod":200}');
        return($this->getData($r));
    }

    public function appendData($data2)
    {
	  try {
      //if( defined($data2['lat'])){
      //  $lat = $data2['lat'];
      //  $lon = $data2['lon'];
      //  $dt = $data['dt'];
    //  } else {
        $lat = $data2['features'][0]['geometry']['coordinates'][0];
        $lon = $data2['features'][0]['geometry']['coordinates'][1];
        // $data2['features'][0]['dt'] = $weatherdata['dt'];
    //  }

      $weatherdata = $this->getWetData($lat, $lon);
      $data2['features'][0]['weather'][0]['id'] = $weatherdata['id'];
      $data2['features'][0]['weather'][0]['main'] = $weatherdata['main'];
      $data2['features'][0]['weather'][0]['description'] = $weatherdata['description'];
      $data2['features'][0]['weather'][0]['icon'] = $weatherdata['icon'];
      $data2['features'][0]['main']['temp'] = $weatherdata['temp'];
      $data2['features'][0]['main']['pressure'] = $weatherdata['pressure'];
      $data2['features'][0]['main']['humidity'] = $weatherdata['humidity'];
      $data2['features'][0]['main']['temp_min'] = $weatherdata['temp_min'];
      $data2['features'][0]['main']['temp_max'] = $weatherdata['temp_max'];
      $data2['features'][0]['main']['sea_level'] = $weatherdata['sea_level'];
      $data2['features'][0]['main']['grnd_level'] = $weatherdata['grnd_level'];
      $data2['features'][0]['wind']['speed'] = $weatherdata['speed'];
      $data2['features'][0]['wind']['deg'] = $weatherdata['deg'];
      $data2['features'][0]['clouds']['all'] = $weatherdata['all'];

	    $inp = file_get_contents($_SERVER["DOCUMENT_ROOT"].'/data/json/datapoints.geojson');
	    $tempArray = json_decode($inp, true);
      array_push($tempArray, $data2);
	    $jsonData = json_encode($tempArray);
	    file_put_contents($_SERVER["DOCUMENT_ROOT"].'/data/json/datapoints.geojson', $jsonData);
	    return 'succes';
	  } catch(Exception $e) {
	    return $e->getMessage();
	  }
  }

  public function getWetData($lat, $lon)
  {
      // Language of data (try your own language here!):
      $lang = 'de';

      // Units (can be 'metric' or 'imperial' [default]):
      $units='metric';
      // Create OpenWeatherMap object.
      // Don't use caching (take a look into Examples/Cache.php to see how it works).
      $owm = new OpenWeatherMap(getenv('WEATHER'));

      try {
          $weather = $owm->getWeather(array('lat' => $lat, 'lon' => $lon), $units, $lang);

          $wheatherout = [];

          $weatherout['id'] = (string)$weather->city->id;
          $weatherout['main'] = 'none';
          $weatherout['description'] = $weather->clouds->getDescription();
          $weatherout['icon'] = 'none';
          $weatherout['temp'] = $weather->temperature->getFormatted();
          $weatherout['pressure'] = $weather->pressure->getFormatted();
          $weatherout['humidity'] = $weather->humidity->getFormatted();
          $weatherout['temp_min'] = $weather->temperature->min->getFormatted();
          $weatherout['temp_max'] = $weather->temperature->max->getFormatted();
          $weatherout['sea_level'] = 'none';
          $weatherout['grnd_level'] = 'none';
          $weatherout['speed'] = $weather->wind->speed->getFormatted();
          $weatherout['deg'] = $weather->wind->direction->getFormatted();
          $weatherout['all'] = $weather->clouds->getFormatted();

          print('<pre>');
          var_dump($weatherout);
          return $weatherout;
      } catch(OWMException $e) {
          echo 'OpenWeatherMap exception: ' . $e->getMessage() . ' (Code ' . $e->getCode() . ').';
      } catch(\Exception $e) {
          echo 'General exception: ' . $e->getMessage() . ' (Code ' . $e->getCode() . ').';
      }
  }

    public function tomyCSV()
    {
        $titleList = array (
            "latitude",
            "longitude",
            "name_of_spiecies",
            "picture_name",
            "weather_desc",
            "temp",
            "pressure",
            "humidity",
            "temp_min",
            "temp_max",
            "sea_level",
            "grnd_level",
            "wind_speed",
            "wind_deg",
            "clouds_all",
            "timespan"
        );
        $csvfile = fopen($_SERVER["DOCUMENT_ROOT"].'/data/file.csv','w+');
        fputcsv($csvfile, $titleList);
        $json = file_get_contents($_SERVER["DOCUMENT_ROOT"].'/data/json/datapoints.geojson');
        $tempArray = json_decode($json, true)["features"];
        // print("<pre>");
        // var_dump($tempArray);
        foreach ($tempArray as $k => $v) {
            $list = array();
            $list[] = $v['geometry']['coordinates'][0];
            $list[] = $v['geometry']['coordinates'][1];
            $list[] = $v["properties"]['species'];
            $list[] = $v["properties"]['picture'];
            $list[] = $v['weather'][0]['description'];
            $list[] = $v['main']['temp'];
            $list[] = $v['main']['pressure'];
            $list[] = $v['main']['humidity'];
            $list[] = $v['main']['temp_min'];
            $list[] = $v['main']['temp_max'];
            $list[] = $v['main']['sea_level'];
            $list[] = $v['main']['grnd_level'];
            $list[] = $v['wind']['speed'];
            $list[] = $v['wind']['deg'];
            $list[] = $v['clouds']['all'];
            $list[] = $v['dt'];
            fputcsv($csvfile, $list);
        }
        fclose($csvfile);

        $request = Request::createFromGlobals();

        $response = new Response(
            "successful",
            Response::HTTP_OK,
            array('Content-Type' => 'text/plain')
        );

        $response->send();
   }

}

 ?>
