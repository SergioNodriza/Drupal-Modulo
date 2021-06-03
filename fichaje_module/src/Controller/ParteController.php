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
    $this->redirectByFilters($_POST['link'], $_POST['date_filter'], $_POST['week_filter']);

    $user = Drupal::currentUser();
    $connection = Database::getConnection();
    $date_filter = Drupal::request()->get('date_filter');
    $week_filter = Drupal::request()->get('week_filter');

    $fichajes = $this->getFichajesByFilters($connection, $user, $empresaName, $date_filter, $week_filter);
    $arrayWeeks = $this->groupFichajesByWeekAndFormat($fichajes);
    $arrayCompleted = $this->getTotalsByWeek($arrayWeeks);

    return array(
      '#title' => $this->formatTitle($empresaName, $date_filter, $week_filter),
      '#theme' => 'usuario_fichajes',
      '#results' => $arrayCompleted,
      '#buttons' => $this->buttons($connection),
      '#route' => Drupal::routeMatch()->getParameter('empresaName') ?? 'General'
    );
  }


  public function redirectByFilters($linkFilter, $dateFilter, $weekFilter = false)
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

      if ($weekFilter) {
        $url .= '&week_filter=' . date('W', strtotime($dateFilter));
      }
    } elseif ($weekFilter) {
      $url .= '?week_filter=' . date('W', strtotime(date('Y-m-d')));
    }

    if ($url) {
      $response = new RedirectResponse($url);
      $response->send();
    }
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


      $year = date('Y', strtotime($date));

      $daysWeek = [];
      for($day=1; $day<8; $day++) {
        $daysWeek[] = date('Y-m-d', strtotime($year."W".$week_filter.$day));
      }

      $params['week_filter'] = $daysWeek;
    }

    return $this->queryService->queryFichajesUsuario($connection, $params);
  }

  public function groupFichajesByWeekAndFormat($fichajes) {

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

    return $arrayWeeks;
  }

  public function getTotalsByWeek($arrayWeeks) {

    foreach ($arrayWeeks as $key => $arrayWeek) {

      $totalSeconds = 0;
      foreach ($arrayWeek['fichajes'] as $key2 => $fichaje) {

        if ($fichaje['type'] === 'Salida') {
          $totalSeconds += $this->timeService->timeToSeconds($fichaje['time']);
        }
      }

      $arrayWeeks[$key]['total'] = $this->timeService->formatSeconds($totalSeconds);
    }
    return $arrayWeeks;
  }

  public function formatTitle($empresaName, $date_filter, $week_filter) {

    $title = 'Parte de Horas ';

    if (!$empresaName) {
      $title .= 'General';
    } else {
      $title .= 'de ' . $empresaName;
    }

    if ($date_filter && !$week_filter) {
      $title .= ' | Filtro día ' . date('d/m/Y', strtotime($date_filter));
    }

    if ($week_filter) {

      if ($date_filter) {
        $date = $date_filter;
      }
      else {
        $date = date('Y-m-d');
      }

      $title .= ' | Filtro semana del ' . date('d/m/Y', strtotime($date));
    }

    return $title;
  }

  public function buttons($connection)
  {

    $empresasIds = $this->queryService->queryEmpresasIds($connection);

    if (Drupal::request()->getRequestUri() !== '/parte') {
      return $this->buttonMakerService->makeButtons($empresasIds, true);
    }

    return $this->buttonMakerService->makeButtons($empresasIds);
  }
}
