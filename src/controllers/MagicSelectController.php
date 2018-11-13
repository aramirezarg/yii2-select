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
     * @param $class
     * @param $search_columns
     * @param $column_description
     * @param null $join
     * @param null $special_function_search
     * @param null $parent_relation
     * @param null $parent_relation_id
     * @param null $q
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionGetData(
        $class,
        $search_columns,
        $column_description,
        $join = null,
        $special_function_search = null,
        $parent_relation = null,
        $parent_relation_id = null,
        $q = null
    ){
        if(!Yii::$app->request->isAjax) throw new NotFoundHttpException('The requested page does not exist.');
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $class = MagicCrypto::decrypt($class);
        $search_columns = MagicCrypto::decrypt($search_columns);
        $column_description = MagicCrypto::decrypt($column_description);

        $out = ['results' => ['id' => null, 'text' => '']];

        $join = strtolower($join);

        if(!is_null($q)) {
            if ($special_function_search) {
                $resultModel = $class::{$special_function_search}($q);
            } else {
                $resultModel = $class::find();

                if ($join) $resultModel->joinWith($join);

                $resultModel->where(['like', ($join ? $join . '.' : '') . ' concat(' . $search_columns . ')', $q]);
            }
        }else{
            $resultModel = $class::find();
        }

        if ($parent_relation) $resultModel->andWhere([$parent_relation . '_id' => $parent_relation_id]);
        $resultModel->limit(20);

        $data = ArrayHelper::map( $resultModel->all(),
            function ($model){
                return $model->id;
            },
            function ($model) use($join, $column_description) {
                return $column_description;// $join ? $model->{$join}->{$column_description} : $model->{$column_description};
            }
        );

        $_out = [];


        foreach ($data as $key => $_data){
            $_out[] = ['id' => $key, 'text' => $_data];
        }

        $out['results'] = $_out;

        return $out;
    }
}
