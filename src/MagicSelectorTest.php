<?php
/**
 * Created by PhpStorm.
 * User: Alfredo Ramirez
 * Date: 25/3/2018
 * Time: 13:49
 */

namespace app\components\magic\selector;

use app\components\magic\MagicModel;
use kartik\select2\Select2;
use webvimark\modules\UserManagement\components\GhostHtml;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

class MagicSelectorTest
{

    private $options;
    private $view;                  /*Vista del objeto donde se registran los JS*/
    private $module;         /*Origen del Modelo a Buscar*/

    private $form;           /*Objeto Form para crear el input del formulario*/
    private $model;          /*Modelo padre del modelo a buscar*/
    private $relationModel;          /*Modelo padre del modelo a buscar*/
    private $attribute;

    /*$form->field($model, $attribute)*/

    private $parent_model;

    const THEME = Select2::THEME_KRAJEE;

    public static function begin($options)
    {
        $selector = new self();
        $selector->options = $options;
        $selector->setConfiguration($options);
        $selector->run();
    }

    public function setConfiguration($options){
        $this->form             = ArrayHelper::getValue($options, 'form', null);
        $this->module           = ArrayHelper::getValue($options, 'module', Yii::$app->controller->module->id);
    }

    public function getModelObject(){
        return is_array($this->model) ? ArrayHelper::getValue($this->model, 'model', null) : $this->model;
    }

    public function getModelName(){
        $class = $this->getModelObject()->className();
        $class_split = explode('\\', $class);
        return lcfirst (end($class_split));
    }

    private function getModelAttributeId(){
        return is_array($this->model) ? ArrayHelper::getValue($this->model, 'attribute', $this->relationModel . '_id') : $this->relationModel . '_id';
    }

    public function getRelationModel(){
        return ArrayHelper::getValue($this->options, 'relationModel', null);
    }

    private function getRelationModelAttributeId(){
        return $this->getRelationModel() ? is_array($this->relationModel) ? ArrayHelper::getValue($this->relationModel, 'attribute', null) : $this->relationModel . '_id' : null;
    }

    private function getRelationModelAttributeColumnDescription(){
        return is_array($this->relationModel) ? ArrayHelper::getValue($this->relationModel, 'column', 'name') : 'name';
    }

    private function getValue(){
        $model = $this->model;
        $relationModel = $this->relationModel;
        if($this->relationModel){
            return $model->{$relationModel}->{$this->getRelationModelAttributeColumnDescription()};
        }else{
            return $model->{$sub_model} ? $model->{$sub_model}->{$this->attribute_get_value ? $this->attribute_get_value : $this->search_column} : '';
        }
    }

    private function run(){
        /* @var $form yii\widgets\ActiveForm */
        $form = $this->form;
        echo $form->field($this->model, $this->attribute)->widget(Select2::classname(), [
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
