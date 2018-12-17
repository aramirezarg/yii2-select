<?php

namespace magicsoft\select\controllers;

use magicsoft\base\MagicSelectHelper;
use magicsoft\base\MagicCrypto;
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
     * @param $search_data
     * @param $return_data
     * @param null $parent
     * @param null $parent_value
     * @param null $own_function_search
     * @param null $join
     * @param null $q
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionGetData(
        $class,
        $search_data,
        $return_data,
        $parent = null,
        $parent_value = null,
        $join = null,
        $own_function_search = null,
        $q = null
    ){
        if(!Yii::$app->request->isAjax) throw new NotFoundHttpException('The requested page does not exist.');
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $class = MagicCrypto::decrypt($class);
        $search_data = MagicCrypto::decrypt($search_data);
        $own_function_search = MagicCrypto::decrypt($own_function_search);

        $join = strtolower($join);

        if(!is_null($q)){
            if ($own_function_search) {
                $resultModel = $class::{$own_function_search}($q);
            }else{
                $resultModel = $class::find();

                if ($join) $resultModel->joinWith($join);

                $resultModel->where(['like', ($join ? $join . '.' : '') . ' concat(' . $search_data . ')', $q]);
            }
        }else{
            $resultModel = $class::find();
        }

        if ($parent) $resultModel->andWhere([$parent . '_id' => $parent_value]);

        $resultModel->orderBy(['id'=>SORT_DESC])->limit(20);

        return MagicSelectHelper::getDataSelect($resultModel, $join, $return_data);
    }
}
