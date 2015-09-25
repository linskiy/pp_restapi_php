# PRICEPLAN REST API
```

$pp = new priceplaneAPI('username','password');
//получаем список переменных
$variables = $pp->getVariables();
//получаем типы данных
$measures = $pp->getMeasures();
//получаем поля клиентов
$ct = $pp->getClientTypes();

//полученине информации о продукте
try {
	echo('<pre>');
	print_r($pp->getProductById(1));
	echo('</pre>');

} catch (Exception $e) {

    print 'Error: '.$e->getMessage()."\n";

}

//пополнение баланса
$param = array(
    'amount' => 100,
    'comment' => 'Бонус за регистрацию',
    'type' => 'internal'
);
$pp->increaseBalance(1,$param);
```