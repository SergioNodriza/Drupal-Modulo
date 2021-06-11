<?php

namespace Drupal\fichaje_module\Form;


use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;

class EmpresaForm extends FormBase {

  private $connection;
  private $queryService;

  public function __construct()
  {
    $this->connection = Database::getConnection();
    $this->queryService = Drupal::service('fichaje_module.query_service');
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'new_empresa_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => 'Nombre de la Empresa',
      '#required' => true
    ];

    $form['website'] = [
      '#type' => 'textfield',
      '#title' => 'Página Web de la Empresa',
      '#description' => 'Formato: url externa',
      '#required' => true
    ];

    $form['telefono'] = [
      '#type' => 'number',
      '#title' => 'Teléfono de la Empresa',
      '#required' => true
    ];

    $config = $this->config('new_empresa_form.settings');
    $allowed_ext = 'jpg png';
    $max_upload = 25600000;

    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => 'Imagen',
      '#description' => 'Permitidos: @allowed_ext', ['@allowed_ext' => $allowed_ext],
      '#upload_location' => sprintf("public://empresas/%s", date('Y-m')),
      '#multiple' => FALSE,
      '#default_value' => $config->get('image'),
      '#upload_validators' => [
        'file_validate_extensions' => [
          $allowed_ext,
        ],
        'file_validate_size' => [
          $max_upload,
        ],
      ],
      '#required' => true
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Crear'
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $fid = $form_state->getValue('image');

    if (!$fid) {

      $name = $form_state->getValue('title');
      $website = $form_state->getValue('website');

      if ($this->checkName($name)) {
        $form_state->setErrorByName('title','Ya existe este nombre');
      }

      if ($this->checkWebsite($website)) {
        $form_state->setErrorByName('website','Formato de url incorrecto');
      }

    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $name = $form_state->getValue('title');
    $name = str_replace(' ', '', $name);
    $website = $form_state->getValue('website');
    $telefono = $form_state->getValue('telefono');
    $fid = $form_state->getValue('image');

    $node = Node::create(['type' => 'empresa']);
    $node->setTitle($name);
    $node->set('field_website', ['uri' => $website, 'title' => '', 'options' => []]);
    $node->set('field_telefono', ['value' => $telefono]);
    $node->set('field_image', ['target_id' => $fid[0], 'alt' => $name . '_image']);
    $node->save();

    Drupal::messenger()->addMessage('Empresa Creada');
  }

  public function checkName($name) {
    return $this->queryService->queryIdEmpresa($this->connection, $name);
  }
  public function checkWebsite($website) {
    return !filter_var($website, FILTER_VALIDATE_URL);
  }
}
