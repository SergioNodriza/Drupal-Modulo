<?php

namespace Drupal\fichaje_module\Service;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;

class CreateNodeService {

  /**
   * @throws EntityStorageException
   */
  public function createNode($user, $type, $empresaId, $interval = false, $dateFix = false) {

    if ($dateFix) {
      $date = date('Y-m-d\TH:i:s', strtotime($dateFix)+1);
      $dateInterval = date('Y-m-d\TH:i:s', strtotime($dateFix));
      $dateTitle = date('d-m-Y H:i:s', strtotime($dateFix));
    } else {
      $date = date('Y-m-d\TH:i:s');
      $dateInterval = date('Y-m-d\TH:i:s');
      $dateTitle = date('d-m-Y H:i:s');
    }

    $node = Node::create(['type' => 'hoja_fichaje']);
    $node->set('title', $user->getAccountName() . ' | ' . $dateTitle);
    $node->set('field_user_mark', $user->id());

    if ($interval) {
      $node->set('field_date_mark', $dateInterval);
      $node->set('field_time_diff_mark', $interval);
    } else {
      $node->set('field_date_mark', $date);
      $node->set('field_time_diff_mark', '0');
    }

    $node->set('field_type_mark', $type);
    $node->set('field_empresa_mark', $empresaId);

    $node->save();
  }

  public function resultConfig($type, $empresaName, $interval = false, $dateFix = false)
  {
    if ($dateFix) {
      $date = $dateFix;
    } else {
      $date = date('H:i:s');
    }

    return [
      'type' => $type,
      'empresa' => $empresaName,
      'date' => $date,
      'time' => $interval
    ];
  }
}
