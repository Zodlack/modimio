<?php

namespace app\controllers;

use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\LogEntry;
use yii\web\Response;
use yii\data\ArrayDataProvider;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $request = \Yii::$app->request;
        
        // Получаем параметры фильтров
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $os = $request->get('os');
        $architecture = $request->get('architecture');
        $sort = $request->get('sort', 'date');
        $order = $request->get('order', 'asc');

        // Получаем данные для таблицы
        $tableData = LogEntry::getDateStatistics($dateFrom, $dateTo, $os, $architecture);
        
        // Сортируем данные
        if ($sort && $order) {
            usort($tableData, function($a, $b) use ($sort, $order) {
                $result = strcmp($a[$sort], $b[$sort]);
                return $order === 'desc' ? -$result : $result;
            });
        }

        // Создаем провайдер данных для таблицы
        $dataProvider = new ArrayDataProvider([
            'allModels' => $tableData,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);


        // Получаем данные для графиков
        $chartData = LogEntry::getBrowserStatistics($dateFrom, $dateTo, $os, $architecture);
        
        // Группируем данные для графиков
        $requestsChart = $this->prepareRequestsChart($tableData);
        $browsersChart = $this->prepareBrowsersChart($chartData);

        // Получаем списки для фильтров
        $operatingSystems = LogEntry::getOperatingSystems();
        $architectures = LogEntry::getArchitectures();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'requestsChart' => $requestsChart,
            'browsersChart' => $browsersChart,
            'operatingSystems' => $operatingSystems,
            'architectures' => $architectures,
            'filters' => [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'os' => $os,
                'architecture' => $architecture,
                'sort' => $sort,
                'order' => $order,
            ],
        ]);
    }

    /**
     * Подготовка данных для графика запросов
     * @param array $data
     * @return array
     */
    private function prepareRequestsChart($data)
    {
        $chartData = [];
        foreach ($data as $row) {
            $chartData[] = [
                'date' => $row['date'],
                'requests' => (int)$row['request_count'],
            ];
        }
        return $chartData;
    }

    /**
     * Подготовка данных для графика браузеров
     * @param array $data
     * @return array
     */
    private function prepareBrowsersChart($data)
    {
        // Группируем данные по дате и браузеру
        $grouped = [];
        foreach ($data as $row) {
            $date = $row['date'];
            $browser = $row['browser'];
            $count = (int)$row['count'];
            
            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }
            $grouped[$date][$browser] = $count;
        }

        // Получаем топ-3 браузера
        $browserTotals = [];
        foreach ($data as $row) {
            $browser = $row['browser'];
            $count = (int)$row['count'];
            $browserTotals[$browser] = ($browserTotals[$browser] ?? 0) + $count;
        }
        arsort($browserTotals);
        $topBrowsers = array_slice(array_keys($browserTotals), 0, 3);

        // Формируем данные для графика
        $chartData = [];
        foreach ($grouped as $date => $browsers) {
            $total = array_sum($browsers);
            $row = ['date' => $date];
            
            foreach ($topBrowsers as $browser) {
                $count = $browsers[$browser] ?? 0;
                $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
                $row[$browser] = $percentage;
            }
            
            $chartData[] = $row;
        }

        return $chartData;
    }

    /**
     * API для получения данных графиков
     * @return array
     */
    public function actionChartData()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        
        $request = \Yii::$app->request;
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $os = $request->get('os');
        $architecture = $request->get('architecture');

        $tableData = LogEntry::getDateStatistics($dateFrom, $dateTo, $os, $architecture);
        $chartData = LogEntry::getBrowserStatistics($dateFrom, $dateTo, $os, $architecture);

        return [
            'requestsChart' => $this->prepareRequestsChart($tableData),
            'browsersChart' => $this->prepareBrowsersChart($chartData),
        ];
    }
}
