<?php

declare(strict_types=1);

namespace Bconnect\Amsociallogin\Provider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @author  Agence Dn'D <contact@dnd.fr>
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    https://www.dnd.fr/
 */
class Config
{
    public const AMASTY_TYPE = 'bconnect';
    public const AMASTY_NAME = 'b.connect';
    private const XML_PATH_BCONNECT_ENABLED = 'amsociallogin/bconnect/enabled';
    private const XML_PATH_BCONNECT_PROD_MODE = 'amsociallogin/bconnect/prod_mode';
    private const XML_PATH_BCONNECT_BASE_URL = 'amsociallogin/bconnect/redirect_page_base_url';
    private const XML_PATH_BCONNECT_BASE_URL_PROD = 'amsociallogin/bconnect/redirect_page_base_url_prod';
    private const XML_PATH_BCONNECT_API_BASE_URL = 'amsociallogin/bconnect/api_base_url';
    private const XML_PATH_BCONNECT_API_BASE_URL_PROD = 'amsociallogin/bconnect/api_base_url_prod';
    private const XML_PATH_BCONNECT_AUTHORIZE_ENDPOINT = 'amsociallogin/bconnect/authorize_endpoint';
    private const XML_PATH_BCONNECT_ACCESS_TOKEN_ENDPOINT = 'amsociallogin/bconnect/access_token_endpoint';
    private const XML_PATH_BCONNECT_SCOPE = 'amsociallogin/bconnect/scope';
    private const XML_PATH_BCONNECT_CLIENT_ID = 'amsociallogin/bconnect/api_key';
    private const XML_PATH_BCONNECT_SECRET_ID = 'amsociallogin/bconnect/api_secret';
    private const XML_PATH_BCONNECT_API_CHALLENGE_METHOD = 'amsociallogin/bconnect/challenge_method';
    private const XML_PATH_BCONNECT_API_INCLUDE_CHALLENGE = 'amsociallogin/bconnect/include_challenge';

    public function __construct(
        protected ScopeConfigInterface $config,
    ) {}

    public function getBaseUrl(?string $store = null): ?string
    {
        return $this->config->getValue(
            $this->isModeProd($store) ? self::XML_PATH_BCONNECT_BASE_URL_PROD : self::XML_PATH_BCONNECT_BASE_URL,
            ScopeInterface::SCOPE_STORE,
            $store,
        );
    }

    public function getApiBaseUrl(?string $store = null): ?string
    {
        return $this->config->getValue(
            $this->isModeProd($store) ? self::XML_PATH_BCONNECT_API_BASE_URL_PROD : self::XML_PATH_BCONNECT_API_BASE_URL,
            ScopeInterface::SCOPE_STORE,
            $store,
        );
    }

    public function getAuthorizeEndpoint(?string $store = null): ?string
    {
        return $this->config->getValue(
            self::XML_PATH_BCONNECT_AUTHORIZE_ENDPOINT,
            ScopeInterface::SCOPE_STORE,
            $store,
        );
    }

    public function getAccessTokenEndpoint(?string $store = null): ?string
    {
        return $this->config->getValue(
            self::XML_PATH_BCONNECT_ACCESS_TOKEN_ENDPOINT,
            ScopeInterface::SCOPE_STORE,
            $store,
        );
    }

    public function getScope(?string $store = null): array
    {
        $scope = $this->config->getValue(
            self::XML_PATH_BCONNECT_SCOPE,
            ScopeInterface::SCOPE_STORE,
            $store,
        );

        if (!$scope) {
            return [];
        }

        return json_decode($scope, true) ?: [];
    }

    public function getChallengeMethod(?string $store = null): ?string
    {
        return $this->config->getValue(self::XML_PATH_BCONNECT_API_CHALLENGE_METHOD, ScopeInterface::SCOPE_STORE, $store);
    }

    public function getClientId(?string $store = null): ?string
    {
        return $this->config->getValue(self::XML_PATH_BCONNECT_CLIENT_ID, ScopeInterface::SCOPE_STORE, $store);
    }

    public function getSecretId(?string $store = null): ?string
    {
        return $this->config->getValue(self::XML_PATH_BCONNECT_SECRET_ID, ScopeInterface::SCOPE_STORE, $store);
    }

    public function canIncludeChallenge(?string $store = null): bool
    {
        return $this->config->isSetFlag(self::XML_PATH_BCONNECT_API_INCLUDE_CHALLENGE, ScopeInterface::SCOPE_STORE, $store);
    }

    public function isEnabled(?string $store = null): bool
    {
        return (bool)$this->config->getValue(
            self::XML_PATH_BCONNECT_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $store,
        );
    }

    public function isModeProd(?string $store = null): bool
    {
        return (bool)$this->config->getValue(
            self::XML_PATH_BCONNECT_PROD_MODE,
            ScopeInterface::SCOPE_STORE,
            $store,
        );
    }
}
