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

  const typeOpen = 'Entrada';

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
    $this->redirectByFilters($_POST['empresa'], $_POST['date_filter'], $_POST['week_filter'], $_POST['submit']);

    $user = Drupal::currentUser();
    $connection = Database::getConnection();
    $filters = Drupal::request()->get('filters');
    $date_filter = Drupal::request()->get('date_filter');

    if (!$filters) {
      $week_filter = date('W', strtotime(date('Y-m-d')));
    } else {
      $week_filter = Drupal::request()->get('week_filter');
    }

    $fichajes = $this->getFichajesByFilters($connection, $user, $empresaName, $date_filter, $week_filter);
    $arrayWeeks = $this->groupFichajesByWeekAndFormat($fichajes);
    $arrayCompleted = $this->getTotalsByWeek($arrayWeeks);

    return array(
      '#title' => $this->formatTitle($empresaName),
      '#theme' => 'usuario_fichajes',
      '#results' => $arrayCompleted,
      '#buttons' => $this->buttons($connection, $user),
      '#route' => Drupal::routeMatch()->getParameter('empresaName') ?? 'general',
      '#date' => $this->getDate($date_filter, $week_filter),
      '#isWeek' => !empty($week_filter)
    );
  }


  public function redirectByFilters($empresaFilter, $dateFilter, $weekFilter, $submit)
  {
    $url = null;

    if ($empresaFilter) {
      if ($empresaFilter === 'general') {
          $url .= '/parte';
        } else {
          $url .= '/parte/' . $empresaFilter;
        }
    }

    if ($dateFilter) {
      $url .= '?date_filter=' . $dateFilter;
    }

    if ($weekFilter) {

      if ($dateFilter) {
        $url .= '&week_filter=' . date('W', strtotime($dateFilter));
      } else {
        $url .= '?week_filter=' . date('W', strtotime(date('Y-m-d')));
      }

    }

    if ($submit) {
      if ($weekFilter || $dateFilter) {
        $url .= '&filters=submit';
      } else {
        $url .= '?filters=submit';
      }
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
    if ($week_filter) {
      $params['week_filter'] = $week_filter;
      if ($date_filter) {$params['year_filter'] = date('Y', strtotime($date_filter));}
      else {$params['year_filter'] = date('Y');}
    }
    elseif ($date_filter) {$params['date_filter'] = $date_filter;}


    return $this->queryService->queryFichajesUsuario($connection, $params);
  }
  public function groupFichajesByWeekAndFormat($fichajes) {

    $arrayWeeks = array();
    foreach($fichajes as $key => $fichaje) {

      $date = $fichaje['date'];
      $fichajes[$key]['date'] = $this->timeService->formatDate($date);

      if ($fichajes[$key]['type'] === self::typeOpen) {
        $fichajes[$key]['time'] = $this->timeService->formatDate($fichajes[$key]['time']);
      }

      $week = date('W', strtotime($date));
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

      $arrayWeeks[$key]['total'] = $this->timeService->secondsToTime($totalSeconds);
    }
    return $arrayWeeks;
  }

  public function formatTitle($empresaName) {

    $title = 'Parte de Horas ';

    if (!$empresaName) {
      $title .= 'General';
    } else {
      $title .= 'de ' . $empresaName;
    }

    return $title;
  }
  public function buttons($connection, $user)
  {
    $empresasIds = $this->queryService->queryEmpresasIds($connection, $user);
    $uri = Drupal::request()->getRequestUri();

    if ($uri !== '/parte' && !str_starts_with($uri, '/parte?')) {
      return $this->buttonMakerService->makeButtons($empresasIds, true);
    }

    return $this->buttonMakerService->makeButtons($empresasIds);
  }
  public function getDate($date_filter, $week_filter) {

    if ($date_filter) {
      return $date_filter;
    }

    if ($week_filter) {
      return date('Y-m-d');
    }

    return '';
  }
}
