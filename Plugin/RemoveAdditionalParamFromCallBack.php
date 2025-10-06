<?php

declare(strict_types=1);

namespace Bconnect\Amsociallogin\Plugin;

use Amasty\SocialLogin\Model\SocialData;
use Bconnect\Amsociallogin\Provider\Config;

/**
 * @author  Agence Dn'D <contact@dnd.fr>
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    https://www.dnd.fr/
 */
class RemoveAdditionalParamFromCallBack
{
    public function afterGetRedirectUrl(SocialData $subject, string $result, string $type): string
    {
        if ($type !== Config::AMASTY_TYPE) {
            return $result;
        }
        return str_replace(sprintf('/?hauth.done=%s', Config::AMASTY_NAME), '', $result);
    }
}
