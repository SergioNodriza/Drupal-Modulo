<?php
/**
 * @file
 * Contains \Drupal\fichaje_module\Controller\EmpresasController.
 */
namespace Drupal\fichaje_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

class EmpresasController extends ControllerBase {

  const typeOpen = 'Entrada';
  const typeClose = 'Salida';

  private $queryService;
  private $buttonMakerService;

  public function __construct()
  {
    $this->queryService = \Drupal::service('fichaje_module.query_service');
    $this->buttonMakerService = \Drupal::service('fichaje_module.button_maker_service');
  }

  public function empresas()
  {
    \Drupal::service("router.builder")->rebuild();
    $connection = Database::getConnection();
    $empresasIds = $this->queryService->queryEmpresasIds($connection);


    $last_fichaje = $this->queryService->queryLastFichaje($connection, \Drupal::currentUser());
    if ($last_fichaje['type'] === self::typeOpen) {

      $actual = [
        'name' => $last_fichaje['name'],
        'type' => $last_fichaje['time']
      ];

    } else {
      $actual = null;
    }

    dd($this->buttonMakerService->makeButtons($empresasIds), $actual);

    return array(
      '#theme' => 'empresas_list',
      '#results' => $this->buttonMakerService->makeButtons($empresasIds),
      '#actual' => $actual
    );
  }
}
