<?php
/**
 * Created by PhpStorm.
 * User: ethan
 * Date: 11/5/18
 * Time: 6:15 PM
 */

namespace magicsoft\select;

use Yii;
use yii\helpers\ArrayHelper;

class MagicSelectHelper
{
    public static $configurationsModels = [
        'default' => [
            'icon' => 'fa fa-list-ul',
        ],
    ];

    /**
     * @return array
     */
    public static function mergeConfiguration(){
        return array_merge(self::$configurationsModels, ArrayHelper::getValue(Yii::$app->params, 'configForMagicSelect', []));
    }

    /**
     * @param null $model
     * @return mixed
     */
    public static function getIcon($model = null){
        return ArrayHelper::getValue(self::getModel($model), 'icon', self::getDefaultModel()['icon']);
    }

    /**
     * @param null $model
     * @return mixed
     */
    public static function getModel($model = null){
        $model = $model ? $model : Yii::$app->controller->id;
        return ArrayHelper::getValue(self::mergeConfiguration(), $model, self::getDefaultModel());
    }

    /**
     * @return mixed
     */
    public static function getDefaultModel(){
        return self::mergeConfiguration()['default'];
    }
}