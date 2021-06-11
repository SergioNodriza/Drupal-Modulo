<?php
/**
 * @file
 * Contains \Drupal\fichaje_module\Controller\EmpresasController.
 */
namespace Drupal\fichaje_module\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

class EmpresasController extends ControllerBase {

  const typeOpen = 'Entrada';
  const typeClose = 'Salida';

  private $timeService;
  private $queryService;
  private $buttonMakerService;

  public function __construct()
  {
    $this->timeService = Drupal::service('fichaje_module.time_service');
    $this->queryService = Drupal::service('fichaje_module.query_service');
    $this->buttonMakerService = Drupal::service('fichaje_module.button_maker_service');
  }

  public function empresas()
  {
    Drupal::service("router.builder")->rebuild();
    $user = Drupal::currentUser();
    $connection = Database::getConnection();
    $empresasIds = $this->queryService->queryEmpresasIdsByUser($connection, $user);

    $last_fichaje = $this->queryService->queryLastFichaje($connection, $user);
    if ($last_fichaje['type'] === self::typeOpen) {

      $time = $this->timeService->timeDiff($last_fichaje['date'], $last_fichaje['time']);
      $actual = [
        'empresa' => $last_fichaje['empresa'],
        'time' => $time,
        'limit' => $this->queryService->queryJornadaUser($connection, $user->id())['day']
      ];

    } else {
      $actual = [
        'time' => '0',
        'limit' => '1',
      ];
    }

    if ($actual['time'] >= $actual['limit']) {
      $title = ' | Aviso en ' . $actual['empresa'];
    } else {
      $title = '';
    }

    return array(
      '#title' => 'Fichador' . $title,
      '#theme' => 'empresas_fichar',
      '#empresas' => $this->buttonMakerService->makeButtons($empresasIds),
      '#actual' => $actual
    );
  }
}
