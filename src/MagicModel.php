<?php
/**
 * Created by PhpStorm.
 * User: Alfredo Ramirez
 * Date: 21/3/2018
 * Time: 14:41
 */

namespace magicsoft\select;

use Yii;
use yii\helpers\ArrayHelper;

class MagicModel
{
    public static $tituloGeneral = "";
    public static $models = [
        'system'        => [
            'icon'          => 'fa fa-tint',
            'title'         => ['singular' => 'Sistema Ficha Mortalidad de Niños', 'plural' => 'Sistema Ficha Mortalidad de Niños'],
            'group'         => 'operating'
        ],

        'default'       => [
            'icon'          => 'glyphicon glyphicon-book',
            'title'          => 'Sistema',
            'group'         => 'site', 'sub_group'     => 'site'
        ],

        'user-interface-config'       => [
            'icon'          => 'fa fa-gear',
            'title'         => ['singular' => 'Interfaz de Usuario', 'plural' => 'Interfaces de Usuario'],
            'group'         => 'config'
        ],

        'config'            => ['icon' => 'fa fa-gear'],
        'option'            => ['icon' => 'fa fa-gear'],
        'filter'            => ['icon' => 'fa fa-filter'],
        'reporte'            => ['icon' => 'fa fa-file'],
        'visualization'     => ['icon' => 'fa fa-eye'],
        'audit'             => [
            'icon'          => 'fa fa-history',
            'name'          => 'Auditoría',
            'group'         => 'audit', 'sub_group' => 'audit',
            'ajax'          => false
        ],
        'auth'              => [
            'icon'      => 'ion ion-android-lock',
            'title'      => 'Autorización',
            'group'     => 'user'
        ],

        'user'              => ['icon' => 'ion ion-android-lock', 'title' => ['plural' => 'Usuario', 'singular' => 'Usuarios'], 'group' => 'user', 'ajax' => true, 'freeAjax' => ['set']],
        'role'              => ['icon' => 'fa fa-user-secret', 'title' => 'Roles de Usuario', 'group' => 'user', 'ajax' => true, 'freeAjax' => ['view', 'index']],
        'permission'        => ['icon' => 'fa fa-unlock-alt', 'title' => 'Permisos de Usuario', 'group' => 'user', 'ajax' => true],
        'user-permission'        => ['icon' => 'fa fa-unlock-alt', 'title' => 'Permisos de Usuario', 'group' => 'user', 'ajax' => true],
        'auth-item-group'   => ['icon' => 'fa fa-unlock-alt', 'title' => 'Item',       'group' => 'user', 'ajax' => true],

        'user-visit-log'    => [
            'icon'      => 'fa fa-history',
            'title'     => 'Log de Visitas',
            'group'     => 'user',
            'ajax'      => true
        ],
        'configuracion'       => [
            'icon'      => 'fa fa-institution',
            'title'     => ['singular' => 'Configuración', 'plural' => 'Configuraciones'],
            'group'     => 'config',
            'ajax'      => false, 'freeAjax' => ['view', 'create']
        ],
        'ficha'       => [
            'icon'      => 'fa  fa-child',
            'title'     => ['singular' => 'Ficha de mortalidad de niños', 'plural' => 'Fichas de mortalidad de niños'],
            'group'     => 'ficha',
            'ajax'      => false, 'freeAjax' => ['view', 'create']
        ],
        'ficha-reporte'       => [
            'icon'      => 'fa fa-file',
            'title'     => ['singular' => 'Ficha mortalidad Reporte', 'plural' => 'Fichas mortalidad Reportes'],
            'group'     => 'ficha',
            'ajax'      => true
        ],
        'empleado'       => [
            'icon'      => 'fa  fa-users',
            'title'     => ['singular' => 'Empleado', 'plural' => 'Empleados'],
            'group'     => 'config',
            'ajax'      => true, 'freeAjax' => ['view', 'create']
        ],
        'region'       => [
            'icon'      => 'fa  fa-map',
            'title'     => ['singular' => 'Region', 'plural' => 'Regiones'],
            'group'     => 'config',
            'ajax'      => true, 'freeAjax' => ['view', 'create']
        ],
        'departamento'       => [
            'icon'      => 'fa  fa-map-o',
            'title'     => ['singular' => 'Departamento', 'plural' => 'Departamentos'],
            'group'     => 'config',
            'ajax'      => true, 'freeAjax' => ['view', 'create']
        ],
        'municipio'       => [
            'icon'      => 'fa  fa-map-signs',
            'title'     => ['singular' => 'Municipio', 'plural' => 'Municipios'],
            'group'     => 'config',
            'ajax'      => true, 'freeAjax' => ['view', 'create']
        ],
        'unidad'       => [
            'icon'      => 'fa  fa-map-marker',
            'title'     => ['singular' => 'Unidad', 'plural' => 'Unidades'],
            'group'     => 'config',
            'ajax'      => true, 'freeAjax' => ['view', 'create']
        ],
        'malformacion'       => [
            'icon'      => 'fa  fa-bug',
            'title'     => ['singular' => 'Malformación', 'plural' => 'Malformaciones'],
            'group'     => 'config',
            'ajax'      => true, 'freeAjax' => ['view', 'create']
        ],
        'causa-muerte' => [
            'icon'      => 'fa fa-bed',
            'title'     => ['singular' => 'Causa de Muerte', 'plural' => 'Causas de Muerte'],
            'group'     => 'config',
            'ajax'      => true
        ],
        'admin' =>          ['icon' => 'ion ion-ios-pie-outline', 'name' => 'Administrar',      'group' => 'admin'],
            'browser'         => [
                'icon'          => 'fa fa-globe',
            ],
    ];

    public static function getDefaultModel($model = null){
        return self::$models['default'];
    }

    public static function getModel($model = null){
        $model = $model ? $model : Yii::$app->controller->id;
        return ArrayHelper::getValue(self::$models, $model, self::getDefaultModel());
    }

    public static function getIcon($model = null){
        return ArrayHelper::getValue(self::getModel($model), 'icon', self::getDefaultModel()['icon']);
    }

    public static function getGroup($model = null){
        return ArrayHelper::getValue(self::getModel($model), 'group', self::getDefaultModel()['group']);
    }

    public static function getSubGroup($model = null){
        return ArrayHelper::getValue(self::getModel($model), 'sub_group', self::getDefaultModel()['sub_group']);
    }

    public static function getSingularTitle($model = null){
        $array_title = ArrayHelper::getValue(self::getModel($model), 'title', []);
        return ArrayHelper::getValue($array_title, 'singular', is_array($array_title) ? Yii::$app->controller->id : $array_title);
    }

    public static function getPluralTitle($model = null){
        $array_title = ArrayHelper::getValue(self::getModel($model), 'title', []);
        return ArrayHelper::getValue($array_title, 'plural', is_array($array_title) ? Yii::$app->controller->id : $array_title);
    }

    public static function isAjax($model = null){
        return ArrayHelper::getValue(self::getModel($model), 'ajax', false);
    }

    public static function isFreeAjax($model = null, $action){//var_dump($model); die();
        if (self::isAjax($model)) {
            return in_array($action, ArrayHelper::getValue(self::getModel($model), 'freeAjax', []));
        }
        return true;
    }
}