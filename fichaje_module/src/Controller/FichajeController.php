<?php
/**
 * @file
 * Contains \Drupal\fichaje_module\Controller\FichajeController.
 */
namespace Drupal\fichaje_module\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

class FichajeController extends ControllerBase {

  const typeOpen = 'Entrada';
  const typeClose = 'Salida';

  private $connection;
  private $timeService;
  private $queryService;
  private $createNodeService;

  public function __construct()
  {
    $this->connection = Database::getConnection();
    $this->timeService = Drupal::service('fichaje_module.time_service');
    $this->queryService = Drupal::service('fichaje_module.query_service');
    $this->createNodeService = Drupal::service('fichaje_module.create_node_service');
  }


  public function fichador($empresaName)
  {
    Drupal::service("router.builder")->rebuild();
    $user = Drupal::currentUser();

    $empresaId = $this->queryService->queryIdEmpresa($this->connection, $empresaName);
    $last_fichaje = $this->queryService->queryLastFichaje($this->connection, $user);

    if ($last_fichaje['type'] === self::typeOpen) {

      $interval = $this->timeService->timeDiff($last_fichaje['date']);

      if ($last_fichaje['empresa'] !== $empresaName) {

        $old_empresaID = $this->queryService->queryIdEmpresa($this->connection, $last_fichaje['empresa']);
        $this->createNodeService->createNode($user, self::typeClose, $old_empresaID, $interval);
        $this->createNodeService->createNode($user, self::typeOpen, $empresaId);

        $results[] = $this->createNodeService->resultConfig(self::typeClose, $last_fichaje['empresa'], $interval);
        $results[] = $this->createNodeService->resultConfig(self::typeOpen, $empresaName);

      } else {

        $this->createNodeService->createNode($user, self::typeClose, $empresaId, $interval);
        $results[] = $this->createNodeService->resultConfig(self::typeClose, $empresaName, $interval);
      }

    } else {

      $this->createNodeService->createNode($user, self::typeOpen, $empresaId);
      $results[] = $this->createNodeService->resultConfig(self::typeOpen, $empresaName);
    }

    return array(
      '#title' => 'Fichajes',
      '#theme' => 'result_fichar',
      '#results' => $results
    );
  }
}
