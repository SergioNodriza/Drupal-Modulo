<?php
/**
 * @file
 * Contains \Drupal\fichaje_module\Controller\ParteController.
 */
namespace Drupal\fichaje_module\Controller;

use Drupal;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

class ParteController extends ControllerBase {

  private $timeService;
  private $queryService;
  private $buttonMakerService;

  public function __construct()
  {
    $this->timeService = Drupal::service('fichaje_module.time_service');
    $this->queryService = Drupal::service('fichaje_module.query_service');
    $this->buttonMakerService = Drupal::service('fichaje_module.button_maker_service');
  }


  public function parte($empresaName)
  {
    Drupal::service("router.builder")->rebuild();
    $user = Drupal::currentUser();
    $connection = Database::getConnection();

    $dateFilter = $_POST['date_filter'];
    $linkFilter = $_POST['link'];
    $this->tryRedirect($linkFilter, $dateFilter);

    $date = Drupal::request()->get('date_filter');
    if (!$empresaName) {$empresaName = '%';}
    $fichajes = $this->queryService->queryFichajesUsuario($connection, $user, $empresaName, $date . '%');


    $arrayWeeks = array();
    foreach($fichajes as $key => $fichaje) {

      $date = $fichaje['date'];
      $week = date('W', strtotime($date));
      $fichajes[$key]['date'] = $this->timeService->formatDate($date);

      if(!isset($arrayWeeks[$week]) ) {
        $arrayWeeks[$week]['fichajes'] = array();
      }
      $arrayWeeks[$week]['fichajes'][] = $fichajes[$key];
    }


    foreach ($arrayWeeks as $key => $arrayWeek) {

      $totalSeconds = 0;
      foreach ($arrayWeek['fichajes'] as $key2 => $fichaje) {
        $time = $fichaje['time'];
        if ($time !== '') {
          $totalSeconds += $this->timeService->timeToSeconds($time);
        }
      }

      $arrayWeeks[$key]['total'] = $this->timeService->formatSeconds($totalSeconds);
    }

    if ($empresaName === '%') {
      $title = 'Parte de Horas General';
    } else {
      $title = 'Parte de Horas de ' . $empresaName;
    }

    return array(
      '#title' => $title,
      '#theme' => 'fichajes_usuario',
      '#results' => $arrayWeeks,
      '#buttons' => $this->buttons($connection),
      '#route' => Drupal::routeMatch()->getParameter('empresaName')
    );
  }


  public function tryRedirect($linkFilter, $dateFilter)
  {
    $url = null;

    if ($linkFilter) {
      if ($linkFilter === 'General') {
          $url .= '/parte';
        } else {
          $url .= '/parte/' . $linkFilter;
        }
    }

    if ($dateFilter) {
      $url .= '?date_filter=' . $dateFilter;
    }

    if ($url) {
      $response = new RedirectResponse($url);
      $response->send();
    }
  }


  public function buttons($connection)
  {

    $empresasIds = $this->queryService->queryEmpresasIds($connection);

    if (Drupal::request()->getRequestUri() !== '/parte') {
      return $this->buttonMakerService->makeButtonsParte($empresasIds, true);
    }

    return $this->buttonMakerService->makeButtonsParte($empresasIds);
  }
}
