<?php

namespace magicsoft\select\controllers;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * MagicSelectController implements methods for the selector.
 */
class MagicSelectController extends Controller
{

    /**
     * @param null $model
     * @param $column_name
     * @param null $attribute_get_value
     * @param null $special_function_search
     * @param null $module
     * @param null $relation_model
     * @param null $parent_select
     * @param null $parent_select_id
     * @param null $model_id
     * @param null $q
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionGetData(
        $model,
        $column_name,
        $attribute_get_value = null,
        $special_function_search = null,
        $module = null,
        $relation_model = null,
        $parent_select = null,
        $parent_select_id = null,
        $model_id = null,
        $q = null
    ){
        if(!Yii::$app->request->isAjax) throw new NotFoundHttpException('The requested page does not exist.');

        $model_route = $module ? "app\\modules\\$module\\models\\" : "app\\models\\";

        $__model = $model_route . ucwords($model);

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $out = ['results' => ['id' => '', 'text' => '']];

        $relation_model = strtolower($relation_model);

        if(!is_null($q)) {
            if ($special_function_search) {
                $resultModel = $__model::{$special_function_search}($q);
            } else {
                $resultModel = $__model::find();

                if ($relation_model) $resultModel->joinWith($relation_model);

                $resultModel->where(['like', ($relation_model ? $relation_model . '.' : '') . $column_name, $q]);
            }
        }else{
            $resultModel = $__model::find();
        }

        if ($parent_select) $resultModel->andWhere([$parent_select . '_id' => $parent_select_id]);
        $resultModel->limit(20);

        $data = ArrayHelper::map( $resultModel->all(),
            function ($model){
                return $model->id;
            },
            function ($model) use($relation_model, $column_name, $attribute_get_value) {
                return $relation_model ? $model->{$relation_model}->{$column_name} : $model->{$attribute_get_value ? $attribute_get_value : $column_name};
            }
        );

        $_out = [];

        $controller = strtolower(preg_replace('/([A-Z])/', '-$1', lcfirst ($model)));
        $controller = mb_strtolower($controller);

        /*$_out[] = ['id' => null, 'text' => GhostHtml::a(
            '<i class="fa fa-plus"></i> Agregar',
            ['/' . $module . '/' . $controller . '/create'],
            [
                'id' => 'use-modal',
                'style' => 'padding:-10px',
                'onClick' => 'return false;'
            ]
        )];*/


        foreach ($data as $key => $_data){
            $_out[] = ['id' => $key, 'text' => $_data];
        }

        $out['results'] = $_out;

        return $out;
    }
}
