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


  public function warning($empresaNameOpen)
  {
    \Drupal::service("router.builder")->rebuild();
    $user = \Drupal::currentUser();
    $connection = Database::getConnection();

    if (($date = \Drupal::request()->get('date_filter')) &&
        ($time = \Drupal::request()->get('time_filter'))) {

      return $this->fix($connection, $user, $empresaNameOpen, $date, $time);
    }

    return $this->twigWarning($connection, $user);
  }


  public function fix($connection, $user, $empresaNameOpen, $date, $time)
  {
    $dateTime = $date . ' ' . $time;
    $interval = $this->queryService->queryTimeDiff($connection, $user, $dateTime);

    if ($interval === false) {
      return $this->twigWarning($connection, $user, true);
    }

    $empresaNameClose = \Drupal::request()->get('empresaNameClose');
    $empresaIdClose = $this->queryService->queryIdEmpresa($connection, $empresaNameClose);
    $empresaIdOpen = $this->queryService->queryIdEmpresa($connection, $empresaNameOpen);

    $this->createNodeService->createNode($user, self::typeClose, $empresaIdClose, $interval, $dateTime);
    $this->createNodeService->createNode($user, self::typeOpen, $empresaIdOpen);

    $results[] = $this->createNodeService->resultConfig(self::typeClose, $empresaNameClose, $interval, $time);
    $results[] = $this->createNodeService->resultConfig(self::typeOpen, $empresaNameOpen);

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
      '#title' => 'Corrección Fichaje',
      '#theme' => 'warning_fichar',
      '#fichaje' => $fichaje,
      '#error' => $error
    );
  }
}
