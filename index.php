<?php declare(strict_types=1);

enum CurrencyEnum {
    case USD;
    case EUR;
    case KGS;
}

class DateHelper {
    public function getDatesFromPreviousMonday(): array {
        $today = new DateTime();
        $today->setTime(0, 0, 0);

        $lastMonday = clone $today;
        $lastMonday->modify('last monday');

        $dates = [];
        $interval = new DateInterval('P1D');
        $endDate = clone $today;

        while ($lastMonday <= $endDate) {
            $dates[] = $lastMonday->format('d/m/Y');
            $lastMonday->add($interval);
        }

        return $dates;
    }
}

interface RemoteDataConverterInterface {
    public function toPhpArray(string $xmlStr): array;
}

class XmlConverter implements RemoteDataConverterInterface {
    public function toPhpArray(string $str): array {
        $xml = simplexml_load_string($str);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        return $array;
    }
}

class CurrencyModel {
    public function filterCurrencies(array $valute, array $currencies): array {
        $filteredValute = [];

        foreach ($valute as $currency) {
            $currencyCode = $currency['CharCode'];

            if (in_array($currencyCode, $currencies)) {
                $filteredValute[$currencyCode] = $currency;
            }
        }

        return $filteredValute;
    }
}

class CBRClient {
    private string $baseUrl = 'https://www.cbr.ru/scripts/';
    private RemoteDataConverterInterface $remoteDataConverter;

    public function __construct(RemoteDataConverterInterface $remoteDataConverter) {
        $this->remoteDataConverter = $remoteDataConverter;
    }

    public function getExchangeRateForDate(string $date): string {
        $url = "{$this->baseUrl}XML_daily.asp?date_req={$date}";

        return $this->makeRequest($url);
    }

    private function makeRequest(string $url): string {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Ошибка при выполнении запроса: ' . curl_error($ch);
        }

        curl_close($ch);

        return $response;
    }

    public function fetchExchangeRateXMLData(string $date): array {
        $exchangeRateXML = $this->getExchangeRateForDate($date);
        $exchangeRate = $this->remoteDataConverter->toPhpArray($exchangeRateXML);

        return $exchangeRate['Valute'];
    }
}

class App {
    private DateHelper $dateHelper;
    private CBRClient $cbrClient;
    private CurrencyModel $currencyModel;

    public function __construct(
        DateHelper $dateHelper,
        CBRClient $cbrClient,
        CurrencyModel $currencyModel
    ) {
        $this->dateHelper = $dateHelper;
        $this->cbrClient = $cbrClient;
        $this->currencyModel = $currencyModel;
    }

    public function run(array $currencies) {
        $prevMonToNowDates = $this->dateHelper->getDatesFromPreviousMonday();

        $rates = [];
        foreach ($prevMonToNowDates as $date) {
            $valuteData = $this->cbrClient->fetchExchangeRateXMLData($date);
            $filteredValute = $this->currencyModel->filterCurrencies($valuteData, $currencies);
            $rates[$date] = $filteredValute;
        }

        $this->displayRates($rates);
    }

    public function displayRates(array $rates) {
        foreach ($rates as $date => $valuteData) {
            $dateTime = DateTime::createFromFormat('d/m/Y', $date);
            $formattedDate = $dateTime->format('Y-m-d');
            
            echo "Дата: {$formattedDate} \n";

            foreach ($valuteData as $valute => $data) {
                echo "Валюта: {$valute} Значение: {$data['Value']} \n";
            }
        }
    }
}

$selectedCurrencies = [CurrencyEnum::USD->name, CurrencyEnum::EUR->name, CurrencyEnum::KGS->name];

$dateHandler = new DateHelper();
$xmlConverter = new XmlConverter();
$cbrClient = new CBRClient($xmlConverter);
$currencyModel = new CurrencyModel();

$app = new App($dateHandler, $cbrClient, $currencyModel);
$app->run($selectedCurrencies);
?>
