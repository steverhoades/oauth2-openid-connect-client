<?php

require '../vendor/autoload.php';

$key = sprintf('file://%s/public.key', realpath(__DIR__));

$signer = new \Lcobucci\JWT\Signer\Rsa\Sha256();
$provider = new \OpenIDConnectClient\OpenIDConnectProvider(
    [
        'clientId' => 'demoapp',
        'clientSecret' => 'demopass',
        'idTokenIssuer' => 'brentertainment.com',
        // Your server
        'redirectUri' => 'http://localhost:8082/',
        'urlAuthorize' => 'http://brentertainment.com/oauth2/lockdin/authorize',
        'urlAccessToken' => 'http://brentertainment.com/oauth2/lockdin/token',
        'urlResourceOwnerDetails' => 'http://brentertainment.com/oauth2/lockdin/resource',
        // Find the public key here: https://github.com/bshaffer/oauth2-demo-php/blob/master/data/pubkey.pem
        // to test against brentertainment.com
        'publicKey' => $key,
    ],
    [
        'signer' => $signer,
    ],
);

// send the authorization request
if (empty($_GET['code'])) {
    $redirectUrl = $provider->getAuthorizationUrl();
    header(sprintf('Location: %s', $redirectUrl), true, 302);

    return;
}

// receive authorization response
try {
    $token = $provider->getAccessToken(
        'authorization_code',
        ['code' => $_GET['code']],
    );
} catch (\OpenIDConnectClient\Exception\InvalidTokenException $e) {
    $errors = $provider->getValidatorChain()->getMessages();
    echo $e->getMessage();
    var_dump($errors);

    return;
} catch (\Exception $e) {
    echo $e->getMessage();
    $errors = $provider->getValidatorChain()->getMessages();
    var_dump($errors);

    return;
}

$response = [
    'Token: ' . $token->getToken(),
    'Refresh Token: ' . $token->getRefreshToken(),
    'Expires: ' . $token->getExpires(),
    'Has Expired: ' . $token->hasExpired(),
    'All Claims: ' . print_r($token->getIdToken()->claims(), true),
];

echo implode('<br />', $response);
