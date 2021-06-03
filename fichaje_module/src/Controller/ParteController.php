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

    $this->redirectByFilters($_POST['link'], $_POST['date_filter'], $_POST['week_filter']);

    if (!$empresaName) {$empresaName = '%';}

    $date_filter = Drupal::request()->get('date_filter');
    $week_filter = Drupal::request()->get('week_filter');

    $fichajes = $this->getFichajesByFilters($connection, $user, $empresaName, $date_filter, $week_filter);

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

        if ($fichaje['type'] === 'Salida') {
          $totalSeconds += $this->timeService->timeToSeconds($fichaje['time']);
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
      '#theme' => 'usuario_fichajes',
      '#results' => $arrayWeeks,
      '#buttons' => $this->buttons($connection),
      '#route' => Drupal::routeMatch()->getParameter('empresaName') ?? 'General'
    );
  }


  public function redirectByFilters($linkFilter, $dateFilter, $week = false)
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

      if ($week) {
        $url .= '&week_filter=' . date('W', strtotime($dateFilter));
      }
    } elseif ($week) {
      $url .= '?week_filter=' . date('W', strtotime($dateFilter));
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
      return $this->buttonMakerService->makeButtons($empresasIds, true);
    }

    return $this->buttonMakerService->makeButtons($empresasIds);
  }

  public function getFichajesByFilters($connection, $user, $empresaName, $date_filter, $week_filter)
  {
    $params = [];
    $params['userId'] = $user->id();

    if ($empresaName) {$params['empresaName'] = $empresaName;}

    if ($date_filter && !$week_filter) {$params['date_filter'] = $date_filter;}

    if ($week_filter) {

      if ($date_filter) {
        $date = $date_filter;
      }
      else {
        $date = date('Y-m-d');
      }

      $week = date('W', strtotime($date));
      $year = date('Y', strtotime($date));

      $daysWeek = [];
      for($day=1; $day<8; $day++)
      {
        $daysWeek[] = date('Y-m-d', strtotime($year."W".$week.$day));
      }

      $params['week_filter'] = $daysWeek;
    }

    return $this->queryService->queryFichajesUsuario($connection, $params);
  }
}
