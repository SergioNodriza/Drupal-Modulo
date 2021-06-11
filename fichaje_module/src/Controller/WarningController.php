<?php
/**
 * @file
 * Contains \Drupal\fichaje_module\Controller\FichajeController.
 */
namespace Drupal\fichaje_module\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

class WarningController extends ControllerBase {

  const typeOpen = 'Entrada';
  const typeClose = 'Salida';

  private $connection;
  private $queryService;
  private $createNodeService;
  private $timeService;

  public function __construct()
  {
    $this->connection = Database::getConnection();
    $this->queryService = Drupal::service('fichaje_module.query_service');
    $this->createNodeService = Drupal::service('fichaje_module.create_node_service');
    $this->timeService = Drupal::service('fichaje_module.time_service');
  }


  public function warning($empresaNameOpen)
  {
    Drupal::service("router.builder")->rebuild();
    $user = Drupal::currentUser();

    if (($date = Drupal::request()->get('date_filter')) &&
        ($time = Drupal::request()->get('time_filter'))) {

      return $this->fix($user, $empresaNameOpen, $date, $time);
    }

    return $this->twigWarning($user);
  }


  public function fix($user, $empresaNameOpen, $date, $time)
  {
    $dateTime = $date . ' ' . $time;
    $last_fichaje = $this->queryService->queryLastFichaje($this->connection, $user);
    $interval = $this->timeService->timeDiff($last_fichaje['date'], $dateTime);

    if ($interval === false) {
      return $this->twigWarning($user, true);
    }

    $empresaNameClose = Drupal::request()->get('empresaNameClose');
    $empresaIdClose = $this->queryService->queryIdEmpresa($this->connection, $empresaNameClose);
    $empresaIdOpen = $this->queryService->queryIdEmpresa($this->connection, $empresaNameOpen);

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



  public function twigWarning($user, $error = false)
  {
    $fichaje = $this->queryService->queryLastFichaje($this->connection, $user);

    if ($fichaje['type'] === self::typeClose) {
      return array(
        '#title' => 'Correcto',
        '#theme' => 'no_warning_fichar'
      );
    }

    $fichaje['date'] = $this->timeService->formatDate($fichaje['date']);

    return array(
      '#title' => 'Corrección Fichaje',
      '#theme' => 'warning_fichar',
      '#fichaje' => $fichaje,
      '#error' => $error
    );
  }
}
