<?php

namespace Drupal\fichaje_module\Service;

use Drupal\node\Entity\Node;

class ButtonMakerService {

  public function makeButtons($empresasIds, $back = false)
  {
    $buttons = [];
    foreach ($empresasIds as $empresaId) {
      $node = Node::load($empresaId);

      $buttons[] = [
        'name' => $node->getTitle(),
        'image' => file_create_url($node->field_image->entity->getFileUri()),
      ];
    }

    if ($back) {
      $buttons[] = [
        'name' => 'general',
        'image' => drupal_get_path('module', 'fichaje_module') . '/img/arrow.png'
      ];
    }

    return $buttons;
  }
}
