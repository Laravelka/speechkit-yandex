<?php

const ROOT = __DIR__; // Сам знаешь для чего

/*
 * Задержка в секундах между запросами на проверку распознан текст или нет
 */
const SLEEP_TIME = 5;

/*
 * Столько раз скрипт будет проверять распознан текст или нет
 * Т.е 5*20 = 100 секунд максимального ожидания
 * В доке яндекса написно, что файл длинной в одну минуту распознается примерно 10 секунд
 */
const COUNT_QUERY = 20;

const BUCKET = ''; // имя бакета
const API_ID = ''; // идентификатор API ключа
const API_KEY = ''; // сам API ключ
const SERVICE_KEY = ''; // идентификатор ключа доступа
const SERVICE_SECRET = ''; // сам секретный ключ

// тут будут полезные функции в̶е̶л̶о̶с̶и̶п̶е̶д̶ы
function isJson(string $data): bool {
	json_decode($data);
	
	return (json_last_error() == JSON_ERROR_NONE);
}
