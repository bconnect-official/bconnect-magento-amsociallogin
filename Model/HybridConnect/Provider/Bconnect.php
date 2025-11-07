<?php

declare(strict_types=1);

namespace Bconnect\Amsociallogin\Model\HybridConnect\Provider;

use Exception;
use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Adapter\AdapterInterface;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\HttpClient\Util;
use Hybridauth\User\Profile;

/**
 * @author  Agence Dn'D <contact@dnd.fr>
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    https://www.dnd.fr/
 */
class Bconnect extends AbstractAdapter implements AdapterInterface
{
    private const ACTION_AUTHORIZE = 'authorize';
    private const ACTION_GET_ACCESS_TOKEN = 'access_token';
    private const CODE_CHALLENGE = 'code_challenge';
    private const CODE_CHALLENGE_ORIGINAL = 'code_challenge_original';
    private const STATE = 'state';
    private const GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';

    private ?Profile $currentUser = null;
    private ?string $baseAuthorizeUrl = null;
    private ?string $baseUrl = null;
    private ?string $apiBaseUrl = null;
    private ?string $tokenEndpoint = null;
    private ?string $scope = null;
    private ?bool $canIncudeChallenge = false;
    private ?string $challengeMethod = null;
    private ?string $callbackUrl = null;
    private ?string $consumerKey = null;
    private ?string $consumerSecret = null;

    /**
     * @inheritDoc
     */
    public function authenticate()
    {
        $this->logger->info(sprintf('%s::authenticate()', get_class($this)));

        if (empty($_REQUEST['session_state'])) {
            $this->authenticateBegin();
        } else {
            $this->authenticateFinish();
        }

        return null;
    }

    /**
     * @throws UnexpectedApiResponseException
     */
    public function getUserProfile()
    {
        if (!empty($this->currentUser)) {
            return $this->currentUser;
        }

        if (!$this->checkRequestAuthenticity()) {
            throw new UnexpectedApiResponseException('Error occured while checking authenticity');
        }

        $userProfile = new Profile();
        try {
            // Get the authorization code from the request
            $code = $_REQUEST['code'] ?? '';
            if (!$code) {
                $errorMsg = 'Bconnect - Error occured while retrieving code parameter';
                $this->logger->error($errorMsg);
                throw new UnexpectedApiResponseException($errorMsg);
            }

            $jwtCustomerData = $this->getJwtCustomerData($code);
            if (!$jwtCustomerData) {
                $errorMsg = 'Bconnect - Error occured while checking authenticity';
                $this->logger->error($errorMsg);
                throw new UnexpectedValueException($errorMsg);
            }

            $userProfile->firstName = $jwtCustomerData['firstname'];
            $userProfile->lastName = $jwtCustomerData['lastname'];
            $userProfile->email = $jwtCustomerData['email'];
            $userProfile->identifier = $jwtCustomerData['identifier'];
            $userProfile->phone = $jwtCustomerData['phone'];
            $userProfile->displayName = $userProfile->firstName.' '.$userProfile->lastName;

            $postalAddress = explode('|', (string)$jwtCustomerData['postal_address']);
            if ($postalAddress !== []) {
                $country = (string)end($postalAddress);
                $city = (string)end($postalAddress);
                $postCode = (string)end($postalAddress);
                $street = implode(' ', array_splice($postalAddress, 0, -3));

                $userProfile->address = $street;
                $userProfile->zip = $postCode;
                $userProfile->city = $city;
                $userProfile->country = $country;
            }

            $this->currentUser = $userProfile;

            return $this->currentUser;
        } catch (Exception $e) {
            $this->logger->error(sprintf('Bconnect authentication error: %s', $e->getMessage()));
        }

        return $userProfile;
    }

    /**
     * @inheritDoc
     */
    public function isConnected()
    {
        return $this->currentUser && $this->currentUser->identifier;
    }

    /**
     * @inheritDoc
     */
    public function disconnect()
    {
        $this->clearStoredData();
    }

    /**
     * @inheritDoc
     */
    protected function initialize()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->apiBaseUrl = $this->config->get('api_base_url') ?: $this->apiBaseUrl;
        $this->scope = $this->config->get('scope') ?: $this->scope;
        $this->callbackUrl = $this->config->get('callback_url') ?: $this->callbackUrl;
        $this->consumerKey = $this->config->filter('keys')->get('id') ?: $this->config->filter('keys')->get('key');
        $this->consumerSecret = $this->config->get('consumer_secret') ?: $this->consumerSecret;
        $this->baseUrl = $this->config->get('base_url') ?: $this->baseUrl;
        $this->tokenEndpoint = $this->config->get('token_access_url') ?: $this->tokenEndpoint;
        $this->baseAuthorizeUrl = $this->config->get('base_authorize_url') ?: $this->baseAuthorizeUrl;
        $this->canIncudeChallenge = (bool)$this->config->get('can_incude_challenge') ?: $this->canIncudeChallenge;
        $this->challengeMethod = $this->config->get('challenge_method') ?: $this->challengeMethod;

        if (!$this->consumerKey || !$this->consumerSecret) {
            throw new InvalidApplicationCredentialsException(
                sprintf('Your application id is required in order to connect to %s', $this->providerId),
            );
        }
    }

    private function authenticateBegin(): void
    {
        $authorizeUrl = $this->getUrl(self::ACTION_AUTHORIZE);
        $this->logger->debug(
            sprintf('%s::authenticateBegin(), redirecting user to:', get_class($this)), [$authorizeUrl],
        );
        Util::redirect($authorizeUrl);
    }

    /**
     *  Retrieve user data
     *
     * @throws UnexpectedApiResponseException
     */
    private function authenticateFinish(): void
    {
        $this->logger->debug(
            sprintf('%s::authenticateFinish(), callback url:', get_class($this)),
            [Util::getCurrentUrl(true)],
        );
        $this->getUserProfile();
    }

    /**
     * Get specific url for given action
     */
    private function getUrl(string $action): string
    {
        $endpoint = null;
        $baseUrl = $this->baseUrl;

        switch ($action) {
            case self::ACTION_AUTHORIZE:
                $endpoint = $this->getAuthorizationEndpointParams();
                break;
            case self::ACTION_GET_ACCESS_TOKEN:
                $baseUrl = $this->apiBaseUrl;
                $endpoint = $this->tokenEndpoint;
                break;
        }

        return $baseUrl . $endpoint;
    }

    private function getAuthorizationEndpointParams(): string
    {
        $codeChallengeAndState = $this->generateCodeChallengeState();
        $params = [
            'response_type' => 'code',
            'client_id' => $this->consumerKey,
            'redirect_uri' => $this->callbackUrl,
            'scope' => $this->scope,
            'state' => $codeChallengeAndState[self::STATE],
        ];

        if ($this->canIncudeChallenge) {
            $params['code_challenge_method'] = $this->challengeMethod;
            $params['code_challenge'] = $codeChallengeAndState[self::CODE_CHALLENGE];
        }

        return $this->baseAuthorizeUrl . '?' . http_build_query($params);
    }

    /**
     * Generate code challenge code to send it to the /authorize and /access_token endpoint for security purpose
     * Add into the session the redirect url (checkout or customer account)
     */
    private function generateCodeChallengeState(): array
    {
        $codeVerifier = bin2hex(random_bytes(16));
        $base64Digest = base64_encode(hash('sha256', $codeVerifier, true));
        $base64urlDigest = strtr($base64Digest, '+/', '-_');
        $codeChallenge = rtrim($base64urlDigest, '=');

        $state = hash('sha256', bin2hex(random_bytes(16)));

        $this->storage->set(self::CODE_CHALLENGE, $codeChallenge);
        $this->storage->set(self::CODE_CHALLENGE_ORIGINAL, $codeVerifier);
        $this->storage->set(self::STATE, $state);

        return [self::STATE => $state, self::CODE_CHALLENGE => $codeChallenge];
    }

    /**
     * Check the state value from the Bconnect request and the value in session
     */
    private function checkRequestAuthenticity(): bool
    {
        $sessionState = $this->storage->get(self::STATE);
        $currentState = $_REQUEST[self::STATE] ?? '';

        return $sessionState === $currentState;
    }

    private function getJwtCustomerData(string $authorizeCode): ?array
    {
        $accessToken = null;
        try {
            $payload = [
                'code' => $authorizeCode,
                'grant_type' => self::GRANT_TYPE_AUTHORIZATION_CODE,
                'redirect_uri' => $this->callbackUrl,
            ];
            if ($this->canIncudeChallenge) {
                $payload['code_verifier'] = $this->storage->get(self::CODE_CHALLENGE_ORIGINAL);
            }

            $accessToken = $this->sendRequestAccessToken($payload);
        } catch (Exception $e) {
            $errorMsg = sprintf('Bconnect error while getting user datas: %s', $e->getMessage());
            $this->logger->error($errorMsg);
        }

        // Check return of the request
        if (!$accessToken && !isset($accessToken['access_token'], $accessToken['token_type'], $accessToken['id_token'])) {
            return null;
        }

        $customerData = $this->decodeJwtToken($accessToken['id_token']);
        $email = $customerData['email'];
        if (!$email) {
            return null;
        }

        return $customerData;
    }

    private function decodeJwtToken(string $jwtToken): ?array
    {
        $tokenParts = explode(".", $jwtToken);
        $payload = $tokenParts[1] ?? null;
        if (!$payload) {
            return null;
        }

        $decodedPayload = json_decode($this->base64UrlDecode($payload), true);

        return [
            'email' => $decodedPayload['email'] ?? '',
            'firstname' => $decodedPayload['given_name'] ?? '',
            'lastname' => $decodedPayload['family_name'] ?? '',
            'identifier' => $decodedPayload['sub'] ?? '',
            'phone' => $decodedPayload['phone'] ?? '',
            'postal_address' => $decodedPayload['postaladdress'] ?? '',
        ];
    }

    private function base64UrlDecode($data): string|false
    {
        $base64 = strtr($data, '-_', '+/');

        return base64_decode($base64);
    }

    /**
     * @throws UnexpectedApiResponseException
     */
    private function sendRequestAccessToken(array $params = []): ?array
    {
        $url = $this->getUrl(self::ACTION_GET_ACCESS_TOKEN);

        $this->httpClient->request($url, 'POST', $params,
            [
                'accept' => 'text/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($this->consumerKey . ':' . $this->consumerSecret),
            ]);

        try {
            $body = $this->httpClient->getResponseBody();
            $responseData = json_decode($body, true);
        } catch (Exception $e) {
            $this->logger->error(sprintf('B.Connect : Error while getting access token: %s', $e->getMessage()));
            throw new UnexpectedValueException(sprintf('An error has occurred when unserializing response from %s: %s', $url, $e->getMessage()));
        }

        if (isset($responseData['error'])) {
            $this->logger->error(sprintf('B.Connect : %s', $responseData['error_description']));
            throw new UnexpectedApiResponseException($responseData['error_description']);
        }

        return $responseData;
    }
}
