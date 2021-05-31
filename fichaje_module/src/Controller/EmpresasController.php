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

    return array(
      '#theme' => 'empresas_list',
      '#results' => $this->buttonMakerService->makeButtons($empresasIds)
    );
  }
}