<?php
/**
 * @file
 * Contains \Drupal\fichaje_module\Controller\FichajeController.
 */
namespace Drupal\fichaje_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageException;

class FichajeController extends ControllerBase {

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

  /**
   * @throws EntityStorageException
   */
  public function fichador($empresaName)
  {
    \Drupal::service("router.builder")->rebuild();
    $user = \Drupal::currentUser();
    $connection = Database::getConnection();


    if (\Drupal::request()->get('date_filter')) {

      $date = \Drupal::request()->get('date_filter');
      $time = \Drupal::request()->get('time_filter');
      $dateTime = $date . ' ' . $time;

      $empresaId = $this->queryService->queryIdEmpresa($connection, $empresaName);
      $interval = $this->queryService->queryTimeDiff($connection, $user, $dateTime);

      $this->createNodeService->createNode($user, self::typeClose, $empresaId, $interval);
      $this->createNodeService->createNode($user, self::typeOpen, $empresaId);

      $results[] = $this->resultConfig($empresaName, $interval);
      $results[] = $this->resultConfig($empresaName);

      return array(
        '#title' => 'Fichajes',
        '#theme' => 'fichajes_list',
        '#results' => $results
      );
    }


    if (\Drupal::request()->get('warning')) {

      $fichaje = $this->queryService->queryLastFichaje($connection, $user);
      $fichaje['date'] = $this->timeService->formatDate($fichaje['date']);

      return array(
        '#title' => 'Fichar',
        '#theme' => 'fichar',
        '#fichaje' => $fichaje
      );

    }


    $empresaId = $this->queryService->queryIdEmpresa($connection, $empresaName);
    $last_fichaje = $this->queryService->queryLastFichaje($connection, $user);

    if ($last_fichaje['type'] === self::typeOpen) {

      $interval = $this->queryService->queryTimeDiff($connection, $user);

      if ($last_fichaje['name'] !== $empresaName) {

        $old_empresaID = $this->queryService->queryIdEmpresa($connection, $last_fichaje['name']);
        $this->createNodeService->createNode($user, self::typeClose, $old_empresaID, $interval);
        $this->createNodeService->createNode($user, self::typeOpen, $empresaId);

        $results[] = $this->resultConfig($last_fichaje['name'], $interval);
        $results[] = $this->resultConfig($empresaName);

      } else {

        $this->createNodeService->createNode($user, self::typeClose, $empresaId, $interval);
        $results[] = $this->resultConfig($empresaName, $interval);
      }

    } else {

      $this->createNodeService->createNode($user, self::typeOpen, $empresaId);
      $results[] = $this->resultConfig($empresaName);
    }

    return array(
      '#title' => 'Fichajes',
      '#theme' => 'fichajes_list',
      '#results' => $results
    );
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
