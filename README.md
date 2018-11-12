Magic select
============
Magic Select

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

k
Usage
-----

You can use directly from a form, the widget will dynamically build the selector with dynamic query.

```php
<?= $form->field($model, 'attribute_id')->widget(\magicsoft\select\MagicSelector::className(), [])?>

With this configuration, the witget assumes that its fields of search and return of data are: 'name' or 'description'
```

But you can configure your own search and data return fields
```php
<?=$form->field($model, 'attribute_id')->widget(\magicsoft\select\MagicSelector::className(), [
     'searchColumns' => 'code,name,...',
     'columnDescription' => 'description' 
])?>
searchColumns: one or more fiel, separed by ',';
columnDescription: This can will be field in the table or function in model.
```

Configure multiples select with parent select
```php
This is a parent select
<?= $form->field($model, 'country_id')->widget(\magicsoft\select\MagicSelector::className(), [])?>

<?=$form->field($model, 'state_id')->widget(\magicsoft\select\MagicSelector::className(), [
     'parentRelation' => 'country'
])?>

<?=$form->field($model, 'province_id')->widget(\magicsoft\select\MagicSelector::className(), [
     'parentRelation' => 'state'
])?>

... More relation
```
The second selector connects with the first, the third with the second....