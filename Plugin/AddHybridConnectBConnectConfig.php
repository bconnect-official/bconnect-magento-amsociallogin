<?php

declare(strict_types=1);

namespace Bconnect\Amsociallogin\Plugin;

use Amasty\SocialLogin\Model\SocialData;
use Amasty\SocialLogin\Model\SocialData as AmastySocialData;
use Bconnect\Amsociallogin\Model\HybridConnect\Provider\Bconnect;
use Bconnect\Amsociallogin\Provider\Config;
use Bconnect\Amsociallogin\Provider\Config as BconnectConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @author  Agence Dn'D <contact@dnd.fr>
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    https://www.dnd.fr/
 */
class AddHybridConnectBConnectConfig
{
    public function __construct(
        private BconnectConfig $bConnectConfig,
        private AmastySocialData $amastySocialData,
        private ScopeConfigInterface $scopeConfig,
    ) {}

    public function afterGetSocialConfig(SocialData $subject, array $result, string $type): array
    {
        if ($type !== Config::AMASTY_TYPE) {
            return $result;
        }
        $useNewUrl = (bool)$this->scopeConfig->getValue('amsociallogin/general/use_new_url');

        $result['adapter'] = Bconnect::class;
        $result['api_base_url'] = $this->bConnectConfig->getApiBaseUrl();
        $result['base_authorize_url'] = $this->bConnectConfig->getAuthorizeEndpoint();
        $result['token_access_url'] = $this->bConnectConfig->getAccessTokenEndpoint();
        $result['consumer_secret'] = $this->bConnectConfig->getSecretId();
        $result['base_url'] = $this->bConnectConfig->getBaseUrl();
        $result['callback_url'] = $useNewUrl ? rtrim($this->amastySocialData->getNewBaseAuthUrl(), '/') : $this->amastySocialData->getBaseAuthUrl();
        $result['scope'] = implode(' ', array_column($this->bConnectConfig->getScope(), 'scope_value'));
        $result['can_incude_challenge'] = $this->bConnectConfig->canIncludeChallenge();
        $result['challenge_method'] = $this->bConnectConfig->getChallengeMethod();

        return $result;
    }
}
