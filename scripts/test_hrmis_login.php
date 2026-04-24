<?php
require __DIR__ . '/../vendor/autoload.php';

$client = new \GuzzleHttp\Client([
    'base_uri' => 'https://mef-pd.net',
    'allow_redirects' => false,
    'cookies' => true,
]);

$jar = new \GuzzleHttp\Cookie\CookieJar();

// Step 1: GET login page
$r1 = $client->get('/hrmis/login', ['cookies' => $jar]);
$html = (string) $r1->getBody();

preg_match('/name="_token" value="([^"]+)"/', $html, $m);
$csrf = $m[1] ?? '';
echo "CSRF: $csrf\n";
echo "Cookies after GET: ";
foreach ($jar as $c) { echo $c->getName() . '=' . $c->getValue() . ' '; }
echo "\n";

// Step 2: POST login
$r2 = $client->post('/hrmis/postLogin', [
    'cookies' => $jar,
    'form_params' => [
        '_token'   => $csrf,
        'username' => 'sarotmoniodom',
        'password' => 'Moniodom@8899',
    ],
]);
echo "Login status: " . $r2->getStatusCode() . "\n";
echo "Location: " . $r2->getHeaderLine('Location') . "\n";
echo "Cookies after POST: ";
foreach ($jar as $c) { echo $c->getName() . '=' . substr($c->getValue(), 0, 20) . '... '; }
echo "\n";

// Step 3: Follow redirect (dashboard or home)
$location = $r2->getHeaderLine('Location');
if ($location) {
    $r3 = $client->get($location, ['cookies' => $jar, 'allow_redirects' => true]);
    echo "Dashboard status: " . $r3->getStatusCode() . "\n";
}

// Step 4: Fetch document
$r4 = $client->get('/hrmis/civilservant/viewDocument/208', [
    'cookies' => $jar,
    'allow_redirects' => true,
]);
echo "Doc status: " . $r4->getStatusCode() . "\n";
echo "Doc Content-Type: " . $r4->getHeaderLine('Content-Type') . "\n";
$body = (string) $r4->getBody();
echo "Doc size: " . strlen($body) . "\n";
echo "Doc preview (hex): " . bin2hex(substr($body, 0, 8)) . "\n";
echo "Doc preview (text): " . substr($body, 0, 100) . "\n";
