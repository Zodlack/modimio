<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ArrayDataProvider */
/* @var $requestsChart array */
/* @var $browsersChart array */
/* @var $operatingSystems array */
/* @var $architectures array */
/* @var $filters array */

$this->title = 'Анализ логов nginx';
// $this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-index">
    <h1><?=$this->title?></h1>

    <!-- Фильтры -->
    <div class="filters-panel">
        <h3>Фильтры</h3>
        <?php $form = ActiveForm::begin(['method' => 'get', 'options' => ['class' => 'form-inline']]); ?>
        
        <div class="row">
            <div class="col-md-3">
                <label>Дата от:</label>
                <input type="date" name="date_from" value="<?= $filters['dateFrom'] ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label>Дата до:</label>
                <input type="date" name="date_to" value="<?= $filters['dateTo'] ?>" class="form-control">
            </div>
            <div class="col-md-2">
                <label>ОС:</label>
                <select name="os" class="form-control">
                    <option value="">Все</option>
                    <?php foreach ($operatingSystems as $os): ?>
                        <option value="<?= $os ?>" <?= $filters['os'] === $os ? 'selected' : '' ?>>
                            <?= $os ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>Архитектура:</label>
                <select name="architecture" class="form-control">
                    <option value="">Все</option>
                    <?php foreach ($architectures as $arch): ?>
                        <option value="<?= $arch ?>" <?= $filters['architecture'] === $arch ? 'selected' : '' ?>>
                            <?= $arch ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <div>
                    <?= Html::submitButton('Применить', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('Сбросить', ['index'], ['class' => 'btn btn-default']) ?>
                </div>
            </div>
        </div>
        
        <?php ActiveForm::end(); ?>
    </div>

    <!-- Графики -->
    <div class="charts-section">
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h3>Количество запросов по дням</h3>
                    <canvas id="requestsChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h3>Доля браузеров по дням (%)</h3>
                    <canvas id="browsersChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица -->
    <div class="table-section">
        <h3>Статистика по дням</h3>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => 'date',
                    'label' => 'Дата',
                    'value' => function ($model) {
                        return Yii::$app->formatter->asDate($model['date']);
                    },
                    'headerOptions' => ['style' => 'width: 120px;'],
                ],
                [
                    'attribute' => 'request_count',
                    'label' => 'Число запросов',
                    'value' => function ($model) {
                        return number_format($model['request_count']);
                    },
                    'headerOptions' => ['style' => 'width: 150px;'],
                ],
                [
                    'attribute' => 'most_popular_url',
                    'label' => 'Самый популярный URL',
                    'value' => function ($model) {
                        return Html::encode($model['most_popular_url']);
                    },
                    'format' => 'raw',
                ],
                [
                    'attribute' => 'most_popular_browser',
                    'label' => 'Самый популярный браузер',
                    'value' => function ($model) {
                        return $model['most_popular_browser'] ?: 'Не определен';
                    },
                    'headerOptions' => ['style' => 'width: 200px;'],
                ],
            ],
            'tableOptions' => ['class' => 'table table-striped table-bordered'],
            'summary' => 'Показано {begin}-{end} из {totalCount} записей.',
            'pager' => [
                'options' => ['class' => 'pagination pagination-sm'],
            ],
        ]); ?>
    </div>
</div>

<?php
// Подключаем Chart.js
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js', ['position' => \yii\web\View::POS_HEAD]);
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // График количества запросов
    const requestsCtx = document.getElementById('requestsChart').getContext('2d');
    const requestsChart = new Chart(requestsCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($requestsChart, 'date')) ?>,
            datasets: [{
                label: 'Количество запросов',
                data: <?= json_encode(array_column($requestsChart, 'requests')) ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Ось X – дата'
                    },
                    offset: true
                },
                y: {
                    title: {
                        display: true,
                        text: 'Ось Y - число запросов'
                    },
                    offset: true
                }
            }
        }
    });

    // График браузеров
    const browsersCtx = document.getElementById('browsersChart').getContext('2d');
    const browsersChart = new Chart(browsersCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($browsersChart, 'date')) ?>,
            datasets: [
                <?php
                if (!empty($browsersChart)) {
                    $firstRow = reset($browsersChart);
                    $browserKeys = array_keys($firstRow);
                    unset($browserKeys[array_search('date', $browserKeys)]);
                    
                    $colors = ['rgb(255, 99, 132)', 'rgb(54, 162, 235)', 'rgb(255, 205, 86)'];
                    $datasets = [];
                    
                    foreach ($browserKeys as $index => $browser) {
                        $color = $colors[$index % count($colors)];
                        $datasets[] = "{
                            label: '{$browser}',
                            data: " . json_encode(array_column($browsersChart, $browser)) . ",
                            borderColor: '{$color}',
                            backgroundColor: '{$color}',
                            tension: 0.1
                        }";
                    }
                    echo implode(',', $datasets);
                }
                ?>
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Ось X – дата'
                    },
                    offset: true
                },
                y: {
                    title: {
                        display: true,
                        text: 'Ось Y - % число запросов'
                    },
                    beginAtZero: true,
                    max: 100,
                    offset: true
                }
            }
        }
    });
});
</script>

<style>


.table-bordered, .table-bordered th, .table-bordered td {
    border: 1px solid  purple;
    text-align: center;
}
.table {
    border-collapse: collapse;
}

.filters-panel {
    background: #f8f9fa;
    padding: 20px;
    margin-bottom: 30px;
    border-radius: 5px;
}

.charts-section {
    margin-bottom: 30px;
}

.chart-container {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.table-section {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-inline .form-control {
    margin-right: 10px;
    margin-bottom: 10px;
}

.form-inline label {
    margin-right: 5px;
    font-weight: bold;
}
</style>