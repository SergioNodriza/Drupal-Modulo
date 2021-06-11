<?php

namespace Drupal\fichaje_module\Form;


use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

class UserForm extends FormBase {

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
    return 'new_user_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['email'] = [
      '#type' => 'textfield',
      '#title' => 'Dirección de Correo Electrónico',
      '#required' => true
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => 'Nombre de Usuario',
      '#required' => true
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => 'Contraseña',
      '#required' => true
    ];

    $form['confirmPassword'] = [
      '#type' => 'password',
      '#title' => 'Confirmar Contraseña',
      '#required' => true
    ];

    $form['status'] = [
      '#type' => 'radios',
      '#options' => array('active' => 'Activo', 'blocked' => 'Bloqueado'),
      '#default_value' => 'active',
      '#title' => 'Estado'
    ];

    $form['roles'] = [
      '#type' => 'radios',
      '#options' => array('user' => 'Usuario autenticado', 'admin' => 'Administrador'),
      '#default_value' => 'user',
      '#title' => 'Roles'
    ];

    $form['notify'] = [
      '#type' => 'checkboxes',
      '#options' => array('notify' => 'Notificar al usuario acerca de su nueva cuenta'),
      '#default_value' => array('notify'),
      '#title' => 'Notificar'
    ];

    $config = $this->config('new_user_form.settings');
    $allowed_ext = 'jpg png';
    $max_upload = 25600000;

    $form['imagen'] = [
      '#type' => 'managed_file',
      '#title' => 'Imagen',
      '#description' => 'Permitidos: @allowed_ext', ['@allowed_ext' => $allowed_ext],
      '#upload_location' => sprintf("public://users/%s", date('Y-m')),
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
    ];

    $form['empresas'] = [
      '#type' => 'checkboxes',
      '#options' => $this->empresasOptions(),
      '#title' => 'Empresas',
      '#required' => true
    ];

    $form['hours_day'] = [
      '#type' => 'textfield',
      '#title' => 'Horas día',
      '#default_value' => '08:00:00',
      '#description' => 'Formato: HH:ii:ss',
      '#required' => true
    ];

    $form['hours_week'] = [
      '#type' => 'textfield',
      '#title' => 'Horas Semana',
      '#default_value' => '40:00:00',
      '#description' => 'Formato: HH:ii:ss',
      '#required' => true
    ];
//
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

    $fid = $form_state->getValue('imagen');

    if (!$fid) {

      $email = $form_state->getValue('email');
      $name = $form_state->getValue('name');

      $password = $form_state->getValue('password');
      $confirmPassword = $form_state->getValue('confirmPassword');

      $hoursDay = $form_state->getValue('hours_day');
      $hoursWeek = $form_state->getValue('hours_week');

      if ($this->checkNameAndEmail($name, $email)) {
        $form_state->setErrorByName('email','Ya existe esta correo con este nombre');
      }

      if ($this->checkEmail($email)) {
        $form_state->setErrorByName('email', 'Formato de correo incorrecto');
      }

      if ($password !== $confirmPassword) {
        $form_state->setErrorByName('confirmPassword','La contraseña no coincide');
      }

      [$hoursDay, $minutesDay, $secondsDay] = explode(':', $hoursDay);
      if (strlen($hoursDay) !== 2 || strlen($minutesDay) !== 2 || strlen($secondsDay) !== 2){
        $form_state->setErrorByName('hours_day','Formato de horas incorrecto');
      }

      [$hours, $minutes, $seconds] = explode(':', $hoursWeek);
      if (strlen($hours) !== 2 || strlen($minutes) !== 2 || strlen($seconds) !== 2){
        $form_state->setErrorByName('hours_week','Formato de horas incorrecto');
      }

    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $email = $form_state->getValue('email');
    $name = $form_state->getValue('name');
    $password = $form_state->getValue('password');

    $status = $form_state->getValue('status');
    $roles = $form_state->getValue('roles');

    $notify = $form_state->getValue('notify');
    $fid = $form_state->getValue('imagen');

    $empresas = $form_state->getValue('empresas');
    $checkedEmpesas = $this->checkedEmpresas($empresas);

    $hours_day = $form_state->getValue('hours_day');
    $hours_week = $form_state->getValue('hours_week');

    $user = Drupal\user\Entity\User::create();
    $user->enforceIsNew();
    $user->setEmail($email);
    $user->setUsername($name);
    $user->setPassword($password);

    if ($status === 'active') {
      $user->activate();
    } else {
      $user->block();
    }

    if ($roles === 'admin') {
      $user->addRole('administrator');
    }

    foreach($checkedEmpesas as $checkedEmpesa) {
      $user->get('field_empresas_user')->appendItem(['target_id' => $checkedEmpesa]);
    }

    $user->set('field_hours_day', ['value' => $hours_day, 'format' => 'basic_html']);
    $user->set('field_hours_week', ['value' => $hours_week, 'format' => 'basic_html']);

    $user->set('user_picture', $fid);

    $user->save();

    if ($notify['notify'] !== 0 ) {
      _user_mail_notify('register_admin_created', $user);
    }

    Drupal::messenger()->addMessage('Usuario Creado');
  }

  public function empresasOptions() {

    $array = array();
    $empresas = $this->queryService->queryEmpresasInfo($this->connection);
    foreach ($empresas as $empresa) {
      $array[$empresa['id']] = $empresa['name'];
    }

    return $array;
  }
  public function checkedEmpresas($empresas) {

    $checked = [];
    foreach ($empresas as $empresa) {

      if ($empresa !== 0) {
        $checked[] = $empresa;
      }
    }

    return $checked;
  }
  public function checkEmail($email) {
    return !filter_var($email, FILTER_VALIDATE_EMAIL);
  }
  public function checkNameAndEmail($name, $email) {
    return $this->queryService->queryUserIdByNameAndEmail($this->connection, $name, $email);
  }
}
