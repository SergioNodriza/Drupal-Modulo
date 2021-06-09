<?php
/**
 * @file
 * Contains \Drupal\fichaje_module\Controller\EmpresasController.
 */
namespace Drupal\fichaje_module\Controller;

use DateTime;
use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AdminController extends ControllerBase {

  const typeClose = 'Salida';

  private $timeService;
  private $queryService;

  public function __construct()
  {
    $this->timeService = Drupal::service('fichaje_module.time_service');
    $this->queryService = Drupal::service('fichaje_module.query_service');
  }

  public function admin()
  {
    Drupal::service("router.builder")->rebuild();
    $this->redirectByFilters($_POST['date_filter'], $_POST['user_name'], $_POST['submit']);

    $connection = Database::getConnection();
    $usersIds = $this->queryService->queryCompletedUsersIds($connection);
    $userName = Drupal::request()->get('user_name_filter');
    $date = Drupal::request()->get('date_filter');

    $params = [];
    if ($date) {
      $params['week_filter'] = date('W', strtotime($date));
      $params['year_filter'] = date('Y', strtotime($date));
    } else {
      $params['week_filter'] = date('W');
      $params['year_filter'] = date('Y');
    }

    if ($userName) {
      $userId = $this->queryService->queryUserIdByName($connection, $userName);
      if ($userId && in_array($userId, $usersIds, TRUE)) {
        $usersIds = [$userId];
      }
    }

    $users = $this->getPartes($connection, $usersIds, $params);

    return array(
      '#title' => 'Partes de Horas de la Semana',
      '#theme' => 'admin_partes',
      '#users' => $users,
      '#date' => $this->getDate($date),
      '#names' => $this->getNames($connection),
      '#userName' => $userName,
      '#valid' => count($usersIds) > 0
    );
  }

  public function redirectByFilters($date, $userName, $submit) {

    $url = null;

    if ($date) {
      $url .= '?date_filter=' . $date;
    }

    if ($userName) {
      if ($date) {
        $url .= '&user_name_filter=' . $userName;
      } else {
       $url .= '?user_name_filter=' . $userName;
      }
    }


    if ($submit) {
      if ($date || $userName) {
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
  public function getPartes($connection, $usersIds, $params) {

    $users = array();
    foreach ($usersIds as $userId) {

      $user = User::load($userId);
      $users[$userId] = array();
      $users[$userId]['name'] = $user->getUsername();
      $users[$userId]['jornada'] = $this->queryService->queryJornadaUser($connection, $userId)['week'];

      $params['userId'] = $userId;
      $fichajes = $this->queryService->queryFichajesUsuario($connection, $params);

      $totalSeconds = 0;
      foreach ($fichajes as $fichaje) {
        if ($fichaje['type'] === self::typeClose) {
          $totalSeconds += $this->timeService->timeToSeconds($fichaje['time']);
        }
      }

      $users[$userId]['time'] = $this->timeService->secondsToTime($totalSeconds);


      if ($params['week_filter'] === date('W')) {
        $users[$userId]['state'] = $users[$userId]['time'] > $users[$userId]['jornada'] ? 'colorOk' : 'colorWorking';
      } else {
        $users[$userId]['state'] = $users[$userId]['time'] > $users[$userId]['jornada'] ? 'colorOk' : 'colorNotOk';
      }
    }

    return $users;
  }
  public function getNames($connection) {

    $names = $this->queryService->queryUserNames($connection);
    array_unshift($names, 'Todos');

    return $names;
  }
  public function getDate($date_filter) {

    if ($date_filter) {
      return $date_filter;
    }

    return date('Y-m-d');
  }
}
