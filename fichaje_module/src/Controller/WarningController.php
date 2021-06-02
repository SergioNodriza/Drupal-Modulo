<?php
/**
 * @file
 * Contains \Drupal\fichaje_module\Controller\FichajeController.
 */
namespace Drupal\fichaje_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

class WarningController extends ControllerBase {

  const typeOpen = 'Entrada';
  const typeClose = 'Salida';

  private $queryService;
  private $createNodeService;
  private $timeService;

  public function __construct()
  {
    $this->queryService = \Drupal::service('fichaje_module.query_service');
    $this->createNodeService = \Drupal::service('fichaje_module.create_node_service');
    $this->timeService = \Drupal::service('fichaje_module.time_service');
  }


  public function warning($empresaName)
  {
    \Drupal::service("router.builder")->rebuild();
    $user = \Drupal::currentUser();
    $connection = Database::getConnection();

    if (($date = \Drupal::request()->get('date_filter')) &&
        ($time = \Drupal::request()->get('time_filter'))) {

      return $this->fix($connection, $user, $empresaName, $date, $time);
    }

    return $this->twigWarning($connection, $user);
  }


  public function fix($connection, $user, $empresaName, $date, $time)
  {
    $dateTime = $date . ' ' . $time;
    $interval = $this->queryService->queryTimeDiff($connection, $user, $dateTime);

    if ($interval === false) {
      return $this->twigWarning($connection, $user, true);
    }

    $empresaId = $this->queryService->queryIdEmpresa($connection, $empresaName);
    $this->createNodeService->createNode($user, self::typeClose, $empresaId, $interval, $dateTime);
    $this->createNodeService->createNode($user, self::typeOpen, $empresaId);

    $results[] = $this->createNodeService->resultConfig(self::typeClose, $empresaName, $interval, $time);
    $results[] = $this->createNodeService->resultConfig(self::typeOpen, $empresaName);

    return array(
      '#title' => 'Fichajes',
      '#theme' => 'result_fichar',
      '#results' => $results
    );
  }



  public function twigWarning($connection, $user, $error = false)
  {
    $fichaje = $this->queryService->queryLastFichaje($connection, $user);
    $fichaje['date'] = $this->timeService->formatDate($fichaje['date']);

    return array(
      '#title' => 'Fichar',
      '#theme' => 'warning_fichar',
      '#fichaje' => $fichaje,
      '#error' => $error
    );
  }
}
