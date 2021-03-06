<?php

/**
 * @copyright  Copyright &copy; Alfredo Ramirez, 2017 - 2018
 * @package    yii2-widgets
 * @subpackage yii2-widget-magicselect
 * @version    1.0.0
 */

namespace magicsoft\select;

use foo\bar;
use yii\helpers\Inflector;
use function GuzzleHttp\Psr7\str;
use kartik\grid\GridView;
use kartik\select2\Select2;
use magicsoft\base\MagicCrypto;
use magicsoft\base\MagicSelectHelper;
use magicsoft\base\MagicSoftModule;
use magicsoft\base\TranslationTrait;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;
use yii\helpers\BaseInflector;


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
                'ajax' => $this->getAjaxOptions(),
                'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                'templateResult' => new JsExpression('function (response) { return response.text; }'),
                'templateSelection' => new JsExpression('function (response) { return response.text; }'),
            ],
            $this->pluginOptions
        );

        $this->setAddon();

        $this->registerThisJs();

        if($this->getParent()) $this->registerParentJs();
    }

    public static function getColumn($options = []){
        $self = new self($options);
        $self->setRelation();
        $self->registerThisJs();

        if($self->getParent()){
            $self->registerParentJs();
            if(!$self->model->{$self->attribute} || !$self->model->{$self->relation}::find()->where([$self->getParent() . '_id' => $self->model->{$self->getParent() . '_id'}])->all()){
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
            'filter' => $self->model->{$self->attribute} ? ArrayHelper::map($self->model->{$self->relation}::find()->where(['id' => $self->model->{$self->attribute}])->all(),
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
                    'ajax' => $self->getAjaxOptions(),
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

    private function getAjaxOptions(){
        return [
            'url' => Url::to(['/magicsoft/magic-select/get-data']),
            'dataType' => 'json',
            'data' => new JsExpression(
                'function(params) {' .
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
            )
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
        return $this->getParent() ? ',parent:"' . MagicCrypto::encrypt($this->getThisParentAttribute()) . '",parent_value:get' . $this->getParent() . 'Value()' : '';
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
            $this->relation = lcfirst(BaseInflector::camelize(substr($this->attribute, 0, strlen($this->attribute) - 3)));
        }
    }

    public function getParentAttribute()
    {
        return is_array($this->parent) ? ArrayHelper::getValue($this->parent, 'attribute', null) : BaseInflector::underscore($this->parent . '_id');
    }

    public function getThisParentAttribute()
    {
        $default = BaseInflector::underscore($this->getParent() . '_id');

        return is_array($this->parent) ? ArrayHelper::getValue($this->parent, 'thisAttribute', $default) : $default;
    }

    private function getParent()
    {
        return is_array($this->parent) ? ArrayHelper::getValue($this->parent, 'relation', null) : $this->parent;
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
        return ($this->getParent() && ($this->model->{$this->getParent()})) ? false :
            ($this->getParent() && !$this->staticParentValue) || ArrayHelper::getValue($this->options, 'disabled', false);
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
    private function getBaseControllerClass()
    {
        $base = strtolower (preg_replace(
            '/(?<!^)([A-Z])/',
            '-\\1',
            '/' . (($module = $this->getModule()) ? $module . '/' : '') . $this->getControllerForSearchModel()
        ));

        return substr($base, 0, 2) == '/-' ? '/' . substr($base, 2, strlen ($base)) : $base;
    }

    /**
     * @return string
     */
    private function getCreateUrl()
    {
        return $this->getBaseControllerClass() . '/create';
    }

    /**
     * @return string
     */
    private function getUpdateUrl()
    {
        return $this->getBaseControllerClass() . '/update';
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
                    (!$user->can($this->getUpdateUrl()) ? Html::button(
                        '<span class="fa fa-pencil fas fa-pencil-alt ' .  $this->getThisSelectId() . '-updatetext" style="color: ' . (!$this->getValue() ? 'lightgray' : 'blue') . '"></span>',
                        [
                            'class' => 'magic-modal btn-default btn-flat btn-for-update-' . $this->getThisSelectId() . ($this->subModelIsActive() ? '' : (!$this->getValue() ? ' disabled' : '')),
                            'ajaxOptions' => ArrayHelper::getValue($this->modalOptions, 'ajaxOptions', '"confirmToLoad":false'),
                            'data-params' => '"magic_select_attribute":' . $this->getThisSelectId() . ',"magic_select_return_data":' . MagicCrypto::encrypt($this->returnData),
                            'style' => 'margin-left:1px; height: 100%',
                            'href' => Url::to([$this->getUpdateUrl(), 'id' => ($this->model ? $this->model->{$this->attribute} : null)]),
                            'disabled' => !$this->getValue()
                        ]
                    ) : '').
                    (!$user->can($this->getCreateUrl()) ? Html::button(
                        '<span class="fa fa-plus fas fa-plus ' .  $this->getThisSelectId() . '-createtext" style="color: green"></span>',
                        [
                            'class' => 'magic-modal btn-default btn-flat btn-for-create-' . $this->getThisSelectId()  . ($this->isDisabled() ? ' disabled' : ''),
                            'ajaxOptions' => ArrayHelper::getValue($this->modalOptions, 'ajaxOptions', '"confirmToLoad":false'),
                            'jsFunctions' => ArrayHelper::getValue($this->modalOptions, 'jsFunctions', ('beforeLoad:magicSelect_setTextSearched_ToForm_' . $this->getModelForSearch() . '()')),
                            'data-params' => (!$this->getParent() ? '"magic_select_attribute":' . $this->getThisSelectId() . ',"magic_select_return_data":' . MagicCrypto::encrypt($this->returnData): ''),
                            'style' => 'height: 100%',
                            'href' => Url::to([$this->getCreateUrl()])
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
        $select_idCamel = Inflector::id2camel($select_id);

        $js = <<< JS
        $("#{$this->getParentAttributeIdForm()}").change(function(){
            val = objectIsSet(_val = $(this).find("option:selected" ).val()) ? _val : false ;
            
            $('#{$select_id}').empty().append($("<option>", {value: null, text: null})).val(null).prop('disabled', (val === false)).trigger('change');
            
            {$select_idCamel}SetButtonStatus(val);
        });
        
        function get{$this->getParent()}Value(){
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
        $select_idCamel = Inflector::id2camel($select_id);

        $updateUrl = Url::to([$this->getUpdateUrl()]);

        $js = <<< JS
        
    var $var_for_writing_data = '';
    function magicSelect_setTextSearched_ToForm_{$model_for_search_tostring}(){
        $('#{$attribute_on_form}').val($var_for_writing_data).focus();
        $var_for_writing_data = '';
    }
    
    function {$select_idCamel}SetButtonStatus(status){
        var _attribute = (status === false ? 'no-link' : 'href');
         $('.btn-for-update-{$this->getThisSelectId()}').attr({'disabled': status === false, 'href' : (status === false ? 'no-link' : '{$updateUrl}?id=' + status)});
         $('.{$this->getThisSelectId()}-updatetext').css("color", (status === false ? 'lightgray' : 'blue'));
    }
    
    $("#{$this->getThisSelectId()}").change(function(){
        val = (objectIsSet(_val = $(this).find("option:selected" ).val()) ? _val : false);
        {$select_idCamel}SetButtonStatus(val);
    });
JS;
        $this->view->registerJs($js, View::POS_END);
    }
}