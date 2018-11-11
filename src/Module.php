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

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    protected $_msgCat = 'mselect';
}