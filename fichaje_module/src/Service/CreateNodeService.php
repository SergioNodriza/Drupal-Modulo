<?php

namespace Drupal\fichaje_module\Service;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;

class CreateNodeService {

  /**
   * @throws EntityStorageException
   */
  public function createNode($user, $type, $empresaId, $interval = false, $cron = false) {

    $node = Node::create(['type' => 'hoja_fichaje']);
    $node->set('title', $user->getAccountName() . ' | ' . date('d-m-Y H:i:s'));
    $node->set('field_user_mark', $user->id());

    if ($interval) {
      $node->set('field_date_mark', date('Y-m-d\TH:i:s'));
      $node->set('field_time_diff_mark', $interval);
    } else {
      $node->set('field_date_mark', date('Y-m-d\TH:i:s', strtotime("+1 sec")));
      $node->set('field_time_diff_mark', '0');
    }

    $node->set('field_type_mark', $type);
    $node->set('field_empresa_mark', $empresaId);

    if ($cron) {
      $node->setOwner($cron);
    }

    $node->save();
  }
}
