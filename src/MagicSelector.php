<?php
/**
 * Created by PhpStorm.
 * User: Alfredo Ramirez
 * Date: 25/3/2018
 * Time: 13:49
 */

namespace magicsoft\select;

use app\components\magic\MagicCrypto;
use app\components\magic\MagicModel;
use kartik\select2\Select2;
use webvimark\modules\UserManagement\components\GhostHtml;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\JsExpression;
use yii\web\View;


/**
 * MagicSelect widget is a Yii2 wrapper for the Select2 Krajee plugin.
 *
 * The MagicSelect can generate lists of dynamic data, without configuring modeles, views or controllers.
 *
 * @author Alfredo Ramirez <alfredrz2012@gmail.com>
 * @since 1.0
 * @see https://github.com/select2/select2
 */

class MagicSelector extends Select2
{
    /**
     * MagicSelect relation in your model
     */
    public $relation;

    /**
     * MagicSelect searchColumns: Columns used to generate the search in the BD.
     */
    public $searchColumns;

    /**
     * MagicSelect columnDescription is the attribute or function to get data result, can be the same searchColumn or a function in model.
     */
    public $columnDescription;

    /**
     * MagicSelect join can get you a union in the database, for example a user who has a profile, The union is user->profile
     */
    public $join;

    /**
     * MagicSelect parentRelation for multiple chain select
     */
    public $parentRelation;

    /**
     * MagicSelect parentRelationId is the value_id for parentSelect
     */
    public $staticParentRelationValue;

    /**
     * Decide if it contains action buttons (create and update)
     */
    public $setButtons;

    /**
     * Define the actions of modal (update or create)
     */
    public $modalOptions;

    /**
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->setConfig();
    }

    private function setConfig()
    {
        if($this->parentRelation) $this->registerJs();

        if($this->setButtons === null) $this->setButtons = true;
        $this->setRelation();

        $this->theme = isset($this->theme) ? $this->theme : self::THEME_DEFAULT;

        $this->initValueText = $this->getValue();

        $this->options = array_merge(
            $this->options,
            [
                'id'            => $this->getThisSelectId(),
                'placeholder'   => $this->getPlaceHolder(),
            ]
        );

        $this->pluginOptions = array_merge(
            [
                'style' => 'font-size:45px;',
                'disabled' => $this->isDisabled(),
                'allowClear' => true,
                'delay' => 250,
                'cache' => true,
                'minimumInputLength' => 0,
                'language' => [
                    'errorLoading' => new JsExpression("function () { return'" . Yii::t('mselect', 'waiting for results') . "'; }"),
                ],
                'ajax' => [
                    'url' => \yii\helpers\Url::to(['/magic-select/magic-select/get-data']),
                    'dataType' => 'json',
                    'data' => new JsExpression(
                        'function(params) {
                                return {
                                    q:params.term,
                                    class:"' . $this->getClass() . '",
                                    join:"' . $this->join . '",
                                    search_columns:"' . $this->getColumnName() . '",
                                    column_description:"' . MagicCrypto::encrypt($this->getColumnDescription()) . '"' . ($this->parentRelation ? ',
                                    parent_relation:"' . strtolower($this->parentRelation) . '",
                                    parent_relation_id:get' . $this->parentRelation . 'Value()' : ''). '
                                    
                                };
                            }'
                    ),
                ],
                'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                'templateResult' => new JsExpression('function (response) { return response.text; }'),
                'templateSelection' => new JsExpression('function (response) { return response.text; }'),
            ],
            $this->pluginOptions
        );

        $this->addon = $this->getAddon();
    }

    /**
     * @return string
     */
    private function getClass()
    {
        return MagicCrypto::encrypt($this->model->getRelation($this->relation)->modelClass);
    }

    /**
     * @return mixed|string
     */
    public function getModule()
    {
        foreach ($_class = explode('\\', $this->model->className()) as $key => $value){
            if($value == 'modules'){
                return ArrayHelper::getValue($_class, $key + 1, '');
                break;
            }
        }

        return '';
    }

    private function setRelation()
    {
        if(!$this->relation){
            $array = explode('_', $this->attribute);
            $this->relation = strtolower(current($array));
        }
    }

    /**
     * @return string
     */
    public function getModelForSearch()
    {
        $array = explode('\\', $this->model->getRelation($this->relation)->modelClass);
        return strtolower(end($array));
    }

    /**
     * @return bool
     */
    private function isDisabled()
    {
        return ($this->parentRelation && ($this->model->{lcfirst($this->parentRelation . '_id')})) > 0 ? false :
            ($this->parentRelation && !$this->staticParentRelationValue) || ArrayHelper::getValue($this->options, 'disabled', false);
    }

    /**
     * @return string
     */
    private function getValue()
    {
        $parent_model = $this->model;
        if($this->join){
            return $parent_model->{$this->relation} ? $parent_model->{$this->relation}->{strtolower($this->join)}->{$this->getColumnDescription()} : null;
        }else{
            return $parent_model->{$this->relation} ? $parent_model->{$this->relation}->{$this->getColumnDescription()} : null;
        }
    }

    /**
     * @return string
     */
    private function getColumnName()
    {
        if($this->searchColumns){
            return MagicCrypto::encrypt($this->searchColumns);
        }else{
            $relationModel = $this->model->getRelation($this->relation)->modelClass;
            $class = new $relationModel;

            if($this->join){
                $_relationModel = $class->getRelation($this->join)->modelClass;
                $_class = new $_relationModel;
                return MagicCrypto::encrypt($_class->hasProperty('name') ? 'name' : $_class->hasProperty('description') ? 'description' : null);
            }else{
                return MagicCrypto::encrypt($class->hasProperty('name') ? 'name' : $class->hasProperty('description') ? 'description' : null);
            }
        }
    }

    /**
     * @return string
     */
    private function getColumnDescription()
    {
        if($this->columnDescription) return $this->columnDescription;

        $relationModel = $this->model->getRelation($this->relation)->modelClass;
        $class = new $relationModel;

        if($this->join){
            $_relationModel = $class->getRelation($this->join)->modelClass;
            $_class = new $_relationModel;
            return $_class->hasProperty('name') ? 'name' : $_class->hasProperty('description') ? 'description' : $this->getColumnName();
        }else{
            return $class->hasProperty('name') ? 'name' : $class->hasProperty('description') ? 'description' : $this->getColumnName();
        }
    }

    /**
     * @return string
     */
    private function getThisSelectId()
    {
        return $this->model->formName() . '-' . $this->attribute;
    }

    /**
     * @return string
     */
    private function getUrlForCreate(){
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '-\\1', '/' . $this->getModule() . '/' . $this->getModelForSearch())) . '/create';
    }

    /**
     * @return string
     */
    private function getUrlForUpdate()
    {
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '-\\1', '/' . $this->getModule() . '/' . $this->getModelForSearch())) . '/update';
    }

    /**
     * @return false|mixed|string|string[]|null
     */
    private function getControllerForModelByFind()
    {
        $string = strtolower(preg_replace('/([A-Z])/', '-$1', lcfirst ($this->getModelForSearch())));
        return mb_strtolower($string);
    }

    /**
     * @return array|string
     */
    private function getAddon()
    {
        return $this->addon ? $this->addon : [
            'prepend' => [
                'content' => Html::tag('i', '', ['class' => MagicSelectHelper::getIcon($this->getControllerForModelByFind())])
            ],
            'append' => $this->setButtons ? [
                'content' => ($this->subModelIsActive() ? GhostHtml::a(
                        '<span class="glyphicon glyphicon-pencil"></span>',
                        [$this->getUrlForUpdate(), 'id' => $this->attribute],
                        [
                            'id' => 'use-modal',
                            'onClick' => 'return false;',
                            'class' => 'btn btn-primary btn-group btn-flat',
                            'ajaxOptions' => ArrayHelper::getValue($this->modalOptions, 'ajaxOptions', ''),
                            'jsFunctions' => ArrayHelper::getValue($this->modalOptions, 'jsFunctions', '')
                        ]
                    ) : '') . GhostHtml::a(
                        '<span class="glyphicon glyphicon-plus"></span>',
                        [$this->getUrlForCreate(),
                            (!$this->parentRelation ? 'magic_response_attribute_id' : '') => $this->getThisSelectId(),
                            (!$this->parentRelation ? 'magic_response_attribute_value' : '') => $this->getColumnDescription()
                        ],
                        [
                            'id'        => 'use-modal',
                            'onClick'   => 'return false;',
                            'class'     => 'btn btn-success btn-group btn-flat btn-create-for-' . $this->getThisSelectId()  . ($this->isDisabled() ? ' disabled' : ''),
                            'ajaxOptions' => ArrayHelper::getValue($this->modalOptions, 'ajaxOptions', ''),
                            'jsFunctions' => ArrayHelper::getValue($this->modalOptions, 'jsFunctions', '')
                        ]
                    ),
                'asButton' => true
            ] : ''
        ];
    }

    /**
     * @return mixed
     */
    private function subModelIsActive()
    {
        return $this->model->{$this->relation};
    }

    /**
     * @return mixed
     */
    private function getPlaceHolder()
    {
        return $this->getLabel();
    }

    /**
     * @return mixed
     */
    private function getLabel()
    {
        return ArrayHelper::getValue($this->options, 'label', MagicModel::getSingularTitle($this->getControllerForModelByFind()));
    }

    /**
     * @return string
     */
    private function getParentSelect()
    {
        return $this->model->formName() . '-' . $this->parentRelation;
    }

    /**
     * @return string
     */
    private function getParentSelectId()
    {
        return $this->getParentSelect() . '_id';
    }

    private function registerJs()
    {
        $static_select_id = ($this->staticParentRelationValue ? $this->staticParentRelationValue : '$( "#' . $this->getParentSelectId() . '").find("option:selected" ).val()');
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
            
            function get{$this->parentRelation}Value(){
                return $static_select_id;
            }
JS;
        $this->view->registerJs($js, View::POS_END);
    }
}