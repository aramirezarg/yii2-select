<?php
/**
 * Created by PhpStorm.
 * User: Alfredo Ramirez
 * Date: 25/3/2018
 * Time: 13:49
 */

namespace magicsoft\select;

use kartik\select2\Select2;
use webvimark\modules\UserManagement\components\GhostHtml;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

class MagicSelector
{
    private $form;                  /*Objeto Form para crear el input del formulario*/
    private $view;                  /*Vista del objeto donde se registran los JS*/

    private $module;                /*Origen del Modelo a Buscar*/
    private $parent_model;          /*Modelo padre del modelo a buscar*/
    private $parent_model_relation_id;          /*Modelo padre del modelo a buscar*/
    private $parent_select;         /*Modelo fitro para el modelo a buscar*/
    private $parent_select_id;      /*En caso que el valor del filtro sea estático*/
    private $sub_model;             /*Modelo a buscar*/
    private $search_column;         /*Columna de búsqueda*/
    private $attribute_get_value;   /*Columna o función de retorno*/
    private $special_function_search;   /*Funcion especial del modelo para búsqueda*/
    private $relation_model;        /*Modelo relacionado --Crea un inner join--*/
    private $minimumInputLength;    /*Minimo de caracteres para empezar a buscar*/
    private $setButtons;            /*Bool para set buttons*/
    private $via_ajax;              /*Define if data get via-ajax or direct from model*/
    private $options;               /*Opciones del objeto [css & html]*/
    private $modal_options;         /*Opciones del Modal*/
    private $addon;
    const THEME = Select2::THEME_KRAJEE;

    public static function begin($options)
    {
        $selector = new self();
        $selector->setConfiguration($options);
        $selector->run();
    }

    public function setConfiguration($options){
        $this->form             = ArrayHelper::getValue($options, 'form', null);
        $this->module           = ArrayHelper::getValue($options, 'module', Yii::$app->controller->module->id);

        $parent_model = ArrayHelper::getValue($options, 'parent_model', null);

        if(is_array($parent_model)){
            $this->parent_model = ArrayHelper::getValue($parent_model, 'model', null);
            $this->parent_model_relation_id = ArrayHelper::getValue($parent_model, 'relation_id', null);
        }else{
            $this->parent_model =  ArrayHelper::getValue($options, 'parent_model', null);
            $this->parent_model_relation_id = null;
        }

        $this->parent_select    = ArrayHelper::getValue($options, 'parent_select', null);
        $this->parent_select_id = ArrayHelper::getValue($options, 'parent_select_id', null);
        $this->sub_model        = ArrayHelper::getValue($options, 'sub_model', null);
        $this->relation_model   = ArrayHelper::getValue($options, 'relation_model', null);
        $this->search_column    = ArrayHelper::getValue($options, 'search_column', null);
        $this->attribute_get_value    = ArrayHelper::getValue($options, 'attribute_get_value', null);
        $this->special_function_search    = ArrayHelper::getValue($options, 'special_function_search', null);
        $this->view             = Yii::$app->controller->view;

        $this->options              = ArrayHelper::getValue($options, 'options', []);
        $this->modal_options        = ArrayHelper::getValue($this->options, 'modal_options', []);
        $this->via_ajax             = ArrayHelper::getValue($this->options, 'via_ajax', true);
        $this->setButtons           = ArrayHelper::getValue($this->options, 'set_buttons', true);
        $this->minimumInputLength   = ArrayHelper::getValue($this->options, 'minimumInputLength', 0);
        $this->addon   = ArrayHelper::getValue($this->options, 'addon', null);
    }

    private function getParentId(){
        return $this->parent_model_relation_id ? $this->parent_model_relation_id : $this->parent_model->{$this->getSubModelTableName() . '_id'};
    }

    public function getSubModelRelation(){
        if(is_array($this->sub_model)){
            return ArrayHelper::getValue($this->sub_model, 'relation', null);
        }
        return $this->sub_model;
    }

    public function getSubModel(){
        if(is_array($this->sub_model)){
            return ArrayHelper::getValue($this->sub_model, 'model', null);
        }
        return $this->sub_model;
    }

    public function getSubModelRelationName(){
        return lcfirst ($this->getSubModelRelation());
        //return lcfirst($this->getSubModel(->formName());
    }

    public function getSubModelName(){
        return lcfirst ($this->getSubModel());
        //return lcfirst($this->getSubModel(->formName());
    }

    private function getSubModelId(){
        if(is_array($this->sub_model)){
            return ArrayHelper::getValue($this->sub_model, 'id', null);
        }
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $this->getSubModelTableName())) . '_id';
    }

    public function getSubModelFromController(){
        $controller = $this->getSubModelRelation();
        if(is_array($this->sub_model)){
            $controller = ArrayHelper::getValue($this->sub_model, 'controller', $this->getSubModelRelation());
        }

        $string = strtolower(preg_replace('/([A-Z])/', '-$1', lcfirst ($controller)));
        return mb_strtolower($string);
    }

    public function getSubModelTableName(){
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $this->getSubModelRelationName()));
        $model_route = $this->module ? "app\\modules\\$this->module\\models\\" : "app\\models\\";

        $__model = $model_route.ucwords($this->getSubModelRelation());
        $_model = new $__model ;

        return $_model->tableName();
    }

    public function getControllerForSubModel(){
        if(is_array($this->sub_model)){
            return ArrayHelper::getValue($this->sub_model, 'controller', $this->getSubModelRelationName());
        }
        return $this->getSubModelRelationName();
    }

    private function getUrlForCreate(){
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '-\\1', '/' . $this->module . '/' . $this->getControllerForSubModel())) . '/create';
    }

    private function getUrlForUpdate(){
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '-\\1', '/' . $this->module . '/' . $this->getControllerForSubModel())) . '/update';
    }

    private function getSearchModelClass(){
        return $this->{$this->getSubModelRelationName()}->className();
    }

    private function getValue(){
        $parent_model = $this->parent_model;
        $sub_model = $this->getSubModelRelationName();
        if($this->relation_model){
            return $parent_model->{$sub_model} ? $parent_model->{$sub_model}->{strtolower($this->relation_model)}->{$this->attribute_get_value ? $this->attribute_get_value : $this->search_column} : '';
        }else{
            return $parent_model->{$sub_model} ? $parent_model->{$sub_model}->{$this->attribute_get_value ? $this->attribute_get_value : $this->search_column} : '';
        }
    }

    private function subModelIsActive(){
        $parent_model = $this->parent_model;
        $sub_model = $this->getSubModelRelationName();

        return $parent_model->{$sub_model} ? true : false;
    }

    private function getParentSelect(){/*return name string parent select*/
        return strtolower($this->parent_model->tableName() . '-' . $this->parent_select);
    }

    private function getParentSelectId(){/*return name_id string parent select*/
        return $this->getParentSelect() . '_id';
    }

    private function getThisSelectId(){/*return name_id string this select*/
        return strtolower($this->parent_model->tableName() . '-' . $this->getSubModelId() . ArrayHelper::getValue($this->options, 'id', ''));
    }

    private function getPlaceHolder(){
        return $this->getLabel();
        /*return  ($this->via_ajax ? 'Buscar ' : 'Seleccionar ') . ArrayHelper::getValue($this->options,'place_holder', MagicModel::getSingularTitle($this->getSubModelFromController()));*/
    }

    private function getLabel(){
        return ArrayHelper::getValue($this->options, 'label', MagicModel::getSingularTitle($this->getSubModelFromController()));
    }

    private function isDisabled(){
        return ($this->parent_select && ($this->parent_model->{lcfirst($this->parent_select . '_id')})) > 0 ? false :
            ($this->parent_select && !$this->parent_select_id) || ArrayHelper::getValue($this->options, 'disabled', false);
    }

    private function run(){
        if($this->parent_select) $this->registerJs();

        if($this->via_ajax == true || $this->parent_select || $this->relation_model) {
            echo $this->form->field($this->parent_model, $this->getSubModelId())->widget(Select2::classname(), [
                'initValueText' => $this->getValue(),
                'theme' => self::THEME,
                'options' => array_merge(
                    $this->options,
                    [
                        'id'            => $this->getThisSelectId(),
                        'placeholder'   => $this->getPlaceHolder(),
                        'disabled'      => $this->isDisabled(),
                        'theme' => Select2::THEME_BOOTSTRAP,
                    ]
                ),
                'size' => Select2::MEDIUM,
                'pluginOptions'     => [
                    'style' => 'font-size:45px;',
                    'disabled'              => $this->isDisabled(),
                    'allowClear'            => true,
                    'delay'                 => 250,
                    'cache'                 => true,
                    'minimumInputLength'    => $this->minimumInputLength,
                    'language'              => [
                        'errorLoading'  => new JsExpression("function () { return 'Esperando Resultados...'; }"),
                    ],
                    'ajax' => [
                        'url'       => \yii\helpers\Url::to(['/magic-select/magic-select/get-data']),
                        'dataType'  => 'json',
                        'data'      => new JsExpression(
                            'function(params) {
                                return {
                                    q:params.term,
                                    module:"' . $this->module . '",
                                    model:"' . $this->getSubModelName() . '",
                                    relation_model:"' . $this->relation_model . '",
                                    model_id:"' . $this->getParentId() . '",
                                    attribute_get_value:"' . $this->attribute_get_value . '",
                                    special_function_search:"' . $this->special_function_search . '",
                                    column_name:"' . $this->search_column . '"' . ($this->parent_select ? ',
                                    parent_select:"' . strtolower($this->parent_select) . '",
                                    parent_select_id:getParentSelectId()' : '') . '
                                }; 
                            }'
                        ),
                    ],
                    'escapeMarkup'      => new JsExpression('function (markup) { return markup; }'),
                    'templateResult'    => new JsExpression('function (response) { return response.text; }'),
                    'templateSelection' => new JsExpression('function (response) { return response.text; }'),
                ],
                'addon' => $this->getAddon()
            ])->label($this->getLabel());
        }else{
            $model_class = "app\\modules\\$this->module\\models\\";
            $__model = $model_class.ucwords($this->getSubModelRelationName());
            $_model = new $__model ;

            echo $this->form->field($this->parent_model, $this->getSubModelId())->widget(\kartik\widgets\Select2::classname(), [
                'theme' => self::THEME,
                'data' => \yii\helpers\ArrayHelper::map($_model::find()->orderBy('id')->all(),
                    function($model) {
                        return $model->id;
                    },
                    function($model) {
                        return $model->{$this->search_column};
                    }
                ),
                'options' => [
                    'placeholder'   => $this->getPlaceHolder(),
                    'onChange'      => ArrayHelper::getValue($this->options, 'onChange', 'return false;'),
                    'url'           => \yii\helpers\Url::to(ArrayHelper::getValue($this->options, 'url', '')),
                    'id'            => $this->getThisSelectId(), 'disabled' => ($this->parent_select && !$this->parent_select_id ? true : false)
                ],
                'pluginOptions'     => [
                    'allowClear'        => true,
                ],
                'addon' => [
                    'prepend' => [
                        'content' => Html::tag('i', '', ['class' => MagicModel::getIcon($this->getSubModelFromController())])
                    ],
                    //'append' => $this->addon ? $this->addon['append'] : []
                ]
            ])->label($this->getLabel());;
        }
    }

    private function getAddon(){
        $url_for_create = [$this->getUrlForCreate()];
        if($this->parent_select){
            $url_for_create = [
                $this->getUrlForCreate(),
                'magic_response_attribute_id' => $this->getThisSelectId(),
                'magic_response_attribute_value' => ($this->attribute_get_value ? $this->attribute_get_value : $this->search_column)
            ];
        }

        return $this->addon ? $this->addon : ($this->setButtons ? [
            'prepend' => [
                'content' => Html::tag('i', '', ['class' => MagicModel::getIcon($this->getSubModelFromController())])
            ],
            'append' => [
                'content' => ($this->subModelIsActive() ? GhostHtml::a(
                        '<span class="glyphicon glyphicon-pencil"></span>',
                        [$this->getUrlForUpdate(), 'id' => $this->parent_model->{$this->getSubModelId()}],
                        [
                            'id'        => 'use-modal',
                            'onClick'   => 'return false;',
                            'url'       => Url::to([$this->getUrlForUpdate()]),
                            'class'     => 'btn btn-primary btn-group btn-flat',
                            'ajaxOptions' => ArrayHelper::getValue($this->modal_options, 'ajaxOptions', ''),
                            'jsFunctions' => ArrayHelper::getValue($this->modal_options, 'jsFunctions', '')
                        ]
                    ) : '') . GhostHtml::a(
                        '<span class="glyphicon glyphicon-plus"></span>',
                        [$this->getUrlForCreate(),
                            (!$this->parent_select ? 'magic_response_attribute_id' : '') => $this->getThisSelectId(),
                            (!$this->parent_select ? 'magic_response_attribute_value' : '') => ($this->attribute_get_value ? $this->attribute_get_value : $this->search_column)
                        ],
                        [
                            'id'        => 'use-modal',
                            'onClick'   => 'return false;',
                            'url'       => Url::to([$this->getUrlForCreate() . '?magic_response_attribute_id = ' . $this->getSubModelId() . '&magic_response_attribute_value=' . $this->search_column]),
                            'class'     => 'btn btn-success btn-group btn-flat btn-create-for-' . $this->getThisSelectId()  . ($this->isDisabled() ? ' disabled' : ''),
                            'ajaxOptions' => ArrayHelper::getValue($this->modal_options, 'ajaxOptions', ''),
                            'jsFunctions' => ArrayHelper::getValue($this->modal_options, 'jsFunctions', '')
                        ]
                    ),
                'asButton' => true
            ]
        ] : '');
    }

    public static function getExternalConfiguration($options){
        $selector = new self();
        $selector->setConfiguration($options);

        return  [
            'url'       => \yii\helpers\Url::to(['/magic-select/magic-select/get-data']),
            'dataType'  => 'json',
            'data'      => new JsExpression(
                'function(params) {
                    return {
                        q:params.term,
                        module:"' . $selector->module . '",
                        model:"' . $selector->getSubModelName() . '",
                        relation_model:"' . $selector->relation_model . '",
                        model_id:"' . $selector->getParentId() . '",
                        attribute_get_value:"' . $selector->attribute_get_value . '",
                        special_function_search:"' . $selector->special_function_search . '",
                        column_name:"' . $selector->search_column . '"' . ($selector->parent_select ? ',
                        parent_select:"' . strtolower($selector->parent_select) . '",
                        parent_select_id:getParentSelectId()' : '') . '
                    }; 
                }'
            ),
        ];
    }
    private function registerJs(){
        $static_select_id = ($this->parent_select_id ? $this->parent_select_id : '$( "#' . $this->getParentSelectId() . '").find("option:selected" ).val()');
        $select_id = $this->getThisSelectId();
        $js = <<< JS
            $( "#{$this->getParentSelectId()}" ).change(function() {
                $('#{$this->getThisSelectId()}').html('').prop('disabled', ($(this).find("option:selected" ).val() === ''));
                
                if($(this).find("option:selected" ).val() === ''){
                    $('.btn-create-for-$select_id').addClass('disabled');
                }else{
                    $('.btn-create-for-$select_id').removeClass('disabled');
                }
            });
            
            function getParentSelectId(){
                return $static_select_id;
            }
JS;
        $this->view->registerJs($js, 3);
    }
}
