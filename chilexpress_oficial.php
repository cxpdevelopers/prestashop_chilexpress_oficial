<?php

if (!defined('_PS_VERSION_'))
    exit();

include_once(dirname( __FILE__ )  . '/chilexpress_oficial_api.php');
include_once(dirname( __FILE__ )  . '/controllers/admin/ChilexpressOrderController.php');

class Chilexpress_Oficial extends CarrierModule // extends Module // 
{
    public $id_carrier;

    protected $_errors = array();

    public function __construct()
    {
        $this->name = 'chilexpress_oficial';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.4';
        $this->author = 'Chilexpress';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);
        $this->limited_countries = array('cl');
        $this->bootstrap = true;

        $this->carriers_list = array(
            'CHILEXPRESS_OFCL_DHS' => 'Dia Habil Siguiente',
            //'CHILEXPRESS_OFCL_DHSS' => 'Dia Habil Subsiguiente',
            //'CHILEXPRESS_OFCL_3DH' => 'Tercer dia Hábil'
        );

        $this->carriers_serviceTypeCode = array(
            'CHILEXPRESS_OFCL_DHS' => 3,
            //'CHILEXPRESS_OFCL_DHSS' => 4,
            //'CHILEXPRESS_OFCL_3DH' => 5
        );

        $this->api = new Chilexpress_Oficial_API();

        parent::__construct();

        $this->displayName = $this->l('Chilexpress Oficial', 'chilexpress_oficial');
        $this->description = $this->l('Agrega soporte oficial para Chilexpress en Prestashop agregando soporte de calculo de costos de envio y generación de OTs.', 'chilexpress_oficial');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?', 'chilexpress_oficial');
    }


    public function installTab(){
        $id_tab = (int)Tab::getIdFromClassName('ChilexpressOrder');
        if (!$id_tab) {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = 'ChilexpressOrder';
            $tab->name = array();
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = "Chilexpress";
            }
            $tab->id_parent = (int) Tab::getIdFromClassName('AdminParentShipping');
            $tab->module = $this->name;
            $tab->visible = false;
            return $tab->add();
        } else {
            return true;
        }
    }

    public function uninstallTab(){
        $id_tab = (int)Tab::getIdFromClassName('ChilexpressOrder');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        } else {
            return false;
        }
    }

    public function install()
    {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        if (!parent::install())
            return false;

        // Execute module install SQL statements
        $sql_file = dirname(__FILE__).'/install/install.sql';
        if (!$this->loadSQLFile($sql_file))
            return false;


        return
            $this->checkAndAddChileanStatesOnInstall() &&
            $this->installTab() &&
            $this->installCarriers() &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayBackOfficeTop') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('updateCarrier') &&
            $this->registerHook('actionAdminCustomersListingFieldsModifier') &&
            $this->registerHook('actionAdminOrdersListingFieldsModifier') &&
            $this->registerHook('displayAdminOrderContentShip') &&
            Configuration::updateValue('chilexpress_oficial_url', 'wlsdMpnDBn8') && 
            Configuration::updateValue('api_key_georeferencia_value', '134b01b545bc4fb29a994cddedca9379') && 
            Configuration::updateValue('api_key_generacion_ot_value', '0112f48125034f8fa42aef2441773793') && 
            Configuration::updateValue('api_key_cotizador_value', 'fd46aa18a9fe44c6b49626692605a2e8') && 
            Configuration::updateValue('api_key_georeferencia_enabled', true) && 
            Configuration::updateValue('api_key_generacion_ot_enabled', true) && 
            Configuration::updateValue('api_key_cotizador_enabled', true) && 
            Configuration::updateValue('ambiente', 'staging') && 
            Configuration::updateValue('region_origen', 'RM') && 
            Configuration::updateValue('comuna_origen', 'SANT') && 
            Configuration::updateValue('numero_tcc_origen', '18578680') && 
            Configuration::updateValue('rut_marketplace_remitente', '96756430') && 
            Configuration::updateValue('region_devolucion', 'RM') && 
            Configuration::updateValue('comuna_devolucion', 'STGO');
    }
    public function hookActionAdminControllerSetMedia()
    {
        
        if (!Module::isEnabled($this->name)) {
            return;
        }   
        
        // Necesitamos activar que modulos en especifico contendran nuestro js
        $controller = Dispatcher::getInstance()->getController();
        if ($controller == 'AdminModules' || $controller == 'AdminOrders' || $controller == 'adminaddresses') {
            if (method_exists($this->context->controller, 'addJquery')) {
                $this->context->controller->addJquery();
            }
            $this->context->controller->addJs($this->_path.'views/js/'.$this->name.'.js');
        }
    }

   

    public function uninstall()
    {
        if (!parent::uninstall())
        {
            return false;
        }

        // Execute module install SQL statements
        $sql_file = dirname(__FILE__).'/install/uninstall.sql';
        if (!$this->loadSQLFile($sql_file))
        {
            return false;
        }

        if (!$this->uninstallTab())
        {
            return false;
        }
        
        return true;
    }

    public function postProcess()
    {
        return parent::postProcess();
    }

    public function displayForm()
    {        

        $this->checkAndAddChileanStatesOnInstall();
        // $this->installTab();
        // die();
        // < init fields for form array >
        $regiones_query = array();
        $comunas_origen_query = array();
        $comunas_devolucion_query = array();
        $regiones = $this->api->obtener_regiones();
        foreach($regiones as $region_id => $region_nombre) {
            $regiones_query[] = array('id' => $region_id, 'name' => $region_nombre);
        }
        $ro = Configuration::get('region_origen');
        $rd = Configuration::get('region_devolucion');

        $comunas_origen = $this->api->obtener_comunas_desde_region(isset($ro)?$ro:'R1');
        $comunas_devolucion = $this->api->obtener_comunas_desde_region(isset($rd)?$rd:'R1');
        
        foreach($comunas_origen as $comuna_id => $comuna_nombre) {
            $comunas_origen_query[] = array('id' => $comuna_id, 'name' => $comuna_nombre);
        }
        foreach($comunas_devolucion as $comuna_id => $comuna_nombre) {
            $comunas_devolucion_query[] = array('id' => $comuna_id, 'name' => $comuna_nombre);
        }
        
        $fields_form = array(
                array(
                    'form' => array(
                        'id_form' => 'modules_chilexpress',
                        'legend' => array(
                            'title' => $this->l('Habilitación de Módulos')
                        ),
                        'input' => array(
                            array(
                                'type' => 'switch',
                                'label' => $this->trans(
                                    'Habilitar módulo de Georeferencia',
                                    [],
                                    'Modules.Contactform.Admin'
                                ),
                                'desc' => $this->trans(
                                    "Necesitas este módulo para poder obtener información actualizada de Regiones y Comunas, crea tu API KEY en https://developers.wschilexpress.com/products/georeference/subscribe",
                                    [],
                                    'Modules.Contactform.Admin'
                                ),
                                'name' => 'api_key_georeferencia_enabled',
                                'is_bool' => true,
                                'required' => true,
                                'values' => array(
                                    array(
                                        'id' => 'api_key_georeferencia_enabled' . '_on',
                                        'value' => 1,
                                        'label' => $this->trans('Habilitado', [], 'Admin.Global')
                                    ),
                                    array(
                                        'id' => 'api_key_georeferencia_enabled' . '_off',
                                        'value' => 0,
                                        'label' => $this->trans('Deshabilitado', [], 'Admin.Global')
                                    )
                                )
                            ),
                            array(
                                'type' => 'text',
                                'label' => $this->trans('API KEY Georeferencia', array(), 'Modules.Banner.Admin'),
                                'desc' => $this->l('Puedes encontrar esta Api Key, bajo el producto Coberturas en tu página de perfil: https://developers.wschilexpress.com/developer'),
                                'name' => 'api_key_georeferencia_value',
                                'lang' => false,
                                'size' => 20,
                                'required' => false
                            ),
                            array(
                                'type' => 'switch',
                                'label' => $this->trans(
                                    'Habilitar módulo de generación de OT',
                                    [],
                                    'Modules.Contactform.Admin'
                                ),
                                'desc' => $this->trans(
                                    "Necesitas este módulo para poder obtener generar Ordenes de Transporte e Imprimir tus etiquetas, crea tu API KEY en https://developers.wschilexpress.com/products/transportorders/subscribe",
                                    [],
                                    'Modules.Contactform.Admin'
                                ),
                                'name' => 'api_key_generacion_ot_enabled',
                                'is_bool' => true,
                                'required' => true,
                                'values' => array(
                                    array(
                                        'id' => 'api_key_generacion_ot_enabled' . '_on',
                                        'value' => 1,
                                        'label' => $this->trans('Habilitado', [], 'Admin.Global')
                                    ),
                                    array(
                                        'id' => 'api_key_generacion_ot_enabled' . '_off',
                                        'value' => 0,
                                        'label' => $this->trans('Deshabilitado', [], 'Admin.Global')
                                    )
                                )
                            ),
                            array(
                                'type' => 'text',
                                'label' => $this->trans('API KEY Órdenes de transporte', array(), 'Modules.Banner.Admin'),
                                'desc' => $this->l('Puedes encontrar esta Api Key, bajo el producto Envíos en tu página de perfil: https://developers.wschilexpress.com/developer'),
                                'name' => 'api_key_generacion_ot_value',
                                'lang' => false,
                                'size' => 20,
                                'required' => false
                            ),
                            array(
                                'type' => 'switch',
                                'label' => $this->trans(
                                    'Habilitar módulo de cotización',
                                    [],
                                    'Modules.Contactform.Admin'
                                ),
                                'desc' => $this->trans(
                                    "Necesitas este módulo para poder obtener generar Ordenes de Transporte e Imprimir tus etiquetas, crea tu API KEY en https://developers.wschilexpress.com/products/transportorders/subscribe",
                                    [],
                                    'Modules.Contactform.Admin'
                                ),
                                'name' => 'api_key_cotizador_enabled',
                                'is_bool' => true,
                                'required' => true,
                                'values' => array(
                                    array(
                                        'id' => 'api_key_cotizador_enabled' . '_on',
                                        'value' => 1,
                                        'label' => $this->trans('Habilitado', [], 'Admin.Global')
                                    ),
                                    array(
                                        'id' => 'api_key_cotizador_enabled' . '_off',
                                        'value' => 0,
                                        'label' => $this->trans('Deshabilitado', [], 'Admin.Global')
                                    )
                                )
                            ),
                             array(
                                'type' => 'text',
                                'label' => $this->trans('API KEY Módulo de Cotización', array(), 'Modules.Banner.Admin'),
                                'desc' => $this->l('Puedes encontrar esta Api Key, bajo el producto Cotizador en tu página de perfil: https://developers.wschilexpress.com/developer'),
                                'name' => 'api_key_cotizador_value',
                                'lang' => false,
                                'size' => 20,
                                'required' => false
                            ),
                            array(
                                'label' => $this->trans('Ambiente', array(), 'Modules.Dashactivity.Admin'),
                                'desc' => $this->l('Elige el ambiente de Staging para hacer las pruebas con tu plugin, y el ambiente de production una vez estas seguro(a) que todo funciona correctamente.'),
                                'name' => 'ambiente',
                                'type' => 'select',
                                'options' => array(
                                    'query' => array(
                                        array('id' => 'staging', 'name' => 'Staging'),
                                        array('id' => 'production', 'name' => 'Production'),
                                    ),
                                    'id' => 'id',
                                    'name' => 'name',
                                ),
                            )
                           
                        ),
                        'submit' => array(
                            'title' => $this->l('Guardar'),
                            'class' => 'btn btn-default pull-right'
                        )
                    )
                ),
                array(
                    'form' => array(
                        'id_form' => 'general_chilexpress',
                        'legend' => array(
                            'title' => $this->l('Datos de Origen')
                        ),
                        'input' => array(
                            array(
                                'type' => 'select',
                                'options' => array(
                                    'query' =>$regiones_query,
                                    'id' => 'id',
                                    'name' => 'name',
                                ),
                                'label' => $this->trans('Región de Origen', array(), 'Modules.Banner.Admin'),
                                'name' => 'region_origen',
                                'lang' => false,
                                'required' => true
                            ),
                            array(
                                'type' => 'select',
                                'options' => array(
                                    'query' => $comunas_origen_query,
                                    'id' => 'id',
                                    'name' => 'name',
                                ),
                                'label' => $this->trans('Código de comuna de origen', array(), 'Modules.Banner.Admin'),
                                'name' => 'comuna_origen',
                                'lang' => false,
                                'required' => true
                            ),
                            array(
                                'type' => 'text',
                                'label' => $this->trans('Número TCC', array(), 'Modules.Banner.Admin'),
                                'name' => 'numero_tcc_origen',
                                'lang' => false,
                                'size' => 20,
                                'required' => true
                            )
                        ),
                        'submit' => array(
                            'title' => $this->l('Guardar'),
                            'class' => 'btn btn-default pull-right'
                        )
                    )
                ),
                array(
                    'form' => array(
                        'id_form' => 'general_chilexpress2',
                        'legend' => array(
                            'title' => $this->l('Datos del Remitente')
                        ),
                        'input' => array(
                            array(
                                'type' => 'text',
                                'label' => $this->trans('Nombre', array(), 'Modules.Banner.Admin'),
                                'name' => 'nombre_remitente',
                                'lang' => false,
                                'size' => 20,
                                'required' => true
                            ),
                            array(
                                'type' => 'text',
                                'label' => $this->trans('Teléfono', array(), 'Modules.Banner.Admin'),
                                'name' => 'telefono_remitente',
                                'lang' => false,
                                'size' => 6,
                                'required' => true
                            ),
                            array(
                                'type' => 'text',
                                'prefix'=>'<i class="icon-envelope-o"></i>',
                                'label' => $this->trans('E-mail', array(), 'Modules.Banner.Admin'),
                                'name' => 'email_remitente',
                                'lang' => false,
                                'size' => 20,
                                'required' => true
                            ),
                            array(
                                'type' => 'text',
                                'label' => $this->trans('RUT Seller', array(), 'Modules.Banner.Admin'),
                                'name' => 'rut_seller_remitente',
                                'lang' => false,
                                'size' => 20,
                                'required' => true
                            ),
                            array(
                                'type' => 'text',
                                'label' => $this->trans('RUT Marketplace', array(), 'Modules.Banner.Admin'),
                                'name' => 'rut_marketplace_remitente',
                                'lang' => false,
                                'size' => 20,
                                'required' => true
                            )
                        ),
                        'submit' => array(
                            'title' => $this->l('Guardar'),
                            'class' => 'btn btn-default pull-right'
                        )
                    )
                ),
                array(
                    'form' => array(
                        'id_form' => 'general_chilexpress3',
                        'legend' => array(
                            'title' => $this->l('Dirección de Devolución')
                        ),
                        'input' => array(
                            array(
                                'type' => 'select',
                                'options' => array(
                                    'query' => $regiones_query,
                                    'id' => 'id',
                                    'name' => 'name',
                                ),
                                'label' => $this->trans('Región de Devolución', array(), 'Modules.Banner.Admin'),
                                'name' => 'region_devolucion',
                                'lang' => false,
                                'required' => true
                            ),
                            array(
                                'type' => 'select',
                                'options' => array(
                                    'query' => $comunas_devolucion_query,
                                    'id' => 'id',
                                    'name' => 'name',
                                ),
                                'label' => $this->trans('Código Comuna', array(), 'Modules.Banner.Admin'),
                                'name' => 'comuna_devolucion',
                                'lang' => false,
                                'required' => true
                            ),
                            array(
                                'type' => 'text',
                                'label' => $this->trans('Calle Devolución', array(), 'Modules.Banner.Admin'),
                                'name' => 'calle_devolucion',
                                'lang' => false,
                                'size' => 20,
                                'required' => true
                            ),
                            array(
                                'type' => 'text',
                                'label' => $this->trans('Número de la Dirección', array(), 'Modules.Banner.Admin'),
                                'name' => 'numero_calle_devolucion',
                                'lang' => false,
                                'size' => 20,
                                'required' => true
                            ),
                            array(
                                'type' => 'text',
                                'label' => $this->trans('Complemento', array(), 'Modules.Banner.Admin'),
                                'name' => 'complemento_devolucion',
                                'lang' => false,
                                'size' => 20,
                                'required' => true
                            )
                        ),
                        'submit' => array(
                            'title' => $this->l('Guardar'),
                            'class' => 'btn btn-default pull-right'
                        )
                    )
                )
        );

        // < load helperForm >
        $helper = new HelperForm();

        // < module, token and currentIndex >
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // < title and toolbar >
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;        // false -> remove toolbar
        $helper->toolbar_scroll = false;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                        '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // < load current value >
        $helper->fields_value['api_key_georeferencia_value'] = Configuration::get('api_key_georeferencia_value');
        $helper->fields_value['api_key_generacion_ot_value'] = Configuration::get('api_key_generacion_ot_value');
        $helper->fields_value['api_key_cotizador_value'] = Configuration::get('api_key_cotizador_value');
        
        $helper->fields_value['api_key_georeferencia_enabled'] = Configuration::get('api_key_georeferencia_enabled');
        $helper->fields_value['api_key_generacion_ot_enabled'] = Configuration::get('api_key_generacion_ot_enabled');
        $helper->fields_value['api_key_cotizador_enabled'] = Configuration::get('api_key_cotizador_enabled');
        $helper->fields_value['ambiente'] = Configuration::get('ambiente');

        $helper->fields_value['region_origen'] = Configuration::get('region_origen');
        $helper->fields_value['comuna_origen'] = Configuration::get('comuna_origen');
        $helper->fields_value['numero_tcc_origen'] = Configuration::get('numero_tcc_origen');

        $helper->fields_value['nombre_remitente'] = Configuration::get('nombre_remitente');
        $helper->fields_value['telefono_remitente'] = Configuration::get('telefono_remitente');
        $helper->fields_value['email_remitente'] = Configuration::get('email_remitente');
        $helper->fields_value['rut_seller_remitente'] = Configuration::get('rut_seller_remitente');
        $helper->fields_value['rut_marketplace_remitente'] = Configuration::get('rut_marketplace_remitente');

        $helper->fields_value['region_devolucion'] = Configuration::get('region_devolucion');
        $helper->fields_value['comuna_devolucion'] = Configuration::get('comuna_devolucion');
        $helper->fields_value['calle_devolucion'] = Configuration::get('calle_devolucion');
        $helper->fields_value['numero_calle_devolucion'] = Configuration::get('numero_calle_devolucion');
        $helper->fields_value['complemento_devolucion'] = Configuration::get('complemento_devolucion');

        return $helper->generateForm($fields_form);
        
    }

    public function getContent()
    {
        $output = null;


        // < here we check if the form is submited for this module >
        if (Tools::isSubmit('submit'.$this->name)) {
            $chilexpress_url = strval(Tools::getValue('chilexpress_oficial_url'));

            $api_key_georeferencia_value = strval(Tools::getValue('api_key_georeferencia_value'));
            $api_key_generacion_ot_value = strval(Tools::getValue('api_key_generacion_ot_value'));
            $api_key_cotizador_value = strval(Tools::getValue('api_key_cotizador_value'));

            $api_key_georeferencia_enabled = boolval(Tools::getValue('api_key_georeferencia_enabled'));
            $api_key_generacion_ot_enabled = boolval(Tools::getValue('api_key_generacion_ot_enabled'));
            $api_key_cotizador_enabled = boolval(Tools::getValue('api_key_cotizador_enabled'));

            $ambiente = strval(Tools::getValue('ambiente'));

            $region_origen = strval(Tools::getValue('region_origen'));
            $comuna_origen = strval(Tools::getValue('comuna_origen'));
            $numero_tcc_origen = strval(Tools::getValue('numero_tcc_origen'));

            $nombre_remitente = strval(Tools::getValue('nombre_remitente'));
            $telefono_remitente = strval(Tools::getValue('telefono_remitente'));
            $email_remitente = strval(Tools::getValue('email_remitente'));
            $rut_seller_remitente = strval(Tools::getValue('rut_seller_remitente'));
            $rut_marketplace_remitente = strval(Tools::getValue('rut_marketplace_remitente'));

            $region_devolucion = strval(Tools::getValue('region_devolucion'));
            $comuna_devolucion = strval(Tools::getValue('comuna_devolucion'));
            $calle_devolucion = strval(Tools::getValue('calle_devolucion'));
            $numero_calle_devolucion = strval(Tools::getValue('numero_calle_devolucion'));
            $complemento_devolucion = strval(Tools::getValue('complemento_devolucion'));


            if (isset($api_key_georeferencia_value)) {
                Configuration::updateValue('api_key_georeferencia_value', $api_key_georeferencia_value);
            }
            if (isset($api_key_generacion_ot_value)) {
                Configuration::updateValue('api_key_generacion_ot_value', $api_key_generacion_ot_value);
            }
            if (isset($api_key_cotizador_value)) {
                Configuration::updateValue('api_key_cotizador_value', $api_key_cotizador_value);
            }

            if (isset($api_key_georeferencia_enabled)) {
                Configuration::updateValue('api_key_georeferencia_enabled', $api_key_georeferencia_enabled);
            }
            if (isset($api_key_generacion_ot_enabled)) {
                Configuration::updateValue('api_key_generacion_ot_enabled', $api_key_generacion_ot_enabled);
            }
            if (isset($api_key_cotizador_enabled)) {
                Configuration::updateValue('api_key_cotizador_enabled', $api_key_cotizador_enabled);
            }
            if (isset($ambiente)) {
                Configuration::updateValue('ambiente', $ambiente);
            }

            ///
            if (isset($region_origen)) {
                Configuration::updateValue('region_origen', $region_origen);
            }
            if (isset($comuna_origen)) {
                Configuration::updateValue('comuna_origen', $comuna_origen);
            }
            if (isset($numero_tcc_origen)) {
                Configuration::updateValue('numero_tcc_origen', $numero_tcc_origen);
            }
            ///
            if (isset($nombre_remitente)) {
                Configuration::updateValue('nombre_remitente', $nombre_remitente);
            }
            if (isset($telefono_remitente)) {
                Configuration::updateValue('telefono_remitente', $telefono_remitente);
            }
            if (isset($email_remitente)) {
                Configuration::updateValue('email_remitente', $email_remitente);
            }
            if (isset($rut_seller_remitente)) {
                Configuration::updateValue('rut_seller_remitente', $rut_seller_remitente);
            }
            if (isset($rut_marketplace_remitente)) {
                Configuration::updateValue('rut_marketplace_remitente', $rut_marketplace_remitente);
            }

            ///
            if (isset($nombre_remitente)) {
                Configuration::updateValue('region_devolucion', $region_devolucion);
            }
            if (isset($telefono_remitente)) {
                Configuration::updateValue('comuna_devolucion', $comuna_devolucion);
            }
            if (isset($calle_devolucion)) {
                Configuration::updateValue('calle_devolucion', $calle_devolucion);
            }
            if (isset($numero_calle_devolucion)) {
                Configuration::updateValue('numero_calle_devolucion', $numero_calle_devolucion);
            }
            if (isset($complemento_devolucion)) {
                Configuration::updateValue('complemento_devolucion', $complemento_devolucion);
            }

            $output .= $this->displayConfirmation($this->l('Datos Actualizados con éxito'));
           /*
            // < make some validation, check if we have something in the input >
            if (!isset($chilexpress_url))
                $output .= $this->displayError($this->l('El campo es obligatorio.'));
            else
            {
                // < this will update the value of the Configuration variable >
                Configuration::updateValue('chilexpress_oficial_url', $chilexpress_url);


                // < this will display the confirmation message >
                $output .= $this->displayConfirmation($this->l('Chilexpress URL updated!'));
            }*/
        }
        return $output.$this->displayForm();
    }


    public function installCarriers()
    {   
        foreach ($this->carriers_list as $carrier_key => $carrier_name) {
            if (Configuration::get($carrier_key) < 1)
            {
                // Create new carrier
                $carrier = new Carrier();
                $carrier->name = 'Chilexpress';
                $carrier->id_tax_rules_group = 0;
                $carrier->active = 1;
                $carrier->deleted = 0;
                foreach (Language::getLanguages(true) as $language){
                    $carrier->delay[(int)$language['id_lang']] = $carrier_name;
                }
                $carrier->shipping_handling = false;
                $carrier->range_behavior = 0;
                $carrier->is_module = true;
                $carrier->shipping_external = true;
                $carrier->external_module_name = $this->name;
                $carrier->need_range = true;
                if (!$carrier->add())
                    return false;

                // Associate carrier to all groups
                $groups = Group::getGroups(true);
                foreach ($groups as $group)
                    Db::getInstance()->insert('carrier_group', array('id_carrier' => (int)$carrier->id, 'id_group' => (int)$group['id_group']));

                // Create price range
                $rangePrice = new RangePrice();
                $rangePrice->id_carrier = $carrier->id;
                $rangePrice->delimiter1 = '0';
                $rangePrice->delimiter2 = '10000000';
                $rangePrice->add();

                // Create weight range
                $rangeWeight = new RangeWeight();
                $rangeWeight->id_carrier = $carrier->id;
                $rangeWeight->delimiter1 = '0';
                $rangeWeight->delimiter2 = '10000000';
                $rangeWeight->add();

                // Associate carrier to all zones
                $zones = Zone::getZones(true);
                foreach ($zones as $zone)
                {
                    Db::getInstance()->insert('carrier_zone', array('id_carrier' => (int)$carrier->id, 'id_zone' => (int)$zone['id_zone']));
                    Db::getInstance()->insert('delivery', array('id_carrier' => (int)$carrier->id, 'id_range_price' => (int)$rangePrice->id, 'id_range_weight' => NULL, 'id_zone' => (int)$zone['id_zone'], 'price' => '0'));
                    Db::getInstance()->insert('delivery', array('id_carrier' => (int)$carrier->id, 'id_range_price' => NULL, 'id_range_weight' => (int)$rangeWeight->id, 'id_zone' => (int)$zone['id_zone'], 'price' => '0'));
                }

                // Copy the carrier logo
                copy(dirname(__FILE__).'/logo_medio_transporte.png', _PS_SHIP_IMG_DIR_.'/'.(int)$carrier->id.'.jpg');

                // Save the carrier ID in the Configuration table
                Configuration::updateValue($carrier_key, $carrier->id);
            }
        }

        return true;
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        $this->context->smarty->assign(
              array(
                  'chilexpress_oficial_ajax_url' => $this->context->link->getModuleLink( 'chilexpress_oficial', 'ajax', array()),
              ));
        return $this->display(__FILE__, $this->name .'.tpl');
    }

    public function hookDisplayBackOfficeTop($params)
    {
        return $this->display(__FILE__, $this->name .'_footer'.'.tpl');
    }

    public function hookdisplayHeader($params)
    {
        $this->context->smarty->assign(
              array(
                  'chilexpress_oficial_ajax_url' => $this->context->link->getModuleLink( 'chilexpress_oficial', 'ajax', array()),
              ));
        
        $this->context->controller->registerJavascript('chilexpress-official-front-script', 'modules/chilexpress_oficial/views/js/chilexpress_oficial_front.js', ['position' => 'bottom', 'priority' => 10]);
        return $this->display(__FILE__, $this->name .'_front.tpl');
    }

    public function hookUpdateCarrier($params)
    {
      $id_carrier_old = (int)($params['id_carrier']);
      $id_carrier_new = (int)($params['carrier']->id);
     foreach ($this->carriers_list as $carrier_key => $carrier_name) {
        $carrier_id = Configuration::get($carrier_key);
        if ($id_carrier_old == $carrier_id) {
            Configuration::updateValue($carrier_key, $id_carrier_new);
        }
      }
    }

    /** shipping related functions **/
    public function getOrderShippingCost($params, $shipping_fees)
    {  

      foreach ($this->carriers_list as $carrier_key => $carrier_name) {
          $carrier_id = Configuration::get($carrier_key);
      }
      
      $carrier = new Carrier($this->id_carrier);
      $current_carrier_key = false;
      $current_carrier_name = '';
      $current_carrier_id = 0;
      foreach ($this->carriers_list as $carrier_key => $carrier_name) {
          $carrier_id = Configuration::get($carrier_key);
          if ($carrier_id == $this->id_carrier) {
            $current_carrier_key = ($carrier_key);
            $current_carrier_name = ($carrier_name);
            $current_carrier_id = ($carrier_id);
          }
      }

      if (!$current_carrier_key) {
        return false;
      }

      $address = new Address($params->id_address_delivery);
      $pais = $address->country;
      $comuna_code = $address->city; 
      $products = $params->getProducts(true);

      $products_required_fields = array();
      $total = 0;

          $biggest_product = false;
          $biggest_size = 0;

          foreach($products as $product) {
            //var_dump($product);
            $products_required_fields[] = array(
                "id" => $product["id_product"],
                "cart_quantity" => $product["cart_quantity"],
                "width" => $product["width"],
                "height" => $product["height"],
                "depth" => $product["depth"],
                "weight" => $product["weight"],
                "total" => $product["total"]
            );
            if ($product["width"]!="" && $product["height"]!="" && $product["depth"]!="" ) {
                if ($product["width"]*$product["height"]*$product["depth"] > $biggest_size) {
                    $biggest_size = $product["width"]*$product["height"]*$product["depth"];
                    $biggest_product = $product;
                }
            }

            $total_weight +=$product["weight"];
            $total += $product["total"];
          }


          $conf = array(
            "comuna_origen" => Configuration::get('comuna_origen'),
            "comuna_destino" => $comuna_code,
            "total" => $total,
            "productos" => $products_required_fields
          );
          if ($biggest_product) { $conf["width"] = $biggest_product["width"]; }
          else { $conf["width"] = 1; }
          if ($biggest_product) $conf["height"] = $biggest_product["height"];
          else { $conf["height"] = 1; }
          if ($biggest_product) { $conf["depth"] = $biggest_product["depth"]; }
          else { $conf["depth"] = 1; }

           $jresult = $this->api->obtener_cotizacion($conf["comuna_origen"], $conf["comuna_destino"], $total_weight,  $conf["width"],  $conf["height"],  $conf["depth"], $conf["total"]);

        $serviceValue = false;
        if (!$jresult){
            return false;
        } else {
            foreach ($jresult["data"]["courierServiceOptions"] as $option) {
                if (
                    $option["serviceTypeCode"] == $this->carriers_serviceTypeCode[$current_carrier_key] &&
                    intval($option["serviceValue"]) >= 3
                ) {
                    $serviceValue = intval($option["serviceValue"]);
                }
            }
        }
        if (!$serviceValue) {
           return false;
        }
        return $serviceValue + $shipping_fees;
    }

    public function getOrderShippingCostExternal($params)
    {
        foreach ($this->carriers_list as $carrier_key => $carrier_name) {
              $carrier_id = Configuration::get($carrier_key);
        }
          $carrier = new Carrier($this->id_carrier);
          $current_carrier_key = false;
          $current_carrier_name = '';
          $current_carrier_id = 0;
          foreach ($this->carriers_list as $carrier_key => $carrier_name) {
              $carrier_id = Configuration::get($carrier_key);
              if ($carrier_id == $this->id_carrier) {
                $current_carrier_key = ($carrier_key);
                $current_carrier_name = ($carrier_name);
                $current_carrier_id = ($carrier_id);
              }
          }

          if (!$current_carrier_key) {
            return false;
          }

          $address = new Address($params->id_address_delivery);
          $pais = $address->country;
          $comuna_code = $address->city; 
          $products = $params->getProducts(true);

          $products_required_fields = array();
          $total = 0;
          
            
          $biggest_product = false;
          $biggest_size = 0;

          foreach($products as $product) {
            // var_dump($product);
            $products_required_fields[] = array(
                "id" => $product["id_product"],
                "cart_quantity" => $product["cart_quantity"],
                "width" => $product["width"],
                "height" => $product["height"],
                "depth" => $product["depth"],
                "weight" => $product["weight"],
                "total" => $product["total"]
            );
            if ($product["width"]!="" && $product["height"]!="" && $product["depth"]!="" ) {
                if ($product["width"]*$product["height"]*$product["depth"] > $biggest_size) {
                    $biggest_size = $product["width"]*$product["height"]*$product["depth"];
                    $biggest_product = $_product;
                }
            }

            $total_weight +=$product["weight"]; 
            $total += $product["total"];
          }


          $conf = array(
            "comuna_origen" => Configuration::get('comuna_origen'),
            "comuna_destino" => $comuna_code,
            "total" => $total,
            "productos" => $products_required_fields
          );
          if ($biggest_product) { $conf["width"] = $biggest_product["width"]; }
          else { $conf["width"] = 1; }
          if ($biggest_product) $conf["height"] = $biggest_product["height"];
          else { $conf["height"] = 1; }
          if ($biggest_product) { $conf["depth"] = $biggest_product["depth"]; }
          else { $conf["depth"] = 1; }


            $jresult = $this->api->obtener_cotizacion($conf["comuna_origen"], $conf["comuna_destino"], $total_weight,  $conf["width"],  $conf["height"],  $conf["depth"], $conf["total"]);

            $serviceValue = false;
            if (!$jresult){
                return false;
            } else {
                foreach ($jresult["data"]["courierServiceOptions"] as $option) {
                    if (
                        $option["serviceTypeCode"] == $this->carriers_serviceTypeCode[$current_carrier_key] &&
                        intval($option["serviceValue"]) >=3
                    ) {
                        $serviceValue = intval($option["serviceValue"]);
                    }
                }
            }
        if(!serviceValue){
           return false;
	}
        return $serviceValue;
    }

    public function hookActionAdminCustomersListingFieldsModifier($params)
    {
        if (!isset($params['fields']['customer_group'])) {
            $params['fields']['customer_group'] = array(
                'title' => 'Grupo',
                "align" => "text-center",
                "class" => "fixed-width-xs",
                "filter_key" => "cg!id_group"
            );
        }
        
        if (isset($params['select'])) {
            $params['select'] .= ", cg.id_group AS customer_group";
        }

        if (isset($params['join'])) {
            $params['join'] .= 'LEFT JOIN `' . _DB_PREFIX_ . 'customer_group` cg ON (a.`id_customer` = cg.`id_customer`)';
        }
    }

    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
         if (!isset($params['fields']['carrier_name'])) {
            $params['fields']['carrier_name'] = array(
                'title' => 'Acciones',
                "align" => "text-center",
                "class" => "fixed-width-xs",
                "filter_key" => "ca!name",
                'callback' => 'callbackMethod2',
                'orderby' => false,
                'search' => false,
                'callback_object' => Module::getInstanceByName($this->name)
            );
        } 

        if (!isset($params['fields']['tracking_number'])) {

            $params['fields']['tracking_number'] = array(
                'title' => 'Tracking',
                "align" => "text-center",
                "class" => "fixed-width-xs",
                "filter_key" => "oca!tracking_number",
                'callback' => 'callbackMethod',
                'orderby' => false,
                'search' => false,
                'callback_object' => Module::getInstanceByName($this->name)
            );
        }
        
        if (isset($params['select'])) {
            $params['select'] .= ", oca.tracking_number AS tracking_number";
            $params['select'] .= ", ca.name AS carrier_name";
        }

        if (isset($params['join'])) {
            $params['join'] .= 'LEFT JOIN `' . _DB_PREFIX_ . 'order_carrier` oca ON (a.`id_order` = oca.`id_order`)';
            $params['join'] .= 'LEFT JOIN `' . _DB_PREFIX_ . 'carrier` ca ON (ca.`id_carrier` = oca.`id_carrier`)';
        }

    }

    public function callbackMethod($value, $tr)
    {
        
        $order = new Order($tr['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }
        $tracking_number = $order->shipping_number;
        $current_state = $order->current_state;
        $carrier_id = $order->id_carrier;

        foreach ($this->carriers_list as $carrier_key => $carrier_name) {
            $official_carrier_id = Configuration::get($carrier_key);
            if( $carrier_id == $official_carrier_id && array_search( $current_state , array(5,4,8,2,11,12,9,3,7)) ) {
                if($tracking_number) {
                    $numbers = explode(",", $tracking_number);
                    $out = array();
                    foreach ($numbers as $number)
                    {
                        $out[] = '<a href="javascript:;" data-tracking-code="'.$number.'" data-orderid="'.$tr['id_order'].'">'.$number.'</a>';
                    }
                    return implode('<br />', $out);
                }
            }
        }

        return '-';
    }

    public function callbackMethod2($value, $tr)
    {
        
        $order = new Order($tr['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }
        $tracking_number = $order->shipping_number;
        $current_state = $order->current_state;
        $carrier_id = $order->id_carrier;

        foreach ($this->carriers_list as $carrier_key => $carrier_name) {
            $official_carrier_id = Configuration::get($carrier_key);
            if( $carrier_id == $official_carrier_id && array_search( $current_state , array(5,4,8,2,11,12,9,3,7)) )
            {
                if(!$tracking_number) {
                    return '<a href="'.$this->context->link->getAdminLink('ChilexpressOrder').'&order_id='.$tr['id_order'].'&action=generar_ot" title="Generar OT" class="btn btn-default with-tooltip"><i class="icon-cog" data-toggle="tooltip" data-placement="top" title="Generar OT"></i></a>';
                } else {
                    return '<a href="'.$this->context->link->getAdminLink('ChilexpressOrder').'&order_id='.$tr['id_order'].'&action=ver_ot" title="Imprimir OT" class="btn btn-default with-tooltip"><i class="icon-print" data-toggle="tooltip" data-placement="top" title="Imprimir OT"></i></a>';
                }
            }
        }

        return '';
    }


    public function loadSQLFile($sql_file)
    {
      // Get install SQL file content
      $sql_content = file_get_contents($sql_file);

      // Replace prefix and store SQL command in array
      $sql_content = str_replace('PREFIX_', _DB_PREFIX_, $sql_content);
      $sql_requests = preg_split("/;\s*[\r\n]+/", $sql_content);

      // Execute each SQL statement
      $result = true;
      foreach($sql_requests as $request)
      if (!empty($request))
        $result &= Db::getInstance()->execute(trim($request));

      // Return result
      return $result;
    }


    function hookDisplayAdminOrderContentShip($param)
    {   
        if ( $param['order']->shipping_number!= '' )
        {
            $order_id = $param['order']->id;
            $carrier_id = $param['order']->id_carrier;


            foreach ($this->carriers_list as $carrier_key => $carrier_name) {
                $official_carrier_id = Configuration::get($carrier_key);
                if ( $carrier_id == $official_carrier_id){
                    $query = new DbQuery();
                    $query->from('chilexpress_oficial_ordermeta', 'com');
                    $query->where('com.id_order = "'.$order_id.'"');
                    $metasArray = Db::getInstance()->executeS($query);
                    $out = '<div class="well"><div class="row"><div class="col-md-12"><h3>Etiquetas de Chilexpress</h3></div>';
                    foreach ($metasArray as $meta) {
                        $src = 'data:image/jpg;base64,'.$meta['labelData'];
                        $out.= '<img src="'.$src.'" style="display:block; margin:0 auto;"/> <br />';

                    }
                    $out.= '<a href="'.$this->context->link->getAdminLink('ChilexpressOrder').'&order_id='.$order_id.'&action=print_ot'.'" class="btn btn-primary btn-block"><i class="icon-print"></i> Imprimir Etiquetas</a>';

                    $out .= "</div></div>";
                    return $out;
                }
            }
        }
        
        return '';
        
    }

    private function checkAndAddChileanStatesOnInstall()
    {

        // // // // // // // //
        $id_country = (int) Country::getByIso(strval('CL'));
        $regiones = $this->api->obtener_regiones();
        foreach ($regiones as $iso => $name) {
            
            $id_state = State::getIdByIso($iso, $id_country);
            if (!$id_state) {
                $state = new State();
                $state->name = substr ($name , 0 , 32) ;
                $state->iso_code = strval($iso);
                $state->id_country = $id_country;

                $id_zone = (int) Zone::getIdByName(strval('South America'));

                if (!$id_zone) {
                    $zone = new Zone();
                    $zone->name = 'South America';
                    $zone->active = true;
                    
                    if (!$zone->add()) {
                        $this->_errors[] = Context::getContext()->getTranslator()->trans('Invalid Zone name.', array(), 'Admin.International.Notification');

                        return false;
                    }

                    $id_zone = $zone->id;
                }
                $state->id_zone = $id_zone;

                $country = new Country($state->id_country);
                if (!$country->contains_states) {
                    $country->contains_states = 1;
                    if (!$country->update()) {
                        $this->_errors[] = Context::getContext()->getTranslator()->trans('Cannot update the associated country: %s', array($country->name), 'Admin.International.Notification');
                    }
                }

                if (!$state->add()) {
                    $this->_errors[] = Context::getContext()->getTranslator()->trans('An error occurred while adding the state.', array(), 'Admin.International.Notification');

                    return false;
                }

            }
        }
        return true;
        // // // // // // // //
    }

    public function getErrors()
    {
        return $this->_errors;
    }


}
