<?php

require __DIR__.'/config.php';
require ROOT.'/vendor/autoload.php';

use Aws\S3\Exception\S3Exception;
use Helpers\{Curl, Logger};
use Aws\S3\S3Client;

$config = [
	'version' => 'latest',
	'region' => 'ru-central1',
	'endpoint'    => 'https://storage.yandexcloud.net',
	'credentials' => [
		'key' => SERVICE_KEY,
		'secret' => SERVICE_SECRET,
	],
	'http' => [
		'verify' => false
	]
];

$curl = new Curl();
$sdk = new Aws\Sdk($config);
$s3Client = $sdk->createS3();

$pathToFile = !empty($argv[1]) ? $argv[1] : readline('Путь до файла(files/primer.ogg):');

if (empty($pathToFile) || !file_exists($pathToFile)) {
	echo 'Вы не ввели путь до файла или файл не найден.';

	sleep(2);
	system('clear');

	$pathToFile = readline('Путь (files/primer.ogg):');

	if (empty($pathToFile)) exit;
} else {
	$getName = readline('Название ('.basename($pathToFile).'):');
	$fileName = !empty($getName) ? $getName : basename($pathToFile);

	try {
		$response = $s3Client->putObject([
			'Bucket' => BUCKET,
			'Key'    => $fileName,
			'Body'   => fopen($pathToFile, 'r'),
		]);

		if (!empty($response['ObjectURL'])) {
			echo 'Файл успешно загружен в бакет.';

			try {
				$cmd = $s3Client->getCommand('GetObject', [
					'Bucket' => BUCKET,
					'Key' => $fileName,
				]);
				$request = $s3Client->createPresignedRequest($cmd, '+30 minutes');

				$presignedUrl = (string) $request->getUri();

				$curl->headers([
					'Authorization: Api-Key '.API_KEY,
					'Content-Type: application/json',
					'Accept: application/json',
				]);
				$curl->post('https://transcribe.api.cloud.yandex.net/speech/stt/v2/longRunningRecognize', [
					'config' => [
						'specification' => [
							'model' => 'general',
							'languageCode' => 'ru-RU',
							'audioEncoding' => 'OGG_OPUS', // 'LINEAR16_PCM',
							'sampleRateHertz' => 48000, // 16000 и 8000
						]
					],
					'audio' => [
						'uri' => $presignedUrl
					]
				]);
				$data = $curl->getData();
				$operationId = $data['id'];
				$resultFileName = ROOT.'/results/'.$operationId.'-'.$fileName.'.text';

				Logger::file($data, '1-'.$operationId);

				if (!empty($data['message'])) {
					exit('Ошибка отправки файла на распознавание: '.$data['message']);
				} else {
					for ($i = 1; $i <= COUNT_QUERY; $i++) {
						system('clear');

						echo 'Проверяем №'.$i;

						$curlTwo = new Curl();
						$curlTwo->headers([
							'Authorization: Api-Key '.API_KEY,
							'Accept: application/json'
						]);
						$curlTwo->get('https://operation.api.cloud.yandex.net/operations/'.$operationId);

						$dataTwo = $curlTwo->getData();

						Logger::file($dataTwo, '2-'.$operationId);

						if (!empty($dataTwo['done']) && !empty($dataTwo['response'])) {
							Logger::file($dataTwo, 'result-'.$operationId);

							$finalText = '';
							foreach ($dataTwo['response']['chunks'] as $chunk) {
								$finalText .= $chunk['alternatives'][0]['text']."\n\r";
							}
							file_put_contents($resultFileName, $finalText);

							system('clear');

							echo 'Текст распознан и записан в файл: '.$resultFileName;

							exit;
						} elseif (!empty($dataTwo['error'])) {
							system('clear');

							Logger::file($dataTwo, 'error-'.$operationId);

							exit('Ошибка распознавания: '.$dataTwo['error']['message']);
						}
					    sleep(SLEEP_TIME);
					}
				}
			} catch (S3Exception $e) {
				var_dump('Ошибка получения ссылки: ', $e->getMessage()); exit;
			}
		}
	} catch (S3Exception $e) {
		var_dump('Ошибка загрузки: ', $e->getMessage()); exit;
	}
}
