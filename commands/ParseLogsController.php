<?php

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;
use app\models\LogEntry;
use yii\helpers\Console;

/**
 * Консольная команда для парсинга логов nginx
 */
class ParseLogsController extends Controller
{
    /**
     * Парсинг логов nginx
     * @param string $logFile Путь к файлу логов
     * @return int
     */
    public function actionIndex($logFile = null)
    {
        if (!$logFile) {
            $logFile = 'modimio.access.log.1';
        }

        if (!file_exists($logFile)) {
            $this->stderr("Файл логов не найден: {$logFile}\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Начинаем парсинг файла: {$logFile}\n", Console::FG_GREEN);

        $handle = fopen($logFile, 'r');
        if (!$handle) {
            $this->stderr("Не удалось открыть файл: {$logFile}\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $count = 0;
        $batchSize = 1000;
        $batch = [];

        while (($line = fgets($handle)) !== false) {
            $parsed = $this->parseLogLine($line);
            if ($parsed) {
                $batch[] = $parsed;
                $count++;

                if (count($batch) >= $batchSize) {
                    $this->saveBatch($batch);
                    $this->stdout("Обработано записей: {$count}\n", Console::FG_YELLOW);
                    $batch = [];
                }
            }
        }

        // Сохраняем оставшиеся записи
        if (!empty($batch)) {
            $this->saveBatch($batch);
        }

        fclose($handle);
        $this->stdout("Парсинг завершен. Всего обработано записей: {$count}\n", Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Парсинг одной строки лога
     * @param string $line
     * @return array|null
     */
    private function parseLogLine($line)
    {
        // Регулярное выражение для парсинга nginx логов
        $pattern = '/^(\S+) - - \[([^\]]+)\] "([^"]+)" (\d+) (\d+) "([^"]*)" "([^"]*)"$/';
        
        if (!preg_match($pattern, trim($line), $matches)) {
            return null;
        }

        $ip = $matches[1];
        $datetime = $matches[2];
        $request = $matches[3];
        $status = $matches[4];
        $bytes = $matches[5];
        $referer = $matches[6];
        $userAgent = $matches[7];

        // Парсим URL из запроса
        $url = $this->extractUrl($request);

        // Парсим User-Agent
        $uaInfo = $this->parseUserAgent($userAgent);

        return [
            'ip_address' => $ip,
            'request_datetime' => $this->formatDateTime($datetime),
            'url' => $url,
            'user_agent' => $userAgent,
            'operating_system' => $uaInfo['os'],
            'architecture' => $uaInfo['architecture'],
            'browser' => $uaInfo['browser'],
        ];
    }

    /**
     * Извлечение URL из строки запроса
     * @param string $request
     * @return string
     */
    private function extractUrl($request)
    {
        $parts = explode(' ', $request);
        return isset($parts[1]) ? $parts[1] : $request;
    }

    /**
     * Парсинг User-Agent
     * @param string $userAgent
     * @return array
     */
    private function parseUserAgent($userAgent)
    {
        $result = [
            'os' => null,
            'architecture' => null,
            'browser' => null,
        ];

        // Определение браузера
        if (strpos($userAgent, 'Chrome') !== false) {
            $result['browser'] = 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            $result['browser'] = 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            $result['browser'] = 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            $result['browser'] = 'Edge';
        } elseif (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
            $result['browser'] = 'Internet Explorer';
        }

        // Определение операционной системы
        if (strpos($userAgent, 'Windows') !== false) {
            $result['os'] = 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            $result['os'] = 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $result['os'] = 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $result['os'] = 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false) {
            $result['os'] = 'iOS';
        }

        // Определение архитектуры
        if (strpos($userAgent, 'x86_64') !== false || strpos($userAgent, 'x64') !== false) {
            $result['architecture'] = 'x64';
        } elseif (strpos($userAgent, 'x86') !== false) {
            $result['architecture'] = 'x86';
        }

        return $result;
    }

    /**
     * Форматирование даты и времени
     * @param string $datetime
     * @return string
     */
    private function formatDateTime($datetime)
    {
        // Преобразуем формат nginx в MySQL datetime
        $date = \DateTime::createFromFormat('d/M/Y:H:i:s O', $datetime);
        return $date ? $date->format('Y-m-d H:i:s') : $datetime;
    }

    /**
     * Сохранение пакета записей
     * @param array $batch
     */
    private function saveBatch($batch)
    {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            foreach ($batch as $data) {
                $logEntry = new LogEntry();
                $logEntry->setAttributes($data);
                if (!$logEntry->save()) {
                    throw new \Exception('Ошибка сохранения записи: ' . json_encode($logEntry->errors));
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stderr("Ошибка при сохранении: " . $e->getMessage() . "\n", Console::FG_RED);
        }
    }
}
