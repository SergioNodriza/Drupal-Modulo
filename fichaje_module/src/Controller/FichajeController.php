<?php
/**
 * @file
 * Contains \Drupal\fichaje_module\Controller\FichajeController.
 */
namespace Drupal\fichaje_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;

class FichajeController extends ControllerBase {

  const typeOpen = 'Entrada';
  const typeClose = 'Salida';

  private $queryService;

  public function __construct()
  {
    $this->queryService = \Drupal::service('fichaje_module.query_service');
  }

  /**
   * @throws EntityStorageException
   */
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
        $this->createNode($user, self::typeClose, $old_empresaID, $interval);
        $this->createNode($user, self::typeOpen, $empresaId);

        $results[] = $this->resultConfig($last_fichaje['name'], $interval);
        $results[] = $this->resultConfig($empresaName);

      } else {

        $this->createNode($user, self::typeClose, $empresaId, $interval);
        $results[] = $this->resultConfig($empresaName, $interval);
      }

    } else {

      $this->createNode($user, self::typeOpen, $empresaId);
      $results[] = $this->resultConfig($empresaName);
    }

    return array(
      '#theme' => 'fichajes_list',
      '#results' => $results
    );
  }

  /**
   * @throws EntityStorageException
   */
  public function createNode($user, $type, $empresaId, $interval = false) {

    $node = Node::create(['type' => 'hoja_fichaje']);
    $node->set('title', $user->getAccountName() . ' | ' . date('d-m-Y H:i:s'));
    $node->set('field_user_mark', $user->id());

    if ($interval) {
      $node->set('field_date_mark', date('Y-m-d\TH:i:s'));
      $node->set('field_time_diff_mark', $interval);
    } else {
      $node->set('field_date_mark', date('Y-m-d\TH:i:s', strtotime("+1 sec")));
    }

    $node->set('field_type_mark', $type);
    $node->set('field_empresa_mark', $empresaId);
    $node->save();
  }

  public function resultConfig($empresaName, $interval = false)
  {
    $date = date('H:i:s');

    if ($interval) {

      return [
        'type' => self::typeClose,
        'empresa' => $empresaName,
        'date' => $date,
        'time' => $interval
      ];
    }

    return [
      'type' => self::typeOpen,
      'empresa' => $empresaName,
      'date' => $date
    ];
  }
}
