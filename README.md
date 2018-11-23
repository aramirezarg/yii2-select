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
echo $form->field($model, 'attribute_id')->widget(\magicsoft\select\MagicSelect::className(), []);

//With this configuration, the widget assumes that its fields of search and return of data are: 'name' or 'description'
```

But you can configure your own search and data return fields
```php
echo $form->field($model, 'attribute_id')->widget(\magicsoft\select\MagicSelect::className(), [
     'searchData' => 'code,name,...',
     'returnData' => 'join:code,description' 
])?>
//searchData: one or more field, separed by ',';
//returnData: this can take tree options: join: join the a few fields or attributes; attr: attributes in model or fiel: one field in bd.
```

Configure multiples select with parent select
```php
//This is a parent select
echo $form->field($model, 'country_id')->widget(\magicsoft\select\MagicSelect::className(), []);

//This is a second select
echo $form->field($model, 'state_id')->widget(\magicsoft\select\MagicSelect::className(), [
     'parent' => 'country'
]);

//This is a tree select
echo $form->field($model, 'province_id')->widget(\magicsoft\select\MagicSelect::className(), [
     'parent' => 'state'
]);

//... More select
```
The second select connects with the first, the third with the second....

## License

**MagicSelect** is released under the BSD 3-Clause License. See the bundled `LICENSE.md` for details.


**Pending documentation,**
**Pending load other library**