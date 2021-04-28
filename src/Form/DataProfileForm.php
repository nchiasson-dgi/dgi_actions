<?php

namespace Drupal\dgi_actions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigSplitEntityForm.
 *
 * @package Drupal\dgi_actions\Form
 */
class DataProfileForm extends EntityForm {

  /**
   * The drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal\Core\Extension\ThemeHandler definition.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The Drupal Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The Drupal Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The Drupal Entity Type Bundle Info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The drupal config factory.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The drupal core entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundleInfo
   *   The drupal core entity type bundle info.
   */
  public function __construct(StateInterface $state, ThemeHandlerInterface $themeHandler, ConfigFactory $configFactory, EntityFieldManager $entityFieldManager, EntityTypeBundleInfo $entityTypeBundleInfo) {
    $this->state = $state;
    $this->themeHandler = $themeHandler;
    $this->configFactory = $configFactory;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): DataProfileForm {
    return new static(
      $container->get('state'),
      $container->get('theme_handler'),
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $config = $this->entity;

    $data_profile_array = $this->dataprofileLists();
    $data_profile_configs = $data_profile_array['data_profile_configs'];
    $data_profile_options = $data_profile_array['data_profile_options'];

    $entity_array = $this->entityDropdownList();
    $entity_bundles = $entity_array['entity_bundles'];
    $entity_options = $entity_array['entity_options'];

    $entity_bundle_array = $this->bundleDropdownList($entity_bundles);
    $entity_bundle_fields = $entity_bundle_array['entity_bundle_fields'];
    $bundle_options = $entity_bundle_array['bundle_options'];

    $selected = [
      'entity' => '',
      'bundle' => '',
      'dataprofile' => '',
    ];

    foreach ($selected as $selected_key => $selected_value) {
      // Check if the previous/currently set Entity value is a valid selection
      // If not, unset and make the user re-select.
      if (empty($form_state->getValue($selected_key))) {
        if ($config->get($selected_key)) {
          $selected[$selected_key] = $config->get($selected_key);
        }
        else {
          $selected[$selected_key] = '';
        }
      }
      else {
        if ($selected_key == 'bundle') {
          $bundle_value = $form_state->getValue('bundle');
          $selected['bundle'] = ($bundle_value && isset($bundle_options[$selected['entity']][$bundle_value])) ? $bundle_value : '';
        }
        else {
          $selected[$selected_key] = $form_state->getValue($selected_key);
        }
      }
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $config->label(),
      '#description' => $this->t("Label for the Identifier setting."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $config->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dgi_actions\Entity\DataProfile::load',
      ],
    ];
    $form['entity_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity Selection'),
    ];

    // Entity Fieldset Reference.
    $entity_fieldset =& $form['entity_fieldset'];
    $entity_fieldset['entity'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($selected['entity']) ?: NULL,
      '#options' => $entity_options,
      '#description' => $this->t('The entity that the data will be captured.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::entityDropdownCallback',
        'wrapper' => 'bundle-fieldset-container',
      ],
    ];
    $entity_fieldset['choose_entity'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose Entity'),
      '#states' => [
        'visible' => ['body' => ['value' => TRUE]],
      ],
    ];
    $form['bundle_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bundle-fieldset-container'],
    ];
    $form['bundle_fieldset_container']['bundle_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bundle Selection'),
    ];

    // Bundle Fieldset Reference.
    $bundle_fieldset =& $form['bundle_fieldset_container']['bundle_fieldset'];
    $bundle_fieldset['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($selected['bundle']) ?: NULL,
      '#options' => (isset($bundle_options[$selected['entity']])) ? $bundle_options[$selected['entity']] : [],
      '#description' => $this->t('The Bundle of the selected Entity Type.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::bundleDropdownCallback',
        'wrapper' => 'dataprofile-fieldset-container',
      ],
    ];
    $bundle_fieldset['choose_bundle'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose Bundle'),
      '#states' => [
        'visible' => [':input[name="bundle"]' => ['value' => TRUE]],
      ],
    ];
    $form['bundle_fieldset_container']['dataprofile_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'dataprofile-fieldset-container'],
    ];
    $form['bundle_fieldset_container']['dataprofile_fieldset_container']['dataprofile_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Data Profile Selection'),
    ];

    // Data Profile Fieldset Reference.
    $dataprofile_fieldset =& $form['bundle_fieldset_container']['dataprofile_fieldset_container']['dataprofile_fieldset'];
    $dataprofile_fieldset['dataprofile'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Profile Type'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => ($selected['dataprofile']) ?: NULL,
      '#options' => ($selected['entity'] && $selected['bundle']) ? $data_profile_options : [],
      '#description' => $this->t('The Data Profile type to be used for the Data Profile Config'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::dataprofileFieldsDropdownCallback',
        'wrapper' => 'dataprofile-fields-fieldset-container',
      ],
    ];
    $dataprofile_fieldset['choose_dataprofile'] = [
      '#type' => 'submit',
      '#value' => $this->t('Choose Data Profile'),
      '#states' => [
        'visible' => [':input[name="dataprofile"]' => ['value' => TRUE]],
      ],
    ];
    $dataprofile_fieldset['dataprofile_fields_fieldset_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'dataprofile-fields-fieldset-container'],
    ];

    if (isset($data_profile_configs[$selected['dataprofile']])) {
      $dataprofile_fieldset['dataprofile_fields_fieldset_container']['dataprofile_fields_fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Data Profile Fields'),
      ];

      // Data Profile Fields Fieldset Reference.
      $dataprofile_fields_fieldset =& $dataprofile_fieldset['dataprofile_fields_fieldset_container']['dataprofile_fields_fieldset'];
      $fields = $data_profile_configs[$selected['dataprofile']]->get('fields');
      foreach ($fields as $field) {
        $field_key = str_replace('.', '_', $field['key']);
        $dataprofile_fields_fieldset[$field_key] = [
          '#type' => 'select',
          '#title' => $field['label'],
          '#empty_option' => $this->t('- None -'),
          '#default_value' => ($config->get('data')[$field_key]) ?: NULL,
          '#options' => ($selected['entity'] && $selected['bundle']) ? $entity_bundle_fields[$selected['entity']][$selected['bundle']] : [],
          '#description' => $field['description'],
        ];
      }
    }

    if (!$selected['entity']) {
      // Change the field title to provide user with some feedback on why the
      // field is disabled.
      $bundle_fieldset['#access'] = FALSE;
      $bundle_fieldset['#disabled'] = TRUE;
      $bundle_fieldset['bundle']['#title'] = $this->t('You must choose an Entity first.');
      $bundle_fieldset['bundle']['#disabled'] = TRUE;
      $bundle_fieldset['choose_bundle']['#access'] = FALSE;
      $bundle_fieldset['choose_bundle']['#disabled'] = TRUE;
    }

    if (!$selected['bundle']) {
      // Change the field title to provide user with some feedback on why the
      // field is disabled.
      $dataprofile_fieldset['#access'] = FALSE;
      $dataprofile_fieldset['#disabled'] = TRUE;
      $dataprofile_fieldset['dataprofile']['#title'] = $this->t('You must choose a Bundle first.');
      $dataprofile_fieldset['dataprofile']['#disabled'] = TRUE;
      $dataprofile_fieldset['choose_dataprofile']['#disabled'] = TRUE;
    }

    if (!$selected['dataprofile']) {
      $dataprofile_fields_fieldset['#access'] = FALSE;
      $dataprofile_fields_fieldset['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * Entity Dropdown AJAX Callback function.
   */
  public function entityDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['bundle_fieldset_container'];
  }

  /**
   * Bundle Dropdown AJAX Callback function.
   */
  public function bundleDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['bundle_fieldset_container']['dataprofile_fieldset_container'];
  }

  /**
   * Data Profile Dropdown AJAX Callback function.
   */
  public function dataprofileFieldsDropdownCallback(array $form, FormStateInterface $form_state) {
    return $form['bundle_fieldset_container']['dataprofile_fieldset_container']['dataprofile_fieldset']['dataprofile_fields_fieldset_container'];
  }

  /**
   * Helper function to build Data Profile Lists.
   *
   * @return array
   *   Returns available Data Profile configs and options.
   */
  public function dataprofileLists(): array {
    $list = $this->configFactory->listAll('dgi_actions.data_profile_type');

    $returns = [];
    foreach ($list as $config_id) {
      $config = $this->configFactory->get($config_id);
      $returns['data_profile_configs'][$config_id] = $config;
      $returns['data_profile_options'][$config->getName()] = $config->get('label');
    }

    return $returns;
  }

  /**
   * Helper function to build Entity Lists.
   *
   * @return array
   *   Returns Entity bundles and options.
   */
  public function entityDropdownList(): array {
    $field_map = $this->entityFieldManager->getFieldMap();

    // Building Entity Bundle List and Options.
    $returns = [];
    foreach (array_keys($field_map) as $entity_key) {
      $returns['entity_bundles'][$entity_key] = $this->entityTypeBundleInfo->getBundleInfo($entity_key);
      $returns['entity_options'][$entity_key] = $entity_key;
    }

    return $returns;
  }

  /**
   * Helper function to build Bundle Lists.
   *
   * @return array
   *   Returns bundle fields and options.
   */
  public function bundleDropdownList($entity_bundles = []): array {
    $returns = [];
    foreach ($entity_bundles as $entity => $bundles) {
      foreach ($bundles as $bundle => $bundle_data) {
        $fields = $this->entityFieldManager->getFieldDefinitions($entity, $bundle);
        $returns['entity_bundle_fields'][$entity][$bundle] = array_combine(array_keys($fields), array_keys($fields));
        $returns['bundle_options'][$entity][$bundle] = $bundle_data['label'];
      }
    }

    return $returns;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $trigger = (string) $form_state->getTriggeringElement()['#value'];
    if (($trigger) == 'Save') {
      $this->setDataprofileDataFields($form_state);
    }
    else {
      $form_state->setRebuild();
    }
  }

  /**
   * A helper function to set the Data Profile fields.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState entity.
   */
  public function setDataprofileDataFields(FormStateInterface $form_state) {
    $data_profile_data = self::dataprofileLists();
    $config =& $this->entity;

    $fields = $data_profile_data['data_profile_configs'][$config->getDataprofile()]->get('fields');
    $data = [];
    foreach ($fields as $field) {
      $form_key = str_replace('.', '_', $field['key']);
      if (!empty($form_state->getValue($form_key))) {
        $data[$form_key] = [
          'key' => $field['key'],
          'source_field' => $form_state->getValue($form_key),
        ];
      }
    }

    // Clearing the data in case there was a different
    // Data Profile with data set previously.
    $config->setData([]);
    $config->setData($data);
  }

  /**
   * Filter text input for valid configuration names (including wildcards).
   *
   * @param string|string[] $text
   *   The configuration names, one name per line.
   *
   * @return string[]
   *   The array of configuration names.
   */
  protected function filterConfigNames($text): array {
    if (!is_array($text)) {
      $text = explode("\n", $text);
    }

    foreach ($text as &$config_entry) {
      $config_entry = strtolower($config_entry);
    }

    // Filter out illegal characters.
    return array_filter(preg_replace('/[^a-z0-9_\.\-\*]+/', '', $text));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $dataprofile = $this->entity;
    $status = $dataprofile->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Data Profile setting.', [
          '%label' => $dataprofile->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Data Profile setting.', [
          '%label' => $dataprofile->label(),
        ]));
    }
    $form_state->setRedirectUrl($dataprofile->toUrl('collection'));
  }

}
