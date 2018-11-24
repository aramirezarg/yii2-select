<?php

/**
 * @copyright  Copyright &copy; Alfredo Ramirez, 2017 - 2018
 * @package    yii2-widgets
 * @subpackage yii2-widget-magicselect
 * @version    1.0.0
 */

namespace magicsoft\select;

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
 * @see https://github.com/aramirezarg/yii2-select
 */

class MagicSelect extends Select2
{
    /**
     * MagicSelect relation in your model
     */
    public $relation;

    /**
     * MagicSelect searchColumns: Columns used to generate the search in the BD.
     */
    public $searchData;

    /**
     * MagicSelect columnDescription is the attribute or function to get data result, can be the same searchColumn or a function in model.
     */
    public $returnData;

    /**
     * MagicSelect join can get you a union in the database, for example a user who has a profile, The union is user->profile
     */
    public $join;

    /**
     * MagicSelect parent for multiple chain select
     */
    public $parent;

    /**
     * MagicSelect parentId is the value_id for parent
     */
    public $staticParentValue;

    /**
     * Decide if it contains action buttons (create and update)
     */
    public $setButtons;

    /**
     * Define the actions of modal (update or create)
     */
    public $modalOptions;

    /**
     * Function for search data in your model
     */
    public $ownFunctionSearch;

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
        if($this->setButtons === null) $this->setButtons = true;
        $this->setRelation();
        $this->setSearchData();
        $this->setReturnData();

        $this->theme = isset($this->theme) ? $this->theme : self::THEME_KRAJEE;

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
                'disabled' => $this->isDisabled(),
                'allowClear' => true,
                'delay' => 250,
                'cache' => true,
                'minimumInputLength' => 0,
                'ajax' => [
                    'url' => \yii\helpers\Url::to(['/magic-select/magic-select/get-data']),
                    'dataType' => 'json',
                    'data' => new JsExpression(
                        'function(params) {_magicSelect_set' . $this->getModelForSearch() . 'WritingText(params.term);' .
                        'return {' .
                        'q:params.term,' .
                        $this->getClasAsParam() .
                        $this->getSearchDataAsParam() .
                        $this->getReturnDataAsParam() .
                        $this->getJoinAsParam() .
                        $this->getOwnFunctionSearchAsParam() .
                        $this->getParentAsParam() .
                        '};' .
                        '}'
                    ),
                ],
                'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                'templateResult' => new JsExpression('function (response) { return response.text; }'),
                'templateSelection' => new JsExpression('function (response) { return response.text; }'),
            ],
            $this->pluginOptions
        );

        $this->setAddon();

        $this->registerThisJs();

        if($this->parent) $this->registerParentFuctionJs();
    }

    private function getClasAsParam(){
        return 'class:"' . $this->getClass() . '",';
    }
    private function getSearchDataAsParam(){
        return 'search_data:"' . MagicCrypto::encrypt($this->searchData) . '",';
    }
    private function getReturnDataAsParam(){
        return 'return_data:"' . MagicCrypto::encrypt($this->returnData) . '"';
    }
    private function getJoinAsParam(){
        return $this->join ? ',join:"' . $this->join . '"' : '';
    }
    private function getOwnFunctionSearchAsParam(){
        return $this->ownFunctionSearch ? ',own_function_search:"' . MagicCrypto::encrypt($this->ownFunctionSearch) . '"' : '';
    }
    private function getParentAsParam(){
        return $this->parent ? ',parent:"' . strtolower($this->parent) . '",parent_value:get' . $this->parent . 'Value()' : '';
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
    private function getModule()
    {
        foreach ($_class = explode('\\', $this->model->className()) as $key => $value){
            if($value == 'modules'){
                return ArrayHelper::getValue($_class, $key + 1, null);
                break;
            }
        }

        return null;
    }

    private function setRelation()
    {
        if(!$this->relation){
            $this->relation = lcfirst(\yii\helpers\BaseInflector::camelize(substr($this->attribute, 0, strlen($this->attribute) - 3)));
        }
    }

    /**
     * @return string
     */
    private function getModelForSearch()
    {
        $array = explode('\\', $this->model->getRelation($this->relation)->modelClass);
        return lcfirst(end($array));
    }

    /**
     * @return bool
     */
    private function isDisabled()
    {
        return ($this->parent && ($this->model->{lcfirst($this->parent . '_id')})) > 0 ? false :
            ($this->parent && !$this->staticParentValue) || ArrayHelper::getValue($this->options, 'disabled', false);
    }

    /**
     * @return string
     */
    private function getValue()
    {
        $model = null;
        if($this->model && $this->model->{$this->relation}){
            $model = $this->model->{$this->relation};
        }

        if($model && $this->join){
            if($model->{$this->join})
                $model = $model->{$this->join};
        }

        return $model ? MagicSelectHelper::getDataDescription($model, MagicCrypto::encrypt($this->returnData)) : null;
    }

    private function getLastSearchField(){
        return is_array($array = explode(',', $this->searchData)) ? end($array) : $this->searchData;
    }

    /**
     * @return string
     */
    private function setSearchData()
    {
        if(!$this->searchData){
            $relationModel = $this->model->getRelation($this->relation)->modelClass;
            $class = new $relationModel;

            if($this->join){
                $_relationClass =  $class->getRelation($this->join)->modelClass;
                $_class = new $_relationClass;

                $this->searchData = $_class->hasProperty('name') ? 'name' : $_class->hasProperty('description') ? 'description' : null;
            }else{
                $this->searchData = $class->hasProperty('name') ? 'name' : $class->hasProperty('description') ? 'description' : null;
            }
        }
    }

    /**
     * @return string
     */
    private function setReturnData()
    {
        if(!$this->returnData) $this->returnData = 'join:' . $this->searchData;
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
    private function getCreateUrl()
    {
        return strtolower(
                preg_replace(
                    '/(?<!^)([A-Z])/',
                    '-\\1',
                    '/' . (($module = $this->getModule())  ? $module . '/' : '') . $this->getControllerForSearchModel()
                )
            ) . '/create';
    }

    /**
     * @return string
     */
    private function getUpdateUrl()
    {
        return strtolower(preg_replace('/(?<!^)([A-Z])/', '-\\1', '/' . $this->getModule() . '/' . $this->getControllerForSearchModel())) . '/update';
    }

    /**
     * @return false|mixed|string|string[]|null
     */
    private function getControllerForSearchModel()
    {
        $string = strtolower(preg_replace('/([A-Z])/', '-$1', lcfirst ($this->getModelForSearch())));
        return mb_strtolower($string);
    }

    /**
     * @return array|string
     */
    private function setAddon()
    {
        $this->addon = $this->addon ? $this->addon : [
            'prepend' => [
                'content' => Html::tag('i', '', ['class' => MagicSelectHelper::getIcon($this->getControllerForSearchModel())])
            ],
            'append' => $this->setButtons ? [
                'content' => '<div>' .
                    GhostHtml::a(
                        '<span class="glyphicon glyphicon-pencil"></span>',
                        [$this->getUpdateUrl(), 'id' => ($this->model ? $this->model->{$this->attribute} : null)],
                        [
                            'id' => 'magic-modal',
                            'onClick' => 'return false;',
                            'class' => 'btn btn-primary btn-flat btn-update-for-' . $this->getThisSelectId() . ($this->subModelIsActive() ?'': ' disabled'),
                            'ajaxOptions' => ArrayHelper::getValue($this->modalOptions, 'ajaxOptions', '"confirmToLoad":false'),
                            'data-params' => '"magic_select_attribute":' . $this->getThisSelectId() . ',"magic_select_return_data":' . MagicCrypto::encrypt($this->returnData)
                        ]
                    ).
                    GhostHtml::a(
                        '<span class="glyphicon glyphicon-plus"></span>',
                        [$this->getCreateUrl()],
                        [
                            'id' => 'magic-modal',
                            'onClick' => 'return false;',
                            'class' => 'btn btn-success btn-flat btn-create-for-' . $this->getThisSelectId()  . ($this->isDisabled() ? ' disabled' : ''),
                            'ajaxOptions' => ArrayHelper::getValue($this->modalOptions, 'ajaxOptions', '"confirmToLoad":false'),
                            'jsFunctions' => ArrayHelper::getValue($this->modalOptions, 'jsFunctions', ('beforeLoad:_magicSelect_set' . $this->getModelForSearch() . 'OnForm()')),
                            'data-params' => (!$this->parent ? '"magic_select_attribute":' . $this->getThisSelectId() . ',"magic_select_return_data":' . MagicCrypto::encrypt($this->returnData): '')
                        ]
                    ) . '</div>',
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
        return $this->model->getAttributeLabel($this->attribute);
    }

    /**
     * @return string
     */
    private function getParentAttributeId()
    {
        return $this->model->formName() . '-' . $this->parent . '_id';
    }

    private function registerParentFuctionJs()
    {
        $static_parent_id_value = ($this->staticParentValue ? $this->staticParentValue : '$( "#' . $this->getParentAttributeId() . '").find("option:selected" ).val()');
        $select_id = $this->getThisSelectId();

        $js = <<< JS
            $( "#{$this->getParentAttributeId()}" ).change(function(){
                val = objectIsSet(_val = $(this).find("option:selected" ).val()) ? _val : '' ;
                
                $('#{$select_id}').html('').prop('disabled', (val === ''));
                $('.btn-create-for-$select_id').removeClass('disabled').addClass(val === '' ? 'disabled' : '');
                $('.btn-update-for-$select_id').removeClass('disabled').addClass('disabled');
            });
            
            function get{$this->parent}Value(){
                return $static_parent_id_value;
            }
JS;
        $this->view->registerJs($js, View::POS_END);
    }

    private function registerThisJs(){
        $model_for_search_tostring = $this->getModelForSearch();
        $attribute_on_form = strtolower($model_for_search_tostring . '-' . $this->getLastSearchField());
        $var_for_writing_data = '_magicSelect_' . $model_for_search_tostring . 'WrittenText';

        $js = <<< JS
            var $var_for_writing_data = '';
            function _magicSelect_set{$model_for_search_tostring}WritingText(value){
                $var_for_writing_data = value;
            }
            
            function _magicSelect_set{$model_for_search_tostring}OnForm(){
                $('#{$attribute_on_form}').val($var_for_writing_data).focus(); $var_for_writing_data = '';
            }
            
            $( "#{$this->getThisSelectId()}" ).change(function(){
                val = objectIsSet(_val = $(this).find("option:selected" ).val()) ? _val : false ;
                $('.btn-update-for-{$this->getThisSelectId()}').removeClass('disabled').addClass(val === false ? 'disabled' : '').prop("href", '{$this->getUpdateUrl()}?id=' + val);
            });
JS;
        $this->view->registerJs($js, View::POS_END);
    }
}