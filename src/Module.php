<?php
namespace magicsoft\select;

/**
 * MagicSelect Module
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'magicsoft\select\controllers';
    //public $defaultRoute = "audit";

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        // custom initialization code goes here
    }
}