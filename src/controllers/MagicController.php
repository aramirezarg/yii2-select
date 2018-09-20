<?php
/**
 * User: aramirezarg
 * Date: 27/5/2017
 * Time: 11:39 AM
 */

namespace magicsoft\select\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

trait MagicController
{
    public $baseView = '';

    protected function getErrors($_errors){
        $title = Yii::t('yii', 'Please fix the following errors:');
        $errors = '';
        foreach ($_errors as $row) {
            foreach ( $row as $f){
                $errors .= '<span style=\'color: red\' class = \'glyphicon glyphicon-remove-sign\'></span> ' . $f . '<br>';
            }
        }
        return ['title' => $title, 'data' => $errors];
    }

    /**
     * @param $param
     * @throws \yii\base\InvalidConfigException
     * @return boolean
     */
    protected function getParam($param){
        return ArrayHelper::getValue(\Yii::$app->request->getQueryParams(), $param, ArrayHelper::getValue(\Yii::$app->request->getBodyParams(), $param, null));
    }

    protected function getAction(){
        return Yii::$app->controller->action->id;
    }

    /*
     * $options = [
     *      'view'      => null or 'string',    //view to render other view
     *      'mode'      => [true or false, ['relationOne', 'relationTwo', '...']], //[0] = Define save or saveAll, [1] = Define skeepRelations
     *      'redirect'  => [
     *          'action'    => string,  //action in controller
     *          'param'     => string,  //param in actionController
     *          'attribute' => string,  //attribute in model to get value of param.
     *      ],
     *      'image'     =>  [
     *          'model'     => string,  //model to save image, null to same.
     *          'alias'     => string,  //path to save the file
     *          'attribute' => string,  //attribute in model for image or file
     *          'id'        => string   //attribute for to get id in model
     *      ],
     *      'returnUrl'  => [
     *          'action'    => string,  //action in controller
     *          'param'     => string,  //param in actionController
     *          'attribute' => string,  //attribute in model to get value of param.
     *      ],
     *      'call_back_functions' => [] //Function for after save
     * ]
     */

    /**
     * @param $model
     * @param array $options
     * @return array
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     */
    protected function save($model, $options = [])
    {
        if(!$model) return $this->pageNotFound();
        $modal = $this->getParam('modal');

        $view                   = ArrayHelper::getValue($options, 'view', null);
        $mode                   = ArrayHelper::getValue($options, 'mode', [false, []]);
        $image                  = ArrayHelper::getValue($options, 'image', null);
        $redirect               = ArrayHelper::getValue($options, 'redirect', null);
        $returnUrl              = ArrayHelper::getValue($options, 'returnUrl', null);
        $sub_model_update       = ArrayHelper::getValue($options, 'sub_model_update', []);
        $call_back_functions    = ArrayHelper::getValue($options, 'call_back_functions', []);

        $load = $mode[0] ? 'loadAll' : 'load';
        $save = $mode[0] ? 'saveAll' : 'save';

        if( $model->{$load}(Yii::$app->request->post())) {
            $thisTrans = Yii::$app->getDb()->beginTransaction();
            if( $model->{$save}($save == 'saveAll' ? $this->getOnlyRelationsUpdate($model, $mode[1]) : true)) {

                if(is_array($image)){
                    $path = Yii::getAlias($image['path']);
                    if (!file_exists($path)) {
                        mkdir($path, 0777, true);
                    }

                    $__model = ArrayHelper::getValue($image, 'model', null);
                    $_model = $__model ? $model->{$__model} : $model;

                    $_model->{$image['attribute']} = UploadedFile::getInstance($_model, $image['attribute']);

                    if($_model->{$image['attribute']}) {
                        $_model->{$image['attribute']}->saveAs($path . $_model->{$image['id']} . '.' . 'jpg');
                        \webvimark\image\Image::factory($path . $_model->{$image['id']} . '.' . 'jpg')->resize(128, 128)->save($path . $_model->{$image['id']} . '-mini.' . 'jpg');
                    }
                }

                foreach ($sub_model_update as $sub_model){
                    if($sub_model && !$this->saveUsingAudit($sub_model)){
                        $thisTrans->rollBack();
                        return $this->responseError($sub_model);
                        break;
                    }
                }

                foreach ($call_back_functions as $function){
                    $params = ArrayHelper::getValue($function, 'params', []);
                    if(!$function['model']->{$function['function']}($params)){
                        if($function['use_transaction']){
                            $thisTrans->rollBack();
                            return $this->responseError($function['model']);
                        }
                    }
                }

                $thisTrans->commit();

                if(is_array($redirect)) {
                    return $this->redirect([$this->baseView . $redirect['action'], $redirect['param'] => $model->{$redirect['attribute']}] );
                }else{
                    if($modal){
                        if(is_array($returnUrl)){
                            $params = '';
                            if(is_array($returnUrl['param'])) {
                                foreach ($returnUrl['param'] as $key => $param){
                                    $params .= $key . '=' .  $model{$param} . '&';
                                }
                            }else{
                                $params = $returnUrl['param'] . '=' . $model{$returnUrl['attribute']} . '&';
                            }

                            $setUrl = [
                                'setUrl'    => true,
                                'url'       => Url::to($returnUrl['action'] . '?' . $params),
                                'close_parent' => ArrayHelper::getValue($returnUrl, 'close_parent', true),
                                'call_back_function' => ArrayHelper::getValue($returnUrl, 'call_back_function', '')
                            ];
                            return $this->responseSuccess($setUrl);
                        }

                        $magic_response_attribute_id = $this->getParam('magic_response_attribute_id');

                        $magic_response = [];
                        if($magic_response_attribute_id){
                            $magic_response = [
                                'magicResponseSet' => true,
                                'magic_response_attribute_id' => $magic_response_attribute_id,
                                'magic_response_attribute_id_value' => $model->id,
                                'magic_response_attribute_value' => $model->{$this->getParam('magic_response_attribute_value')}
                            ];
                        }

                        return $this->responseSuccess($magic_response);
                    }else{
                        return $this->redirect([ $this->baseView. 'view',
                            'id' => $model->id
                        ]);
                    }
                }
            }else{
                if( $modal ){
                    return $this->responseError($model);
                }else{
                    return $this->render(
                        ($view ? $this->baseView . $view : (($this->getAction() == 'create') ? $this->baseView . 'create' : $this->baseView .'update')), [
                            'model' => $model
                        ]
                    );
                }
            }
        } else {
            return $this->renderIsAjax(
                ($view ? $this->baseView . $view : (($this->getAction() == 'create') ? $this->baseView .'create' : $this->baseView .'update')), ['model' => $model]
            );
        }
    }

    /**
     * @param $models
     * @return bool
     */
    public function saveInSeries($models){
        foreach ($models as $model){
            if(!$model->save(true)){
                return $model;
                break;
            }
        }
        return true;
    }

    /**
     * @param $model
     * @param $relationsUpdated
     * @return array
     */
    private function getOnlyRelationsUpdate($model, $relationsUpdated){
        $allRelations =  $model->getRelationData();
        return array_diff(array_keys($allRelations), $relationsUpdated);
    }

    protected function saveUsingAudit($model, $mode = 'save', $skippedRelations = []){
        $isNewRecord = ($model && $model->isNewRecord);

        if($model && $model->{$mode}($mode == 'saveAll' ? $skippedRelations : true)){
            //Audit::newAction( $model, $this, $isNewRecord );
            return true;
        }
        return false;
    }
    /**
     * @param $view
     * @param array $params
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function renderIsAjax($view, $params = [])
    {
        $modal = $this->getParam('modal');
        if ( $modal && Yii::$app->request->isAjax ) {
            return $this->renderAjax($view, $params);
        } else {
            if(!\magicsoft\select\MagicModel::isFreeAjax(Yii::$app->controller->id, $this->action->id)){
                return $this->pageNotFound();
                //throw new NotFoundHttpException($this->render('@app/views/layouts/error/404error'));
            }
            return $this->render($view, $params);
        }
    }

    /**
     * @param $model
     * @param array $params
     * @param null $data
     * @return array
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    protected function responseError($model, $params = [], $data = null){
        $target = $this->getParam('target');
        if ( $target !== '_blank' && Yii::$app->request->isAjax ) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

            return array_merge( [
                'error' => true,
                'data'  => (( $data && is_array( $data ) ) ? $data : $this->getErrors( $model ? $model->getErrors() : null ) )
            ], $params );
        }elseif ($target !== '_blank'){
            throw new ForbiddenHttpException(ArrayHelper::getValue($data, 'data', 'La operación no pudo completarse.'));
        }
    }

    protected function responseSuccess($params = []){
        $target = $this->getParam('target');
        if ( $target !== '_blank' && Yii::$app->request->isAjax ) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return array_merge([
                'error' => false,
                'data'  => [
                    'title' => 'Completado',
                    'data'  => 'La operación se completó exitosamente'
                ]
            ], $params);
        }elseif ($target !== '_blank'){
            $this->successMgs();
        }
    }

    protected function successMgs($msg = null, $title = null)
    {
        \Yii::$app->getSession()->setFlash('success', [
            'type' => 'success',
            'message' => ($msg)? $msg : 'Registro guardado exitosamente',
            'title' => ($title)? $title : 'Registro Guardado',
        ]);
    }

    protected function errorMgs($msg = null, $title = null)
    {
        \Yii::$app->getSession()->setFlash('success', [
            'type' => 'danger',
            'message' => ($msg)? $msg : 'El registro no se guardó, por favor vuelva a intentarlo',
            'title' => ($title)? $title : 'Intente Nuevamente',
        ]);
    }

    public function requestIsAjax(){
        return Yii::$app->request->isAjax;
    }
}