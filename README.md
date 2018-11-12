Magic select
============
Magic select fully utilizes the functionality of https://github.com/kartik-v/yii2-widget-select2, but extends its functionality to function dynamically without configuration.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist magicsoft/yii2-select "*"
```

or add

```
"magicsoft/yii2-select": "*"
```

to the require section of your `composer.json` file.

Usage
-----

You can use directly from a form, the widget will dynamically build the selector with dynamic query.

```php
echo $form->field($model, 'attribute_id')->widget(\magicsoft\select\MagicSelector::className(), []);

With this configuration, the witget assumes that its fields of search and return of data are: 'name' or 'description'
```

But you can configure your own search and data return fields
```php
echo $form->field($model, 'attribute_id')->widget(\magicsoft\select\MagicSelector::className(), [
     'searchColumns' => 'code,name,...',
     'columnDescription' => 'description' 
])?>
//searchColumns: one or more fiel, separed by ',';
//columnDescription: This can will be field in the table or function in model.
```

Configure multiples select with parent select
```php
//This is a parent select
echo $form->field($model, 'country_id')->widget(\magicsoft\select\MagicSelector::className(), []);

//This is a second select
echo $form->field($model, 'state_id')->widget(\magicsoft\select\MagicSelector::className(), [
     'parentRelation' => 'country'
]);

//This is a tree select
echo $form->field($model, 'province_id')->widget(\magicsoft\select\MagicSelector::className(), [
     'parentRelation' => 'state'
]);

//... More select
```
The second selector connects with the first, the third with the second....

## License

**yii2-select** is released under the BSD 3-Clause License. See the bundled `LICENSE.md` for details.