<?php

namespace Drupal\fichaje_module\Service;

use Drupal\node\Entity\Node;

class ButtonMakerService {

  public function makeButtons($empresasIds)
  {
    $buttons = [];
    foreach ($empresasIds as $empresaId) {
      $node = Node::load($empresaId);

      $buttons[] = [
        'name' => $node->getTitle(),
        'image' => file_create_url($node->field_image->entity->getFileUri()),
        'link' => $node->field_link_empresa->getValue()[0]['value'],
      ];
    }

    return $buttons;
  }

  public function makeButtonsParte($empresasIds, $back = false)
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
        'name' => 'General',
        'image' => file_create_url("public://2021-05/arrow.png"),
      ];
    }

    return $buttons;
  }
}
