<?php
/**
 * @file
 * Contains \Drupal\fichaje_module\Controller\FichajeController.
 */
namespace Drupal\fichaje_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

class FichajeController extends ControllerBase {

  const typeOpen = 'Entrada';
  const typeClose = 'Salida';

  private $queryService;
  private $createNodeService;

  public function __construct()
  {
    $this->queryService = \Drupal::service('fichaje_module.query_service');
    $this->createNodeService = \Drupal::service('fichaje_module.create_node_service');
  }


  public function fichador($empresaName)
  {
    \Drupal::service("router.builder")->rebuild();
    $user = \Drupal::currentUser();
    $connection = Database::getConnection();

    $empresaId = $this->queryService->queryIdEmpresa($connection, $empresaName);
    $last_fichaje = $this->queryService->queryLastFichaje($connection, $user);

    if ($last_fichaje['type'] === self::typeOpen) {

      $interval = $this->queryService->queryTimeDiff($connection, $user);

      if ($last_fichaje['name'] !== $empresaName) {

        $old_empresaID = $this->queryService->queryIdEmpresa($connection, $last_fichaje['name']);
        $this->createNodeService->createNode($user, self::typeClose, $old_empresaID, $interval);
        $this->createNodeService->createNode($user, self::typeOpen, $empresaId);

        $results[] = $this->createNodeService->resultConfig(self::typeClose, $last_fichaje['name'], $interval);
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
