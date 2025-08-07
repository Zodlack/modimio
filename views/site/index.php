<?php

/** @var yii\web\View $this */
/* @var $model app\models\EntryForm */
$this->title = 'Siber';   // заголовк страницы
use app\widgets\ModalWidget;
use yii\helpers\Html;

?>

<?=$this->render('siber/promo');?>
<?=$this->render('siber/lesson');?>
<?=$this->render('siber/greeting');?>
<?=$this->render('siber/education');?>
<?=$this->render('siber/inclusive');?>
<?=$this->render('siber/relation');?>
<?=$this->render('siber/prank');?>
<?=$this->render('siber/notification');?>
<?=$this->render('siber/address');?>

<!-- Вставляем форму из файла entry.php -->
<?= $this->render('//site/entry', ['model' => $model]) ?>