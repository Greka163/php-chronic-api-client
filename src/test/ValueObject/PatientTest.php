<?php

namespace DocDoc\RgsApiClient\ValueObject\test;

use DocDoc\RgsApiClient\Exception\ValidationException;
use DocDoc\RgsApiClient\ValueObject\Patient\MetaData;
use DocDoc\RgsApiClient\ValueObject\Patient\Patient;
use DocDoc\RgsApiClient\ValueObject\Patient\TimeZone;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \DocDoc\RgsApiClient\ValueObject\Patient\Patient
 */
class PatientTest extends TestCase
{
	/**
	 * Тест на удачное преобразование объекта в json
	 *
	 * @dataProvider successJsonSerializeDataProvider
	 *
	 * @covers ::jsonSerialize
	 * @param string $jsonPatient
	 *
	 * @throws ValidationException
	 */
	public function testSuccessJsonSerialize(string $jsonPatient): void
	{
		$jsonPatientObject = json_decode($jsonPatient, false);
		$patient = new Patient();
		$patient->setCategoryKey($jsonPatientObject->categoryKey);
		$patient->setFirstName($jsonPatientObject->firstName);
		$patient->setPhone($jsonPatientObject->phone);
		$patient->setPatronymic($jsonPatientObject->patronymic);
		$patient->setExternalId($jsonPatientObject->externalId);
		if ($jsonPatientObject->active === false) {
			$patient->deactivate();
		}

		if ($jsonPatientObject->monitoringEnabled === false) {
			$patient->monitoringDisabled();
		}

		$patient->setMetadata(
			new MetaData($jsonPatientObject->metadata->productId, $jsonPatientObject->metadata->contractId)
		);
		$patient->setTimezone(new TimeZone($jsonPatientObject->timezone));
		$expected = json_encode($patient);
		//сброс красивого форматирования
		$actual = json_encode($jsonPatientObject);
		$this->assertEquals(
			$expected,
			$actual,
			'Представление объекта Пациента не соответствует ожидаемому'
		);
	}

	/**
	 * Тест на не удачное преобразование объекта в json
	 * Не наполняем объект и пытаемся преобразовать в json
	 *
	 * @dataProvider failJsonSerializeDataProvider
	 *
	 * @covers ::jsonSerialize
	 * @param string $jsonPatient
	 *
	 */
	public function testFailJsonSerialize(string $jsonPatient): void
	{
		$this->expectException(ValidationException::class);
		$jsonPatientObject = json_decode($jsonPatient, false);
		$patient = new Patient();
		$patient->setCategoryKey($jsonPatientObject->categoryKey);

		json_encode($patient);
	}

	/**
	 * @inheritDoc
	 */
	public function successJsonSerializeDataProvider(): array
	{
		return [
			[
				'{
                    "categoryKey": "covid",
                    "firstName": "Иван",
                    "patronymic": "Иванов",
                    "phone": "+7 (904) 999-99-99",
                    "externalId": -100000000,
                    "metadata": {
                        "productId": 13,
                        "contractId": 10293
                    },
                    "timezone": "+02:00",
                    "active": true,
                    "monitoringEnabled": true,
                    "metricsRanges":[]
                }'
			],
			[
				'{
                    "categoryKey": "hypertonic",
                    "firstName": "Иван",
                    "patronymic": "Иванов",
                    "phone": "+7 (904) 999-99-99",
                    "externalId": -100000000,
                    "metadata": {
                        "productId": 13,
                        "contractId": 10293
                    },
                    "timezone": "+02:00",
                    "active": false,
                    "monitoringEnabled": false,
                    "metricsRanges":[]
                }'
			],
			[
				'{
                    "categoryKey": "diabetic",
                    "firstName": "Иван",
                    "patronymic": "Иванов",
                    "phone": "+7 (904) 999-99-99",
                    "externalId": -100000000,
                    "metadata": {
                        "productId": 13,
                        "contractId": 10293
                    },
                    "timezone": "+02:00",
                    "active": false,
                    "monitoringEnabled": false,
                    "metricsRanges":[]
                }'
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function failJsonSerializeDataProvider(): array
	{
		return [
			[
				'{
                    "categoryKey": "Не известный тип"
                }'
			],
			[
				'{
                    "categoryKey": "hypertonic"
                }'
			],
		];
	}
}
