<?php

/**
 * @copyright  Copyright &copy; Alfredo Ramirez, 2017 - 2018
 * @package    yii2-widgets
 * @subpackage yii2-widget-magicselect
 * @version    1.0.0
 */

namespace magicsoft\select;

use kartik\grid\GridView;
use kartik\select2\Select2;
use magicsoft\base\MagicCrypto;
use magicsoft\base\MagicSelectHelper;
use magicsoft\base\MagicSoftModule;
use magicsoft\base\TranslationTrait;
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
    use TranslationTrait;
    /**
     * MagicSelect relation in your model
     */
    public $relation;

    /**
     * MagicSelect searchData: Columns used to generate the search in the BD.
     */
    public $searchData;

    /**
     * MagicSelect returnData is the attribute or function to get data result, can be the same searchData or a function in model.
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
     * MagicSelect staticParentValue is the value of parent
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
        $this->initI18N(MagicSoftModule::getSorceLangage(), 'magicselect');

        parent::init();
        $this->setConfig();
    }

    private function setConfig()
    {
        if($this->setButtons === null) $this->setButtons = true;
        $this->setRelation();
        $this->setSearchData();
        $this->setReturnData();

        $this->initValueText = $this->getValue();

        $this->options = array_merge(
            $this->options,
            [
                'id' => $this->getThisSelectId(),
                'placeholder' => $this->getPlaceHolder(),
            ]
        );

        $this->pluginOptions = array_merge(
            [
                'disabled' => $this->isDisabled(),
                'allowClear' => true,
                'delay' => 250,
                'cache' => true,
                'minimumInputLength' => 0,
                'language' => [
                    'errorLoading' => new JsExpression("function () { return '" .  Yii::t('magicselect', 'Witing for results...') . "' ; }"),
                ],
                'ajax' => [
                    'url' => \yii\helpers\Url::to(['/magicsoft/magic-select/get-data']),
                    'dataType' => 'json',
                    'data' => new JsExpression(
                        'function(params) {' . $this->getVarForWritingText() . '=params.term;' .
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

        if($this->parent) $this->registerParentJs();
    }

    public static function getColumn($options = []){
        $self = new self($options);
        $self->setRelation();
        $self->registerThisJs();

        if($self->parent){
            $self->registerParentJs();
            if(!$self->model->{$self->attribute} || !$self->model->{$self->relation}::find()->where([$self->parent . '_id' => $self->model->{$self->parent . '_id'}])->all()){
                $self->model->{$self->attribute} = null;
            }
        }

        return [
            'attribute' => $self->attribute,
            'label' => $self->getLabel(),
            'value' => function($model) use ($self){
                return MagicSelectHelper::getDataDescription($model->{$self->relation}, MagicCrypto::encrypt($self->returnData));
            },
            'filterType' => GridView::FILTER_SELECT2,
            'filter' => $self->model->{$self->attribute} ? \yii\helpers\ArrayHelper::map($self->model->{$self->relation}::find()->where(['id' => $self->model->{$self->attribute}])->all(),
                function($model) {
                    return $model->id;
                },
                function($model) use($self) {
                    return MagicSelectHelper::getDataDescription($model, MagicCrypto::encrypt($self->returnData));
                }
            ) : null,
            'filterWidgetOptions' => [
                'pluginOptions' => [
                    'allowClear' => true,
                    'ajax' => [
                        'url' => \yii\helpers\Url::to(['/magicsoft/magic-select/get-data']),
                        'dataType' => 'json',
                        'data' => new JsExpression(
                            'function(params) {' .
                            'return {' .
                            'q:params.term,' .
                            $self->getClasAsParam() .
                            $self->getSearchDataAsParam() .
                            $self->getReturnDataAsParam() .
                            $self->getJoinAsParam() .
                            $self->getOwnFunctionSearchAsParam() .
                            $self->getParentAsParam() .
                            '};' .
                            '}'
                        ),
                    ],
                ],
            ],
            'filterInputOptions' => [
                'placeholder' => $self->getPlaceHolder(),
                'id' => $self->getThisSelectId(),
                'disabled' => $self->isDisabled(),
                'style' => 'width: auto'
            ],
        ];
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
        return $this->parent ? ',parent:"' . $this->parent . '",parent_value:get' . $this->parent . 'Value()' : '';
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
        foreach ($_class = explode('\\', $this->model->getRelation($this->relation)->modelClass) as $key => $value){
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

    public function getParentAttribute(){
        return \yii\helpers\BaseInflector::underscore($this->parent . '_id');
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
        return ($this->parent && ($this->model->{$this->getParentAttribute()})) > 0 ? false :
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
                $class = new $_relationClass;
            }

            $this->searchData = ($class->hasProperty('name') ? 'name' : ($class->hasProperty('description') ? 'description' : null));
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
        return strtolower($this->model->formName() . '-' . $this->attribute);
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
        $buttonCreate = '';
        $user = Yii::$app->user;

        $this->addon = $this->addon ? $this->addon : [
            'prepend' => [
                'content' => Html::tag('i', '', ['class' => MagicSelectHelper::getIcon($this->getControllerForSearchModel())])
            ],
            'append' => $this->setButtons ? [
                'content' => '<div>' .
                    (!$user->can($this->getUpdateUrl()) ? Html::a(
                        '<span class="fa fa-pencil fas fa-pencil-alt"></span>',
                        [$this->getUpdateUrl(), 'id' => ($this->model ? $this->model->{$this->attribute} : null)],
                        [
                            'class' => 'magic-modal btn btn-default btn-flat btn btn-outline-dark btn-for-update-' . $this->getThisSelectId() . ($this->subModelIsActive() ?'': ' disabled'),
                            'ajaxOptions' => ArrayHelper::getValue($this->modalOptions, 'ajaxOptions', '"confirmToLoad":false'),
                            'data-params' => '"magic_select_attribute":' . $this->getThisSelectId() . ',"magic_select_return_data":' . MagicCrypto::encrypt($this->returnData),
                        ]
                    ) : '').
                    (!$user->can($this->getCreateUrl()) ? Html::a(
                        '<span class="fa fa-plus fas fa-plus"></span>',
                        [$this->getCreateUrl()],
                        [
                            'class' => 'magic-modal btn btn-default btn-flat btn btn-outline-dark btn-for-create-' . $this->getThisSelectId()  . ($this->isDisabled() ? ' disabled' : ''),
                            'ajaxOptions' => ArrayHelper::getValue($this->modalOptions, 'ajaxOptions', '"confirmToLoad":false'),
                            'jsFunctions' => ArrayHelper::getValue($this->modalOptions, 'jsFunctions', ('beforeLoad:magicSelect_setTextSearched_ToForm_' . $this->getModelForSearch() . '()')),
                            'data-params' => (!$this->parent ? '"magic_select_attribute":' . $this->getThisSelectId() . ',"magic_select_return_data":' . MagicCrypto::encrypt($this->returnData): '')
                        ]
                    ) : '') . '</div>',
                'asButton' => true,
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
    private function getLabel()
    {
        return $this->model->getAttributeLabel($this->attribute);
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
    private function getParentAttributeIdForm()
    {
        return strtolower($this->model->formName() . '-' . $this->getParentAttribute());
    }

    private function getVarForWritingText(){
        return 'magicSelect_TextSearchedIn_' . $this->getModelForSearch();
    }

    private function registerParentJs()
    {
        $static_parent_id_value = ($this->staticParentValue ? $this->staticParentValue : '$( "#' . $this->getParentAttributeIdForm() . '").find("option:selected" ).val()');
        $select_id = $this->getThisSelectId();

        $js = <<< JS
        $("#{$this->getParentAttributeIdForm()}").change(function(){
            val = objectIsSet(_val = $(this).find("option:selected" ).val()) ? _val : '' ;
            
            $('#{$select_id}').empty().html('').prop('disabled', (val === ''));
            $('.btn-for-create-$select_id').removeClass('disabled').addClass(val === '' ? 'disabled' : '');
            $('.btn-for-update-$select_id').addClass('disabled');
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
        $var_for_writing_data = $this->getVarForWritingText();
        $select_id = $this->getThisSelectId();

        $js = <<< JS
        
    var $var_for_writing_data = '';
    function magicSelect_setTextSearched_ToForm_{$model_for_search_tostring}(){
        $('#{$attribute_on_form}').val($var_for_writing_data).focus();
        $var_for_writing_data = '';
    }
    
    $("#{$this->getThisSelectId()}").change(function(){
        val = (objectIsSet(_val = $(this).find("option:selected" ).val()) ? _val : false);
        $('.btn-for-update-{$this->getThisSelectId()}').removeClass('disabled').addClass(val === false ? 'disabled' : '').prop("href", '{$this->getUpdateUrl()}?id=' + val);
    });
JS;
        $this->view->registerJs($js, View::POS_END);
    }
}